<?php
// Tests de fiche-core.php — PHP CLI puro (7.4+), sin WordPress.
// Uso: php wp-plugin/tests/test-fiche.php   (exit 1 si algo falla)
define('CANAL_HOME_TESTING', true);
require __DIR__ . '/../canal-home/includes/fiche-core.php';

$fails = 0;
function check(bool $cond, string $label): void
{
    global $fails;
    if ($cond) {
        echo "ok   - $label\n";
    } else {
        $fails++;
        echo "FAIL - $label\n";
    }
}

// Vídeo: solo YouTube/Vimeo, id validado.
check(canal_fiche_video_embed('https://www.youtube.com/watch?v=V6bsAQRZyAg') === 'https://www.youtube-nocookie.com/embed/V6bsAQRZyAg', 'video: youtube watch');
check(canal_fiche_video_embed('https://youtu.be/V6bsAQRZyAg?t=3') === 'https://www.youtube-nocookie.com/embed/V6bsAQRZyAg', 'video: youtu.be');
check(canal_fiche_video_embed('https://www.youtube.com/embed/V6bsAQRZyAg') === 'https://www.youtube-nocookie.com/embed/V6bsAQRZyAg', 'video: youtube embed');
check(canal_fiche_video_embed('https://vimeo.com/123456789') === 'https://player.vimeo.com/video/123456789', 'video: vimeo');
check(canal_fiche_video_embed('https://evil.example/watch?v=V6bsAQRZyAg') === '', 'video: host no permitido');
check(canal_fiche_video_embed('https://www.youtube.com/watch?v=abc"><script>') === '', 'video: id inválido');
check(canal_fiche_video_embed('https://www.youtube.com/watch?v[]=x') === '', 'video: v como array');
check(canal_fiche_video_embed('') === '' && canal_fiche_video_embed('pas une url') === '', 'video: vacío / basura');

// Teléfono: el tel: solo lleva el número.
check(canal_fiche_tel('04 68 91 59 30') === '0468915930', 'tel: con espacios');
check(canal_fiche_tel('Tél Atelier : 07 68 13 87 23') === '0768138723', 'tel: con texto delante');
check(canal_fiche_tel('+33 4.68.91.59.30') === '+33468915930', 'tel: internacional con puntos');
check(canal_fiche_tel('04 68 12 34 56 - 06 12 34 56 78') === '0468123456', 'tel: dos números → solo el primero');
check(canal_fiche_tel('0468123456 0612345678') === '0468123456', 'tel: dos números pegados → solo el primero');
check(canal_fiche_tel('+33 (0)4 68 12 34 56') === '+33468123456', 'tel: +33 (0) conserva el prefijo');
check(canal_fiche_tel('fermé') === '' && canal_fiche_tel('') === '', 'tel: sin número');

// Redes: _facebook + _links, solo http(s) y dominio exacto.
$links = [
    ['network' => 'Facebook', 'url' => 'https://www.facebook.com/otra/'],
    ['network' => 'Instagram', 'url' => 'https://www.instagram.com/chambre_lamarelle/'],
    ['network' => 'X', 'url' => 'https://notfacebook.com/x'],
    'basura',
];
$s = canal_fiche_social('https://www.facebook.com/maisonlamarelle/', $links);
check(($s['facebook'] ?? '') === 'https://www.facebook.com/maisonlamarelle/', 'social: _facebook tiene prioridad');
check(($s['instagram'] ?? '') === 'https://www.instagram.com/chambre_lamarelle/', 'social: instagram desde _links');
check(count($s) === 2, 'social: dominio falso descartado');
check(canal_fiche_social('javascript:alert(1)//facebook.com', '') === [], 'social: esquema no http descartado');

// Coordenadas y distancia.
check(canal_fiche_has_coords(43.21, 2.35) && !canal_fiche_has_coords(null, 2.35) && !canal_fiche_has_coords(0.0, 0.0), 'coords: null y 0,0 no valen');
$d = canal_fiche_distance_km(43.6045, 1.4440, 43.2130, 2.3491); // Toulouse → Carcassonne ≈ 84 km
check($d > 80 && $d < 88, "distancia: Toulouse–Carcassonne ≈ 84 km ($d)");
check(canal_fiche_km_label(0.85) === '850 m' && canal_fiche_km_label(3.24) === '3,2 km', 'km_label: m y km con coma');

