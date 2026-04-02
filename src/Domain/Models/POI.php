<?php
namespace App\Domain\Models;

class POI {
    public float $distanceKm;

    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public float $lat,
        public float $lng,
        public ?string $imageUrl = null
    ) {}

    // Calcula la distancia desde el servicio hasta este POI
    public function calculateDistanceFrom(float $sLat, float $sLng): void {
        $earthRadius = 6371; // Kilómetros

        $dLat = deg2rad($this->lat - $sLat);
        $dLng = deg2rad($this->lng - $sLng);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($sLat)) * cos(deg2rad($this->lat)) *
             sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $this->distanceKm = $earthRadius * $c;
    }

    public function getFormattedDistance(): string {
        if ($this->distanceKm < 1) {
            return round($this->distanceKm * 1000) . " m";
        }
        return round($this->distanceKm, 1) . " km";
    }

    public function getIcon(): string {
        return match($this->type) {
            'écluse' => 'bi-water',
            'port' => 'bi-anchor',
            'monument' => 'bi-bank',
            default => 'bi-geo-alt'
        };
    }
}