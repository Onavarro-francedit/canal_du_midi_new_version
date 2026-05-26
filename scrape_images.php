<?php
/**
 * Script para scrapear las imágenes de las fichas de plan-canal-du-midi.com,
 * guardarlas físicamente en public/clients_images/{pimcore_id} y actualizar
 * la tabla canal_du_midi_image.
 * 
 * Uso: php scrape_images.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=canal_du_midi;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * Download a remote image URL and save it to disk.
 */
function downloadImageToPath(string $url, string $targetPath): bool {
    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header' => "User-Agent: canal-du-midi-scraper/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $binary = @file_get_contents($url, false, $context);
    if ($binary === false || $binary === '') {
        return false;
    }

    return file_put_contents($targetPath, $binary) !== false;
}

/**
 * Extract file extension from URL. Defaults to jpg when unknown.
 */
function extensionFromUrl(string $url): string {
    $path = parse_url($url, PHP_URL_PATH) ?: '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
    return in_array($ext, $allowed, true) ? $ext : 'jpg';
}

/**
 * Normalize listing names before matching WP and Pimcore records.
 */
function normalizeNameForMatch(string $name): string {
    $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $name = str_replace(["\xE2\x80\x99", "\xC2\xB4", '`'], "'", $name); // smart apostrophes
    $name = mb_strtolower($name, 'UTF-8');

    // Remove branding suffixes and frequent boilerplate from WP titles.
    $name = preg_replace('/\s*[\-–—|]\s*l\'?officiel\s+du\s+canal\s+du\s+midi\s*$/iu', '', $name);
    $name = preg_replace('/\bl\'?officiel\s+du\s+canal\s+du\s+midi\b/iu', '', $name);

    // Remove star ratings and punctuation noise.
    $name = preg_replace('/\*+/', ' ', $name);

    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    if ($ascii !== false) {
        $name = strtolower($ascii);
    }

    // Keep only alnum and spaces.
    $name = preg_replace('/[^a-z0-9\s]/', ' ', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));

    return $name;
}

/**
 * Return a normalized token map for overlap scoring.
 */
function tokensFromNormalizedName(string $normalized): array {
    if ($normalized === '') {
        return [];
    }

    $tokens = array_filter(explode(' ', $normalized), fn($t) => strlen($t) > 2);
    $unique = [];
    foreach ($tokens as $token) {
        $unique[$token] = true;
    }

    return $unique;
}

/**
 * Compute a robust score combining exact, contains, token overlap and similar_text.
 */
function computeMatchScore(string $wpNorm, array $wpTokens, string $piNorm, array $piTokens): float {
    if ($wpNorm === '' || $piNorm === '') {
        return 0.0;
    }

    if ($wpNorm === $piNorm) {
        return 100.0;
    }

    $containsScore = 0.0;
    if (str_contains($piNorm, $wpNorm) || str_contains($wpNorm, $piNorm)) {
        $containsScore = 92.0;
    }

    similar_text($wpNorm, $piNorm, $similarPct);

    $tokenScore = 0.0;
    if (!empty($wpTokens) && !empty($piTokens)) {
        $intersect = array_intersect_key($wpTokens, $piTokens);
        $unionCount = count($wpTokens) + count($piTokens) - count($intersect);
        if ($unionCount > 0) {
            $jaccard = count($intersect) / $unionCount;
            $tokenScore = $jaccard * 100.0;
        }
    }

    return max($containsScore, $similarPct, $tokenScore);
}

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

// Carpeta base para guardar imágenes físicas
$baseImagesDir = __DIR__ . '/public/clients_images';
if (!is_dir($baseImagesDir) && !mkdir($baseImagesDir, 0775, true) && !is_dir($baseImagesDir)) {
    die("Error: Could not create directory {$baseImagesDir}\n");
}

// Cargar referencias Pimcore para mapear por similitud
$pimcore = $pdo->query("SELECT oo_id, nom FROM object_query_60")->fetchAll(PDO::FETCH_ASSOC);
$pimcorePrepared = array_map(function (array $p): array {
    $norm = normalizeNameForMatch((string)($p['nom'] ?? ''));
    return [
        'oo_id' => (int)$p['oo_id'],
        'nom' => (string)($p['nom'] ?? ''),
        'norm' => $norm,
        'tokens' => tokensFromNormalizedName($norm),
    ];
}, $pimcore);

