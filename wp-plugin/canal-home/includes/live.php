<?php
/**
 * Interruptor de publicación del sitio 2026 (TASK-063, docs/plan-publicacion-2026.md).
 * - Privado (hoy): las páginas 2026 viven en rutas -2026 que solo ve quien tiene sesión.
 * - Publicado: opción `canal_2026_live` = '1' → el diseño 2026 en las URLs de siempre, indexable, con las 301 activas.
 *   Encender: `wp option update canal_2026_live 1` · apagar (vuelta atrás): `wp option delete canal_2026_live`.
 * - Vista previa: un administrador que visita /?canal_2026_preview=1 ve el sitio publicado (cookie); =0 la quita.
 *   Nadie más lo nota y WPFC no sirve ni guarda caché a usuarios con sesión.
 */
defined('ABSPATH') || defined('CANAL_HOME_TESTING') || exit;

/** Rutas de las páginas 2026 en cada modo (una sola fuente). */
function canal_2026_paths(bool $live): array
{
    return [
        'CANAL_HOME_PATH'      => $live ? '/' : '/accueil-2026/',
        'CANAL_CARTE_PATH'     => $live ? '/explorer/' : '/explorer-2026/',
        'CANAL_FICHE_PATH'     => $live ? '/fiche/' : '/fiche-2026/',
        'CANAL_PLANNER_PATH'   => $live ? '/planificateur/' : '/planificateur-2026/',
        'CANAL_CONTENU_SUFFIX' => $live ? '' : '-2026',
        'CANAL_ETAPE_PATH'     => $live ? '/etape/' : '/etape-2026/',
        'CANAL_ETAPES_PATH'    => $live ? '/etapes/' : '/etapes-2026/',
    ];
}

/** @param mixed $option valor de la opción canal_2026_live */
function canal_2026_live_mode($option, bool $previewCookie, bool $isAdmin): bool
{
    return $option === '1' || ($previewCookie && $isAdmin);
}

function canal_2026_define_paths(bool $live): void
{
    defined('CANAL_2026_LIVE') || define('CANAL_2026_LIVE', $live);
    foreach (canal_2026_paths($live) as $name => $value) {
        defined($name) || define($name, $value);
    }
}

/** Vista previa de un administrador (no la publicación real): no se tocan estados globales como las reglas de URL. */
function canal_2026_is_preview(): bool
{
    return CANAL_2026_LIVE && (function_exists('get_option') ? get_option('canal_2026_live') !== '1' : false);
}

if (function_exists('add_action')) {
    // Tras cargar pluggable.php (usuario disponible) y antes de cualquier hook que use las rutas.
    add_action('plugins_loaded', function () {
        $cookie = ($_COOKIE['canal_2026_preview'] ?? '') === '1';
        canal_2026_define_paths(canal_2026_live_mode(get_option('canal_2026_live'), $cookie, $cookie && current_user_can('manage_options')));
    }, 0);

    // Activar / quitar la vista previa (solo administradores).
    add_action('init', function () {
        if (!isset($_GET['canal_2026_preview']) || !current_user_can('manage_options')) {
            return;
        }
        $on = $_GET['canal_2026_preview'] === '1';
        setcookie('canal_2026_preview', $on ? '1' : '', ['expires' => $on ? time() + DAY_IN_SECONDS : time() - HOUR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax']);
        wp_safe_redirect(remove_query_arg('canal_2026_preview'));
        exit;
    });

    // Encender o apagar: purga la caché de página (WPFC) para que nadie reciba HTML del otro modo.
    foreach (['add_option_', 'update_option_', 'delete_option_'] as $canal_live_prefix) {
        add_action($canal_live_prefix . 'canal_2026_live', function () {
            do_action('wpfc_clear_all_cache');
            delete_transient('canal_2026_content_paths_v1');
        });
    }
    unset($canal_live_prefix);
}