// Dirección que en realidad son coordenadas (écluses: _job_location = « lat, lng »).
check(canal_fiche_is_coords_text('43.265531891736615, 2.109718510697836'), 'coords_text: « lat, lng »');
check(canal_fiche_is_coords_text(' -1.5,43.2 '), 'coords_text: sin espacio y negativa');
check(!canal_fiche_is_coords_text('20, rue du Pech 11170 Sainte-Eulalie'), 'coords_text: dirección real');
check(!canal_fiche_is_coords_text(''), 'coords_text: vacío');

// Cercanas.
$L = function (int $id, $lat, $lng): array { return ['id' => $id, 'title' => "F$id", 'lat' => $lat, 'lng' => $lng]; };
$all = [$L(1, 43.60, 1.44), $L(2, 43.61, 1.45), $L(3, 43.21, 2.35), $L(4, null, null), $L(5, 0.0, 0.0), $L(6, 43.605, 1.445)];
$near = canal_fiche_nearby($all, 1, 43.60, 1.44, 2);
check(array_column($near, 'id') === [6, 2], 'nearby: orden por distancia, excluye la propia, n=2');
check(isset($near[0]['distance_km']) && $near[0]['distance_km'] < 1, 'nearby: añade distance_km');
check(!in_array(4, array_column(canal_fiche_nearby($all, 1, 43.60, 1.44, 10), 'id'), true), 'nearby: sin coords excluida');
check(!in_array(5, array_column(canal_fiche_nearby($all, 1, 43.60, 1.44, 10), 'id'), true), 'nearby: 0,0 excluida');
check(canal_fiche_nearby($all, 1, null, null) === [] && canal_fiche_nearby($all, 1, 0.0, 0.0) === [], 'nearby: ficha sin coords → []');

// Extracto.
$long = str_repeat('Le canal du Midi ', 20);
$ex = canal_fiche_excerpt($long);
check(mb_strlen($ex, 'UTF-8') <= 155 && substr($ex, -3) === '…', 'excerpt: ≤155 y termina en …');
check(canal_fiche_excerpt("  Écluse   de\n Sauzens  ") === 'Écluse de Sauzens', 'excerpt: espacios colapsados, corto sin …');

// Grafo JSON-LD.
$f = [
    'title' => 'Hôtel de Bordeaux', 'excerpt' => 'Hôtel au bord du canal.', 'cover' => 'https://x/c.jpg', 'gallery' => ['https://x/c.jpg', 'https://x/g.jpg'],
    'address' => '4 Boulevard Bonrepos, 31000 Toulouse, France', 'city' => 'Toulouse', 'postcode' => '31000',
    'lat' => 43.6, 'lng' => 1.45, 'phone' => '05 61 62 41 09', 'email' => 'a@b.fr', 'website' => 'https://www.hoteldebordeaux31.fr/',
    'social' => ['facebook' => 'https://www.facebook.com/h/'],
];
$g = canal_fiche_seo_graph($f, 'https://s/fiche-2026/h/', 'https://s/', 'https://s/explorer-2026/');
$place = $g['@graph'][0];
check($g['@context'] === 'https://schema.org', 'graph: @context');
check($place['@type'] === 'LocalBusiness', 'graph: con contacto → LocalBusiness');
check($place['image'] === ['https://x/c.jpg', 'https://x/g.jpg'], 'graph: imágenes sin duplicar');
check($place['sameAs'] === ['https://www.hoteldebordeaux31.fr/', 'https://www.facebook.com/h/'], 'graph: sameAs web + redes');
check($place['address']['postalCode'] === '31000' && $place['geo']['latitude'] === 43.6, 'graph: address y geo');
check(array_column($g['@graph'][1]['itemListElement'], 'position') === [1, 2, 3], 'graph: breadcrumb de 3 niveles');
$min = canal_fiche_seo_graph(['title' => 'Écluse', 'excerpt' => '', 'cover' => '', 'gallery' => [], 'address' => '', 'city' => '', 'postcode' => '',
    'lat' => null, 'lng' => null, 'phone' => '', 'email' => '', 'website' => '', 'social' => []], 'https://s/f/', 'https://s/', 'https://s/c/');
$mp = $min['@graph'][0];
check($mp['@type'] === 'TouristAttraction', 'graph: sin contacto → TouristAttraction');
check(!isset($mp['geo']) && !isset($mp['address']) && !isset($mp['image']) && !isset($mp['telephone']) && !isset($mp['description']), 'graph: campos vacíos omitidos');

echo $fails ? "\n$fails FALLO(S)\n" : "\nTODO OK\n";
exit($fails ? 1 : 0);
