<?php
namespace App\Domain\Repositories;

use App\Domain\Models\Service;

interface ServiceRepository {
    public function findAll(string $lang): array;
    public function findById(int $id, string $lang): ?Service;
}