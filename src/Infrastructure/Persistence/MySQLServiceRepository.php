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
        // 1. Obtener datos principales y traducciones
        $sql = "SELECT s.*, c.slug as category_name, 
                       t_title.field_value as title, 
                       t_desc.field_value as description,
                       t_tag.field_value as tag
                FROM services s
                JOIN categories c ON s.category_id = c.id
                LEFT JOIN translations t_title ON s.id = t_title.rel_id 
                     AND t_title.rel_type = 'service' AND t_title.lang_code = :lang AND t_title.field_name = 'title'
                LEFT JOIN translations t_desc ON s.id = t_desc.rel_id 
                     AND t_desc.rel_type = 'service' AND t_desc.lang_code = :lang AND t_desc.field_name = 'description'
                LEFT JOIN translations t_tag ON s.id = t_tag.rel_id 
                     AND t_tag.rel_type = 'service' AND t_tag.lang_code = :lang AND t_tag.field_name = 'tag'
                WHERE s.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'lang' => $lang]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;

        // 2. Obtener los Amenities (Iconos)
        $sql_amenities = "SELECT a.icon_name, a.slug 
                          FROM amenities a
                          JOIN service_amenities sa ON a.id = sa.amenity_id
                          WHERE sa.service_id = :id";
        $stmt_am = $this->db->prepare($sql_amenities);
        $stmt_am->execute(['id' => $id]);
        $amenities = $stmt_am->fetchAll(PDO::FETCH_ASSOC);

        // 3. Obtener la Galería de fotos
        $sql_gallery = "SELECT image_url FROM service_media WHERE service_id = :id ORDER BY is_main DESC";
        $stmt_gal = $this->db->prepare($sql_gallery);
        $stmt_gal->execute(['id' => $id]);
        $gallery = $stmt_gal->fetchAll(PDO::FETCH_COLUMN);


        // 4. Obtener valoraciones y reviews
        $stmt_rev = $this->db->prepare("SELECT * FROM service_reviews WHERE service_id = :id AND is_approved = 1 ORDER BY created_at DESC");
        $stmt_rev->execute(['id' => $id]);
        $reviewsData = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
        
        $reviews = [];
        $totalStars = 0;
        foreach ($reviewsData as $r) {
            $reviews[] = new \App\Domain\Models\Review($r['id'], $r['customer_name'], $r['rating'], $r['comment'], $r['created_at']);
            $totalStars += $r['rating'];
        }
        
        // 4. Crear y retornar el objeto Service hidratado
        $service = new Service(
            id: (int)$row['id'],
            type: $row['category_name'],
            price: (float)$row['price'],
            imageUrl: $row['image_url'],
            translations: [
                'title' => $row['title'], 
                'description' => $row['description'], 
                'tag' => $row['tag']
            ],
            contact: [
                'phone' => $row['phone'] ?? '',
                'email' => $row['email'] ?? '',
                'website' => $row['website'] ?? '',
                'address' => ($row['address'] ?? '') . ' ' . ($row['city'] ?? '')
            ],
            amenities: $amenities,
            gallery: $gallery,
            features: [
                'rooms_count' => $row['rooms_count'] ?? 0,
                'pmr_rooms' => $row['pmr_rooms'] ?? 0,
                'is_hybrid' => (bool)($row['is_hybrid'] ?? 0)
            ],
            lat: (float)($row['lat'] ?? 0),
            lng: (float)($row['lng'] ?? 0)
        );

        $service->reviews = $reviews;
        $service->reviewCount = count($reviews);
        $service->avgRating = $service->reviewCount > 0 ? round($totalStars / $service->reviewCount, 1) : 0;

        return $service;
    }

    // No olvides actualizar también el findAll si quieres mostrar iconos en la Home
    public function findAll(string $lang, bool $withDetails = false): array {
        if (empty($lang)) $lang = 'fr';
        
        $sql = "SELECT s.*, c.slug as category_name, 
                   t_title.field_value as title, 
                   t_tag.field_value as tag,
                   t_desc.field_value as description,
                   s.address, s.city, s.phone, s.email, s.website,
                   s.rooms_count, s.pmr_rooms, s.is_hybrid, s.lat, s.lng
            FROM services s
            JOIN categories c ON s.category_id = c.id
            LEFT JOIN translations t_title ON s.id = t_title.rel_id 
                 AND t_title.rel_type = 'service' AND t_title.lang_code = :lang AND t_title.field_name = 'title'
            LEFT JOIN translations t_tag ON s.id = t_tag.rel_id 
                 AND t_tag.rel_type = 'service' AND t_tag.lang_code = :lang AND t_tag.field_name = 'tag'
            LEFT JOIN translations t_desc ON s.id = t_desc.rel_id 
                 AND t_desc.rel_type = 'service' AND t_desc.lang_code = :lang AND t_desc.field_name = 'description'
            WHERE s.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $service = new \App\Domain\Models\Service(
                id: (int)$row['id'],
                type: $row['category_name'],
                price: (float)$row['price'],
                imageUrl: $row['image_url'],
                translations: [
                    'title' => $row['title'], 
                    'description' => $row['description'], 
                    'tag' => $row['tag']
                ],
                contact: [
                    'phone' => $row['phone'] ?? '', 'email' => $row['email'] ?? '',
                    'website' => $row['website'] ?? '', 'address' => ($row['address'] ?? '') . ' ' . ($row['city'] ?? '')
                ],
                features: [
                    'rooms_count' => $row['rooms_count'] ?? 0,
                    'pmr_rooms' => $row['pmr_rooms'] ?? 0,
                    'is_hybrid' => (bool)($row['is_hybrid'] ?? 0)
                ],
                lat: (float)($row['lat'] ?? 0),
                lng: (float)($row['lng'] ?? 0)
            );

            // Si pedimos los detalles, cargamos también amenities y galería
            if ($withDetails) {
                $service->amenities = $this->getAmenitiesForService($service->id);
                // $service->gallery = $this->getGalleryForService($service->id); // Opcional: si quieres pasar toda la galería a la IA
            }
            $results[] = $service;
        }
        return $results;
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
        $sql = "SELECT s.*, c.slug as category_name, t.field_value as title 
                FROM services s
                JOIN categories c ON s.category_id = c.id
                JOIN translations t ON s.id = t.rel_id AND t.field_name = 'title' AND t.lang_code = :lang
                WHERE (6371 * acos(cos(radians(:lat)) * cos(radians(s.lat)) * cos(radians(s.lng) - radians(:lng)) + sin(radians(:lat)) * sin(radians(s.lat)))) < :radius
                LIMIT 3";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lat' => $lat, 'lng' => $lng, 'lang' => $lang, 'radius' => $radiusKm]);
        
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $results[] = new \App\Domain\Models\Service($row['id'], $row['category_name'], (float)$row['price'], $row['image_url'], ['title' => $row['title']]);
        }
        return $results;
    }

    public function searchServices(string $query, string $lang): array {
        $sql = "SELECT s.*, c.slug as category_name, t.field_value as title 
                FROM services s
                JOIN categories c ON s.category_id = c.id
                JOIN translations t ON s.id = t.rel_id AND t.field_name = 'title' AND t.lang_code = :lang
                WHERE (t.field_value LIKE :query OR c.slug LIKE :query)
                AND s.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang, 'query' => "%$query%"]);
        
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $results[] = new \App\Domain\Models\Service($row['id'], $row['category_name'], (float)$row['price'], $row['image_url'], ['title' => $row['title']]);
        }
        return $results;
    }

    public function search(string $query, string $city, string $type, string $lang): array {
        $sql = "SELECT s.*, c.slug as category_name, 
                    t_title.field_value as title, 
                    t_tag.field_value as tag
                FROM services s
                JOIN categories c ON s.category_id = c.id
                LEFT JOIN translations t_title ON s.id = t_title.rel_id 
                    AND t_title.rel_type = 'service' AND t_title.lang_code = :lang AND t_title.field_name = 'title'
                LEFT JOIN translations t_tag ON s.id = t_tag.rel_id 
                    AND t_tag.rel_type = 'service' AND t_tag.lang_code = :lang AND t_tag.field_name = 'tag'
                LEFT JOIN translations t_desc ON s.id = t_desc.rel_id 
                    AND t_desc.rel_type = 'service' AND t_desc.lang_code = :lang AND t_desc.field_name = 'description'
                WHERE s.is_active = 1";

        $params = ['lang' => $lang];

        // Filtro por palabra clave (busca en título, tag o descripción)
        if (!empty($query)) {
            $sql .= " AND (t_title.field_value LIKE :q OR t_tag.field_value LIKE :q OR t_desc.field_value LIKE :q)";
            $params['q'] = "%$query%";
        }

        // Filtro por ciudad
        if (!empty($city)) {
            $sql .= " AND s.city = :city";
            $params['city'] = $city;
        }

        // Filtro por tipo de categoría
        if (!empty($type)) {
            $sql .= " AND c.slug = :type";
            $params['type'] = $type;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new \App\Domain\Models\Service(
                $row['id'], $row['category_name'], (float)$row['price'], $row['image_url'],
                ['title' => $row['title'], 'tag' => $row['tag'], 'description' => $row['description'] ?? ''],
                [
                    'address' => trim(($row['address'] ?? '') . ' ' . ($row['city'] ?? '')),
                ],
                [],
                [],
                [
                    'rooms_count' => $row['rooms_count'] ?? 0,
                    'is_hybrid' => (bool)($row['is_hybrid'] ?? 0),
                ],
                (float)($row['lat'] ?? 0),
                (float)($row['lng'] ?? 0)
            );
        }
        return $results;
    }


    public function getAllCategories(string $lang): array {
        $sql = "SELECT c.*, t.field_value as name, COUNT(s.id) as offers_count
                FROM categories c
                LEFT JOIN translations t ON c.id = t.rel_id 
                    AND t.rel_type = 'category' AND t.lang_code = :lang AND t.field_name = 'name'
                LEFT JOIN services s ON s.category_id = c.id AND s.is_active = 1
                GROUP BY c.id, t.field_value";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
        // Añadimos c.image_url a la consulta
        $sql = "SELECT c.id, c.slug, c.icon_class, c.image_url, t.field_value as name,
                (SELECT COUNT(*) FROM services s WHERE s.category_id = c.id AND s.is_active = 1) as offers_count
                FROM categories c
                LEFT JOIN translations t ON c.id = t.rel_id 
                    AND t.rel_type = 'category' 
                    AND t.lang_code = :lang 
                    AND t.field_name = 'name'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}