// 3. Preparar UPSERT en tabla final
$upsert = $pdo->prepare("
    INSERT INTO canal_du_midi_image (pimcore_id, wp_slug, wp_title, logo_url, presentation, images)
    VALUES (:pimcore_id, :wp_slug, :wp_title, :logo_url, :presentation, :images)
    ON DUPLICATE KEY UPDATE
        wp_title = VALUES(wp_title),
        logo_url = VALUES(logo_url),
        presentation = VALUES(presentation),
        images = VALUES(images)
");

// 4. Scrapear cada fiche
$count = 0;
$errors = 0;
$mapped = 0;
$foldersCreated = 0;
$filesSaved = 0;
$filesFailed = 0;
$dbUpdated = 0;
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

    // Mapping local con object_query_60 (sin pasar por wp_images)
    $bestMatch = null;
    $bestScore = 0.0;
    $wpNorm = normalizeNameForMatch($title);
    $wpTokens = tokensFromNormalizedName($wpNorm);

    foreach ($pimcorePrepared as $p) {
        $score = computeMatchScore($wpNorm, $wpTokens, $p['norm'], $p['tokens']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $p['oo_id'];
            if ($bestScore >= 99.9) {
                break;
            }
        }
    }

    if (!$bestMatch || $bestScore < 72.0) {
        echo "  ~ [{$i}] {$slug} — {$title} — no Pimcore match\n";
        $count++;
        continue;
    }

    $mapped++;
    $clientDir = $baseImagesDir . '/' . $bestMatch;
    if (!is_dir($clientDir)) {
        if (!mkdir($clientDir, 0775, true) && !is_dir($clientDir)) {
            echo "  ✗ Could not create folder for #{$bestMatch}\n";
            $errors++;
            $count++;
            continue;
        }
        $foldersCreated++;
    }

    // Limpiar imágenes anteriores para dejar estado consistente.
    foreach (glob($clientDir . '/img*.*') ?: [] as $oldImg) {
        @unlink($oldImg);
    }
    foreach (glob($clientDir . '/logo.*') ?: [] as $oldLogo) {
        @unlink($oldLogo);
    }

    $localImagePaths = [];
    $imgIndex = 1;
    foreach ($cleanImages as $imgUrl) {
        $ext = extensionFromUrl($imgUrl);
        $fileName = 'img' . $imgIndex . '.' . $ext;
        $target = $clientDir . '/' . $fileName;

        if (downloadImageToPath($imgUrl, $target)) {
            $filesSaved++;
            $localImagePaths[] = '/public/clients_images/' . $bestMatch . '/' . $fileName;
            $imgIndex++;
        } else {
            $filesFailed++;
            echo "  ✗ Download failed for #{$bestMatch}: {$imgUrl}\n";
        }
    }

    $localLogoPath = null;
    if (!empty($logo)) {
        $logoExt = extensionFromUrl($logo);
        $logoName = 'logo.' . $logoExt;
        $logoTarget = $clientDir . '/' . $logoName;
        if (downloadImageToPath($logo, $logoTarget)) {
            $filesSaved++;
            $localLogoPath = '/public/clients_images/' . $bestMatch . '/' . $logoName;
        } else {
            $filesFailed++;
            echo "  ✗ Logo download failed for #{$bestMatch}: {$logo}\n";
        }
    }

    $presentation = $localImagePaths[0] ?? null;
    $imagesCsv = !empty($localImagePaths) ? implode(',', $localImagePaths) : null;

    $upsert->execute([
        'pimcore_id' => $bestMatch,
        'wp_slug' => $slug,
        'wp_title' => $title,
        'logo_url' => $localLogoPath,
        'presentation' => $presentation,
        'images' => $imagesCsv,
    ]);
    $dbUpdated++;
    
    $count++;
    $imgCount = count($cleanImages);
    echo "  ✓ [{$i}] {$slug} — {$title} — {$imgCount} images" . ($logo ? " + logo" : "") . "\n";
    
    // Pause pour ne pas surcharger le serveur
    usleep(300000); // 300ms
}

echo "\n\n=== Scraping terminé ===\n";
echo "Fiches traitées: {$count}\n";
echo "Erreurs: {$errors}\n";
echo "Mapped: {$mapped}\n";
echo "Rows upserted in canal_du_midi_image: {$dbUpdated}\n";
echo "Saved folders: {$foldersCreated}\n";
echo "Saved files: {$filesSaved}\n";
echo "Failed files: {$filesFailed}\n";
echo "\n=== Résumé ===\n";
echo "Total fiches sitemap: " . count($urls) . "\n";
echo "Images folder: {$baseImagesDir}\n";
