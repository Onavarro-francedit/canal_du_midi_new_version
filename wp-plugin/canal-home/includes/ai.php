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
    if (!canal_home_rate_hit('canal_home_ai_ip_' . md5($ip), CANAL_HOME_AI_IP_LIMIT, CANAL_HOME_AI_IP_WINDOW)) {
        return canal_home_ai_error('rate', 429);
    }
    if (!canal_home_daily_hit('canal_home_ai_daily_', $config['daily_cap'])) {
        return canal_home_ai_error('daily', 429);
    }

    $catalog = canal_home_ai_catalog();
    $request = canal_home_build_request($config['model'], canal_home_build_catalog_text($catalog), $prompt);
    $send = function (array $body) use ($config) {
        return wp_remote_post(CANAL_HOME_AI_ENDPOINT, [
            'timeout' => 30,
            'headers' => canal_home_request_headers($config['key'], $config['model']),
            'body'    => wp_json_encode($body),
        ]);
    };
    // Servicio de json_schema caído en la API: un reintento sin schema (el parser valida igual).
    // Mientras dure la caída (10 min) se pide directamente sin schema: el 503 tarda 10-17 s en llegar.
    if (get_transient('canal_home_ai_no_schema')) {
        $response = $send(canal_home_request_without_schema($request));
    } else {
        $response = $send($request);
        if (!is_wp_error($response) && canal_home_is_grammar_outage(
            (int) wp_remote_retrieve_response_code($response),
            (string) wp_remote_retrieve_body($response)
        )) {
            error_log('[canal-home] IA: json_schema no disponible, reintento sin schema');
            set_transient('canal_home_ai_no_schema', 1, 10 * MINUTE_IN_SECONDS);
            $response = $send(canal_home_request_without_schema($request));
        }
    }
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
