<?php
namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\BookingRepository;
use App\Config\Database;
use PDO;

class MySQLBookingRepository implements BookingRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function isAvailable(int $serviceId, string $start, string $end): bool {
        // Buscamos si existe alguna reserva confirmada o pendiente que choque con estas fechas
        $sql = "SELECT COUNT(*) FROM bookings 
                WHERE service_id = :sid 
                AND status != 'cancelled'
                AND (:start < checkout_date AND :end > checkin_date)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sid'   => $serviceId,
            'start' => $start,
            'end'   => $end
        ]);

        return $stmt->fetchColumn() == 0; // Disponible si el conteo es 0
    }

    public function save(array $data): bool {
        $sql = "INSERT INTO bookings 
                (service_id, customer_email, checkin_date, checkout_date, adults, children, has_disabled, is_pregnant, promo_code) 
                VALUES (:sid, :email, :checkin, :checkout, :adults, :children, :disabled, :pregnant, :promo)";
        
        return $this->db->prepare($sql)->execute($data);
    }
}