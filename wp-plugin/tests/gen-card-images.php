<?php
// Genera de una vez las fotos de tarjeta de la carte (archivo.jpg.c640.webp, TASK-070). Solo AÑADE archivos junto a
// los originales (o rehace las suyas). Uso: wp-plugin/remote.sh run tests/gen-card-images.php
define('CANAL_HOME_IMAGE_BUDGET', PHP_INT_MAX);
define('CANAL_HOME_CARD_REBUILD', true); // ponytail: rehace siempre las 254 (~30 s); quitar si crece mucho la carte
$made = $kept = $skipped = 0;
$before = $after = 0;
foreach (canal_carte_listings() as $item) {
    if ($item['image'] === '') {
        continue;
    }
    $file = ABSPATH . ltrim((string) wp_parse_url($item['image'], PHP_URL_PATH), '/');
    $exists = is_file($file . '.c' . CANAL_HOME_CARD_WIDTH . '.webp');
    $url = canal_home_card_image($item['image']);
    if ($url === $item['image']) {
        $skipped++;
        continue;
    }
    $exists ? $kept++ : $made++;
    $before += is_file($file . '.webp') ? filesize($file . '.webp') : filesize($file);
    $after += filesize($file . '.c' . CANAL_HOME_CARD_WIDTH . '.webp');
}
printf("nuevas %d · ya estaban %d · sin variante %d · peso medio %d KB → %d KB\n", $made, $kept, $skipped,
    $before / max(1, $made + $kept) / 1024, $after / max(1, $made + $kept) / 1024);
