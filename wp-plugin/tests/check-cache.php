<?php
// Dos llamadas reales con el mismo catálogo: la 2ª debe leer el prefijo de caché.
$config = canal_home_ai_config();
if ($config === null) {
    WP_CLI::error('Falta canal-ai-config.php');
}
$catalogText = canal_home_build_catalog_text(canal_home_ai_catalog());
foreach (['balade à vélo', 'location de péniche'] as $prompt) {
    $response = wp_remote_post(CANAL_HOME_AI_ENDPOINT, [
        'timeout' => 30,
        'headers' => canal_home_request_headers($config['key'], $config['model']),
        'body'    => wp_json_encode(canal_home_build_request($config['model'], $catalogText, $prompt)),
    ]);
    $usage = (json_decode((string) wp_remote_retrieve_body($response), true) ?: [])['usage'] ?? [];
    WP_CLI::log(sprintf(
        '%s in=%s cache_write=%s cache_read=%s out=%s',
        wp_remote_retrieve_response_code($response),
        $usage['input_tokens'] ?? '?',
        $usage['cache_creation_input_tokens'] ?? '?',
        $usage['cache_read_input_tokens'] ?? '?',
        $usage['output_tokens'] ?? '?'
    ));
}
