<?php
namespace App\Domain\Models;

class Service {
    public function __construct(
        public ?int $id,
        public string $type,
        public float $price,
        public string $imageUrl,
        public array $translations = [],
        public array $contact = [],    
        public array $amenities = [],  
        public array $gallery = [],    
        public array $features = [],   
        public float $lat = 0,
        public float $lng = 0
    ) {
        // 1. Evitar errores de nulos en las traducciones (Vital para PHP 8.2+)
        $this->translations['title'] = $this->translations['title'] ?? 'Sans titre';
        $this->translations['description'] = $this->translations['description'] ?? '';
        $this->translations['tag'] = $this->translations['tag'] ?? '';

        // 2. Inicializar arrays de soporte para evitar "Undefined Index" en la vista
        $this->contact['phone'] = $this->contact['phone'] ?? '';
        $this->contact['email'] = $this->contact['email'] ?? '';
        $this->contact['address'] = $this->contact['address'] ?? '';
        
        // 3. Asegurar que las features numéricas existan
        $this->features['rooms_count'] = $this->features['rooms_count'] ?? 0;
        $this->features['pmr_rooms'] = $this->features['pmr_rooms'] ?? 0;
    }

    public function getFormattedPrice(): string {
        // Si el precio es 0, podrías devolver "Sur demande" o algo similar
        if ($this->price <= 0) return ($this->translations['title'] === 'Sans titre') ? '' : 'Sur demande';
        
        return number_format($this->price, 0, ',', ' ') . ' €';
    }

    /**
     * Mejora de isHybrid:
     * Ahora comprueba tanto si el string tiene un '+' como si el flag de la DB está activo
     */
    public function isHybrid(): bool {
        return str_contains($this->type, '+') || ($this->features['is_hybrid'] ?? false);
    }
}