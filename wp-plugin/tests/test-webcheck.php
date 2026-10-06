<?php
// Tests de webcheck-core.php (revisión semanal de las webs de las fichas) — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-webcheck.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/webcheck-core.php';

$fails = 0;
function check(bool $cond, string $label): void
{
    global $fails;
    if ($cond) {
        echo "ok   - $label\n";
    } else {
        echo "FAIL - $label\n";
        $fails++;
    }
}

// Casos reales de la revisión del 06/10.
check(canal_webcheck_classify(200, '', '<title>Apache3 Ubuntu Default Page: It works</title>')['status'] === 'down', 'sallelesdaude.fr (www): página por defecto de Apache → caída');
check(canal_webcheck_classify(404, '', '')['status'] === 'down', 'camping de Montolieu /fr: 404 → caída');
check(canal_webcheck_classify(0, 'cURL error 6: Could not resolve host: www.port-serignan.fr', '')['reason'] === 'le nom de domaine n’existe plus', 'port-serignan.fr: dominio inexistente');
check(canal_webcheck_classify(521, '', '')['status'] === 'down', 'port-carcassonne.com: 521 → caída');
check(canal_webcheck_classify(403, '', '')['status'] === 'check', 'hoteles (403 a robots) → a verificar, enlace visible');
check(canal_webcheck_classify(503, '', '')['status'] === 'check', 'OVH anti-robots (503) → a verificar');
check(canal_webcheck_classify(200, '', '<title>Mairie de Sallèles d’Aude</title>')['status'] === 'ok', 'web correcta → ok');
check(canal_webcheck_classify(0, 'cURL error 28: Failed to connect to www.olydea.com port 443', '')['status'] === 'check', 'olydea.com: timeout solo desde el servidor → a verificar, enlace visible');

$row = ['title' => 'Ville de Sallèles-d’Aude', 'url' => 'http://www.sallelesdaude.fr', 'reason' => 'page par défaut du serveur (Apache)', 'since' => '6 octobre 2026', 'edit' => 'https://x/wp-admin/post.php?post=1&action=edit'];
$mail = canal_webcheck_email([$row], [], [], 137, '6 octobre 2026');
check($mail['subject'] === 'Canal du Midi — 1 site web en panne (lien masqué) · 6 octobre 2026', 'e-mail: asunto');
check(strpos($mail['html'], 'Ville de Sallèles-d’Aude') !== false && strpos($mail['html'], 'depuis le 6 octobre 2026') !== false && strpos($mail['html'], '137 sites vérifiés') !== false, 'e-mail: ficha, fecha y total');
check(strpos($mail['html'], 'ne sont pas modifiées') !== false, 'e-mail: aclara que los datos no se tocan');
$evil = canal_webcheck_email([['title' => '<script>x</script>', 'url' => 'http://a', 'reason' => 'r', 'since' => '', 'edit' => '']], [], [], 1, 'd');
check(strpos($evil['html'], '<script>') === false, 'e-mail: títulos escapados');
check(CANAL_WEBCHECK_TO === ['agomes@francedit.com', 'onavarro@francedit.com'], 'destinatarios');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
