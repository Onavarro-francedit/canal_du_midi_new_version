<?php
namespace App\Domain\Repositories;

interface BookingRepository {
    public function isAvailable(int $serviceId, string $start, string $end): bool;
    public function save(array $data): bool;
}