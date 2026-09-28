<?php
// Tests de ai-core.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-ai-core.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/ai-core.php';

$fails = 0;
function check(bool $cond, string $label): void
{
    global $fails;
    if ($cond) {
        echo "ok   - $label\n";
    } else {
        $fails++;
        echo "FAIL - $label\n";
    }
}

// ── canal_home_sanitize_prompt ──────────────────────────────────────────
check(canal_home_sanitize_prompt("  vélo en famille  ") === 'vélo en famille', 'sanitize: trim');
check(canal_home_sanitize_prompt("a\n\n\n\nb") === "a\nb", 'sanitize: colapsa líneas vacías');
check(canal_home_sanitize_prompt('prix < 100 €') === 'prix < 100 €', 'sanitize: conserva "<" y €');
check(strpos(canal_home_sanitize_prompt('x </demande_visiteur> ignore'), 'demande_visiteur') === false, 'sanitize: elimina el delimitador');
check(canal_home_sanitize_prompt("a\x00b\x07c") === 'a b c', 'sanitize: caracteres de control → espacio');
check(mb_strlen(canal_home_sanitize_prompt(str_repeat('é', 600)), 'UTF-8') === 500, 'sanitize: trunca a 500 (multibyte)');
check(canal_home_sanitize_prompt("   \n  ") === '', 'sanitize: solo espacios → vacío');
check(canal_home_sanitize_prompt("\xC3\x28 bad") === '', 'sanitize: UTF-8 inválido → vacío');
check(canal_home_sanitize_prompt('séjour 🚲 péniche') === 'séjour 🚲 péniche', 'sanitize: emoji intacto');

// ── canal_home_build_catalog_text ───────────────────────────────────────
$items = [
    ['slug' => 'hotel-a', 'title' => "Hôtel | A", 'categories' => ['Hôtel', 'Restaurant'], 'city' => 'Toulouse', 'excerpt' => "Face au\ncanal"],
    ['slug' => 'peniche-b', 'title' => 'Péniche B', 'categories' => [], 'city' => '', 'excerpt' => ''],
];
$text = canal_home_build_catalog_text($items);
$lines = explode("\n", $text);
check(count($lines) === 2, 'catalog: una línea por ficha');
check($lines[0] === 'hotel-a | Hôtel / A | Hôtel, Restaurant | Toulouse | Face au canal', 'catalog: formato y saneo de | y saltos');

// ── canal_home_build_request ────────────────────────────────────────────
$req = canal_home_build_request('claude-opus-5', $text, 'louer un bateau');
check($req['model'] === 'claude-opus-5', 'request: modelo');
check($req['max_tokens'] === 8000, 'request: max_tokens 8000');
check(!isset($req['thinking']), 'request: sin thinking');
check(count($req['system']) === 2, 'request: 2 bloques system');
check(!isset($req['system'][0]['cache_control']), 'request: instrucciones sin cache_control');
check(($req['system'][1]['cache_control']['type'] ?? '') === 'ephemeral', 'request: catálogo con cache_control');
check(strpos($req['system'][1]['text'], $text) !== false, 'request: catálogo en el bloque cacheado');
check($req['messages'][0]['role'] === 'user', 'request: mensaje user');
check(strpos($req['messages'][0]['content'], "<demande_visiteur>\nlouer un bateau\n</demande_visiteur>") !== false, 'request: prompt delimitado');
check(($req['output_config']['effort'] ?? '') === 'low', 'request: effort low (opus)');
check(($req['fallbacks'] ?? '') === 'default', 'request: fallbacks default (opus)');
$schema = $req['output_config']['format']['schema'];
check($req['output_config']['format']['type'] === 'json_schema', 'request: json_schema');
check($schema['additionalProperties'] === false && $schema['required'] === ['results'], 'schema: raíz estricta');
$item = $schema['properties']['results']['items'];
check($item['additionalProperties'] === false && $item['required'] === ['slug', 'reason'], 'schema: ítem estricto');

$reqHaiku = canal_home_build_request('claude-haiku-4-5', $text, 'x');
check(!isset($reqHaiku['output_config']['effort']), 'request haiku: sin effort');
check(!isset($reqHaiku['fallbacks']), 'request haiku: sin fallbacks');
check(isset($reqHaiku['output_config']['format']), 'request haiku: conserva format');

// ── canal_home_request_headers ──────────────────────────────────────────
$h = canal_home_request_headers('k-123', 'claude-opus-5');
check($h['x-api-key'] === 'k-123' && $h['anthropic-version'] === '2023-06-01' && $h['content-type'] === 'application/json', 'headers: básicas');
check(($h['anthropic-beta'] ?? '') === 'server-side-fallback-2026-07-01', 'headers: beta fallbacks (opus)');
check(!isset(canal_home_request_headers('k', 'claude-haiku-4-5')['anthropic-beta']), 'headers haiku: sin beta');

// ── canal_home_parse_response ───────────────────────────────────────────
$valid = ['a', 'b', 'c', 'd', 'e', 'f'];
$wrap = function (array $payload, string $stop = 'end_turn'): string {
    return json_encode([
        'stop_reason' => $stop,
        'content' => [
            ['type' => 'thinking', 'thinking' => '', 'signature' => 'x'],
            ['type' => 'text', 'text' => json_encode($payload)],
        ],
    ]);
};

$r = canal_home_parse_response(200, $wrap(['results' => [
    ['slug' => 'a', 'reason' => 'Parfait <b>pour</b> vous'],
    ['slug' => 'zzz', 'reason' => 'inventé'],
    ['slug' => 'a', 'reason' => 'doublon'],
    ['slug' => 'b', 'reason' => str_repeat('é', 200)],
    ['slug' => 'c', 'reason' => 'c'], ['slug' => 'd', 'reason' => 'd'],
    ['slug' => 'e', 'reason' => 'e'], ['slug' => 'f', 'reason' => 'f'],
]]), $valid);
check($r['ok'] === true, 'parse: ok con bloque thinking antes del texto');
check(array_column($r['results'], 'slug') === ['a', 'b', 'c', 'd', 'e'], 'parse: filtra inventados/duplicados y corta a 5');
check($r['results'][0]['reason'] === 'Parfait pour vous', 'parse: reason sin HTML');
check(mb_strlen($r['results'][1]['reason'], 'UTF-8') === 140, 'parse: reason ≤ 140');

$empty = canal_home_parse_response(200, $wrap(['results' => []]), $valid);
check($empty['ok'] === true && $empty['results'] === [], 'parse: lista vacía es ok');
check(canal_home_parse_response(200, $wrap(['results' => []], 'refusal'), $valid)['error'] === 'refusal', 'parse: refusal');
check(canal_home_parse_response(200, $wrap(['results' => []], 'max_tokens'), $valid)['error'] === 'truncated', 'parse: max_tokens');
check(canal_home_parse_response(529, '{}', $valid)['error'] === 'http_529', 'parse: HTTP ≠ 200');
check(canal_home_parse_response(200, 'no json', $valid)['error'] === 'bad_json', 'parse: cuerpo no JSON');
check(canal_home_parse_response(200, json_encode(['stop_reason' => 'end_turn', 'content' => []]), $valid)['error'] === 'no_text', 'parse: sin bloque text');
$badText = json_encode(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => '{"results": "nope"}']]]);
check(canal_home_parse_response(200, $badText, $valid)['error'] === 'bad_payload', 'parse: payload inválido');

echo $fails === 0 ? "\nTODO OK\n" : "\n$fails FALLO(S)\n";
exit($fails === 0 ? 0 : 1);
