#!/bin/bash
# Uso: wp-plugin/remote.sh test|smoke|deploy|run <archivo>|wp <args…>
#  test   → tests de ai-core + lint PHP 7.4 de todo el plugin, en /tmp del servidor
#  smoke  → smoke de data.php con WP cargado (ANTES de activar el plugin: si no, "Cannot redeclare")
#  deploy → test + rsync al directorio del plugin + permisos
#  run    → wp eval-file de un script de wp-plugin/tests/ (recibe el dir src en $args[0])
#  wp     → cualquier comando WP-CLI como el usuario del sitio, con PHP 7.4
set -eu
SRC="$(cd "$(dirname "$0")" && pwd)"
REMOTE=plesk-prod
SITE=/var/www/vhosts/plan-canal-du-midi.com
DEST="$SITE/httpdocs/wp-content/plugins/canal-home"
TMP=/tmp/canal-home-src
PHP74=/opt/plesk/php/7.4/bin/php
WP="sudo -u ga241453_canal $PHP74 /usr/local/bin/wp --path=$SITE/httpdocs"

sync_tmp() {
    rsync -a --delete --exclude build/ "$SRC/" "$REMOTE:$TMP/"
    ssh "$REMOTE" "chmod -R a+rX $TMP"
}

run_test() {
    sync_tmp
    ssh "$REMOTE" "set -e
        for f in \$(find $TMP/canal-home -name '*.php'); do $PHP74 -l \"\$f\"; done
        $PHP74 $TMP/tests/test-ai-core.php"
}

case "${1:-}" in
    test)
        run_test
        ssh "$REMOTE" "rm -rf $TMP"
        ;;
    smoke|run)
        FILE="tests/smoke-data.php"
        [ "$1" = run ] && FILE="${2:?falta el archivo, p. ej. tests/check-cache.php}"
        sync_tmp
        ssh "$REMOTE" "$WP eval-file $TMP/$FILE $TMP; rm -rf $TMP"
        ;;
    wp)
        shift
        ssh "$REMOTE" "$WP $(printf '%q ' "$@")"
        ;;
    deploy)
        run_test
        rsync -a --delete "$SRC/canal-home/" "$REMOTE:$DEST/"
        ssh "$REMOTE" "chown -R ga241453_canal:psacln '$DEST' \
            && find '$DEST' -type d -exec chmod 755 {} + \
            && find '$DEST' -type f -exec chmod 644 {} + \
            && rm -rf $TMP"
        ;;
    *)
        echo "uso: $0 test|smoke|deploy|run <archivo>|wp <args…>" >&2
        exit 2
        ;;
esac
