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
    public function findAll(string $lang): array {
        if (empty($lang)) $lang = 'fr';
        
        $sql = "SELECT s.*, c.slug as category_name, 
                       t_title.field_value as title, 
                       t_tag.field_value as tag
                FROM services s
                JOIN categories c ON s.category_id = c.id
                LEFT JOIN translations t_title ON s.id = t_title.rel_id 
                     AND t_title.rel_type = 'service' AND t_title.lang_code = :lang AND t_title.field_name = 'title'
                LEFT JOIN translations t_tag ON s.id = t_tag.rel_id 
                     AND t_tag.rel_type = 'service' AND t_tag.lang_code = :lang AND t_tag.field_name = 'tag'
                WHERE s.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new Service(
                id: (int)$row['id'],
                type: $row['category_name'],
                price: (float)$row['price'],
                imageUrl: $row['image_url'],
                translations: ['title' => $row['title'], 'tag' => $row['tag']]
            );
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
}