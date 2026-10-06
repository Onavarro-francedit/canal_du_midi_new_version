<?php
// Inventario de cobertura 2026 (TASK-053, 06/10): cada URL publicada → qué la sirve al publicar (plantilla 2026, 301 o nada).
// Solo lectura. Uso: wp-plugin/remote.sh run tests/inventario-2026.php > docs/data/cobertura-2026.tsv
// Columnas: ruta · tipo · cobertura · nota
$out = function (string $url, string $kind, string $cover, string $note = '') {
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    echo $path, "\t", $kind, "\t", $cover, "\t", str_replace(["\t", "\n"], ' ', $note), "\n";
};
$front = (int) get_option('page_on_front');

foreach (get_posts(['post_type' => ['page', 'post'], 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID']) as $p) {
    $url = (string) get_permalink($p);
    $rel = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    $tpl = (string) get_page_template_slug($p);
    $redirect = canal_2026_redirect_target('/' . $rel . '/');
    if ($p->ID === $front) {
        $cover = 'home 2026';
    } elseif ($p->post_name === 'explorer' || $tpl === CANAL_CARTE_TEMPLATE) {
        $cover = 'carte 2026';
    } elseif ($p->post_name === CANAL_CALCUL_SLUG) {
        $cover = 'calcul 2026';
    } elseif ($tpl === CANAL_PLANNER_TEMPLATE) {
        $cover = 'planificateur 2026';
    } elseif ($tpl === CANAL_HOME_TEMPLATE) {
        $cover = 'home 2026 (privada)';
    } elseif (substr($p->post_name, -5) === '-2026') {
        $cover = 'página 2026 (privada)';
    } elseif ($redirect !== null) {
        $cover = '301 → ' . $redirect;
    } elseif ($p->post_name === 'mon-compte') {
        $cover = 'mon compte 2026';
    } elseif (canal_contenu_post_eligible($p)) {
        $cover = 'contenu 2026';
    } else {
        $cover = 'SIN 2026';
    }
    $notes = [];
    if ($cover === 'SIN 2026') {
        $notes[] = $p->post_password !== '' ? 'con contraseña' : (in_array($p->post_name, CANAL_CONTENU_EXCLUDED_SLUGS, true) ? 'excluida a propósito' : 'plantilla ' . ($tpl ?: 'default'));
    }
    if ($cover === 'contenu 2026') {
        $text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($p->post_content))));
        if (get_post_meta($p->ID, '_elementor_edit_mode', true) === 'builder') {
            $notes[] = 'Elementor';
        }
        if (mb_strlen($text) < 300) {
            $notes[] = 'texto corto (' . mb_strlen($text) . ' car.)';
        }
        if (preg_match_all('/\[([a-z][a-z0-9_-]+)/i', $p->post_content, $m)) {
            $notes[] = 'shortcodes: ' . implode(',', array_unique($m[1]));
        }
        if (stripos($p->post_content, '<iframe') !== false) {
            $notes[] = 'iframe';
        }
        if (in_array($p->post_name, CANAL_CONTENU_PLUGIN_SLUGS, true)) {
            $notes[] = 'formulario/plugin';
        }
    }
    $out($url, $p->post_type, $cover, implode(' · ', $notes));
}

foreach (get_posts(['post_type' => 'job_listing', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
    $out((string) get_permalink($id), 'fiche', 'fiche 2026');
}

$taxes = ['job_listing_category' => 'carte 2026 (categoría)', 'region' => 'carte 2026 (región)', 'case27_job_listing_tags' => 'carte 2026 (etiqueta)',
    'category' => 'archivo blog 2026', 'post_tag' => 'SIN 2026', 'zone' => 'SIN 2026'];
foreach ($taxes as $tax => $cover) {
    if (!taxonomy_exists($tax)) {
        continue;
    }
    foreach (get_terms(['taxonomy' => $tax, 'hide_empty' => false]) as $t) {
        $link = get_term_link($t);
        if (!is_wp_error($link)) {
            $out($link, 'término ' . $tax, $cover, $t->count . ' elementos');
        }
    }
}
