<?php
// Formulario « Recevoir le plan par e-mail » con WP cargado, plugin SIN activar.
// wp_mail interceptado con pre_wp_mail: NO sale ningún correo real.
// Uso: wp-plugin/remote.sh run tests/smoke-plan.php
$src = $args[0] ?? '';
require_once $src . '/canal-home/canal-home.php';

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};

$mails = [];
$mailResult = null; // null = simular envío correcto
add_filter('pre_wp_mail', function ($short, $atts) use (&$mails, &$mailResult) {
    $mails[] = $atts;
    return $mailResult === null ? true : $mailResult;
}, 10, 2);

global $wpdb;
$ip = '203.0.113.7';
$ipKey = 'canal_home_plan_ip_' . md5($ip);
$dayKey = 'canal_home_plan_daily_' . gmdate('Ymd');
$dayCount = function () use ($wpdb, $dayKey) {
    return $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $dayKey));
};
$setDay = function ($value) use ($wpdb, $dayKey) {
    $wpdb->delete($wpdb->options, ['option_name' => $dayKey]);
    if ($value !== null) {
        $wpdb->insert($wpdb->options, ['option_name' => $dayKey, 'option_value' => (string) $value, 'autoload' => 'off']);
    }
};
$realDay = $dayCount();
$reset = function () use ($ipKey, $setDay, &$mails) {
    delete_transient($ipKey);
    $setDay(0);
    $mails = [];
};

$reset();
$check(canal_home_plan_handle(['email' => 'pas-un-email'], $ip) === 'invalid' && $mails === [], 'e-mail inválido → invalid, sin envío');
$check(canal_home_plan_handle(['email' => "a@b.com\r\nBcc: x@y.com"], $ip) === 'invalid' && $mails === [], 'inyección de cabeceras → invalid');
$check(canal_home_plan_handle(['email' => 'bot@example.com', 'website' => 'http://spam'], $ip) === 'ok' && $mails === [], 'honeypot relleno → ok aparente, sin envío');

$reset();
$status = canal_home_plan_handle(['email' => '  visiteur@example.com '], $ip);
$m = $mails[0] ?? [];
$check($status === 'ok' && count($mails) === 1, 'e-mail válido → ok, 1 envío');
$check(($m['to'] ?? '') === 'visiteur@example.com', 'destinatario recortado');
$check(($m['subject'] ?? '') === 'Votre plan du Canal du Midi 2026', 'asunto fijo');
$check(strpos((string) ($m['message'] ?? ''), 'uploads/pdf/Plan-Canal-du-Midi-2026.pdf') !== false && strpos((string) $m['message'], 'calameo.com/read/003331405edc35288442a') !== false, 'cuerpo con PDF y Calaméo');
$check(strpos((string) $m['message'], 'canaldumidi.fr') === false, 'sin el dominio ajeno canaldumidi.fr');
$check(strpos(implode("\n", (array) ($m['headers'] ?? [])), 'text/html') !== false, 'cabecera HTML');
$check((int) $dayCount() === 1, 'contador diario en wp_options = 1');

// Límite por IP: 3 por hora.
canal_home_plan_handle(['email' => 'visiteur@example.com'], $ip);
canal_home_plan_handle(['email' => 'visiteur@example.com'], $ip);
$before = count($mails);
$check(canal_home_plan_handle(['email' => 'visiteur@example.com'], $ip) === 'rate' && count($mails) === $before, '4º envío desde la misma IP → rate, sin envío');

// Tope diario.
$reset();
$setDay(200);
$check(canal_home_plan_handle(['email' => 'visiteur@example.com'], $ip) === 'rate' && $mails === [], 'tope diario 200 → rate, sin envío');

// Fallo del SMTP.
$reset();
$mailResult = false;
$check(canal_home_plan_handle(['email' => 'visiteur@example.com'], $ip) === 'error', 'wp_mail falla → error');
$mailResult = null;

$reset();
$setDay($realDay);
$check($dayCount() === $realDay, 'contador real de hoy restaurado (' . var_export($realDay, true) . ')');
WP_CLI::log($fails === 0 ? 'TODO OK' : "$fails FALLO(S)");
