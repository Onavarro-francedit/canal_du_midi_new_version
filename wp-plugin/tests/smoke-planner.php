<?php
// Smoke del planificador con WP cargado y el plugin DESPLEGADO Y ACTIVO (no carga el código fuente).
// API de Claude simulada con pre_http_request y correo con pre_wp_mail: NO sale nada real.
// Uso: wp-plugin/remote.sh run tests/smoke-planner.php
$fails = 0;
$check = function (bool $cond, string $label) use (&$fails): void {
    if (!$cond) {
        $fails++;
    }
    WP_CLI::log(($cond ? 'ok   - ' : 'FAIL - ') . $label);
};
if (!function_exists('canal_planner_plan_endpoint')) {
    WP_CLI::error('El plugin canal-home con el planificador no está activo en este sitio.');
}

global $wpdb;
$table = canal_planner_table();
$check((bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)), 'tabla creada');
$catalog = canal_planner_catalog();
$slugs = array_keys($catalog);
$withKm = array_values(array_filter($catalog, function ($c) { return $c['km'] !== null; }));
$check(count($catalog) > 200, 'catálogo: ' . count($catalog) . ' fichas');
$check(count($withKm) > 100, 'catálogo: ' . count($withKm) . ' con km');
$a = $withKm[0];
$b = $withKm[1];

// API simulada: devuelve un plan con dos fichas reales del mismo tramo.
$plan = ['title' => 'Smoke', 'mode' => 'voiture', 'when' => '14-17 mai', 'people' => '2 adultes', 'missing' => [], 'days' => [
    ['label' => 'Jour 1', 'place' => $a['city'], 'km' => 0, 'text' => 'Test', 'slug' => $a['slug']],
    ['label' => 'Jour 2', 'place' => $b['city'], 'km' => 0, 'text' => 'Test', 'slug' => $b['slug']],
]];
add_filter('pre_http_request', function ($pre, $args, $url) use ($plan) {
    if ($url !== CANAL_HOME_AI_ENDPOINT) {
        return $pre;
    }
    $text = wp_json_encode(['reply' => 'Voici un séjour.', 'plan' => $plan]);
    return ['response' => ['code' => 200], 'body' => wp_json_encode(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => $text]]]), 'headers' => [], 'cookies' => []];
}, 10, 3);
$mails = [];
add_filter('pre_wp_mail', function ($short, $atts) use (&$mails) {
    $mails[] = $atts;
    return true;
}, 10, 2);

$_SERVER['REMOTE_ADDR'] = '203.0.113.' . wp_rand(10, 250);
$rest = function (string $route, array $params) {
    $r = new WP_REST_Request('POST', '/canal-home/v1' . $route);
    foreach ($params as $k => $v) {
        $r->set_param($k, $v);
    }
    return rest_do_request($r);
};

// /plan
$res = $rest('/plan', ['messages' => [['role' => 'user', 'text' => 'deux jours en voiture']]]);
$data = $res->get_data();
$check($res->get_status() === 200 && $data['reply'] === 'Voici un séjour.', '/plan: 200 con reply');
$check(isset($data['plan']['days'][0]['url']) && strpos($data['plan']['days'][0]['url'], CANAL_FICHE_PATH) !== false, '/plan: enlace a la ficha 2026');
$check(strpos((string) ($data['plan']['days'][0]['image'] ?? ''), 'https://') === 0, '/plan: imagen de la ficha en cada día');
$check(($data['plan']['days'][0]['cat'] ?? '') === (string) ($a['categories'][0] ?? ''), '/plan: categoría de la ficha en cada día');
$check(is_float($data['plan']['days'][0]['lat'] ?? null) && is_float($data['plan']['days'][0]['lng'] ?? null), '/plan: coordenadas de la ficha en cada día');
$check($rest('/plan', ['messages' => []])->get_status() === 400, '/plan: sin mensajes → 400');

