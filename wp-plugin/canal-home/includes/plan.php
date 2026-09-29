<?php
/**
 * Formulario « Recevoir le plan par e-mail »: POST admin-post.php?action=canal_home_plan.
 * Como en local: no se guarda ningún e-mail; se envía un correo fijo con el enlace al PDF.
 */
defined('ABSPATH') || exit;

const CANAL_HOME_PLAN_IP_LIMIT  = 3;
const CANAL_HOME_PLAN_IP_WINDOW = HOUR_IN_SECONDS;
const CANAL_HOME_PLAN_DAILY_CAP = 200;
const CANAL_HOME_PLAN_SUBJECT   = 'Votre plan du Canal du Midi 2026';

/** @return string ok|invalid|rate|error */
function canal_home_plan_handle(array $post, string $ip): string
{
    // Honeypot: los bots rellenan el campo oculto; se responde « ok » sin enviar nada.
    if (trim((string) ($post['website'] ?? '')) !== '') {
        return 'ok';
    }
    $email = trim((string) ($post['email'] ?? ''));
    if ($email === '' || !is_email($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return 'invalid';
    }
    if (!canal_home_rate_hit('canal_home_plan_ip_' . md5($ip), CANAL_HOME_PLAN_IP_LIMIT, CANAL_HOME_PLAN_IP_WINDOW)) {
        return 'rate';
    }
    if (!canal_home_daily_hit('canal_home_plan_daily_', CANAL_HOME_PLAN_DAILY_CAP)) {
        return 'rate';
    }
    // HTML generado una vez desde EmailTemplates::planByEmail() de la app local (contenido fijo).
    $html = (string) file_get_contents(CANAL_HOME_DIR . 'emails/plan.html');
    $sent = wp_mail($email, CANAL_HOME_PLAN_SUBJECT, $html, ['Content-Type: text/html; charset=UTF-8']);
    return $sent ? 'ok' : 'error';
}

function canal_home_plan_request(): void
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $status = canal_home_plan_handle(wp_unslash($_POST), $ip);
    $back = wp_get_referer();
    $back = $back ? remove_query_arg('plan', $back) : home_url('/');
    wp_safe_redirect(add_query_arg('plan', $status, $back) . '#plan');
    exit;
}
add_action('admin_post_nopriv_canal_home_plan', 'canal_home_plan_request');
add_action('admin_post_canal_home_plan', 'canal_home_plan_request');
