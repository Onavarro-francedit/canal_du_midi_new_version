<?php
namespace App\Domain\Models;

class Review {
    public function __construct(
        public int $id,
        public string $customerName,
        public int $rating,
        public string $comment,
        public string $date
    ) {}

    // Método para generar el HTML de las estrellas según el rating
    public function getStarsHtml(): string {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            $html .= ($i <= $this->rating) 
                ? '<i class="bi bi-star-fill text-warning"></i>' 
                : '<i class="bi bi-star text-muted"></i>';
        }
        return $html;
    }
}
