<?php
/**
 * Planificateur 2026 (TASK-044): tabla de demandas, catálogo con km, endpoints REST, correos y cron.
 * Spec: docs/superpowers/specs/2026-10-01-planificateur-2026-design.md
 */
defined('ABSPATH') || exit;

const CANAL_PLANNER_DB_VERSION      = '2'; // 2: columna message
const CANAL_PLANNER_FE_INBOX        = 'mbauwens@francedit.com';
const CANAL_PLANNER_DEV_TO          = 'onavarro@francedit.com';
const CANAL_PLANNER_DAILY_CAP       = 300;
const CANAL_PLANNER_AI_IP_LIMIT     = 10;
const CANAL_PLANNER_AI_IP_WINDOW    = 600;
const CANAL_PLANNER_REQ_IP_LIMIT    = 3;
const CANAL_PLANNER_REQ_EMAIL_LIMIT = 2;
const CANAL_PLANNER_REQ_DAILY_CAP   = 100;

// Temas e ideas del inicio (del mockup aprobado; no se gestionan desde wp-admin, decisión del usuario).
const CANAL_PLANNER_THEMES = [
    ['key' => 'boat', 'label' => 'En bateau', 'prompts' => [
        '3 jours en bateau sans permis entre Carcassonne et Castelnaudary…',
        "Une croisière d'une journée avec passage d'écluses, pour 4 amis…",
        "Une semaine en péniche de Béziers à l'étang de Thau…",
    ], 'cards' => [
        ['t' => "Sans permis, au fil de l'eau", 'd' => 'Bateau électrique de Carcassonne à Castelnaudary, 17 écluses.', 'm' => ['3 jours', 'Sans permis']],
        ['t' => 'Les 9 écluses de Fonseranes', 'd' => "Croisière commentée au départ de Béziers, l'ouvrage le plus célèbre.", 'm' => ['1 journée', 'Croisière']],
        ['t' => "Jusqu'à la Méditerranée", 'd' => "Péniche de Béziers à Agde et l'étang de Thau, écluse ronde comprise.", 'm' => ['7 jours', 'Péniche']],
    ]],
    ['key' => 'bike', 'label' => 'À vélo', 'prompts' => [
        '4 jours à vélo de Toulouse à Carcassonne, bagages transportés…',
        'Une balade à vélo plate et ombragée, avec des enfants…',
        'Le canal à vélo électrique avec dégustations dans le Minervois…',
    ], 'cards' => [
        ['t' => 'De Toulouse au Lauragais', 'd' => "Le chemin de halage jusqu'au seuil de Naurouze et Castelnaudary.", 'm' => ['3 jours', 'Facile']],
        ['t' => 'Sous les platanes', 'd' => 'Étapes de 15 km entre Trèbes et Homps, idéal avec des enfants.', 'm' => ['2 jours', 'Tout plat']],
        ['t' => 'Minervois à vélo électrique', 'd' => 'Domaines viticoles entre Homps et Capestang, bagages transportés.', 'm' => ['4 jours', 'VAE']],
    ]],
    ['key' => 'family', 'label' => 'En famille', 'prompts' => [
        'Une semaine en famille avec deux enfants, entre vélo et baignade…',
        'Un gîte avec piscine près du canal pour 2 adultes et 3 enfants…',
        'Des activités au bord du canal pour des ados…',
    ], 'cards' => [
        ['t' => 'Vélo, bateau et baignade', 'd' => "Castelnaudary → Trèbes, une journée sur l'eau au milieu.", 'm' => ['5 jours', 'Enfants 6-12']],
        ['t' => 'Gîte et piscine dans le Minervois', 'd' => "Base fixe près d'Homps, sorties à la journée.", 'm' => ['7 nuits', 'Base fixe']],
        ['t' => 'Canoë et Cité médiévale', 'd' => 'Canoë sur le canal et visite de la Cité de Carcassonne.', 'm' => ['3 jours', 'Ados']],
    ]],
    ['key' => 'food', 'label' => 'Vins & terroir', 'prompts' => [
        'Un week-end gourmand entre cassoulet et vins du Minervois…',
        'Une route des vins le long du canal, sans voiture…',
        'Marchés et producteurs locaux autour de Béziers…',
    ], 'cards' => [
        ['t' => 'Le cassoulet de Castelnaudary', 'd' => 'Tables de tradition autour du Grand Bassin et du moulin de Cugarel.', 'm' => ['2 jours', 'Gourmand']],
        ['t' => "Minervois au fil de l'eau", 'd' => 'Caves et domaines accessibles depuis le chemin de halage.', 'm' => ['3 jours', 'Oenotourisme']],
        ['t' => "Marchés de l'Hérault", 'd' => "Producteurs, huîtres de l'étang de Thau et halles de Béziers.", 'm' => ['3 jours', 'Terroir']],
    ]],
    ['key' => 'love', 'label' => 'En amoureux', 'prompts' => [
        "Un week-end romantique en chambre d'hôtes au bord de l'eau…",
        'Surprendre ma compagne pour nos 10 ans de mariage…',
        'Deux nuits dans un hôtel de charme près de la Cité…',
    ], 'cards' => [
        ['t' => "Chambre d'hôtes au fil de l'eau", 'd' => "Maison d'éclusier rénovée et dîner en terrasse.", 'm' => ['2 nuits', 'Calme']],
        ['t' => 'Dîner sur un bateau-restaurant', 'd' => 'Croisière au coucher du soleil depuis Toulouse.', 'm' => ['1 soirée', 'Surprise']],
        ['t' => 'Carcassonne, la nuit', 'd' => 'Cité illuminée et hôtel de charme au pied des remparts.', 'm' => ['2 nuits', 'Charme']],
    ]],
];

