<?php
/**
 * Contenido editorial de la home usado a la vez por la plantilla (visible) y por el JSON-LD
 * (FAQPage): una sola fuente, así el schema nunca dice algo distinto de lo que se ve.
 * Datos tomados de las páginas del propio sitio (FAQ, Permis de conduire, Voie verte,
 * calculadora de distancias: PK de cada écluse), 2026-09-29.
 */
defined('ABSPATH') || exit;

// Preguntas frecuentes: respuesta en texto plano + enlace opcional a la página que la amplía.
const CANAL_HOME_FAQ = [
    [
        'q'    => 'Faut-il un permis pour naviguer sur le Canal du Midi ?',
        'a'    => "Dans la majorité des cas, non : les bateaux proposés à la location se pilotent sans permis. Le permis « eaux intérieures » n'est obligatoire que si la puissance du moteur dépasse 4,5 kW (6 ch).",
        'url'  => '/navigation/permis-de-conduire/',
        'link' => 'Tout savoir sur le permis',
    ],
    [
        'q'    => 'Quelle est la longueur du Canal du Midi et combien compte-t-il d’écluses ?',
        'a'    => "Le Canal du Midi relie Toulouse à l'étang de Thau sur 240 km et compte 63 écluses. Construit par Pierre-Paul Riquet et inauguré en 1681, il est inscrit au patrimoine mondial de l'UNESCO depuis 1996.",
        'url'  => '/calcul-de-distance-canal-du-midi/',
        'link' => 'Calculer une distance entre deux écluses',
    ],
    [
        'q'    => 'Faut-il payer pour naviguer sur le Canal du Midi ?',
        'a'    => "Oui : la navigation de plaisance est soumise à un péage perçu par Voies Navigables de France (VNF). Son montant dépend de la surface du bateau et de la durée, avec plusieurs forfaits (journée, vacances de 16 jours, loisirs de 30 jours…).",
        'url'  => '/foire-aux-question-faq-canal-du-midi/',
        'link' => 'Voir toutes les questions fréquentes',
    ],
    [
        'q'    => 'Peut-on longer le Canal du Midi à vélo ?',
        'a'    => "Oui. Les voies vertes aménagées sur les anciens chemins de halage se parcourent sans autorisation, par exemple Toulouse – Port-Lauragais (49 km) ou Castelnaudary – Carcassonne (40 km). Pour connaître l'état de la piste, renseignez-vous auprès de VNF.",
        'url'  => '/voie-verte-et-veloroute/',
        'link' => 'Voies vertes et véloroutes',
    ],
    [
        'q'    => 'Quels ouvrages ne pas manquer le long du canal ?',
        'a'    => "Le Grand Bassin et l'écluse quadruple de Saint-Roch à Castelnaudary, le pont-canal de Répudre (1676, le plus ancien, construit par Riquet), les 7 écluses de Fonseranes à Béziers (1697) et le pont-canal de l'Orb (1858).",
        'url'  => '/le-canal/ouvrages/',
        'link' => 'Les ouvrages du canal',
    ],
    [
        'q'    => 'Comment obtenir le plan du Canal du Midi 2026 ?',
        'a'    => "Téléchargez gratuitement le plan officiel 2026 en PDF, recevez-le par e-mail depuis cette page, ou commandez la version papier par courrier.",
        'url'  => '/recevoir-le-plan-du-canal-du-midi-2/',
        'link' => 'Recevoir le plan par courrier',
    ],
];

// Etapas con su punto kilométrico (PK de la calculadora de distancias del sitio).
// 'search' = texto que /explorer/ geocodifica (search_location); vacío = sin enlace.
const CANAL_HOME_ETAPES = [
    ['name' => 'Toulouse',        'km' => 0,   'note' => 'Port de l’Embouchure',        'search' => 'Toulouse'],
    ['name' => 'Castelnaudary',   'km' => 66,  'note' => 'Grand Bassin, écluse Saint-Roch', 'search' => 'Castelnaudary'],
    ['name' => 'Carcassonne',     'km' => 105, 'note' => 'Cité médiévale',              'search' => 'Carcassonne'],
    ['name' => 'Trèbes',          'km' => 118, 'note' => 'Écluse triple',               'search' => 'Trèbes'],
    ['name' => 'Homps',           'km' => 146, 'note' => 'Port du Minervois',           'search' => 'Homps'],
    ['name' => 'Béziers',         'km' => 208, 'note' => 'Écluses de Fonseranes',       'search' => 'Béziers'],
    ['name' => 'Agde',            'km' => 231, 'note' => 'Écluse ronde',                'search' => 'Agde'],
    ['name' => 'Étang de Thau',   'km' => 240, 'note' => 'Les Onglous, vers Sète',      'search' => 'Sète'],
];
