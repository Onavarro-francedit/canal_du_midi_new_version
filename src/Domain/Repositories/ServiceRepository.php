<?php
namespace App\Domain\Repositories;

use App\Domain\Models\Service;

interface ServiceRepository {
    public function findAll(string $lang, bool $withDetails = false): array;
    public function findById(int $id, string $lang): ?Service;
}