<?php
// Smoke de fiche-data.php con WordPress cargado (solo lectura).
// Uso: wp-plugin/remote.sh run tests/smoke-fiche.php   (después de desplegar, como smoke-carte-data)
$src = $args[0] ?? '';
// Guard por archivo: con el plugin activo ya están cargados data.php y carte-*.php.
if (!function_exists('canal_carte_listings')) {
    require_once $src . '/canal-home/includes/data.php';
    require_once $src . '/canal-home/includes/carte-filter.php';
    require_once $src . '/canal-home/includes/carte-data.php';
}
if (!function_exists('canal_fiche_tel')) {
    require_once $src . '/canal-home/includes/fiche-core.php';
}
if (!function_exists('canal_fiche_data')) {
    require_once $src . '/canal-home/includes/fiche-data.php';
}

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};

// Completa (web, facebook, instagram), mínima (écluse) y con vídeo — publicadas el 29/09.
foreach (['maison-rassier', 'ecluse-de-sauzens', 'easyvelocarcassonne'] as $slug) {
    $post = canal_fiche_post($slug);
    $check($post instanceof WP_Post, "post: $slug");
    if (!$post) {
        continue;
    }
    $f = canal_fiche_data($post);
    $check($f['title'] !== '' && $f['slug'] === $post->post_name, "$slug: título y slug");
    $all = array_merge([$f['cover']], $f['gallery']);
    $check(count(array_filter($all, fn($u) => $u !== '' && strpos($u, 'https://') !== 0)) === 0, "$slug: imágenes en https");
    $check(strpos($f['description_html'], '[') === false || strpos($f['description_html'], '[/') === false, "$slug: sin shortcodes");
    $check(count($f['nearby']) === 6 || !canal_fiche_has_coords($f['lat'], $f['lng']), "$slug: 6 cercanas");
    $check(!in_array($post->ID, array_column($f['nearby'], 'id'), true), "$slug: no es cercana de sí misma");
    $check($f['title'] === canal_fiche_display_title($f['title']) && (mb_strtoupper($f['title'], 'UTF-8') !== $f['title']), "$slug: título legible (no todo mayúsculas)");
    $check($f['modified'] !== '' && strtotime($f['modified']) !== false, "$slug: fecha de modificación");
    $check(count($f['faq']) >= 1 && strpos($f['faq'][0]['q'], 'Où se trouve') === 0, "$slug: FAQ con « où »");
    $check(count($f['gallery_imgs']) === count($f['gallery']), "$slug: una imagen reducida por foto");
    $withDims = array_filter($f['gallery_imgs'], fn($g) => !empty($g['image_w']) && !empty($g['image_h']));
    $check(!$f['gallery'] || count($withDims) > 0, "$slug: galería con ancho/alto");
    if ($slug === 'easyvelocarcassonne') {
        $check(strpos($f['video'], 'https://www.youtube-nocookie.com/embed/') === 0, "$slug: vídeo embebible");
    }
    WP_CLI::log("     vídeo=" . ($f['video'] ?: '-') . " redes=" . implode(',', array_keys($f['social'])) . " zonas=" . implode(' | ', $f['zones']));
}
$check(canal_fiche_post('nicols-le-canal-du-midi-en-bateau') === null, 'ficha expired → null');
$check(canal_fiche_post('no-existe-' . wp_generate_password(6, false)) === null, 'slug inexistente → null');
$draft = get_posts(['post_type' => 'job_listing', 'post_status' => 'draft', 'numberposts' => 1]);
$check(!$draft || canal_fiche_post($draft[0]->post_name) === null, 'borrador → null');

WP_CLI::log($fails ? "$fails FALLO(S)" : 'TODO OK');
exit($fails ? 1 : 0);