function canal_planner_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'canal_plan_requests';
}

// El plugin ya está activo: un hook de activación no se ejecutaría al desplegar → versión en una opción.
function canal_planner_install(): void
{
    if (get_option('canal_planner_db_version') === CANAL_PLANNER_DB_VERSION) {
        return;
    }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = canal_planner_table();
    dbDelta("CREATE TABLE $table (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  token_hash char(64) NOT NULL,
  email varchar(190) NOT NULL,
  plan longtext NOT NULL,
  when_text varchar(190) NOT NULL DEFAULT '',
  people_text varchar(190) NOT NULL DEFAULT '',
  message text NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  recipients longtext NULL,
  created_at datetime NOT NULL,
  confirmed_at datetime NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY token_hash (token_hash),
  KEY status_created (status,created_at)
) " . $wpdb->get_charset_collate() . ';');
    // autoload: se lee en cada petición (init); así no cuesta una consulta extra.
    update_option('canal_planner_db_version', CANAL_PLANNER_DB_VERSION, true);
}
add_action('init', 'canal_planner_install');

// Correos reales solo con CANAL_PLANNER_LIVE = true en canal-ai-config.php (fuera de httpdocs).
function canal_planner_live(): bool
{
    canal_home_ai_config(); // carga canal-ai-config.php
    return defined('CANAL_PLANNER_LIVE') && CANAL_PLANNER_LIVE === true;
}

function canal_planner_send(array $mail): bool
{
    $m = canal_planner_finalize_mail($mail, canal_planner_live(), CANAL_PLANNER_DEV_TO);
    return (bool) wp_mail($m['to'], $m['subject'], $m['html'], $m['headers']);
}

