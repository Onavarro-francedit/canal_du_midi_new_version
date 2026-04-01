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

    public function findAll(string $lang): array {
        // Query que une el servicio con sus traducciones
        $sql = "SELECT s.*, c.slug as category_name, 
                       t_title.field_value as title, 
                       t_tag.field_value as tag
                FROM services s
                JOIN categories c ON s.category_id = c.id
                LEFT JOIN translations t_title ON s.id = t_title.rel_id 
                     AND t_title.rel_type = 'service' AND t_title.lang_code = :lang AND t_title.field_name = 'title'
                LEFT JOIN translations t_tag ON s.id = t_tag.rel_id 
                     AND t_tag.rel_type = 'service' AND t_tag.lang_code = :lang AND t_tag.field_name = 'tag'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new Service(
                $row['id'],
                $row['category_name'],
                (float)$row['price'],
                $row['image_url'],
                ['title' => $row['title'], 'tag' => $row['tag']]
            );
        }
        return $results;
    }

    public function findById(int $id, string $lang): ?Service {
        // (Implementación similar para un solo servicio)
        $sql = "SELECT s.*, c.slug as category_name, 
                       t_title.field_value as title, 
                       t_tag.field_value as tag
                FROM services s
                JOIN categories c ON s.category_id = c.id
                LEFT JOIN translations t_title ON s.id = t_title.rel_id 
                     AND t_title.rel_type = 'service' AND t_title.lang_code = :lang AND t_title.field_name = 'title'
                LEFT JOIN translations t_tag ON s.id = t_tag.rel_id 
                     AND t_tag.rel_type = 'service' AND t_tag.lang_code = :lang AND t_tag.field_name = 'tag'
                WHERE s.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'lang' => $lang]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return new Service(
                $row['id'],
                $row['category_name'],
                (float)$row['price'],
                $row['image_url'],
                ['title' => $row['title'], 'tag' => $row['tag']]
            );
        }
        return null;
    }
}