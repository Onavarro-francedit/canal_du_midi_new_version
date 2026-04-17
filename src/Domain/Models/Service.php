<?php
namespace App\Domain\Models;

class Service {
    public array $nearbyPOIs = []; 
    public float $avgRating = 0;
    public int $reviewCount = 0;
    public array $reviews = [];


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
        public float $lng = 0,
        // Pimcore fields
        public string $responsable = '',
        public string $raison = '',
        public string $label = '',
        public string $zone = '',
        public string $actualite = '',
        public array $categories = [],
        public array $equipments = [],
        public array $socials = [],
        public array $videos = [],
    ) {
        $this->translations['title'] = $this->translations['title'] ?? 'Sans titre';
        $this->translations['description'] = $this->translations['description'] ?? '';
        $this->translations['tag'] = $this->translations['tag'] ?? '';

        $this->contact['phone'] = $this->contact['phone'] ?? '';
        $this->contact['phone2'] = $this->contact['phone2'] ?? '';
        $this->contact['mobile'] = $this->contact['mobile'] ?? '';
        $this->contact['fax'] = $this->contact['fax'] ?? '';
        $this->contact['email'] = $this->contact['email'] ?? '';
        $this->contact['email2'] = $this->contact['email2'] ?? '';
        $this->contact['address'] = $this->contact['address'] ?? '';
        $this->contact['cp'] = $this->contact['cp'] ?? '';
        $this->contact['ville'] = $this->contact['ville'] ?? '';
        $this->contact['website'] = $this->contact['website'] ?? '';
        $this->contact['website2'] = $this->contact['website2'] ?? '';
        
        $this->features['rooms_count'] = $this->features['rooms_count'] ?? 0;
        $this->features['pmr_rooms'] = $this->features['pmr_rooms'] ?? 0;
    }

    public function getFormattedPrice(): string {
        if ($this->price <= 0) return ($this->translations['title'] === 'Sans titre') ? '' : 'Sur demande';
        
        return number_format($this->price, 0, ',', ' ') . ' €';
    }

    public function isHybrid(): bool {
        return str_contains($this->type, '+') || ($this->features['is_hybrid'] ?? false);
    }

    public function getFullAddress(): string {
        $parts = array_filter([
            $this->contact['address'],
            $this->contact['cp'],
            $this->contact['ville'],
        ]);
        return implode(', ', $parts) ?: 'Occitanie, France';
    }

    public function getActiveCategories(): array {
        return array_filter($this->categories, fn($v) => $v);
    }

    public function getActiveEquipments(): array {
        return array_filter($this->equipments, fn($v) => $v);
    }
}