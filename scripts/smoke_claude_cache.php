<?php
// Smoke aislado de prompt caching: dos llamadas idénticas; la 2ª debe leer de caché.
// Uso: ANTHROPIC_API_KEY=sk-ant-... php scripts/smoke_claude_cache.php
require __DIR__ . '/../vendor/autoload.php';

$apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
if ($apiKey === '') {
    fwrite(STDERR, "FAIL: define ANTHROPIC_API_KEY en el entorno\n");
    exit(1);
}

$client = new Anthropic\Client(apiKey: $apiKey);

// Bloque > 2048 tokens para superar el mínimo cacheable de Sonnet 4.6.
$big = str_repeat("Le Canal du Midi relie Toulouse à la Méditerranée sur 240 km. ", 400);

$call = function () use ($client, $big) {
    $m = $client->messages->create(
        model: 'claude-sonnet-4-6',
        maxTokens: 16,
        system: [
            ['type' => 'text', 'text' => $big, 'cacheControl' => ['type' => 'ephemeral']],
        ],
        messages: [['role' => 'user', 'content' => 'Réponds "ok".']],
    );
    return [
        'read' => $m->usage->cacheReadInputTokens ?? 0,
        'creation' => $m->usage->cacheCreationInputTokens ?? 0,
    ];
};

$a = $call();
echo "1ª llamada: creation={$a['creation']} read={$a['read']}\n";
$b = $call();
echo "2ª llamada: creation={$b['creation']} read={$b['read']}\n";

if (($b['read'] ?? 0) > 0) {
    echo "PASS: prompt caching activo (cache_read > 0 en la 2ª llamada)\n";
    exit(0);
}
fwrite(STDERR, "FAIL: cache_read = 0 en la 2ª llamada\n");
exit(1);
