<?php

/**
 * fix_serialized.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Limpia los campos cover y gallery de la tabla listings que contienen
 * datos serializados de PHP en lugar de URLs limpias o JSON.
 *
 * USO:
 *   php fix_serialized.php
 *   php fix_serialized.php --dry-run
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv ?? [], true);
if ($dryRun) echo "[DRY-RUN] Solo lectura, no se escribirá nada.\n\n";

// ── Configuración ────────────────────────────────────────────────────────────
$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'canal_du_midi',  // ← cambia esto
    'user'     => 'root',    // ← cambia esto
    'password' => '', // ← cambia esto
    'charset'  => 'utf8mb4',
];

$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$db  = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
echo "✔ Conectado.\n\n";

// ── Funciones de extracción ──────────────────────────────────────────────────

/**
 * Extrae la primera URL de un valor que puede ser:
 *  - Una URL directa           → "https://..."
 *  - Un array serializado      → a:1:{i:0;s:90:"https://...";}
 *  - Un JSON                   → ["https://..."]
 */
function extractFirstUrl(string $value): string
{
    $value = trim($value);
    if ($value === '') return '';

    // Ya es una URL directa
    if (preg_match('#^https?://#i', $value)) return $value;

    // Intentar deserializar
    $unserialized = @unserialize($value, ['allowed_classes' => false]);
    if (is_array($unserialized)) {
        $first = array_values(array_filter($unserialized))[0] ?? '';
        return is_string($first) ? trim($first) : '';
    }
    if (is_string($unserialized) && $unserialized !== '') return trim($unserialized);

    // Intentar JSON
    $decoded = json_decode($value, true);
    if (is_array($decoded)) {
        $first = array_values(array_filter($decoded))[0] ?? '';
        return is_string($first) ? trim($first) : '';
    }

    return $value;
}

/**
 * Extrae todas las URLs de un valor que puede ser:
 *  - Un JSON ya limpio         → ["url1","url2"]
 *  - Un array serializado      → a:2:{i:0;s:10:"url1";...}
 *  - URLs separadas por coma   → "url1,url2"
 * Devuelve JSON limpio o null si está vacío.
 */
function extractGalleryJson(string $value): ?string
{
    $value = trim($value);
    if ($value === '') return null;

    // Ya es JSON válido
    $decoded = json_decode($value, true);
    if (is_array($decoded) && !empty($decoded)) {
        $urls = array_values(array_filter(array_map('trim', $decoded)));
        return !empty($urls) ? json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    // Array serializado de PHP
    $unserialized = @unserialize($value, ['allowed_classes' => false]);
    if (is_array($unserialized) && !empty($unserialized)) {
        $urls = array_values(array_filter(array_map('trim', $unserialized)));
        return !empty($urls) ? json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    // CSV de URLs
    if (str_contains($value, ',') || preg_match('#^https?://#i', $value)) {
        $urls = array_values(array_filter(array_map('trim', explode(',', $value))));
        return !empty($urls) ? json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    return $value;
}

// ── Leer todos los listings ──────────────────────────────────────────────────
$rows = $db->query("SELECT id, cover, gallery FROM listings")->fetchAll();
echo "→ " . count($rows) . " listados a revisar.\n\n";

$update = $dryRun ? null : $db->prepare(
    "UPDATE listings SET cover = :cover, gallery = :gallery WHERE id = :id"
);

$countFixed = 0;
$countClean = 0;

foreach ($rows as $row) {
    $originalCover   = (string) ($row['cover']   ?? '');
    $originalGallery = (string) ($row['gallery']  ?? '');

    $cleanCover   = extractFirstUrl($originalCover);
    $cleanGallery = extractGalleryJson($originalGallery);

    // Si cover está vacío pero gallery tiene imágenes, usar la primera
    if ($cleanCover === '' && $cleanGallery !== null) {
        $decoded    = json_decode($cleanGallery, true);
        $cleanCover = $decoded[0] ?? '';
    }

    $coverChanged   = $cleanCover   !== $originalCover;
    $galleryChanged = $cleanGallery !== $originalGallery;

    if (!$coverChanged && !$galleryChanged) {
        $countClean++;
        continue;
    }

    if ($dryRun) {
        echo "  [id={$row['id']}] cover: {$originalCover}\n";
        echo "             → {$cleanCover}\n\n";
    } else {
        $update->execute([
            'id'      => $row['id'],
            'cover'   => $cleanCover,
            'gallery' => $cleanGallery,
        ]);
    }

    $countFixed++;
}

// ── Resumen ──────────────────────────────────────────────────────────────────
echo "\n────────────────────────────────────────\n";
echo $dryRun ? "SIMULACIÓN completada\n" : "LIMPIEZA completada\n";
echo "  Corregidos : $countFixed\n";
echo "  Ya limpios : $countClean\n";
echo "────────────────────────────────────────\n";