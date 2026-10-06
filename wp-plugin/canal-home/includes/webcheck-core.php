<?php
/**
 * Revisión semanal de las webs de las fichas (06/10, petición del usuario): una web caída no se enlaza en las páginas 2026
 * (el dato de la ficha NUNCA se modifica) y se envía un resumen por e-mail. Funciones puras: clasificar una respuesta y
 * componer el e-mail. Lo que toca WordPress (cron, peticiones, opción, envío) está en webcheck.php. Test: tests/test-webcheck.php.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

const CANAL_WEBCHECK_TO = ['agomes@francedit.com', 'onavarro@francedit.com'];

// Páginas que el servidor devuelve con 200 pero que no son la web del prestatario.
const CANAL_WEBCHECK_PLACEHOLDERS = [
    'Apache2 Default Page'        => 'page par défaut du serveur (Apache)',
    'Apache2 Ubuntu Default Page' => 'page par défaut du serveur (Apache)',
    'Apache3 Ubuntu Default Page' => 'page par défaut du serveur (Apache)',
    'Welcome to nginx'            => 'page par défaut du serveur (nginx)',
    'Account Suspended'           => 'hébergement suspendu',
    'domain is for sale'          => 'nom de domaine à vendre',
    'domaine est à vendre'        => 'nom de domaine à vendre',
];

/**
 * ¿Caída? $code: código HTTP final (0 si no hubo respuesta), $error: mensaje de error de red, $body: inicio del HTML.
 * 401/403/429/503 = protecciones anti-robots que un visitante sí atraviesa (hoteles, OVH): « à vérifier », no se oculta.
 * @return array{status: string, reason: string} status: ok | down | check
 */
function canal_webcheck_classify(int $code, string $error, string $body): array
{
    if ($code === 0) {
        // Sin DNS = caída segura. Sin conexión puede ser un cortafuegos que bloquea solo nuestro servidor (olydea.com, 06/10:
        // timeout desde el servidor, 200 en un navegador) → a verificar, el enlace se queda.
        return stripos($error, 'resolve') !== false
            ? ['status' => 'down', 'reason' => 'le nom de domaine n’existe plus']
            : ['status' => 'check', 'reason' => 'le serveur ne répond pas à notre robot'];
    }
    if (in_array($code, [401, 403, 429, 503], true)) {
        return ['status' => 'check', 'reason' => 'accès refusé aux robots (HTTP ' . $code . ')'];
    }
    if ($code === 404 || $code === 410) {
        return ['status' => 'down', 'reason' => 'page introuvable (HTTP ' . $code . ')'];
    }
    if ($code >= 500) {
        return ['status' => 'down', 'reason' => 'erreur du serveur (HTTP ' . $code . ')'];
    }
    if ($code >= 400) {
        return ['status' => 'down', 'reason' => 'erreur HTTP ' . $code];
    }
    foreach (CANAL_WEBCHECK_PLACEHOLDERS as $needle => $reason) {
        if (stripos($body, $needle) !== false) {
            return ['status' => 'down', 'reason' => $reason];
        }
    }
    return ['status' => 'ok', 'reason' => ''];
}

/**
 * E-mail del resumen semanal. $down / $check / $back: [['title','url','reason','since','edit'], …].
 * @return array{subject: string, html: string}
 */
function canal_webcheck_email(array $down, array $check, array $back, int $total, string $date): array
{
    $e = function (string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
    $list = function (array $rows, bool $since) use ($e): string {
        if (!$rows) {
            return '<p style="color:#6c718d">Aucune.</p>';
        }
        $li = array_map(function ($r) use ($e, $since) {
            return '<li style="margin:0 0 8px"><b>' . $e($r['title']) . '</b> — <a href="' . $e($r['url']) . '">' . $e($r['url']) . '</a><br>'
                . '<span style="color:#6c718d">' . $e($r['reason']) . ($since && $r['since'] !== '' ? ' · depuis le ' . $e($r['since']) : '')
                . ($r['edit'] !== '' ? ' · <a href="' . $e($r['edit']) . '">modifier la fiche</a>' : '') . '</span></li>';
        }, $rows);
        return '<ul style="padding-left:18px">' . implode('', $li) . '</ul>';
    };
    $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1f2340;max-width:640px">'
        . '<p>Revue hebdomadaire des sites web des fiches du Canal du Midi (' . $e($date) . ') : ' . $total . ' sites vérifiés.</p>'
        . '<h2 style="font-size:17px">Sites en panne — lien masqué sur les pages 2026 (' . count($down) . ')</h2>'
        . '<p style="color:#6c718d">Les données des fiches ne sont pas modifiées : seul le lien n’est plus affiché tant que le site ne répond pas. '
        . 'À corriger avec le prestataire si l’adresse a changé.</p>' . $list($down, true)
        . '<h2 style="font-size:17px">À vérifier à la main (' . count($check) . ')</h2>'
        . '<p style="color:#6c718d">Ces sites refusent les robots ; ils fonctionnent souvent dans un navigateur. Le lien reste affiché.</p>' . $list($check, false)
        . '<h2 style="font-size:17px">De nouveau en ligne — lien rétabli (' . count($back) . ')</h2>' . $list($back, false)
        . '</div>';
    return [
        'subject' => 'Canal du Midi — ' . count($down) . ' site' . (count($down) > 1 ? 's' : '') . ' web en panne (lien masqué) · ' . $date,
        'html'    => $html,
    ];
}
