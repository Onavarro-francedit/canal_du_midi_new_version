<?php
/**
 * Revisión semanal de las webs de las fichas (06/10): cada lunes, WP-Cron pide la web de cada ficha publicada; las caídas
 * se guardan en la opción `canal_webcheck` (nueva: la ficha NUNCA se modifica), fiche-data.php deja de enlazarlas en las
 * páginas 2026 y se envía el resumen a CANAL_WEBCHECK_TO. A mano: remote.sh wp eval 'canal_webcheck_run();'
 * Lógica pura y e-mail: webcheck-core.php. Doc: docs/webs-caidas-fiches.md.
 */
defined('ABSPATH') || exit;

const CANAL_WEBCHECK_OPTION = 'canal_webcheck';
const CANAL_WEBCHECK_HOOK = 'canal_webcheck_weekly';
const CANAL_WEBCHECK_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36';

/** ¿La web de esta ficha estaba caída en la última revisión? Si la URL cambió desde entonces, se vuelve a mostrar. */
function canal_webcheck_is_down(int $id, string $url): bool
{
    $down = get_option(CANAL_WEBCHECK_OPTION, [])['down'] ?? [];
    return isset($down[$id]) && $down[$id]['url'] === $url;
}

/** Una petición como la de un navegador → ['status','reason']. */
function canal_webcheck_fetch(string $url): array
{
    $r = wp_remote_get(preg_match('#^https?://#i', $url) ? $url : 'http://' . $url, [
        'timeout'             => 15,
        'redirection'         => 5,
        'user-agent'          => CANAL_WEBCHECK_UA,
        'headers'             => ['Accept' => 'text/html', 'Accept-Language' => 'fr-FR,fr'],
        'limit_response_size' => 300000,
    ]);
    if (is_wp_error($r)) {
        return canal_webcheck_classify(0, $r->get_error_message(), '');
    }
    return canal_webcheck_classify((int) wp_remote_retrieve_response_code($r), '', substr((string) wp_remote_retrieve_body($r), 0, 20000));
}

function canal_webcheck_run(): void
{
    if (function_exists('set_time_limit')) {
        set_time_limit(0);
    }
    $prev = get_option(CANAL_WEBCHECK_OPTION, [])['down'] ?? [];
    $today = wp_date('j F Y');
    $rows = ['down' => [], 'check' => [], 'back' => []];
    $down = [];
    $total = 0;
    // ponytail: secuencial (≈140 webs, unos minutos una vez por semana); peticiones en paralelo si las fichas se multiplican.
    foreach (get_posts(['post_type' => 'job_listing', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
        $url = trim((string) get_post_meta($id, '_job_website', true));
        if ($url === '') {
            continue;
        }
        $total++;
        $res = canal_webcheck_fetch($url);
        if ($res['status'] === 'down') {
            sleep(5);
            $res = canal_webcheck_fetch($url); // segunda oportunidad: un corte de un minuto no oculta el enlace una semana
        }
        $row = ['title' => html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8'), 'url' => $url, 'reason' => $res['reason'],
            'since' => $prev[$id]['since'] ?? $today, 'edit' => admin_url('post.php?post=' . $id . '&action=edit')];
        if ($res['status'] === 'down') {
            $down[$id] = $row;
            $rows['down'][] = $row;
        } elseif ($res['status'] === 'check') {
            $rows['check'][] = $row;
        } elseif (isset($prev[$id])) {
            $rows['back'][] = $row;
        }
    }
    update_option(CANAL_WEBCHECK_OPTION, ['down' => array_map(function ($r) {
        return ['url' => $r['url'], 'reason' => $r['reason'], 'since' => $r['since']];
    }, $down), 'checked' => time()], false);

    $mail = canal_webcheck_email($rows['down'], $rows['check'], $rows['back'], $total, $today);
    if (!wp_mail(CANAL_WEBCHECK_TO, $mail['subject'], $mail['html'], ['Content-Type: text/html; charset=UTF-8'])) {
        error_log('[canal-home] webcheck: wp_mail falló');
    }
}
add_action(CANAL_WEBCHECK_HOOK, 'canal_webcheck_run');

// Cada lunes a las 7:00 (hora de París).
add_action('init', function () {
    if (!wp_next_scheduled(CANAL_WEBCHECK_HOOK)) {
        wp_schedule_event((new DateTime('next monday 07:00', wp_timezone()))->getTimestamp(), 'weekly', CANAL_WEBCHECK_HOOK);
    }
});
