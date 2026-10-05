<?php
// Regenera CANAL_METEO_STATIONS (includes/meteo-core.php) con la media de los 5 últimos años completos.
// Fuente: Météo-France, « Données climatologiques de base – mensuelles » (meteo.data.gouv.fr, Licence Ouverte v2).
// Uso (cada enero): php wp-plugin/build/build-meteo.php [último año, por defecto el año pasado] > /tmp/stations.php
// y pegar la salida en CANAL_METEO_STATIONS; actualizar CANAL_METEO_YEARS y los valores esperados de tests/test-meteo.php.

$last = (int) ($argv[1] ?? date('Y') - 1);
$first = $last - 4;
$base = 'https://object.files.data.gouv.fr/meteofrance/data/synchro_ftp/BASE/MENS/';
$stations = [ // [departamento, estación, lugar del canal (texto de la página), nombre de la estación, PK]
    ['31', '31069001', 'Toulouse', 'Toulouse-Blagnac', 0],
    ['31', '31374001', 'Lauragais', 'Montesquieu-Lauragais', 33],
    ['11', '11076001', 'Castelnaudary', 'Castelnaudary', 64],
    ['11', '11069001', 'Carcassonne', 'Carcassonne', 100],
    ['11', '11203004', 'Homps–Lézignan', 'Lézignan-Corbières', 151],
    ['11', '11012001', 'Le Somail', 'Argeliers', 172],
    ['34', '34032002', 'Béziers', 'Béziers-Courtade', 202],
    ['34', '34150001', 'Étang de Thau', 'Marseillan', 240],
];
// Los ficheros « previous » van hasta el año anterior al en curso; « latest » cubre el año en curso y el anterior.
$files = ['MENSQ_%s_previous-1950-' . (date('Y') - 2) . '.csv.gz', 'MENSQ_%s_latest-' . (date('Y') - 1) . '-' . date('Y') . '.csv.gz'];

$f1 = function (array $v): string {
    return '[' . implode(', ', array_map(function ($x) { return number_format($x, 1, '.', ''); }, $v)) . ']';
};
fwrite(STDERR, "Años {$first}–{$last}\n");
foreach ($stations as [$dep, $num, $name, $label, $pk]) {
    $rows = [];
    foreach ($files as $pattern) {
        $csv = @gzdecode((string) @file_get_contents($base . sprintf($pattern, $dep)));
        if ($csv === false || $csv === '') {
            fwrite(STDERR, 'No se pudo leer ' . sprintf($pattern, $dep) . "\n");
            exit(1);
        }
        $lines = explode("\n", $csv);
        $head = str_getcsv(array_shift($lines), ';');
        foreach ($lines as $line) {
            if (strpos($line, $num . ';') !== 0) {
                continue;
            }
            $r = array_combine($head, str_getcsv($line, ';'));
            $year = (int) substr($r['AAAAMM'], 0, 4);
            if ($year >= $first && $year <= $last) {
                $rows[$r['AAAAMM']] = $r; // la clave evita duplicados entre « previous » y « latest »
            }
        }
    }
    $out = ['tmin' => [], 'tmax' => [], 'rain' => [], 'hot' => []];
    for ($m = 1; $m <= 12; $m++) {
        $month = array_filter($rows, function ($r) use ($m) { return (int) substr($r['AAAAMM'], 4) === $m; });
        if (count($month) !== 5) {
            fwrite(STDERR, "$name: mes $m con " . count($month) . " años (se esperan 5)\n");
            exit(1);
        }
        foreach (['tmin' => 'TN', 'tmax' => 'TX', 'rain' => 'NBJRR1', 'hot' => 'NBJTX30'] as $key => $col) {
            $out[$key][] = round(array_sum(array_map('floatval', array_column($month, $col))) / count($month), 1);
        }
    }
    echo "    [\n";
    echo "        'name' => '$name', 'station' => '$label', 'id' => '$num', 'pk' => $pk,\n";
    foreach (['tmin', 'tmax', 'rain', 'hot'] as $k) {
        echo "        '$k' => " . $f1($out[$k]) . ",\n";
    }
    echo "    ],\n";
}
