<?php
// Smoke de carte-data.php con WordPress cargado (lectura + transient propio).
// Se ejecuta DESPUÉS de desplegar: prueba el código desplegado (el plugin activo ya lo ha cargado).
// Uso: wp-plugin/remote.sh run tests/smoke-carte-data.php
$src = $args[0] ?? '';
if (!function_exists('canal_carte_listings')) {
    require_once $src . '/canal-home/includes/data.php';
    require_once $src . '/canal-home/includes/carte-filter.php';
    require_once $src . '/canal-home/includes/carte-data.php';
}

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};

canal_carte_flush();
$published = (int) wp_count_posts('job_listing')->publish;
$listings = canal_carte_listings();
$check(count($listings) === $published, "listings: todas las publicadas ($published)");
$check(is_array(get_transient(CANAL_CARTE_CACHE)), 'cache: transient creado');
$check(count(array_filter($listings, fn($l) => strpos($l['url'], '/fiche/') === false)) === 0, 'listings: todas con url /fiche/');
$check(count(array_filter($listings, fn($l) => !is_float($l['lat']) || !is_float($l['lng']))) === 0, 'listings: todas con lat/lng float');
$check(count(array_filter($listings, fn($l) => $l['image'] !== '')) > 200, 'listings: >200 con imagen de portada');
$check(count(array_filter($listings, fn($l) => strpos($l['title'] . $l['description'], '&#') !== false)) === 0, 'listings: sin entidades HTML');
$check(count(array_filter($listings, fn($l) => count($l['gallery']) > 8)) === 0, 'listings: galería limitada a 8');

$cats = canal_carte_categories($listings);
$bySlug = array_column($cats, null, 'slug');
$check(count(array_filter($cats, fn($c) => $c['parent_id'] === 0)) >= 3, 'categories: al menos 3 raíces con fichas');
$check(($bySlug['hebergement']['offers_count'] ?? 0) > 50, 'categories: « hebergement » cuenta sus descendientes (' . ($bySlug['hebergement']['offers_count'] ?? 0) . ')');
$check(count(array_filter($cats, fn($c) => $c['offers_count'] === 0)) === 0, 'categories: ninguna vacía');

$slugs = array_column($cats, 'slug');
$toulouse = canal_carte_filter($listings, canal_carte_params(['search_location' => 'Toulouse'], $slugs));
$check(count($toulouse) >= 10, 'filtro real: search_location=Toulouse (' . count($toulouse) . ')');
$ecluses = canal_carte_filter($listings, canal_carte_params(['type' => 'ecluses'], $slugs));
$check(count($ecluses) >= 50, 'filtro real: ?type=ecluses (' . count($ecluses) . ')');

$public = canal_carte_public($listings[0]);
$expected = ['id', 'slug', 'lat', 'lng', 'title', 'image', 'gallery', 'address', 'description', 'type', 'label', 'phone', 'email', 'url', 'distance_km'];
$check(array_keys($public) === $expected, 'public: claves del JSON de local');

$check(has_action('save_post_job_listing', 'canal_carte_flush') !== false, 'invalidación: save_post_job_listing');
$check(has_action('edited_job_listing_category', 'canal_carte_flush') !== false, 'invalidación: edited_job_listing_category');
canal_carte_flush();
$check(get_transient(CANAL_CARTE_CACHE) === false, 'invalidación: flush borra el transient');
canal_carte_listings(); // se deja la caché caliente

WP_CLI::log($fails ? "$fails FALLO(S)" : 'TODO OK');
if ($fails) {
    exit(1);
}