// /plan/request
$email = 'smoke-planner@example.com';
$check($rest('/plan/request', ['plan' => $data['plan'], 'email' => "x@y.fr\r\nBcc: z@w.fr"])->get_status() === 400, 'request: e-mail con CRLF → 400');
$fake = $data['plan'];
$fake['days'][0]['slug'] = 'slug-que-no-existe';
$fake['days'][1]['slug'] = 'otro-falso';
$check($rest('/plan/request', ['plan' => $fake, 'email' => $email])->get_status() === 400, 'request: plan con slugs falsos → 400');
$check($rest('/plan/request', ['plan' => $data['plan'], 'email' => $email, 'website' => 'spam'])->get_status() === 200 && !$mails, 'request: honeypot → ok sin correo');
$noDates = $data['plan'];
$noDates['when'] = '';
$check($rest('/plan/request', ['plan' => $noDates, 'email' => $email])->get_status() === 400, 'request: sin fechas ni campo del formulario → 400');
$mails = [];
$res = $rest('/plan/request', ['plan' => $noDates, 'email' => $email, 'when' => 'du 6 au 7 juin', 'people' => '2 adultes', 'message' => 'Smoke : nous avons un chien']);
$check($res->get_status() === 200 && count($mails) === 1, 'request: 200 y 1 correo de confirmación');
$check($mails && $mails[0]['to'] === CANAL_PLANNER_DEV_TO, 'request: sin LIVE el correo va a ' . CANAL_PLANNER_DEV_TO);
preg_match('/confirmer=([a-f0-9]{64})/', (string) ($mails[0]['message'] ?? ''), $m);
$token = $m[1] ?? '';
$check($token !== '', 'request: token en el enlace');
$preview = canal_planner_confirm_preview($token);
$check($preview && $preview['state'] === 'ok' && count($preview['names']) === 2, 'preview: estado ok y 2 nombres');

// Revisión final: una segunda demanda del mismo e-mail anula el enlace anterior (los prestatarios no reciben dos).
$mails = [];
$rest('/plan/request', ['plan' => $noDates, 'email' => $email, 'when' => 'du 6 au 7 juin', 'people' => '2 adultes', 'message' => 'Smoke : nous avons un chien']);
preg_match('/confirmer=([a-f0-9]{64})/', (string) ($mails[0]['message'] ?? ''), $m2);
$token2 = $m2[1] ?? '';
$mails = [];
$check($token2 !== '' && $rest('/plan/confirm', ['token' => $token])->get_status() === 410 && !$mails, 'request: la 2.ª demanda anula el enlace de la 1.ª');
$token = $token2;

// /plan/confirm
$mails = [];
$res = $rest('/plan/confirm', ['token' => $token]);
$check($res->get_status() === 200, 'confirm: 200');
$check(count((array) ($res->get_data()['slugs'] ?? [])) === 2, 'confirm: devuelve los slugs (evento GA planner_request con listing_slug)');
$tos = array_unique(array_column($mails, 'to'));
$check($tos === [CANAL_PLANNER_DEV_TO], 'confirm: TODOS los correos a ' . CANAL_PLANNER_DEV_TO);
$check(count($mails) >= 2, 'confirm: correos a prestatarios/buzón + resumen (' . count($mails) . ')');
$check(strpos((string) ($mails[0]['message'] ?? ''), 'Smoke : nous avons un chien') !== false, 'confirm: el mensaje del visitante llega al prestatario');
$check(strpos((string) ($mails[0]['subject'] ?? ''), 'du 6 au 7 juin') !== false, 'confirm: las fechas del formulario en el asunto');
$mails = [];
$check($rest('/plan/confirm', ['token' => $token])->get_status() === 410 && !$mails, 'confirm: segunda vez → 410 y 0 correos');
$check($rest('/plan/confirm', ['token' => str_repeat('a', 64)])->get_status() === 404, 'confirm: token desconocido → 404');
$check((bool) wp_next_scheduled('canal_planner_purge'), 'cron: purga programada');

// Limpieza: filas y eventos del smoke.
$ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table WHERE email = %s", $email));
foreach ($ids as $id) {
    wp_clear_scheduled_hook('canal_planner_followup', [(int) $id]);
}
$wpdb->delete($table, ['email' => $email]);
delete_transient('canal_planner_req_mail_' . md5($email));

$fails ? WP_CLI::error("$fails FALLOS") : WP_CLI::success('smoke-planner OK');
