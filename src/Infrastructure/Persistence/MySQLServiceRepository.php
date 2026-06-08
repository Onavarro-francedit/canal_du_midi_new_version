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

    public function findAll(string $lang, bool $withDetails = false): array {
        if (empty($lang)) {
            $lang = 'fr';
        }

        return $this->searchPimcore('', '', []);
    }

    public function saveReview(array $data): bool {
        $sql = "INSERT INTO service_reviews (service_id, booking_id, customer_name, rating, comment, is_approved) 
                VALUES (:service_id, :booking_id, :customer_name, :rating, :comment, 1)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'service_id' => $data['service_id'],
            'booking_id' => $data['booking_id'],
            'customer_name' => $data['customer_name'],
            'rating' => $data['rating'],
            'comment' => $data['comment']
        ]);
    }

    public function getNearbyPOIs(float $lat, float $lng, string $lang, float $radiusKm = 10): array {
        $sql = "SELECT p.*, t.name, p.image_url
                FROM points_of_interest p
                JOIN poi_translations t ON p.id = t.poi_id AND t.lang_code = :lang
                WHERE (6371 * acos(cos(radians(:lat)) * cos(radians(p.lat)) * cos(radians(p.lng) - radians(:lng)) + sin(radians(:lat)) * sin(radians(p.lat)))) < :radius
                ORDER BY (abs(p.lat - :lat2) + abs(p.lng - :lng2)) ASC
                LIMIT 4";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'lat' => $lat,
            'lng' => $lng,
            'lang' => $lang,
            'radius' => $radiusKm,
            'lat2' => $lat,
            'lng2' => $lng,
        ]);

        $pois = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
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
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $poi = new \App\Domain\Models\POI($row['id'], $row['type'], $row['name'], $row['lat'], $row['lng'], $row['image_url']);
        $poi->description = $row['description'];
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
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
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
        $sqlAmenities = "SELECT a.icon_name, a.slug 
                        FROM amenities a
                        JOIN service_amenities sa ON a.id = sa.amenity_id
                        WHERE sa.service_id = :id";
        $stmt = $this->db->prepare($sqlAmenities);
        $stmt->execute(['id' => $serviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveFeatures(string $lang): array {
        $sql = "SELECT f.icon_class, t.title, t.content 
                FROM site_features f
                JOIN feature_translations t ON f.id = t.feature_id
                WHERE t.lang_code = :lang AND f.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestArticles(string $lang, int $limit = 3): array {
        $sql = "SELECT a.*, t.title 
                FROM articles a
                JOIN article_translations t ON a.id = t.article_id
                WHERE t.lang_code = :lang AND a.is_active = 1
                ORDER BY a.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lang', $lang);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoriesWithCount(string $lang): array {
        return $this->getPimcoreCategories();
    }

    public function findByIdFromPimcore(int $id): ?Service {
        $sql = "SELECT * FROM object_query_60 WHERE oo_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

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

    private function resolvePimcorePrimaryImage(string $cover, string $imagesCsv, int $pimcoreId): string {
        $coverImage = $this->normalizeMediaUrl($this->normalizeMetaImage($cover));
        if ($coverImage !== '') {
            return $coverImage;
        }

        $images = $this->normalizeMetaImageList($imagesCsv);
        if ($images !== '') {
            $imageList = array_values(array_filter(array_map('trim', explode(',', $images))));
            if (!empty($imageList[0])) {
                return $this->normalizeMediaUrl($imageList[0]);
            }
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
        if (!empty($row['hotel'])) {
            return 'Hôtel';
        }
        if (!empty($row['chambre_hote'])) {
            return 'Chambre d\'hôtes';
        }
        if (!empty($row['restaurant'])) {
            return 'Restaurant';
        }
        if (!empty($row['hotellerie_plein_air'])) {
            return 'Camping';
        }
        if (!empty($row['gites_ruraux'])) {
            return 'Gîte';
        }
        if (!empty($row['location_bateaux']) || !empty($row['location_bateaux_semaine']) || !empty($row['location_bateaux_gites'])) {
            return 'Location bateaux';
        }
        if (!empty($row['promenade_bateau']) || !empty($row['croisiere_chambres']) || !empty($row['croisiere_luxe'])) {
            return 'Croisière';
        }
        if (!empty($row['location_velos']) || !empty($row['voyage_velo'])) {
            return 'Location vélos';
        }
        if (!empty($row['musee'])) {
            return 'Musée';
        }
        if (!empty($row['bar_salon'])) {
            return 'Bar';
        }
        if (!empty($row['epicerie']) || !empty($row['alimentation']) || !empty($row['produits_regions'])) {
            return 'Commerce';
        }
        if (!empty($row['caviste']) || !empty($row['domaine']) || !empty($row['vente_de_vins'])) {
            return 'Vins';
        }
        if (!empty($row['taxi'])) {
            return 'Taxi';
        }
        if (!empty($row['otsi']) || !empty($row['lieux_infos'])) {
            return 'Information';
        }
        if (!empty($row['hebergement']) || !empty($row['residence_tourisme']) || !empty($row['location_saisonniere'])) {
            return 'Hébergement';
        }
        if (!empty($row['excursions']) || !empty($row['loisirs']) || !empty($row['parcs_loisirs'])) {
            return 'Loisirs';
        }

        return 'Établissement';
    }

    private function getPimcoreTypeConditions(string $tableAlias = ''): array {
        $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';

        return [
            'hebergement' => $prefix . 'hebergement = 1',
            'residence_tourisme' => $prefix . 'residence_tourisme = 1',
            'hotellerie_plein_air' => $prefix . 'hotellerie_plein_air = 1',
            'chambre_hote' => $prefix . 'chambre_hote = 1',
            'gites_ruraux' => $prefix . 'gites_ruraux = 1',
            'location_bateaux_gites' => $prefix . 'location_bateaux_gites = 1',
            'hotel' => $prefix . 'hotel = 1',
            'location_saisonniere' => $prefix . 'location_saisonniere = 1',
            'bar_salon' => $prefix . 'bar_salon = 1',
            'restaurant' => $prefix . 'restaurant = 1',
            'table_hote' => $prefix . 'table_hote = 1',
            'bateau_restaurant' => $prefix . 'bateau_restaurant = 1',
            'snack' => $prefix . 'snack = 1',
            'brasserie' => $prefix . 'brasserie = 1',
            'alimentation' => $prefix . 'alimentation = 1',
            'charcuterie_traiteur' => $prefix . 'charcuterie_traiteur = 1',
            'boulangerie' => $prefix . 'boulangerie = 1',
            'produits_regions' => $prefix . 'produits_regions = 1',
            'poissonnerie' => $prefix . 'poissonnerie = 1',
            'epicerie' => $prefix . 'epicerie = 1',
            'vente_de_vins' => $prefix . 'vente_de_vins = 1',
            'caviste' => $prefix . 'caviste = 1',
            'domaine' => $prefix . 'domaine = 1',
            'loisirs' => $prefix . 'loisirs = 1',
            'croisiere_chambres' => $prefix . 'croisiere_chambres = 1',
            'promenade_bateau' => $prefix . 'promenade_bateau = 1',
            'croisiere_luxe' => $prefix . 'croisiere_luxe = 1',
            'location_bateaux_semaine' => $prefix . 'location_bateaux_semaine = 1',
            'location_bateaux' => $prefix . 'location_bateaux = 1',
            'location_voiture' => $prefix . 'location_voiture = 1',
            'location_kayak' => $prefix . 'location_kayak = 1',
            'location_velos' => $prefix . 'location_velos = 1',
            'voyage_velo' => $prefix . 'voyage_velo = 1',
            'excursions' => $prefix . 'excursions = 1',
            'musee' => $prefix . 'musee = 1',
            'parcs_loisirs' => $prefix . 'parcs_loisirs = 1',
            'lieux_voir' => $prefix . 'lieux_voir = 1',
            'artisanat' => $prefix . 'artisanat = 1',
            'commerce' => $prefix . 'commerce = 1',
            'laverie' => $prefix . 'laverie = 1',
            'taxi' => $prefix . 'taxi = 1',
            'transport_bagages' => $prefix . 'transport_bagages = 1',
            'lieux_infos' => $prefix . 'lieux_infos = 1',
            'mairie' => $prefix . 'mairie = 1',
            'otsi' => $prefix . 'otsi = 1',
            'librairie' => $prefix . 'librairie = 1',
            'distr_billet' => $prefix . 'distr_billet = 1',
            'borne_wifi' => $prefix . 'borne_wifi = 1',
        ];
    }

   

    public function searchPimcore(string $query = '', string $city = '', array $types = []): array
    {
        $sql = "SELECT
                    p.ID AS oo_id,
                    MAX(CASE WHEN pm.meta_key IN ('_job_title', '_nom_oi') THEN pm.meta_value END) AS nom,
                    MAX(CASE WHEN pm.meta_key = '_job_description' THEN pm.meta_value END) AS description,
                    MAX(CASE WHEN pm.meta_key IN ('_ligne-adresse-1-papier', '_job_location') THEN pm.meta_value END) AS adresse,
                    MAX(CASE WHEN pm.meta_key = '_ligne-adresse-2-papier' THEN pm.meta_value END) AS adresse2,
                    MAX(CASE WHEN pm.meta_key = '_code-postal' THEN pm.meta_value END) AS cp,
                    MAX(CASE WHEN pm.meta_key = '_job_phone' THEN pm.meta_value END) AS telephone,
                    MAX(CASE WHEN pm.meta_key = '_telephone-portable' THEN pm.meta_value END) AS mobile,
                    MAX(CASE WHEN pm.meta_key = '_job_email' THEN pm.meta_value END) AS email,
                    MAX(CASE WHEN pm.meta_key = '_job_website' THEN pm.meta_value END) AS web,
                    MAX(CASE WHEN pm.meta_key = '_facebook' THEN pm.meta_value END) AS facebook,
                    MAX(CASE WHEN pm.meta_key = '_job_cover' THEN pm.meta_value END) AS cover,
                    MAX(CASE WHEN pm.meta_key = '_job_gallery' THEN pm.meta_value END) AS images,
                    MAX(CASE WHEN pm.meta_key = '_case27_listing_type' THEN pm.meta_value END) AS listing_type,
                    MAX(CASE WHEN pm.meta_key = '_featured' THEN pm.meta_value END) AS featured,
                    MAX(CASE WHEN pm.meta_key = '_claimed' THEN pm.meta_value END) AS claimed,
                    MAX(CASE WHEN pm.meta_key = '_case27_review_count' THEN pm.meta_value END) AS review_count,
                    MAX(CASE WHEN pm.meta_key = '_job_expires' THEN pm.meta_value END) AS job_expires,
                    MAX(CASE WHEN pm.meta_key = '_job_video_url' THEN pm.meta_value END) AS job_video_url,
                    MAX(CASE WHEN pm.meta_key = '_video-facebook' THEN pm.meta_value END) AS video_facebook,
                    MAX(CASE WHEN pm.meta_key = '_lien-video-tiktok' THEN pm.meta_value END) AS lien_video_tiktok,
                    MAX(CASE WHEN pm.meta_key = '_format-papier' THEN pm.meta_value END) AS format_papier,
                    MAX(CASE WHEN pm.meta_key = '_texte_version_papier' THEN pm.meta_value END) AS texte_version_papier,
                    MAX(CASE WHEN pm.meta_key = '_photo-presentation-papier' THEN pm.meta_value END) AS photo_presentation_papier,
                    MAX(CASE WHEN pm.meta_key = '_noi_2023' THEN pm.meta_value END) AS noi_2023,
                    MAX(CASE WHEN pm.meta_key = '_noi_2024' THEN pm.meta_value END) AS noi_2024,
                    MAX(CASE WHEN pm.meta_key = '_noi_2025' THEN pm.meta_value END) AS noi_2025,
                    MAX(CASE WHEN pm.meta_key = '_noi_2026' THEN pm.meta_value END) AS noi_2026,
                    MAX(CASE WHEN pm.meta_key = 'geolocation_lat' THEN pm.meta_value END) AS geopositionnement__latitude,
                    MAX(CASE WHEN pm.meta_key = 'geolocation_long' THEN pm.meta_value END) AS geopositionnement__longitude,
                    MAX(CASE WHEN pm.meta_key = 'post_content' THEN pm.meta_value END) AS post_content_meta,
                    p.post_title  AS post_title,
                    p.post_content AS post_content,
                    p.post_excerpt AS post_excerpt,
                    p.post_name   AS slug
                FROM wp_posts p
                JOIN wp_postmeta pm ON p.ID = pm.post_id
                WHERE p.post_type   = 'job_listing'
                AND p.post_status = 'publish'
                GROUP BY p.ID";

        $params  = [];
        $filters = [];

        if (!empty($types)) {
            $typeConditions = [];
            foreach ($types as $type) {
                $type = trim((string) $type);
                if ($type !== '') {
                    $key                = 'type_' . count($params);
                    $typeConditions[]   = "listing_type LIKE :$key";
                    $params[$key]       = '%' . $this->escapeLike($type) . '%';
                }
            }
            if (!empty($typeConditions)) {
                $filters[] = '(' . implode(' OR ', $typeConditions) . ')';
            }
        }

        if (!empty($query)) {
            $filters[]    = '(nom LIKE :q OR description LIKE :q OR adresse LIKE :q OR cp LIKE :q
                            OR telephone LIKE :q OR mobile LIKE :q OR email LIKE :q OR web LIKE :q
                            OR facebook LIKE :q OR post_title LIKE :q OR post_content LIKE :q
                            OR post_excerpt LIKE :q)';
            $params['q']  = '%' . $this->escapeLike($query) . '%';
        }

        if (!empty($city)) {
            $filters[]       = '(adresse LIKE :city OR post_title LIKE :city OR post_content LIKE :city)';
            $params['city']  = '%' . $this->escapeLike($city) . '%';
        }

        if (!empty($filters)) {
            $sql .= ' HAVING ' . implode(' AND ', $filters);
        }

        $sql .= ' ORDER BY nom ASC LIMIT 200';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        // ── Categorías en una sola query (evita el problema N+1) ─────────────
        // Recogemos todos los IDs de los posts encontrados y hacemos UNA sola
        // consulta que trae los términos de todos ellos de golpe.
        $postIds     = array_column($rows, 'oo_id');
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));

        $catSql = "SELECT
                    tr.object_id AS post_id,
                    t.term_id    AS id,
                    t.name       AS name,
                    t.slug       AS slug,
                    tt.taxonomy  AS taxonomy
                FROM wp_term_relationships  tr
                JOIN wp_term_taxonomy       tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN wp_terms               t  ON tt.term_id          = t.term_id
                WHERE tr.object_id IN ($placeholders)
                ORDER BY tr.object_id, tt.taxonomy, t.name";

        $catStmt = $this->db->prepare($catSql);
        $catStmt->execute($postIds);

        // Indexamos los términos por post_id para recuperarlos en O(1) al
        // iterar los resultados: ['post_id' => ['taxonomy' => [términos…]]]
        $termsByPost = [];
        foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $term) {
            $pid = (int) $term['post_id'];
            $tax = $term['taxonomy'];
            $termsByPost[$pid][$tax][] = [
                'id'   => (int) $term['id'],
                'name' => $term['name'],
                'slug' => $term['slug'],
            ];
        }

        // ── Mapeo de filas a objetos Service ─────────────────────────────────
        $results = [];

        foreach ($rows as $row) {
            $postId = (int) $row['oo_id'];

            // Categorías del post actual (taxonomía principal de MyListing).
            // Ajusta 'job_listing_category' al slug real de tu instalación.
            $allTerms   = $termsByPost[$postId] ?? [];
            $categories = $allTerms['job_listing_category'] ?? [];

            $detected        = $this->detectJobListingType($row['listing_type'] ?? '');
            $descriptionSource = $row['description'] ?? $row['post_content'] ?? $row['post_excerpt'] ?? '';
            $desc            = html_entity_decode((string) $descriptionSource, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $desc            = strip_tags($desc);

            $gallery       = [];
            $gallerySource = $this->normalizeMetaImageList((string) ($row['images'] ?? ''));
            if ($gallerySource !== '') {
                $gallery = array_values(array_filter(array_map('trim', explode(',', $gallerySource))));
                $gallery = array_map(fn($url) => $this->normalizeMediaUrl($url), $gallery);
            }

            $heroImage = $this->resolvePimcorePrimaryImage(
                (string) ($row['cover']  ?? ''),
                (string) ($row['images'] ?? ''),
                $postId
            );

            $cityValue    = trim((string) ($row['post_title'] ?? ''));
            $addressValue = trim((string) ($row['adresse']   ?? ''));
            $addressLine2 = trim((string) ($row['adresse2']  ?? ''));
            if ($addressLine2 !== '') {
                $addressValue = trim($addressValue . ' ' . $addressLine2);
            }

            $results[] = new Service(
                id:       $postId,
                type:     $detected,
                price:    0,
                imageUrl: $heroImage,
                translations: [
                    'title'       => $row['nom'] ?? $row['post_title'] ?? 'Sans titre',
                    'description' => trim($desc),
                    'tag'         => trim((string) ($row['listing_type'] ?? '')),
                ],
                contact: [
                    'phone'    => trim($row['telephone'] ?? ''),
                    'mobile'   => trim($row['mobile']    ?? ''),
                    'email'    => trim($row['email']     ?? ''),
                    'website'  => trim($row['web']       ?? ''),
                    'facebook' => trim($row['facebook']  ?? ''),
                    'address'  => $addressValue,
                    'cp'       => trim($row['cp']        ?? ''),
                    'ville'    => $cityValue,
                ],
                gallery:    $gallery,
                lat: (float) ($row['geopositionnement__latitude']  ?? 0),
                lng: (float) ($row['geopositionnement__longitude'] ?? 0),
                label: trim((string) ($row['label']        ?? $row['format_papier'] ?? '')),
                zone:  trim((string) ($row['listing_type'] ?? $row['noi_2026']     ?? $row['noi_2025'] ?? '')),
                slug:  trim((string) ($row['slug']         ?? '')),

                // ── Categorías obtenidas de wp_terms ─────────────────────────
                // $categories  → lista plana de la taxonomía principal
                // $allTerms    → todas las taxonomías del post si necesitas más
                categories: $categories,
            );
        }

        return $results;
    }

    

    public function findBySlug(string $slug): ?Service
    {
        $normalizedSlug = trim(rawurldecode($slug));

        if ($normalizedSlug === '') {
            return null;
        }

        // ── Query principal: post + postmeta ──────────────────────────────────
        $sql = "SELECT
                    p.ID AS oo_id,
                    MAX(CASE WHEN pm.meta_key IN ('_job_title', '_nom_oi') THEN pm.meta_value END) AS nom,
                    MAX(CASE WHEN pm.meta_key = '_job_description' THEN pm.meta_value END) AS description,
                    MAX(CASE WHEN pm.meta_key IN ('_ligne-adresse-1-papier', '_job_location') THEN pm.meta_value END) AS adresse,
                    MAX(CASE WHEN pm.meta_key = '_ligne-adresse-2-papier' THEN pm.meta_value END) AS adresse2,
                    MAX(CASE WHEN pm.meta_key = '_code-postal' THEN pm.meta_value END) AS cp,
                    MAX(CASE WHEN pm.meta_key = '_job_phone' THEN pm.meta_value END) AS telephone,
                    MAX(CASE WHEN pm.meta_key = '_telephone-portable' THEN pm.meta_value END) AS mobile,
                    MAX(CASE WHEN pm.meta_key = '_job_email' THEN pm.meta_value END) AS email,
                    MAX(CASE WHEN pm.meta_key = '_job_website' THEN pm.meta_value END) AS web,
                    MAX(CASE WHEN pm.meta_key = '_facebook' THEN pm.meta_value END) AS facebook,
                    MAX(CASE WHEN pm.meta_key = '_job_cover' THEN pm.meta_value END) AS cover,
                    MAX(CASE WHEN pm.meta_key = '_job_gallery' THEN pm.meta_value END) AS images,
                    MAX(CASE WHEN pm.meta_key = '_case27_listing_type' THEN pm.meta_value END) AS listing_type,
                    MAX(CASE WHEN pm.meta_key = '_featured' THEN pm.meta_value END) AS featured,
                    MAX(CASE WHEN pm.meta_key = '_claimed' THEN pm.meta_value END) AS claimed,
                    MAX(CASE WHEN pm.meta_key = '_case27_review_count' THEN pm.meta_value END) AS review_count,
                    MAX(CASE WHEN pm.meta_key = '_job_expires' THEN pm.meta_value END) AS job_expires,
                    MAX(CASE WHEN pm.meta_key = '_job_video_url' THEN pm.meta_value END) AS job_video_url,
                    MAX(CASE WHEN pm.meta_key = '_video-facebook' THEN pm.meta_value END) AS video_facebook,
                    MAX(CASE WHEN pm.meta_key = '_lien-video-tiktok' THEN pm.meta_value END) AS lien_video_tiktok,
                    MAX(CASE WHEN pm.meta_key = '_format-papier' THEN pm.meta_value END) AS format_papier,
                    MAX(CASE WHEN pm.meta_key = '_texte_version_papier' THEN pm.meta_value END) AS texte_version_papier,
                    MAX(CASE WHEN pm.meta_key = '_photo-presentation-papier' THEN pm.meta_value END) AS photo_presentation_papier,
                    MAX(CASE WHEN pm.meta_key = '_noi_2023' THEN pm.meta_value END) AS noi_2023,
                    MAX(CASE WHEN pm.meta_key = '_noi_2024' THEN pm.meta_value END) AS noi_2024,
                    MAX(CASE WHEN pm.meta_key = '_noi_2025' THEN pm.meta_value END) AS noi_2025,
                    MAX(CASE WHEN pm.meta_key = '_noi_2026' THEN pm.meta_value END) AS noi_2026,
                    MAX(CASE WHEN pm.meta_key = 'geolocation_lat' THEN pm.meta_value END) AS geopositionnement__latitude,
                    MAX(CASE WHEN pm.meta_key = 'geolocation_long' THEN pm.meta_value END) AS geopositionnement__longitude,
                    p.post_title AS post_title,
                    p.post_content AS post_content,
                    p.post_excerpt AS post_excerpt,
                    p.post_name AS slug
                FROM wp_posts p
                JOIN wp_postmeta pm ON p.ID = pm.post_id
                WHERE p.post_type   = 'job_listing'
                AND p.post_status = 'publish'
                AND p.post_name   = :slug
                GROUP BY p.ID
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $normalizedSlug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // ── Query de categorías: une las 5 tablas del grafo ───────────────────
        // Trae todos los términos asociados al post, agrupados por taxonomía.
        // Ajusta los nombres de taxonomía ('job_listing_category', 'region', …)
        // a los slugs reales que uses en tu instalación de MyListing.
        $catSql = "SELECT
                    t.term_id   AS id,
                    t.name      AS name,
                    t.slug      AS slug,
                    tt.taxonomy AS taxonomy
                FROM wp_term_relationships  tr
                JOIN wp_term_taxonomy       tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN wp_terms               t  ON tt.term_id          = t.term_id
                WHERE tr.object_id = :post_id
                ORDER BY tt.taxonomy, t.name";

        $catStmt = $this->db->prepare($catSql);
        $catStmt->execute(['post_id' => (int) $row['oo_id']]);
        $termRows = $catStmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupa los términos por taxonomía para uso flexible en la aplicación.
        // Resultado: ['job_listing_category' => [['id'=>1,'name'=>'Hébergement','slug'=>'hebergement'], …], …]
        $termsByTaxonomy = [];
        foreach ($termRows as $term) {
            $tax = $term['taxonomy'];
            $termsByTaxonomy[$tax][] = [
                'id'   => (int) $term['id'],
                'name' => $term['name'],
                'slug' => $term['slug'],
            ];
        }

        // Lista plana de categorías principales (compatible con el campo `categories`
        // que ya espera el constructor de Service).
        // Cambia 'job_listing_category' por el slug de taxonomía que uses.
        $categories = $termsByTaxonomy['job_listing_category'] ?? [];

        // ── Resto del mapeo (sin cambios) ─────────────────────────────────────
        $detected        = $this->detectJobListingType((string) ($row['listing_type'] ?? ''));
        $descriptionSource = $row['description'] ?? $row['post_content'] ?? $row['post_excerpt'] ?? '';
        $desc            = html_entity_decode((string) $descriptionSource, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc            = strip_tags($desc);

        $gallery       = [];
        $gallerySource = $this->normalizeMetaImageList((string) ($row['images'] ?? ''));
        if ($gallerySource !== '') {
            $gallery = array_values(array_filter(array_map('trim', explode(',', $gallerySource))));
            $gallery = array_map(fn($url) => $this->normalizeMediaUrl($url), $gallery);
        }

        $heroImage = $this->resolvePimcorePrimaryImage(
            (string) ($row['cover']  ?? ''),
            (string) ($row['images'] ?? ''),
            (int) $row['oo_id']
        );

        $addressValue = trim((string) ($row['adresse']  ?? ''));
        $addressLine2 = trim((string) ($row['adresse2'] ?? ''));
        if ($addressLine2 !== '') {
            $addressValue = trim($addressValue . ' ' . $addressLine2);
        }

        return new Service(
            id: (int) $row['oo_id'],
            type: $detected,
            price: 0,
            imageUrl: $heroImage,
            translations: [
                'title'       => $row['nom'] ?? $row['post_title'] ?? 'Sans titre',
                'description' => trim($desc),
                'tag'         => trim((string) ($row['listing_type'] ?? '')),
            ],
            contact: [
                'phone'    => trim($row['telephone'] ?? ''),
                'mobile'   => trim($row['mobile']    ?? ''),
                'email'    => trim($row['email']     ?? ''),
                'website'  => trim($row['web']       ?? ''),
                'facebook' => trim($row['facebook']  ?? ''),
                'address'  => $addressValue,
                'cp'       => trim($row['cp']        ?? ''),
                'ville'    => trim((string) ($row['post_title'] ?? '')),
            ],
            amenities:  [],
            gallery:    $gallery,
            features:   [],
            lat: (float) ($row['geopositionnement__latitude']  ?? 0),
            lng: (float) ($row['geopositionnement__longitude'] ?? 0),
            responsable: '',
            raison:      '',
            label:  trim((string) ($row['label']        ?? $row['format_papier'] ?? '')),
            zone:   trim((string) ($row['listing_type'] ?? $row['noi_2026']     ?? $row['noi_2025'] ?? '')),
            actualite: trim((string) ($row['texte_version_papier'] ?? '')),

            // ── Categorías obtenidas de wp_terms ──────────────────────────────
            // $categories  → lista plana de la taxonomía principal
            // $termsByTaxonomy → todas las taxonomías, por si necesitas más granularidad
            categories:  $categories,

            equipments:  [],
            socials: array_filter([
                'facebook' => $row['facebook'] ?? '',
            ]),
            videos: array_filter([
                $row['job_video_url']       ?? null,
                $row['video_facebook']      ?? null,
                $row['lien_video_tiktok']   ?? null,
            ]),
            slug: trim((string) ($row['slug'] ?? $normalizedSlug)),
        );
    }

    private function escapeLike(string $value): string {
        return addcslashes($value, '\\%_');
    }

    private function normalizeMetaImage(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if ($decoded !== false && $decoded !== null) {
            if (is_array($decoded)) {
                $firstValue = $decoded[0] ?? reset($decoded);
                return is_string($firstValue) ? trim($firstValue) : '';
            }

            if (is_string($decoded)) {
                return trim($decoded);
            }
        }

        return $value;
    }

    private function normalizeMetaImageList(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if (is_array($decoded)) {
            $images = array_values(array_filter(array_map(function ($item) {
                return is_string($item) ? trim($item) : '';
            }, $decoded)));

            if (!empty($images)) {
                return implode(',', $images);
            }
        }

        return $value;
    }

    private function detectJobListingType(string $listingType): string {
        $normalized = strtolower(trim($listingType));

        switch (true) {
            case str_contains($normalized, 'prestataire'):
                return 'Prestataire';
            case str_contains($normalized, 'hebergement'):
                return 'Hébergement';
            case str_contains($normalized, 'restaurant'):
                return 'Restaurant';
            case str_contains($normalized, 'croisiere'):
                return 'Croisière';
            case str_contains($normalized, 'tour'):
                return 'Tour';
            case str_contains($normalized, 'location'):
                return 'Location';
            case $normalized !== '':
                return ucfirst($normalized);
            default:
                return 'Établissement';
        }
    }

    public function getPimcoreCategories(): array {
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

        $countParts = [];
        foreach ($catalog as $index => $entry) {
            $col = preg_replace('/[^a-z0-9_]/', '', (string)$entry['sql_service_name']);
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

        $results = [];
        foreach ($catalog as $entry) {
            $slug = (string)$entry['sql_service_name'];
            $results[] = [
                'slug' => $slug,
                'name' => (string)($entry['service_title'] ?? ucfirst($slug)),
                'icon_class' => (string)($entry['service_icon'] ?? 'bi-bookmark'),
                'offers_count' => $counts[$slug] ?? 0,
                'image_url' => (string)($entry['service_img'] ?? ''),
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