// Catálogo de la home + km del canal, e-mail y teléfono (estos dos no van al prompt).
// ponytail: una consulta por ficha cada 12 h (254); si crece, una sola consulta de metas.
function canal_planner_catalog(): array
{
    $cached = get_transient('canal_planner_catalog_v2');
    if (is_array($cached)) {
        return $cached;
    }
    $items = [];
    foreach (canal_home_ai_catalog() as $item) {
        $post = get_page_by_path($item['slug'], OBJECT, 'job_listing');
        if (!$post) {
            continue;
        }
        $lat = get_post_meta($post->ID, 'geolocation_lat', true);
        $lng = get_post_meta($post->ID, 'geolocation_long', true);
        $email = sanitize_email((string) get_post_meta($post->ID, '_job_email', true));
        $hasGeo = $lat !== '' && $lng !== '';
        $item['km'] = $hasGeo ? canal_planner_km((float) $lat, (float) $lng) : null;
        $item['lat'] = $hasGeo ? (float) $lat : null;
        $item['lng'] = $hasGeo ? (float) $lng : null;
        $item['email'] = canal_planner_email_ok($email) && is_email($email) ? $email : '';
        $item['has_email'] = $item['email'] !== '';
        $item['phone'] = trim((string) get_post_meta($post->ID, '_job_phone', true));
        $items[$item['slug']] = $item;
    }
    set_transient('canal_planner_catalog_v2', $items, 12 * HOUR_IN_SECONDS);
    return $items;
}

// Prestatarios del plan con lo necesario para los correos.
function canal_planner_items(array $plan, array $catalog): array
{
    $items = [];
    foreach ($plan['providers'] as $slug) {
        if (!isset($catalog[$slug])) {
            continue;
        }
        $days = [];
        foreach ($plan['days'] as $d) {
            if ($d['slug'] === $slug) {
                $days[] = $d['label'] . ' · ' . $d['place'] . ' : ' . $d['text'];
            }
        }
        $c = $catalog[$slug];
        $items[] = ['slug' => $slug, 'title' => $c['title'], 'email' => $c['email'], 'phone' => $c['phone'], 'url' => canal_fiche_url($slug), 'days' => $days];
    }
    return $items;
}

// Plan para el navegador: cada día con el enlace, el nombre, la categoría y la foto de su ficha.
function canal_planner_public_plan(array $plan, array $catalog): array
{
    foreach ($plan['days'] as $i => $d) {
        $slug = $d['slug'];
        $card = $slug !== '' ? canal_home_card_by_slug($slug) : null;
        $plan['days'][$i]['url'] = $slug !== '' ? canal_fiche_url($slug) : '';
        $plan['days'][$i]['name'] = $slug !== '' ? $catalog[$slug]['title'] : '';
        $plan['days'][$i]['cat'] = $slug !== '' ? (string) ($catalog[$slug]['categories'][0] ?? '') : '';
        $plan['days'][$i]['image'] = $card ? (string) $card['image'] : '';
        $plan['days'][$i]['lat'] = $slug !== '' ? $catalog[$slug]['lat'] : null;
        $plan['days'][$i]['lng'] = $slug !== '' ? $catalog[$slug]['lng'] : null;
    }
    $plan['days'] = canal_carte_resized_images($plan['days']); // 768 px + srcset, como la carte
    $plan['names'] = array_map(function ($s) use ($catalog) { return $catalog[$s]['title']; }, $plan['providers']);
    return $plan;
}

function canal_planner_error(string $code, int $status): WP_REST_Response
{
    $messages = [
        'empty'       => 'Décrivez votre séjour en quelques mots.',
        'rate'        => 'Trop de demandes en peu de temps. Réessayez dans quelques minutes.',
        'daily'       => "L'assistant a atteint sa limite du jour. Revenez demain ou explorez la carte.",
        'unavailable' => "L'assistant est momentanément indisponible.",
        'incoherent'  => "Je n'ai pas trouvé de séjour cohérent, précisez votre envie.",
        'email'       => "Il me faut une adresse e-mail valide, par exemple marie@exemple.fr.",
        'plan'        => "Ce séjour ne contient aucune adresse à contacter.",
        'invalid'     => "Ce séjour n'est plus valide : demandez une nouvelle proposition à l'assistant.",
        'details'     => 'Indiquez vos dates et le nombre de personnes.',
        'expired'     => 'Ce lien a expiré ou a déjà été utilisé.',
        'link'        => "Ce lien n'est pas valide.",
    ];
    return new WP_REST_Response(['error' => $code, 'message' => $messages[$code] ?? $messages['unavailable']], $status);
}

