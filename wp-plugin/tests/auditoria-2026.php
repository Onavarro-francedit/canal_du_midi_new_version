<?php
// Auditoría SEO/AEO/GEO de las páginas 2026 (TASK-070, 06/10): mide cada URL 2026 con la rúbrica de la skill seo-geo.
// Solo lectura. Pide cada página como administrador en vista previa de publicación (cookies canal_2026_preview y de
// sesión de 2 h generadas aquí, no salen del servidor): el sitio tal como quedará publicado, en sus URLs definitivas. Uso: wp-plugin/remote.sh run tests/auditoria-2026.php > docs/data/auditoria-2026.tsv
// Entrada: tests/auditoria-urls.tsv (ruta 2026 · tipo · ruta actual · clics · impresiones · posición).
$dir = $args[0] ?? __DIR__ . '/..';
$rows = array_map(function ($l) {
    return explode("\t", rtrim($l, "\n"));
}, file($dir . '/tests/auditoria-urls.tsv'));

$admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID'])[0];
$exp = time() + 7200;
$cookie = SECURE_AUTH_COOKIE . '=' . rawurlencode(wp_generate_auth_cookie($admin, $exp, 'secure_auth'))
    . '; ' . LOGGED_IN_COOKIE . '=' . rawurlencode(wp_generate_auth_cookie($admin, $exp, 'logged_in')) . '; canal_2026_preview=1';
$home = rtrim(home_url(), '/');
$host = (string) wp_parse_url($home, PHP_URL_HOST);
// Las étapes no están en el inventario (son nuevas): se toman de los enlaces de /etapes/.
$get = function (string $path) use ($home, $cookie): string {
    $ch = curl_init($home . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIE => $cookie]);
    $html = (string) curl_exec($ch);
    curl_close($ch);
    return $html;
};
preg_match_all('#href="(?:' . preg_quote($home, '#') . ')?(/etape/[^/"]+/)"#', $get('/etapes/'), $m);
foreach (array_unique($m[1]) as $p) {
    $rows[] = [$p, 'etape', $p, '0', '0', ''];
}

/** Métricas de una página (HTML ya descargado). */
$measure = function (string $html) use ($host): array {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    $x = new DOMXPath($doc);
    $one = function (string $q) use ($x): string {
        $n = $x->query($q);
        return $n->length ? trim(preg_replace('/\s+/u', ' ', $n->item(0)->nodeValue)) : '';
    };
    $title = $one('//title');
    $desc = $one('//meta[@name="description"]/@content');
    $types = [];
    $modified = '';
    foreach ($x->query('//script[@type="application/ld+json"]') as $s) {
        $j = json_decode($s->nodeValue, true);
        array_walk_recursive($j, function ($v, $k) use (&$types, &$modified) {
            if ($k === '@type') {
                $types[$v] = true;
            } elseif ($k === 'dateModified' && $v > $modified) {
                $modified = substr($v, 0, 10);
            }
        });
    }
    $main = $x->query('//main')->item(0) ?: $x->query('//body')->item(0);
    $text = $main ? trim(preg_replace('/\s+/u', ' ', $main->textContent)) : '';
    $h2 = $x->query('.//h2', $main);
    $q = 0;
    foreach ($h2 as $h) {
        $q += substr(trim($h->textContent), -1) === '?' ? 1 : 0;
    }
    $int = $ext = 0;
    foreach ($x->query('.//a[@href]', $main) as $a) {
        $u = wp_parse_url($a->getAttribute('href'));
        if (!isset($u['host']) || $u['host'] === $host) {
            $int += isset($u['path']) ? 1 : 0;
        } else {
            $ext++;
        }
    }
    return [
        'title' => $title,
        'title_len' => mb_strlen($title),
        'desc_len' => mb_strlen($desc),
        'canonical' => $one('//link[@rel="canonical"]/@href') !== '' ? 1 : 0,
        'robots' => $one('//meta[@name="robots"]/@content'),
        'h1' => $x->query('//h1')->length,
        'h1_text' => mb_substr($one('//h1'), 0, 90),
        'og' => ($one('//meta[@property="og:title"]/@content') !== '' && $one('//meta[@property="og:image"]/@content') !== '' ? 1 : 0),
        'twitter' => $one('//meta[@name="twitter:card"]/@content') !== '' ? 1 : 0,
        'schema' => implode(',', array_keys($types)),
        'modified' => $modified,
        'words' => $text === '' ? 0 : count(preg_split('/\s+/u', $text)),
        'h2' => $h2->length,
        'h2_q' => $q,
        'int_links' => $int,
        'ext_links' => $ext,
        'img_noalt' => $x->query('.//img[not(@alt) or @alt=""]', $main)->length,
        'tables_lists' => $x->query('.//table|.//ol|.//ul[not(ancestor::nav)]', $main)->length,
    ];
};

$cols = ['ruta_2026_privada', 'tipo', 'url', 'clics', 'impresiones', 'posicion', 'status', 'title', 'title_len', 'desc_len', 'canonical',
    'robots', 'h1', 'h1_text', 'og', 'twitter', 'schema', 'modified', 'words', 'h2', 'h2_q', 'int_links', 'ext_links', 'img_noalt', 'tables_lists'];
echo implode("\t", $cols), "\n";

// ponytail: 3 peticiones a la vez para no saturar PHP-FPM (pm.max_children pendiente en TASK-063).
$mh = curl_multi_init();
$queue = $rows;
$active = [];
$done = 0;
$start = function () use (&$queue, &$active, $mh, $home, $cookie) {
    while (count($active) < 3 && $queue) {
        $r = array_shift($queue);
        $ch = curl_init($home . $r[2]); // URL definitiva (vista previa)
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIE => $cookie,
            CURLOPT_USERAGENT => 'canal-auditoria-2026', CURLOPT_FOLLOWLOCATION => false]);
        curl_multi_add_handle($mh, $ch);
        $active[(int) $ch] = [$ch, $r];
    }
};
$start();
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh, 1.0);
    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        [, $r] = $active[(int) $ch];
        unset($active[(int) $ch]);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $html = (string) curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        $m = $status === 200 && $html !== '' ? $measure($html) : array_fill_keys(array_slice($cols, 7), '');
        $line = array_merge(array_pad($r, 6, ''), [$status], array_values($m));
        echo implode("\t", array_map(function ($v) {
            return str_replace(["\t", "\n", "\r"], ' ', (string) $v);
        }, $line)), "\n";
        if (++$done % 100 === 0) {
            fwrite(STDERR, "$done/" . count($rows) . "\n");
        }
        $start();
    }
} while ($active || $queue);
curl_multi_close($mh);
