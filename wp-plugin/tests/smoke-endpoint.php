<?php
// Endpoint IA con WP cargado, plugin SIN activar y API de Claude simulada (pre_http_request): 0 llamadas reales.
// Uso: wp-plugin/remote.sh run tests/smoke-endpoint.php
define('CANAL_AI_API_KEY', 'test-key');   // config de prueba (no se usa el archivo real)
define('CANAL_AI_MODEL', 'claude-opus-5');
define('CANAL_AI_DAILY_CAP', 50);
$src = $args[0] ?? '';
require_once $src . '/canal-home/canal-home.php';

$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};
$call = function (string $prompt): WP_REST_Response {
    $request = new WP_REST_Request('POST', '/canal-home/v1/ai');
    $request->set_param('prompt', $prompt);
    return canal_home_ai_endpoint($request);
};
$ipKey = 'canal_home_ai_ip_' . md5(isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '');
$dayKey = 'canal_home_ai_daily_' . gmdate('Ymd');
// El contador diario vive en wp_options (atómico, independiente de Redis). Se guarda el valor
// real de hoy y se restaura al final: el test no debe regalar presupuesto a producción.
global $wpdb;
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
$reset = function () use ($ipKey, $setDay) {
    delete_transient($ipKey);
    $setDay(0);
};

// API simulada: $fake = [status, body]; registra la última petición saliente.
$fake = null;
$sent = null;
add_filter('pre_http_request', function ($pre, $args, $url) use (&$fake, &$sent) {
    if ($url !== CANAL_HOME_AI_ENDPOINT) {
        return $pre;
    }
    $sent = $args;
    return ['headers' => [], 'body' => $fake[1], 'response' => ['code' => $fake[0], 'message' => ''], 'cookies' => [], 'filename' => null];
}, 10, 3);
$answer = function (array $results): string {
    return json_encode(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => json_encode(['results' => $results])]]]);
};

$reset();
$r = $call('   ');
$check($r->get_status() === 400 && $r->get_data()['error'] === 'empty', 'prompt vacío → 400 empty');

$fake = [200, $answer([
    ['slug' => 'hotel-de-bordeaux', 'reason' => 'Face au canal, à Toulouse.'],
    ['slug' => 'inventado-xyz', 'reason' => 'no existe'],
])];
$r = $call('un hôtel à Toulouse');
$data = $r->get_data();
$check($r->get_status() === 200, 'respuesta 200');
$check(count($data['results']) === 1, 'slug inventado descartado (' . count($data['results']) . ')');
$first = $data['results'][0] ?? [];
$check(($first['title'] ?? '') !== '' && substr($first['url'] ?? '', -strlen('/fiche/hotel-de-bordeaux/')) === '/fiche/hotel-de-bordeaux/', 'tarjeta construida en servidor (título + permalink)');
$check(($first['reason'] ?? '') === 'Face au canal, à Toulouse.' && ($first['city'] ?? '') === 'Toulouse', 'reason y ciudad');
$body = json_decode((string) ($sent['body'] ?? ''), true);
$check(($sent['headers']['x-api-key'] ?? '') === 'test-key' && ($sent['timeout'] ?? 0) === 30, 'cabeceras y timeout 30 s');
$check(strpos($body['system'][1]['text'] ?? '', 'hotel-de-bordeaux | ') !== false, 'catálogo real en la petición');

$fake = [529, '{"type":"error"}'];
$r = $call('péniche');
$check($r->get_status() === 502 && $r->get_data()['error'] === 'unavailable', 'API caída → 502 unavailable');

$reset();
set_transient($ipKey, 10, 600);
$sent = null;
$r = $call('vélo');
$check($r->get_status() === 429 && $r->get_data()['error'] === 'rate' && $sent === null, 'límite por IP → 429 rate sin llamar a la API');

$reset();
$setDay(50);
$r = $call('vélo');
$check($r->get_status() === 429 && $r->get_data()['error'] === 'daily' && $sent === null, 'tope diario (fila en wp_options) → 429 daily sin llamar a la API');
$check(strpos($r->get_data()['message'], 'limite du jour') !== false, 'mensaje en francés');

// Independiente de Redis: borrar las claves de caché de options no reinicia el contador.
// (NO usar wp_cache_flush(): vaciaría la caché Redis de todo el sitio en producción.)
$reset();
$setDay(49);
$fake = [200, $answer([['slug' => 'hotel-de-bordeaux', 'reason' => 'ok']])];
$r = $call('hôtel');
$check($r->get_status() === 200 && (int) $dayCount() === 50, 'la llamada 50 pasa y el contador queda en 50 (' . $dayCount() . ')');
wp_cache_delete($dayKey, 'options');
wp_cache_delete('alloptions', 'options');
delete_transient($dayKey);
delete_transient($ipKey);
$sent = null;
$r = $call('hôtel');
$check($r->get_status() === 429 && $r->get_data()['error'] === 'daily' && $sent === null, 'sin caché de objetos el tope sigue aplicándose');

$reset();
$setDay($realDay);
$check($dayCount() === $realDay, 'contador real de hoy restaurado (' . var_export($realDay, true) . ')');
WP_CLI::log($fails === 0 ? 'TODO OK' : "$fails FALLO(S)");