function canal_planner_ip(): string
{
    return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
}

function canal_planner_plan_endpoint(WP_REST_Request $request): WP_REST_Response
{
    $messages = canal_planner_messages((array) $request->get_param('messages'));
    if (!$messages) {
        return canal_planner_error('empty', 400);
    }
    $config = canal_home_ai_config();
    if ($config === null) {
        return canal_planner_error('unavailable', 503);
    }
    if (!canal_home_rate_hit('canal_planner_ai_ip_' . md5(canal_planner_ip()), CANAL_PLANNER_AI_IP_LIMIT, CANAL_PLANNER_AI_IP_WINDOW)) {
        return canal_planner_error('rate', 429);
    }
    if (!canal_home_daily_hit('canal_planner_ai_daily_', CANAL_PLANNER_DAILY_CAP)) {
        return canal_planner_error('daily', 429);
    }
    $catalog = canal_planner_catalog();
    $current = null;
    $raw = $request->get_param('plan');
    if (is_array($raw)) {
        $v = canal_planner_validate(['plan' => $raw], $catalog);
        $current = $v['ok'] ? $v['plan'] : null;
    }
    $body = canal_planner_build_request($config['model'], canal_planner_catalog_text($catalog), $messages, $current);
    // ponytail: el reintento no cuenta en el tope diario (como mucho dobla el gasto de un mensaje).
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $response = canal_home_ai_post($config, $body, CANAL_PLANNER_JSON_HINT);
        if (is_wp_error($response)) {
            error_log('[canal-home] planificateur transporte: ' . $response->get_error_message());
            return canal_planner_error('unavailable', 502);
        }
        $parsed = canal_planner_parse_response((int) wp_remote_retrieve_response_code($response), (string) wp_remote_retrieve_body($response), $catalog);
        if ($parsed['ok'] && ($parsed['reply'] !== '' || $parsed['plan']['days'])) {
            return new WP_REST_Response(['reply' => $parsed['reply'], 'plan' => canal_planner_public_plan($parsed['plan'], $catalog)], 200);
        }
        $error = $parsed['ok'] ? 'empty' : $parsed['error'];
        // Nunca se registra lo que escribe el visitante.
        error_log('[canal-home] planificateur: ' . $error);
        if (!in_array($error, ['too_far', 'empty', 'bad_payload'], true)) {
            return canal_planner_error('unavailable', 502);
        }
        $body = canal_planner_retry_request($body, $error);
    }
    return canal_planner_error('incoherent', 422);
}

