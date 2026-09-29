<?php
/**
 * Endpoint REST del asistente IA: POST canal-home/v1/ai  {prompt}
 */
defined('ABSPATH') || exit;

const CANAL_HOME_AI_IP_LIMIT  = 10;
const CANAL_HOME_AI_IP_WINDOW = 600;

function canal_home_ai_config(): ?array
{
    static $loaded = false;
    static $config = null;
    if ($loaded) {
        return $config;
    }
    $loaded = true;
    // Fuera de httpdocs: /var/www/vhosts/plan-canal-du-midi.com/canal-ai-config.php
    $file = dirname(untrailingslashit(ABSPATH)) . '/canal-ai-config.php';
    if (is_readable($file)) {
        require_once $file;
    }
    if (defined('CANAL_AI_API_KEY') && CANAL_AI_API_KEY !== '') {
        $config = [
            'key'       => (string) CANAL_AI_API_KEY,
            'model'     => defined('CANAL_AI_MODEL') ? (string) CANAL_AI_MODEL : 'claude-opus-5',
            'daily_cap' => defined('CANAL_AI_DAILY_CAP') ? (int) CANAL_AI_DAILY_CAP : 300,
        ];
    }
    return $config;
}

function canal_home_ai_error(string $code, int $status): WP_REST_Response
{
    $messages = [
        'empty'       => 'Décrivez votre envie en quelques mots.',
        'rate'        => 'Trop de demandes en peu de temps. Réessayez dans quelques minutes.',
        'daily'       => "L'assistant IA a atteint sa limite du jour. Utilisez la recherche classique.",
        'unavailable' => "L'assistant IA est momentanément indisponible.",
    ];
    return new WP_REST_Response([
        'error'   => $code,
        'message' => $messages[$code] ?? $messages['unavailable'],
        'results' => [],
    ], $status);
}

// ponytail: límite por IP en transient, no atómico y fail-open si Redis cae; aceptable porque el
// gasto lo acota el tope diario atómico (canal_home_ai_daily_hit). Exactitud: INCR en Redis + EXPIRE.
function canal_home_ai_hit(string $key, int $limit, int $ttl): bool
{
    $count = (int) get_transient($key);
    if ($count >= $limit) {
        return false;
    }
    set_transient($key, $count + 1, $ttl);
    return true;
}

// Tope diario = único límite del gasto → contador ATÓMICO en wp_options, no en transients:
// con el drop-in de Redis activo, una caída o expulsión de la clave reiniciaría el tope (fail-open)
// y get/set pierde incrementos bajo concurrencia. Si la BD falla, se rechaza (fail-closed).
function canal_home_ai_daily_hit(int $cap): bool
{
    global $wpdb;
    $name = 'canal_home_ai_daily_' . gmdate('Ymd');
    $ok = $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'off')
         ON DUPLICATE KEY UPDATE option_value = option_value + 1",
        $name
    ));
    if ($ok === false) {
        return false;
    }
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name));
    // Limpieza de los días anteriores (filas propias canal_home_ai_daily_*).
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name <> %s",
        $wpdb->esc_like('canal_home_ai_daily_') . '%',
        $name
    ));
    return $count <= $cap;
}

function canal_home_ai_endpoint(WP_REST_Request $request): WP_REST_Response
{
    $prompt = canal_home_sanitize_prompt((string) $request->get_param('prompt'));
    if (mb_strlen($prompt, 'UTF-8') < 3) {
        return canal_home_ai_error('empty', 400);
    }
    $config = canal_home_ai_config();
    if ($config === null) {
        return canal_home_ai_error('unavailable', 503);
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    if (!canal_home_ai_hit('canal_home_ai_ip_' . md5($ip), CANAL_HOME_AI_IP_LIMIT, CANAL_HOME_AI_IP_WINDOW)) {
        return canal_home_ai_error('rate', 429);
    }
    if (!canal_home_ai_daily_hit($config['daily_cap'])) {
        return canal_home_ai_error('daily', 429);
    }

    $catalog = canal_home_ai_catalog();
    $response = wp_remote_post(CANAL_HOME_AI_ENDPOINT, [
        'timeout' => 30,
        'headers' => canal_home_request_headers($config['key'], $config['model']),
        'body'    => wp_json_encode(canal_home_build_request(
            $config['model'],
            canal_home_build_catalog_text($catalog),
            $prompt
        )),
    ]);
    if (is_wp_error($response)) {
        error_log('[canal-home] IA transporte: ' . $response->get_error_message());
        return canal_home_ai_error('unavailable', 502);
    }

    $parsed = canal_home_parse_response(
        (int) wp_remote_retrieve_response_code($response),
        (string) wp_remote_retrieve_body($response),
        array_column($catalog, 'slug')
    );
    if (!$parsed['ok']) {
        // Nunca se registra el prompt del visitante.
        error_log('[canal-home] IA error: ' . $parsed['error']);
        return canal_home_ai_error('unavailable', 502);
    }

    $results = [];
    foreach ($parsed['results'] as $row) {
        $card = canal_home_card_by_slug($row['slug']);
        if ($card !== null) {
            $card['reason'] = $row['reason'];
            $results[] = $card;
        }
    }
    return new WP_REST_Response(['results' => $results], 200);
}

add_action('rest_api_init', function () {
    register_rest_route('canal-home/v1', '/ai', [
        'methods'             => 'POST',
        'callback'            => 'canal_home_ai_endpoint',
        // Público a propósito (la home pasa por caché de página; un nonce cacheado caducaría).
        // Protegido por los límites por IP y diario.
        'permission_callback' => '__return_true',
    ]);
});
