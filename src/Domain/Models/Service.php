<?php
namespace App\Domain\Models;

class Service {
    public function __construct(
        public ?int $id,
        public string $type,
        public float $price,
        public string $imageUrl,
        public array $translations = [] // Título, descripción, etc.
    ) {}

    // Aquí irían las reglas de negocio, ej: aplicar descuentos
    public function getFormattedPrice(): string {
        return number_format($this->price, 2) . ' EUR';
    }
}