function canal_planner_request_endpoint(WP_REST_Request $request): WP_REST_Response
{
    // Honeypot: los bots rellenan el campo oculto; se responde « ok » sin enviar nada (como plan.php).
    if (trim((string) $request->get_param('website')) !== '') {
        return new WP_REST_Response(['ok' => true], 200);
    }
    $email = trim((string) $request->get_param('email'));
    if (!canal_planner_email_ok($email) || !is_email($email)) {
        return canal_planner_error('email', 400);
    }
    $catalog = canal_planner_catalog();
    $v = canal_planner_validate(['plan' => (array) $request->get_param('plan')], $catalog);
    if (!$v['ok']) {
        return canal_planner_error('invalid', 400); // p. ej. etapas demasiado largas: el plan ya no es coherente
    }
    if (!$v['plan']['providers']) {
        return canal_planner_error('plan', 400);
    }
    // Fechas y personas del formulario del modal (texto plano, sin saltos de línea); missing se recalcula.
    $v['plan'] = canal_planner_apply_details($v['plan'], (string) $request->get_param('when'), (string) $request->get_param('people'));
    $message = canal_planner_text((string) $request->get_param('message'), 500);
    if ($v['plan']['missing']) {
        return canal_planner_error('details', 400);
    }
    if (!canal_home_rate_hit('canal_planner_req_ip_' . md5(canal_planner_ip()), CANAL_PLANNER_REQ_IP_LIMIT, HOUR_IN_SECONDS)
        || !canal_home_rate_hit('canal_planner_req_mail_' . md5(canal_planner_email_key($email)), CANAL_PLANNER_REQ_EMAIL_LIMIT, DAY_IN_SECONDS)
        || !canal_home_daily_hit('canal_planner_req_daily_', CANAL_PLANNER_REQ_DAILY_CAP)) {
        return canal_planner_error('rate', 429);
    }
    global $wpdb;
    // Una sola demanda viva por e-mail: la nueva anula el enlace anterior (si no, confirmar los dos
    // enviaría dos veces la misma demanda a los prestatarios).
    $wpdb->query($wpdb->prepare('UPDATE ' . canal_planner_table() . " SET status = 'expired' WHERE email = %s AND status = 'pending'", $email));
    [$token, $hash] = canal_planner_new_token();
    $ok = $wpdb->insert(canal_planner_table(), [
        'token_hash'  => $hash,
        'email'       => $email,
        'plan'        => wp_json_encode($v['plan']),
        'when_text'   => $v['plan']['when'],
        'people_text' => $v['plan']['people'],
        'message'     => $message,
        'status'      => 'pending',
        'created_at'  => gmdate('Y-m-d H:i:s'),
    ]);
    if ($ok !== 1) {
        error_log('[canal-home] planificateur: insert falló');
        return canal_planner_error('unavailable', 500);
    }
    $items = canal_planner_items($v['plan'], $catalog);
    $url = add_query_arg('confirmer', $token, home_url(CANAL_PLANNER_PATH));
    if (!canal_planner_send(array_merge(canal_planner_mail_confirm($v['plan'], $url, array_column($items, 'title')), ['to' => $email]))) {
        // Sin correo no hay enlace: no se deja una fila pendiente ni se le dice « vérifiez votre boîte mail ».
        $wpdb->delete(canal_planner_table(), ['token_hash' => $hash]);
        error_log('[canal-home] planificateur: wp_mail de confirmación falló');
        return canal_planner_error('unavailable', 500);
    }
    return new WP_REST_Response(['ok' => true], 200);
}

function canal_planner_row(string $hash): ?array
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . canal_planner_table() . ' WHERE token_hash = %s', $hash), ARRAY_A);
    return is_array($row) ? $row : null;
}

function canal_planner_row_state(?array $row): string
{
    return canal_planner_token_state($row ? ['status' => $row['status'], 'created_at' => (int) strtotime($row['created_at'] . ' UTC')] : null, time());
}

