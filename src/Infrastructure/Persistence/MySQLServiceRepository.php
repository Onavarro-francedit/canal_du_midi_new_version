<?php
namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\ServiceRepository;
use App\Domain\Models\Service;
use App\Config\Database;
use PDO;

class MySQLServiceRepository implements ServiceRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findById(int $id, string $lang): ?Service {
        return $this->findByIdFromPimcore($id);
    }

    // No olvides actualizar también el findAll si quieres mostrar iconos en la Home
    public function findAll(string $lang, bool $withDetails = false): array {
        if (empty($lang)) $lang = 'fr';
        return $this->searchPimcore('', '', []);
    }

    public function saveReview(array $data): bool {
        $sql = "INSERT INTO service_reviews (service_id, booking_id, customer_name, rating, comment, is_approved) 
                VALUES (:service_id, :booking_id, :customer_name, :rating, :comment, 1)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'service_id'    => $data['service_id'],
            'booking_id'    => $data['booking_id'],
            'customer_name' => $data['customer_name'],
            'rating'        => $data['rating'],
            'comment'       => $data['comment']
        ]);
    }

    public function getNearbyPOIs(float $lat, float $lng, string $lang, float $radiusKm = 10): array {
        // Consulta SQL que usa una aproximación de distancia para no sobrecargar
        $sql = "SELECT p.*, t.name, p.image_url
                FROM points_of_interest p
                JOIN poi_translations t ON p.id = t.poi_id AND t.lang_code = :lang
                WHERE (6371 * acos(cos(radians(:lat)) * cos(radians(p.lat)) * cos(radians(p.lng) - radians(:lng)) + sin(radians(:lat)) * sin(radians(p.lat)))) < :radius
                ORDER BY (abs(p.lat - :lat2) + abs(p.lng - :lng2)) ASC
                LIMIT 4";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'lat' => $lat, 'lng' => $lng, 'lang' => $lang, 
            'radius' => $radiusKm, 'lat2' => $lat, 'lng2' => $lng
        ]);

        $pois = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $poi = new \App\Domain\Models\POI($row['id'], $row['type'], $row['name'], $row['lat'], $row['lng'], $row['image_url']);
            $poi->calculateDistanceFrom($lat, $lng);
            $pois[] = $poi;
        }
        return $pois;
    }

    public function findPoiById(int $id, string $lang): ?\App\Domain\Models\POI {
        $sql = "SELECT p.*, t.name, t.description 
                FROM points_of_interest p
                JOIN poi_translations t ON p.id = t.poi_id AND t.lang_code = :lang
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'lang' => $lang]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return null;

        $poi = new \App\Domain\Models\POI($row['id'], $row['type'], $row['name'], $row['lat'], $row['lng'], $row['image_url']);
        $poi->description = $row['description'];  // Asegúrate de declarar public ?string $description en el Modelo POI
        return $poi;
    }

    public function getServicesNearPoi(float $lat, float $lng, string $lang, float $radiusKm = 10): array {
        $sql = "SELECT oo_id, nom, geopositionnement__latitude, geopositionnement__longitude,
                       hotel, chambre_hote, restaurant, hotellerie_plein_air, gites_ruraux,
                       location_bateaux, promenade_bateau, croisiere_chambres, croisiere_luxe,
                       location_velos, voyage_velo, bar_salon, musee, epicerie, alimentation,
                       produits_regions, caviste, domaine, vente_de_vins, taxi, otsi, lieux_infos,
                       hebergement, residence_tourisme, location_saisonniere, excursions, loisirs,
                       parcs_loisirs, location_bateaux_semaine, location_bateaux_gites
                FROM object_query_60
                WHERE (6371 * acos(cos(radians(:lat)) * cos(radians(geopositionnement__latitude)) * cos(radians(geopositionnement__longitude) - radians(:lng))
                       + sin(radians(:lat)) * sin(radians(geopositionnement__latitude)))) < :radius
                LIMIT 3";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lat' => $lat, 'lng' => $lng, 'radius' => $radiusKm]);

        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $results[] = new \App\Domain\Models\Service(
                (int)$row['oo_id'],
                $this->detectPimcoreType($row),
                0,
                '',
                ['title' => $row['nom'] ?? 'Sans titre'],
                [],
                [],
                [],
                [],
                (float)($row['geopositionnement__latitude'] ?? 0),
                (float)($row['geopositionnement__longitude'] ?? 0)
            );
        }
        return $results;
    }

    public function searchServices(string $query, string $lang): array {
        return $this->searchPimcore($query, '', []);
    }

    public function search(string $query, string $city, string $type, string $lang): array {
        $types = $type !== '' ? [$type] : [];
        return $this->searchPimcore($query, $city, $types);
    }


    public function getAllCategories(string $lang): array {
        return $this->getPimcoreCategories();
    }

    private function getAmenitiesForService(int $serviceId): array {
        $sql_amenities = "SELECT a.icon_name, a.slug 
                        FROM amenities a
                        JOIN service_amenities sa ON a.id = sa.amenity_id
                        WHERE sa.service_id = :id";
        $stmt_am = $this->db->prepare($sql_amenities);
        $stmt_am->execute(['id' => $serviceId]);
        return $stmt_am->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveFeatures(string $lang): array {
        $sql = "SELECT f.icon_class, t.title, t.content 
                FROM site_features f
                JOIN feature_translations t ON f.id = t.feature_id
                WHERE t.lang_code = :lang AND f.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLatestArticles(string $lang, int $limit = 3): array {
        $sql = "SELECT a.*, t.title 
                FROM articles a
                JOIN article_translations t ON a.id = t.article_id
                WHERE t.lang_code = :lang AND a.is_active = 1
                ORDER BY a.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lang', $lang);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCategoriesWithCount(string $lang): array {
        return $this->getPimcoreCategories();
    }

    public function findByIdFromPimcore(int $id): ?Service {
        $sql = "SELECT * FROM object_query_60 WHERE oo_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $type = $this->detectPimcoreType($row);

        $categories = [
            'Hébergement' => (bool)$row['hebergement'],
            'Résidence de tourisme' => (bool)$row['residence_tourisme'],
            'Hôtellerie plein air' => (bool)$row['hotellerie_plein_air'],
            'Chambre d\'hôtes' => (bool)$row['chambre_hote'],
            'Gîtes ruraux' => (bool)$row['gites_ruraux'],
            'Location bateaux (gîtes)' => (bool)$row['location_bateaux_gites'],
            'Hôtel' => (bool)$row['hotel'],
            'Location saisonnière' => (bool)$row['location_saisonniere'],
            'Bar / Salon de thé' => (bool)$row['bar_salon'],
            'Restaurant' => (bool)$row['restaurant'],
            'Table d\'hôtes' => (bool)$row['table_hote'],
            'Bateau restaurant' => (bool)$row['bateau_restaurant'],
            'Snack' => (bool)$row['snack'],
            'Brasserie' => (bool)$row['brasserie'],
            'Alimentation' => (bool)$row['alimentation'],
            'Charcuterie / Traiteur' => (bool)$row['charcuterie_traiteur'],
            'Boulangerie' => (bool)$row['boulangerie'],
            'Produits régionaux' => (bool)$row['produits_regions'],
            'Poissonnerie' => (bool)$row['poissonnerie'],
            'Épicerie' => (bool)$row['epicerie'],
            'Vente de vins' => (bool)$row['vente_de_vins'],
            'Caviste' => (bool)$row['caviste'],
            'Domaine' => (bool)$row['domaine'],
            'Loisirs' => (bool)$row['loisirs'],
            'Croisière avec chambres' => (bool)$row['croisiere_chambres'],
            'Promenade en bateau' => (bool)$row['promenade_bateau'],
            'Croisière de luxe' => (bool)$row['croisiere_luxe'],
            'Location bateaux (semaine)' => (bool)$row['location_bateaux_semaine'],
            'Location bateaux' => (bool)$row['location_bateaux'],
            'Location voiture' => (bool)$row['location_voiture'],
            'Location kayak' => (bool)$row['location_kayak'],
            'Location vélos' => (bool)$row['location_velos'],
            'Voyage à vélo' => (bool)$row['voyage_velo'],
            'Excursions' => (bool)$row['excursions'],
            'Musée' => (bool)$row['musee'],
            'Parc de loisirs' => (bool)$row['parcs_loisirs'],
            'Lieux à voir' => (bool)$row['lieux_voir'],
            'Artisanat' => (bool)$row['artisanat'],
            'Commerce' => (bool)$row['commerce'],
            'Laverie' => (bool)$row['laverie'],
            'Taxi' => (bool)$row['taxi'],
            'Transport bagages' => (bool)$row['transport_bagages'],
            'Lieux d\'information' => (bool)$row['lieux_infos'],
            'Mairie' => (bool)$row['mairie'],
            'Office de tourisme' => (bool)$row['otsi'],
            'Librairie' => (bool)$row['librairie'],
            'Distributeur billets' => (bool)$row['distr_billet'],
            'Borne WiFi' => (bool)$row['borne_wifi'],
            'Réparation vélo' => (bool)$row['repar_velo'],
            'Garage vélos' => (bool)$row['garage_velos'],
            'Divers' => (bool)$row['divers'],
        ];

        $equipments = [
            'Animaux acceptés' => (bool)$row['animaux'],
            'Piscine' => (bool)$row['piscine'],
            'Parking' => (bool)$row['parking'],
            'Accessible PMR' => (bool)$row['accessible'],
        ];

        $description = html_entity_decode($row['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = strip_tags($description);
        $actualite = html_entity_decode($row['actualite'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $actualite = strip_tags($actualite);

        $videos = array_filter([
            $row['Video1'] ?? null,
            $row['Video2'] ?? null,
            $row['Video3'] ?? null,
        ]);

        $media = $this->getPimcoreMediaById((int)$row['oo_id']);
        $gallery = [];
        if (!empty($media['images'])) {
            $gallery = array_values(array_filter(array_map('trim', explode(',', (string)$media['images']))));
            $gallery = array_map(fn($u) => $this->normalizeMediaUrl($u), $gallery);
        }

        $heroImage = $this->normalizeMediaUrl((string)($media['presentation'] ?? ''));

        // Fallback: if DB media is missing, try loading images from public/clients_images/{pimcore_id}
        if ($heroImage === '' && empty($gallery)) {
            $diskMedia = $this->getPimcoreMediaFromDisk((int)$row['oo_id']);
            $heroImage = $diskMedia['presentation'];
            $gallery = $diskMedia['gallery'];
        }

        if ($heroImage === '' && !empty($gallery[0])) {
            $heroImage = $gallery[0];
        }

        return new Service(
            id: (int)$row['oo_id'],
            type: $type,
            price: 0,
            imageUrl: $heroImage,
            translations: [
                'title' => $row['nom'] ?? 'Sans titre',
                'description' => trim($description),
                'tag' => trim($actualite),
            ],
            contact: [
                'phone' => trim($row['telephone'] ?? ''),
                'phone2' => trim($row['telephone2'] ?? ''),
                'mobile' => trim($row['mobile'] ?? ''),
                'fax' => trim($row['fax'] ?? ''),
                'email' => trim($row['email'] ?? ''),
                'email2' => trim($row['email2'] ?? ''),
                'address' => trim($row['adresse'] ?? ''),
                'cp' => trim($row['cp'] ?? ''),
                'ville' => trim($row['ville'] ?? ''),
                'website' => trim($row['web'] ?? ''),
                'website2' => trim($row['web2'] ?? ''),
            ],
            amenities: [],
            gallery: $gallery,
            features: [],
            lat: (float)($row['geopositionnement__latitude'] ?? 0),
            lng: (float)($row['geopositionnement__longitude'] ?? 0),
            responsable: trim($row['responsable'] ?? ''),
            raison: trim($row['raison'] ?? ''),
            label: trim($row['label'] ?? ''),
            zone: trim($row['zone'] ?? ''),
            actualite: trim($actualite),
            categories: $categories,
            equipments: $equipments,
            socials: array_filter([
                'facebook' => $row['facebook'] ?? '',
                'instagram' => $row['instagram'] ?? '',
            ]),
            videos: $videos,
        );
    }

    private function getPimcoreMediaById(int $pimcoreId): array {
        $sql = "SELECT presentation, images, logo_url FROM canal_du_midi_image WHERE pimcore_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $pimcoreId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['presentation' => '', 'images' => '', 'logo_url' => ''];
        }

        return [
            'presentation' => (string)($row['presentation'] ?? ''),
            'images' => (string)($row['images'] ?? ''),
            'logo_url' => (string)($row['logo_url'] ?? ''),
        ];
    }

    private function resolvePimcorePrimaryImage(int $pimcoreId, array $media): string {
        $presentation = $this->normalizeMediaUrl((string)($media['presentation'] ?? ''));
        if ($presentation !== '') {
            return $presentation;
        }

        $imagesCsv = (string)($media['images'] ?? '');
        if ($imagesCsv !== '') {
            $images = array_values(array_filter(array_map('trim', explode(',', $imagesCsv))));
            if (!empty($images[0])) {
                return $this->normalizeMediaUrl($images[0]);
            }
        }

        $logo = $this->normalizeMediaUrl((string)($media['logo_url'] ?? ''));
        if ($logo !== '') {
            return $logo;
        }

        $diskMedia = $this->getPimcoreMediaFromDisk($pimcoreId);
        return $diskMedia['presentation'] ?? '';
    }

    private function normalizeMediaUrl(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (defined('BASE_URL')) {
            return rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
        }

        return $url;
    }

    private function getPimcoreMediaFromDisk(int $pimcoreId): array {
        $clientDir = dirname(__DIR__, 3) . '/public/clients_images/' . $pimcoreId;
        if (!is_dir($clientDir)) {
            return ['presentation' => '', 'gallery' => []];
        }

        $files = glob($clientDir . '/img*.*') ?: [];
        if (empty($files)) {
            return ['presentation' => '', 'gallery' => []];
        }

        natsort($files);
        $gallery = [];
        foreach ($files as $filePath) {
            $baseName = basename($filePath);
            $gallery[] = $this->normalizeMediaUrl('/public/clients_images/' . $pimcoreId . '/' . $baseName);
        }

        return [
            'presentation' => $gallery[0] ?? '',
            'gallery' => $gallery,
        ];
    }

    private function detectPimcoreType(array $row): string {
        if (!empty($row['hotel'])) return 'Hôtel';
        if (!empty($row['chambre_hote'])) return 'Chambre d\'hôtes';
        if (!empty($row['restaurant'])) return 'Restaurant';
        if (!empty($row['hotellerie_plein_air'])) return 'Camping';
        if (!empty($row['gites_ruraux'])) return 'Gîte';
        if (!empty($row['location_bateaux']) || !empty($row['location_bateaux_semaine']) || !empty($row['location_bateaux_gites'])) return 'Location bateaux';
        if (!empty($row['promenade_bateau']) || !empty($row['croisiere_chambres']) || !empty($row['croisiere_luxe'])) return 'Croisière';
        if (!empty($row['location_velos']) || !empty($row['voyage_velo'])) return 'Location vélos';
        if (!empty($row['musee'])) return 'Musée';
        if (!empty($row['bar_salon'])) return 'Bar';
        if (!empty($row['epicerie']) || !empty($row['alimentation']) || !empty($row['produits_regions'])) return 'Commerce';
        if (!empty($row['caviste']) || !empty($row['domaine']) || !empty($row['vente_de_vins'])) return 'Vins';
        if (!empty($row['taxi'])) return 'Taxi';
        if (!empty($row['otsi']) || !empty($row['lieux_infos'])) return 'Information';
        if (!empty($row['hebergement']) || !empty($row['residence_tourisme']) || !empty($row['location_saisonniere'])) return 'Hébergement';
        if (!empty($row['excursions']) || !empty($row['loisirs']) || !empty($row['parcs_loisirs'])) return 'Loisirs';
        return 'Établissement';
    }

    private function getPimcoreTypeConditions(): array {
        return [
            'hebergement' => 'hebergement = 1',
            'residence_tourisme' => 'residence_tourisme = 1',
            'hotellerie_plein_air' => 'hotellerie_plein_air = 1',
            'chambre_hote' => 'chambre_hote = 1',
            'gites_ruraux' => 'gites_ruraux = 1',
            'location_bateaux_gites' => 'location_bateaux_gites = 1',
            'hotel' => 'hotel = 1',
            'location_saisonniere' => 'location_saisonniere = 1',
            'bar_salon' => 'bar_salon = 1',
            'restaurant' => 'restaurant = 1',
            'table_hote' => 'table_hote = 1',
            'bateau_restaurant' => 'bateau_restaurant = 1',
            'snack' => 'snack = 1',
            'brasserie' => 'brasserie = 1',
            'alimentation' => 'alimentation = 1',
            'charcuterie_traiteur' => 'charcuterie_traiteur = 1',
            'boulangerie' => 'boulangerie = 1',
            'produits_regions' => 'produits_regions = 1',
            'poissonnerie' => 'poissonnerie = 1',
            'epicerie' => 'epicerie = 1',
            'vente_de_vins' => 'vente_de_vins = 1',
            'caviste' => 'caviste = 1',
            'domaine' => 'domaine = 1',
            'loisirs' => 'loisirs = 1',
            'croisiere_chambres' => 'croisiere_chambres = 1',
            'promenade_bateau' => 'promenade_bateau = 1',
            'croisiere_luxe' => 'croisiere_luxe = 1',
            'location_bateaux_semaine' => 'location_bateaux_semaine = 1',
            'location_bateaux' => 'location_bateaux = 1',
            'location_voiture' => 'location_voiture = 1',
            'location_kayak' => 'location_kayak = 1',
            'location_velos' => 'location_velos = 1',
            'voyage_velo' => 'voyage_velo = 1',
            'excursions' => 'excursions = 1',
            'musee' => 'musee = 1',
            'parcs_loisirs' => 'parcs_loisirs = 1',
            'lieux_voir' => 'lieux_voir = 1',
            'artisanat' => 'artisanat = 1',
            'commerce' => 'commerce = 1',
            'laverie' => 'laverie = 1',
            'taxi' => 'taxi = 1',
            'transport_bagages' => 'transport_bagages = 1',
            'lieux_infos' => 'lieux_infos = 1',
            'mairie' => 'mairie = 1',
            'otsi' => 'otsi = 1',
            'librairie' => 'librairie = 1',
            'distr_billet' => 'distr_billet = 1',
            'borne_wifi' => 'borne_wifi = 1',
        ];
    }

    public function searchPimcore(string $query = '', string $city = '', array $types = []): array {
        $sql = "SELECT oo_id, nom, ville, adresse, cp, description, telephone, email, web, label, zone,
                       geopositionnement__latitude, geopositionnement__longitude,
                       hotel, chambre_hote, restaurant, hotellerie_plein_air, gites_ruraux,
                       location_bateaux, promenade_bateau, croisiere_chambres, croisiere_luxe,
                       location_velos, voyage_velo, bar_salon, musee, epicerie, alimentation,
                       produits_regions, caviste, domaine, vente_de_vins, taxi, otsi, lieux_infos,
                       hebergement, residence_tourisme, location_saisonniere, excursions, loisirs,
                       parcs_loisirs, location_bateaux_semaine, location_bateaux_gites, location_kayak,
                       location_voiture, snack, brasserie, table_hote, bateau_restaurant,
                       animaux, piscine, parking, `accessible`
                FROM object_query_60 WHERE 1=1";

        $params = [];

        if (!empty($query)) {
            $sql .= " AND (nom LIKE :q OR description LIKE :q OR ville LIKE :q OR adresse LIKE :q)";
            $params['q'] = "%$query%";
        }
        if (!empty($city)) {
            $sql .= " AND ville LIKE :city";
            $params['city'] = "%$city%";
        }
        if (!empty($types)) {
            $typeMap = $this->getPimcoreTypeConditions();
            $conditions = [];
            foreach ($types as $t) {
                $t = trim((string)$t);
                if ($t !== '' && isset($typeMap[$t])) {
                    $conditions[] = '(' . $typeMap[$t] . ')';
                }
            }
            if (!empty($conditions)) {
                $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
            }
        }

        $sql .= " ORDER BY nom ASC LIMIT 200";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $detected = $this->detectPimcoreType($row);
            $desc = html_entity_decode($row['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $desc = strip_tags($desc);

            $media = $this->getPimcoreMediaById((int)$row['oo_id']);
            $heroImage = $this->resolvePimcorePrimaryImage((int)$row['oo_id'], $media);

            $results[] = new Service(
                id: (int)$row['oo_id'],
                type: $detected,
                price: 0,
                imageUrl: $heroImage,
                translations: [
                    'title' => $row['nom'] ?? 'Sans titre',
                    'description' => trim($desc),
                    'tag' => '',
                ],
                contact: [
                    'phone' => trim($row['telephone'] ?? ''),
                    'email' => trim($row['email'] ?? ''),
                    'website' => trim($row['web'] ?? ''),
                    'address' => trim($row['adresse'] ?? ''),
                    'cp' => trim($row['cp'] ?? ''),
                    'ville' => trim($row['ville'] ?? ''),
                ],
                lat: (float)($row['geopositionnement__latitude'] ?? 0),
                lng: (float)($row['geopositionnement__longitude'] ?? 0),
                label: trim($row['label'] ?? ''),
                zone: trim($row['zone'] ?? ''),
            );
        }
        return $results;
    }

    public function getPimcoreCategories(): array {
        // 1. Load service definitions from service_catalog
        $catalogStmt = $this->db->query(
            "SELECT sql_service_name, service_title, service_icon, service_img
             FROM service_catalog
             WHERE is_active = 1
             ORDER BY sort_order ASC"
        );
        $catalog = $catalogStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($catalog)) {
            return [];
        }

        // 2. Build UNION ALL counts — one row per service using the column name directly
        $countParts = [];
        foreach ($catalog as $index => $entry) {
            $col     = preg_replace('/[^a-z0-9_]/', '', (string)$entry['sql_service_name']); // safe identifier
            $safeSlug = str_replace("'", "''", (string)$entry['sql_service_name']);
            $countParts[] = "SELECT {$index} AS sort_order, '{$safeSlug}' AS slug,"
                . " SUM(CASE WHEN `{$col}` = 1 THEN 1 ELSE 0 END) AS offers_count"
                . " FROM object_query_60";
        }
        $countsSql = implode(" UNION ALL ", $countParts);

        $countsStmt = $this->db->query("SELECT slug, offers_count FROM ({$countsSql}) cnt ORDER BY sort_order ASC");
        $counts = [];
        while ($row = $countsStmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[(string)$row['slug']] = (int)$row['offers_count'];
        }

        // 3. Merge catalog metadata with counts
        $results = [];
        foreach ($catalog as $entry) {
            $slug = (string)$entry['sql_service_name'];
            $results[] = [
                'slug'         => $slug,
                'name'         => (string)($entry['service_title'] ?? ucfirst($slug)),
                'icon_class'   => (string)($entry['service_icon'] ?? 'bi-bookmark'),
                'offers_count' => $counts[$slug] ?? 0,
                'image_url'    => (string)($entry['service_img'] ?? ''),
            ];
        }

        return $results;
    }

    public function getPimcoreCities(): array {
        $sql = "SELECT DISTINCT ville FROM object_query_60 WHERE ville IS NOT NULL AND ville != '' ORDER BY ville ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}