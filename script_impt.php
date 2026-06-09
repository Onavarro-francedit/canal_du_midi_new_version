<?php

/**
 * migrate.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Lee las tablas WordPress exportadas (wp_posts, wp_postmeta, wp_terms,
 * wp_term_taxonomy, wp_term_relationships) y las importa en las nuevas
 * tablas limpias (listings, categories, listing_categories).
 *
 * Todo en la misma base de datos local.
 *
 * USO:
 *   php migrate.php
 *   php migrate.php --dry-run   ← simula sin escribir nada
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

// ── 0. Modo dry-run ──────────────────────────────────────────────────────────
$dryRun = in_array('--dry-run', $argv ?? [], true);
if ($dryRun) {
    echo "[DRY-RUN] Simulación activa — no se escribirá nada.\n\n";
}

// ── 1. Configuración ─────────────────────────────────────────────────────────
$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'canal_du_midi',   // ← cambia esto
    'user'     => 'root',     // ← cambia esto
    'password' => '',  // ← cambia esto
    'charset'  => 'utf8mb4',
];

// Slug de taxonomía principal de MyListing.
// Para verificar cuál es el tuyo ejecuta:
// SELECT DISTINCT taxonomy FROM wp_term_taxonomy WHERE taxonomy != 'post_tag' AND taxonomy != 'category';
const WP_TAXONOMY = 'job_listing_category';

// ── 2. Conexión ──────────────────────────────────────────────────────────────
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
try {
    $db = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    echo "✔ Conectado a la base de datos local.\n";
} catch (PDOException $e) {
    echo "✘ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// ── 3. Leer listados desde wp_posts + wp_postmeta ────────────────────────────
echo "\n→ Leyendo listados desde wp_posts...\n";

$wpSql = "SELECT
              p.ID           AS wp_id,
              p.post_name    AS slug,
              p.post_title   AS post_title,
              p.post_content AS post_content,
              p.post_excerpt AS post_excerpt,
              MAX(CASE WHEN pm.meta_key IN ('_job_title','_nom_oi')                    THEN pm.meta_value END) AS nom,
              MAX(CASE WHEN pm.meta_key = '_job_description'                           THEN pm.meta_value END) AS description,
              MAX(CASE WHEN pm.meta_key IN ('_ligne-adresse-1-papier','_job_location') THEN pm.meta_value END) AS adresse,
              MAX(CASE WHEN pm.meta_key = '_ligne-adresse-2-papier'                    THEN pm.meta_value END) AS adresse2,
              MAX(CASE WHEN pm.meta_key = '_code-postal'                               THEN pm.meta_value END) AS cp,
              MAX(CASE WHEN pm.meta_key = '_job_phone'                                 THEN pm.meta_value END) AS telephone,
              MAX(CASE WHEN pm.meta_key = '_telephone-portable'                        THEN pm.meta_value END) AS mobile,
              MAX(CASE WHEN pm.meta_key = '_job_email'                                 THEN pm.meta_value END) AS email,
              MAX(CASE WHEN pm.meta_key = '_job_website'                               THEN pm.meta_value END) AS web,
              MAX(CASE WHEN pm.meta_key = '_facebook'                                  THEN pm.meta_value END) AS facebook,
              MAX(CASE WHEN pm.meta_key = '_job_cover'                                 THEN pm.meta_value END) AS cover,
              MAX(CASE WHEN pm.meta_key = '_job_gallery'                               THEN pm.meta_value END) AS images,
              MAX(CASE WHEN pm.meta_key = '_case27_listing_type'                       THEN pm.meta_value END) AS listing_type,
              MAX(CASE WHEN pm.meta_key = '_featured'                                  THEN pm.meta_value END) AS featured,
              MAX(CASE WHEN pm.meta_key = '_claimed'                                   THEN pm.meta_value END) AS claimed,
              MAX(CASE WHEN pm.meta_key = '_job_video_url'                             THEN pm.meta_value END) AS job_video_url,
              MAX(CASE WHEN pm.meta_key = '_video-facebook'                            THEN pm.meta_value END) AS video_facebook,
              MAX(CASE WHEN pm.meta_key = '_lien-video-tiktok'                         THEN pm.meta_value END) AS lien_video_tiktok,
              MAX(CASE WHEN pm.meta_key = '_format-papier'                             THEN pm.meta_value END) AS format_papier,
              MAX(CASE WHEN pm.meta_key = '_texte_version_papier'                      THEN pm.meta_value END) AS texte_version_papier,
              MAX(CASE WHEN pm.meta_key = '_noi_2024'                                  THEN pm.meta_value END) AS noi_2024,
              MAX(CASE WHEN pm.meta_key = '_noi_2025'                                  THEN pm.meta_value END) AS noi_2025,
              MAX(CASE WHEN pm.meta_key = '_noi_2026'                                  THEN pm.meta_value END) AS noi_2026,
              MAX(CASE WHEN pm.meta_key = 'geolocation_lat'                            THEN pm.meta_value END) AS lat,
              MAX(CASE WHEN pm.meta_key = 'geolocation_long'                           THEN pm.meta_value END) AS lng
          FROM wp_posts p
          JOIN wp_postmeta pm ON p.ID = pm.post_id
          WHERE p.post_type   = 'job_listing'
            AND p.post_status = 'publish'
          GROUP BY p.ID
          ORDER BY p.ID ASC";

$wpRows = $db->query($wpSql)->fetchAll();
$total  = count($wpRows);
echo "   → $total listados encontrados.\n";

if ($total === 0) {
    echo "Nada que importar. Verifica que wp_posts contiene filas con post_type='job_listing'.\n";
    exit(0);
}

// ── 4. Leer categorías desde wp_term_* (una sola query) ──────────────────────
echo "→ Leyendo categorías desde wp_terms...\n";

$wpIds        = array_column($wpRows, 'wp_id');
$placeholders = implode(',', array_fill(0, count($wpIds), '?'));

$catSql = "SELECT
               tr.object_id   AS post_id,
               t.term_id      AS term_id,
               t.name         AS name,
               t.slug         AS slug,
               tt.parent      AS parent_term_id
           FROM wp_term_relationships  tr
           JOIN wp_term_taxonomy       tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
           JOIN wp_terms               t  ON tt.term_id          = t.term_id
           WHERE tr.object_id IN ($placeholders)
             AND tt.taxonomy  = '" . WP_TAXONOMY . "'
           ORDER BY tt.parent ASC, t.term_id ASC";

$catStmt = $db->prepare($catSql);
$catStmt->execute($wpIds);
$catRows = $catStmt->fetchAll();

// [post_id => [term_id, ...]]
$termsByPost = [];
// [term_id => ['name', 'slug', 'parent_term_id']]
$allTerms    = [];

foreach ($catRows as $cat) {
    $termsByPost[(int) $cat['post_id']][] = (int) $cat['term_id'];
    $allTerms[(int) $cat['term_id']] = [
        'name'           => $cat['name'],
        'slug'           => $cat['slug'],
        'parent_term_id' => (int) $cat['parent_term_id'],
    ];
}

echo "   → " . count($allTerms) . " categorías únicas encontradas.\n";

// ── 5. Insertar categorías (padres antes que hijos) ──────────────────────────
echo "→ Importando categorías...\n";

// wp term_id → nuevo id en tabla categories
$termIdMap = [];

if (!$dryRun) {
    // Padres primero (parent_term_id = 0), luego hijos
    uasort($allTerms, fn($a, $b) => $a['parent_term_id'] <=> $b['parent_term_id']);

    $catInsert = $db->prepare("
        INSERT INTO categories (name, slug, taxonomy, parent_id)
        VALUES (:name, :slug, :taxonomy, :parent_id)
        ON DUPLICATE KEY UPDATE
            name      = VALUES(name),
            taxonomy  = VALUES(taxonomy),
            parent_id = VALUES(parent_id),
            id        = LAST_INSERT_ID(id)
    ");

    foreach ($allTerms as $termId => $term) {
        $parentNewId = $term['parent_term_id'] > 0
            ? ($termIdMap[$term['parent_term_id']] ?? null)
            : null;

        $catInsert->execute([
            'name'      => $term['name'],
            'slug'      => $term['slug'],
            'taxonomy'  => WP_TAXONOMY,
            'parent_id' => $parentNewId,
        ]);

        $termIdMap[$termId] = (int) $db->lastInsertId();
    }

    echo "   → " . count($termIdMap) . " categorías importadas.\n";
} else {
    foreach ($allTerms as $termId => $term) {
        $termIdMap[$termId] = $termId;
    }
    echo "   [DRY-RUN] Se importarían " . count($allTerms) . " categorías.\n";
}

// ── 6. Insertar listados ─────────────────────────────────────────────────────
echo "→ Importando listados...\n";

$listingInsert = $dryRun ? null : $db->prepare("
    INSERT INTO listings (
        slug, listing_type, status, title, description,
        phone, mobile, email, website, facebook,
        address, address2, postal_code, city,
        lat, lng,
        cover, gallery, videos, socials,
        label, zone, format_papier, actualite,
        noi_2024, noi_2025, noi_2026,
        featured, claimed
    ) VALUES (
        :slug, :listing_type, 'publish', :title, :description,
        :phone, :mobile, :email, :website, :facebook,
        :address, :address2, :postal_code, :city,
        :lat, :lng,
        :cover, :gallery, :videos, :socials,
        :label, :zone, :format_papier, :actualite,
        :noi_2024, :noi_2025, :noi_2026,
        :featured, :claimed
    )
    ON DUPLICATE KEY UPDATE
        listing_type  = VALUES(listing_type),
        title         = VALUES(title),
        description   = VALUES(description),
        phone         = VALUES(phone),
        mobile        = VALUES(mobile),
        email         = VALUES(email),
        website       = VALUES(website),
        facebook      = VALUES(facebook),
        address       = VALUES(address),
        address2      = VALUES(address2),
        postal_code   = VALUES(postal_code),
        city          = VALUES(city),
        lat           = VALUES(lat),
        lng           = VALUES(lng),
        cover         = VALUES(cover),
        gallery       = VALUES(gallery),
        videos        = VALUES(videos),
        socials       = VALUES(socials),
        label         = VALUES(label),
        zone          = VALUES(zone),
        format_papier = VALUES(format_papier),
        actualite     = VALUES(actualite),
        noi_2024      = VALUES(noi_2024),
        noi_2025      = VALUES(noi_2025),
        noi_2026      = VALUES(noi_2026),
        featured      = VALUES(featured),
        claimed       = VALUES(claimed),
        id            = LAST_INSERT_ID(id)
");

$pivotInsert = $dryRun ? null : $db->prepare("
    INSERT IGNORE INTO listing_categories (listing_id, category_id)
    VALUES (:listing_id, :category_id)
");

$countOk  = 0;
$countErr = 0;

foreach ($wpRows as $row) {
    try {
        // ── Título
        $title = trim((string) ($row['nom'] ?? $row['post_title'] ?? 'Sans titre'));

        // ── Slug: fallback si está vacío
        $slug = trim((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            $slug = 'listing-' . $row['wp_id'];
        }

        // ── Descripción
        $descSrc = $row['description'] ?? $row['post_content'] ?? $row['post_excerpt'] ?? '';
        $desc    = strip_tags(html_entity_decode((string) $descSrc, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        // ── Galería: puede venir serializada o como CSV de URLs
        $gallery    = [];
        $rawGallery = trim((string) ($row['images'] ?? ''));
        if ($rawGallery !== '') {
            $unserialized = @unserialize($rawGallery);
            if (is_array($unserialized)) {
                $gallery = array_values(array_filter($unserialized));
            } else {
                $gallery = array_values(array_filter(array_map('trim', explode(',', $rawGallery))));
            }
        }

        // ── Vídeos
        $videos = array_values(array_filter([
            trim((string) ($row['job_video_url']     ?? '')),
            trim((string) ($row['video_facebook']    ?? '')),
            trim((string) ($row['lien_video_tiktok'] ?? '')),
        ]));

        // ── Redes sociales
        $socials = array_filter([
            'facebook' => trim((string) ($row['facebook'] ?? '')),
        ]);

        $params = [
            'slug'         => $slug,
            'listing_type' => trim((string) ($row['listing_type'] ?? '')),
            'title'        => $title,
            'description'  => trim($desc),
            'phone'        => trim((string) ($row['telephone'] ?? '')),
            'mobile'       => trim((string) ($row['mobile']    ?? '')),
            'email'        => trim((string) ($row['email']     ?? '')),
            'website'      => trim((string) ($row['web']       ?? '')),
            'facebook'     => trim((string) ($row['facebook']  ?? '')),
            'address'      => trim((string) ($row['adresse']   ?? '')),
            'address2'     => trim((string) ($row['adresse2']  ?? '')),
            'postal_code'  => trim((string) ($row['cp']        ?? '')),
            'city'         => trim((string) ($row['post_title'] ?? '')),
            'lat'          => $row['lat'] !== null ? (float) $row['lat'] : null,
            'lng'          => $row['lng'] !== null ? (float) $row['lng'] : null,
            'cover'        => trim((string) ($row['cover'] ?? '')),
            'gallery'      => !empty($gallery) ? json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'videos'       => !empty($videos)  ? json_encode($videos,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'socials'      => !empty($socials) ? json_encode($socials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'label'        => trim((string) ($row['format_papier']        ?? '')),
            'zone'         => trim((string) ($row['listing_type']         ?? $row['noi_2026'] ?? $row['noi_2025'] ?? '')),
            'format_papier'=> trim((string) ($row['format_papier']        ?? '')),
            'actualite'    => trim((string) ($row['texte_version_papier'] ?? '')),
            'noi_2024'     => trim((string) ($row['noi_2024'] ?? '')),
            'noi_2025'     => trim((string) ($row['noi_2025'] ?? '')),
            'noi_2026'     => trim((string) ($row['noi_2026'] ?? '')),
            'featured'     => (int) ($row['featured'] ?? 0),
            'claimed'      => (int) ($row['claimed']  ?? 0),
        ];

        if (!$dryRun) {
            $listingInsert->execute($params);
            $newListingId = (int) $db->lastInsertId();

            // ── Relaciones listing ↔ categoría
            foreach ($termsByPost[(int) $row['wp_id']] ?? [] as $termId) {
                $newCatId = $termIdMap[$termId] ?? null;
                if ($newCatId !== null) {
                    $pivotInsert->execute([
                        'listing_id'  => $newListingId,
                        'category_id' => $newCatId,
                    ]);
                }
            }
        }

        $countOk++;

        if ($countOk % 50 === 0) {
            echo "   → $countOk / $total procesados...\n";
        }

    } catch (Throwable $e) {
        $countErr++;
        echo "   ✘ Error en wp_id={$row['wp_id']} ({$row['post_title']}): " . $e->getMessage() . "\n";
    }
}

// ── 7. Resumen ───────────────────────────────────────────────────────────────
echo "\n────────────────────────────────────────\n";
echo $dryRun ? "SIMULACIÓN completada\n" : "MIGRACIÓN completada\n";
echo "  Listados OK  : $countOk\n";
echo "  Errores      : $countErr\n";
echo "  Categorías   : " . count($termIdMap) . "\n";
echo "────────────────────────────────────────\n";