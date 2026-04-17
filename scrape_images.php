<?php
/**
 * Script para scrapear las imágenes de las fichas de plan-canal-du-midi.com
 * y guardarlas en una tabla wp_images para mapearlas con object_query_60
 * 
 * Uso: php scrape_images.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=canal_du_midi;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Crear la tabla wp_images
$pdo->exec("DROP TABLE IF EXISTS wp_images");
$pdo->exec("
    CREATE TABLE wp_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        wp_slug VARCHAR(255) NOT NULL,
        wp_title VARCHAR(500),
        pimcore_id INT DEFAULT NULL,
        image_url VARCHAR(1000),
        image2_url VARCHAR(1000),
        image3_url VARCHAR(1000),
        image4_url VARCHAR(1000),
        logo_url VARCHAR(1000),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (wp_slug),
        KEY (pimcore_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
echo "✓ Table wp_images created\n\n";

// 2. Obtener todas las URLs del sitemap
echo "Fetching sitemap...\n";
$sitemapUrl = 'https://www.plan-canal-du-midi.com/wp-sitemap-posts-job_listing-1.xml';
$xml = @file_get_contents($sitemapUrl);
if (!$xml) {
    die("Error: Could not fetch sitemap\n");
}

preg_match_all('/<loc>([^<]+)<\/loc>/', $xml, $matches);
$urls = $matches[1];
echo "Found " . count($urls) . " fiches\n\n";

// 3. Preparar INSERT
$stmt = $pdo->prepare("
    INSERT INTO wp_images (wp_slug, wp_title, image_url, image2_url, image3_url, image4_url, logo_url) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        wp_title = VALUES(wp_title),
        image_url = VALUES(image_url),
        image2_url = VALUES(image2_url),
        image3_url = VALUES(image3_url),
        image4_url = VALUES(image4_url),
        logo_url = VALUES(logo_url)
");

// 4. Scrapear cada fiche
$count = 0;
$errors = 0;
foreach ($urls as $i => $url) {
    $slug = trim(parse_url($url, PHP_URL_PATH), '/');
    $slug = str_replace('fiche/', '', $slug);
    
    $html = @file_get_contents($url);
    if (!$html) {
        echo "  ✗ [{$i}] Error fetching: {$slug}\n";
        $errors++;
        continue;
    }
    
    // Extraer título de la page
    $title = '';
    if (preg_match('/<h1[^>]*class="[^"]*listing-name[^"]*"[^>]*>([^<]+)/i', $html, $m)) {
        $title = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } elseif (preg_match('/<title>([^<|]+)/i', $html, $m)) {
        $title = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    }
    
    // Extraer imágenes: background-image en la galería (haute résolution)
    $images = [];
    if (preg_match_all('/background-image:\s*url\(([^)]+)\)/i', $html, $bgMatches)) {
        foreach ($bgMatches[1] as $bgUrl) {
            $bgUrl = trim($bgUrl, "' \"");
            if (strpos($bgUrl, 'wp-content/uploads') !== false && strpos($bgUrl, 'logo') === false) {
                $images[] = $bgUrl;
            }
        }
    }
    
    // Fallback: src= images from wp-content
    if (empty($images)) {
        if (preg_match_all('/src="(https:\/\/www\.plan-canal-du-midi\.com\/wp-content\/uploads\/[^"]+)"/i', $html, $srcMatches)) {
            foreach ($srcMatches[1] as $srcUrl) {
                if (strpos($srcUrl, 'logo_canal') === false && strpos($srcUrl, 'pin.png') === false) {
                    $images[] = $srcUrl;
                }
            }
        }
    }
    
    // Chercher logo séparément
    $logo = null;
    if (preg_match_all('/src="(https:\/\/www\.plan-canal-du-midi\.com\/wp-content\/uploads\/[^"]+)"/i', $html, $allSrc)) {
        foreach ($allSrc[1] as $srcUrl) {
            if ((stripos($srcUrl, 'logo') !== false || stripos($srcUrl, 'LOGO') !== false) 
                && strpos($srcUrl, 'logo_canal_nouveau') === false) {
                $logo = $srcUrl;
                break;
            }
        }
    }
    
    // Dédupliquer et nettoyer (enlever les thumbnails -300x168, prendre l'original)
    $cleanImages = [];
    foreach ($images as $img) {
        $clean = preg_replace('/-\d+x\d+(\.\w+)$/', '$1', $img);
        if (!in_array($clean, $cleanImages)) {
            $cleanImages[] = $clean;
        }
    }
    
    $stmt->execute([
        $slug,
        $title,
        $cleanImages[0] ?? null,
        $cleanImages[1] ?? null,
        $cleanImages[2] ?? null,
        $cleanImages[3] ?? null,
        $logo
    ]);
    
    $count++;
    $imgCount = count($cleanImages);
    echo "  ✓ [{$i}] {$slug} — {$title} — {$imgCount} images" . ($logo ? " + logo" : "") . "\n";
    
    // Pause pour ne pas surcharger le serveur
    usleep(300000); // 300ms
}

echo "\n\n=== Scraping terminé ===\n";
echo "Fiches traitées: {$count}\n";
echo "Erreurs: {$errors}\n";

// 5. Mapper avec object_query_60 par similarité de nom
echo "\nMapping avec Pimcore...\n";

$fiches = $pdo->query("SELECT id, wp_slug, wp_title FROM wp_images WHERE wp_title IS NOT NULL AND wp_title != ''")->fetchAll(PDO::FETCH_ASSOC);
$pimcore = $pdo->query("SELECT oo_id, nom FROM object_query_60")->fetchAll(PDO::FETCH_ASSOC);

$mapped = 0;
$updateStmt = $pdo->prepare("UPDATE wp_images SET pimcore_id = ? WHERE id = ?");

foreach ($fiches as $fiche) {
    $bestMatch = null;
    $bestScore = 0;
    
    $wpName = mb_strtoupper(trim($fiche['wp_title']));
    
    foreach ($pimcore as $p) {
        $pName = mb_strtoupper(trim($p['nom']));
        
        // Exact match
        if ($wpName === $pName) {
            $bestMatch = $p['oo_id'];
            $bestScore = 100;
            break;
        }
        
        // One contains the other
        if (mb_strlen($wpName) > 3 && mb_strlen($pName) > 3) {
            if (strpos($pName, $wpName) !== false || strpos($wpName, $pName) !== false) {
                $score = 90;
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $p['oo_id'];
                }
            }
        }
        
        // Similar text percentage
        similar_text($wpName, $pName, $pct);
        if ($pct > 75 && $pct > $bestScore) {
            $bestScore = $pct;
            $bestMatch = $p['oo_id'];
        }
    }
    
    if ($bestMatch && $bestScore >= 75) {
        $updateStmt->execute([$bestMatch, $fiche['id']]);
        $mapped++;
        if ($bestScore < 100) {
            echo "  ~ [{$bestScore}%] \"{$fiche['wp_title']}\" → Pimcore #{$bestMatch}\n";
        }
    }
}

echo "\nMapped: {$mapped} / " . count($fiches) . " fiches\n";

// Stats
$total = $pdo->query("SELECT COUNT(*) FROM wp_images")->fetchColumn();
$withImages = $pdo->query("SELECT COUNT(*) FROM wp_images WHERE image_url IS NOT NULL")->fetchColumn();
$withMapping = $pdo->query("SELECT COUNT(*) FROM wp_images WHERE pimcore_id IS NOT NULL")->fetchColumn();

echo "\n=== Résumé ===\n";
echo "Total fiches WP: {$total}\n";
echo "Avec images: {$withImages}\n";
echo "Mappées à Pimcore: {$withMapping}\n";
