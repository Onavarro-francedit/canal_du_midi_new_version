<?php
/**
 * Límites compartidos (IA y formulario del plan).
 */
defined('ABSPATH') || exit;

// ponytail: límite por IP en transient, no atómico y fail-open si Redis cae; aceptable porque el
// gasto lo acota el tope diario atómico (canal_home_daily_hit). Exactitud: INCR en Redis + EXPIRE.
function canal_home_rate_hit(string $key, int $limit, int $ttl): bool
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
function canal_home_daily_hit(string $prefix, int $cap): bool
{
    global $wpdb;
    $name = $prefix . gmdate('Ymd');
    $ok = $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'off')
         ON DUPLICATE KEY UPDATE option_value = option_value + 1",
        $name
    ));
    if ($ok === false) {
        return false;
    }
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name));
    // Limpieza de los días anteriores del mismo contador ($prefix*).
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name <> %s",
        $wpdb->esc_like($prefix) . '%',
        $name
    ));
    return $count <= $cap;
}