function canal_planner_confirm_endpoint(WP_REST_Request $request): WP_REST_Response
{
    $token = (string) $request->get_param('token');
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return canal_planner_error('link', 404);
    }
    $row = canal_planner_row(canal_planner_token_hash($token));
    $state = canal_planner_row_state($row);
    if ($state !== 'ok') {
        return canal_planner_error($state === 'unknown' ? 'link' : 'expired', $state === 'unknown' ? 404 : 410);
    }
    global $wpdb;
    // Reclamación atómica: un doble clic o dos pestañas no envían dos veces.
    $claimed = $wpdb->update(canal_planner_table(), ['status' => 'sent', 'confirmed_at' => gmdate('Y-m-d H:i:s')], ['id' => (int) $row['id'], 'status' => 'pending']);
    if ($claimed !== 1) {
        return canal_planner_error('expired', 410);
    }
    $plan = json_decode((string) $row['plan'], true);
    $catalog = canal_planner_catalog();
    $items = canal_planner_items($plan, $catalog);
    $routes = canal_planner_route_recipients($items);
    $req = ['email' => $row['email'], 'when' => $row['when_text'], 'people' => $row['people_text'], 'title' => $plan['title'], 'message' => (string) ($row['message'] ?? '')];
    $log = [];
    foreach ($routes['provider'] as $it) {
        $log[] = ['slug' => $it['slug'], 'to' => 'provider', 'ok' => canal_planner_send(canal_planner_mail_provider($req, $it))];
    }
    if ($routes['fe']) {
        $ok = canal_planner_send(canal_planner_mail_fe($req, $routes['fe'], CANAL_PLANNER_FE_INBOX));
        foreach ($routes['fe'] as $it) {
            $log[] = ['slug' => $it['slug'], 'to' => 'fe_inbox', 'ok' => $ok];
        }
    }
    canal_planner_send(canal_planner_mail_summary($req, $plan, $items));
    $wpdb->update(canal_planner_table(), ['recipients' => wp_json_encode($log)], ['id' => (int) $row['id']]);
    wp_schedule_single_event(time() + 3 * DAY_IN_SECONDS, 'canal_planner_followup', [(int) $row['id']]);
    return new WP_REST_Response(['ok' => true, 'providers' => count($routes['provider']), 'fe' => count($routes['fe']), 'slugs' => array_column($items, 'slug')], 200);
}

// Vista previa para la página ?confirmer=<token>: solo lectura, no envía nada.
function canal_planner_confirm_preview(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $row = canal_planner_row(canal_planner_token_hash($token));
    $state = canal_planner_row_state($row);
    $plan = $row ? json_decode((string) $row['plan'], true) : null;
    $names = [];
    if (is_array($plan)) {
        $catalog = canal_planner_catalog();
        foreach ($plan['providers'] as $slug) {
            if (isset($catalog[$slug])) {
                $names[] = $catalog[$slug]['title'];
            }
        }
    }
    return ['token' => $token, 'state' => $state, 'title' => is_array($plan) ? (string) $plan['title'] : '', 'names' => $names];
}

// Número de demandas enviadas que incluyen una ficha (argumento de renovación).
function canal_planner_request_count(string $slug): int
{
    global $wpdb;
    $like = '%' . $wpdb->esc_like('"slug":"' . $slug . '"') . '%';
    return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . canal_planner_table() . " WHERE status = 'sent' AND recipients LIKE %s", $like));
}

add_action('canal_planner_followup', function ($id) {
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . canal_planner_table() . ' WHERE id = %d', (int) $id), ARRAY_A);
    if (!$row || $row['status'] !== 'sent') {
        return;
    }
    $plan = json_decode((string) $row['plan'], true);
    canal_planner_send(canal_planner_mail_followup(['email' => $row['email'], 'title' => (string) ($plan['title'] ?? '')], home_url(CANAL_PLANNER_PATH)));
});

// Purga diaria: 12 meses de conservación; pendientes de más de 48 h → expired.
add_action('canal_planner_purge', function () {
    global $wpdb;
    $table = canal_planner_table();
    $wpdb->query("DELETE FROM $table WHERE created_at < (UTC_TIMESTAMP() - INTERVAL 12 MONTH)");
    $wpdb->query("UPDATE $table SET status = 'expired' WHERE status = 'pending' AND created_at < (UTC_TIMESTAMP() - INTERVAL 48 HOUR)");
});
add_action('init', function () {
    if (!wp_next_scheduled('canal_planner_purge')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'canal_planner_purge');
    }
});

add_action('rest_api_init', function () {
    // Públicos a propósito (como /ai): protegidos por límites, honeypot y confirmación por e-mail.
    $routes = ['/plan' => 'canal_planner_plan_endpoint', '/plan/request' => 'canal_planner_request_endpoint', '/plan/confirm' => 'canal_planner_confirm_endpoint'];
    foreach ($routes as $route => $callback) {
        register_rest_route('canal-home/v1', $route, ['methods' => 'POST', 'callback' => $callback, 'permission_callback' => '__return_true']);
    }
});
