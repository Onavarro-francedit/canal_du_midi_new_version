<?php
namespace App\Infrastructure\Views\Helpers;

use App\Domain\Models\Service;

class SchemaGenerator {

    public static function generateHome(string $lang): string {
        $schema = [
            "@context" => "https://schema.org",
            "@graph" => [
                [
                    "@type" => "TravelAgency",
                    "@id" => BASE_URL . "#organization",
                    "name" => "Canal du Midi - Voyages & Escapades",
                    "url" => BASE_URL,
                    "logo" => BASE_URL . "public/assets/img/logo.png",
                    "description" => "Plateforme premium dédiée à l'exploration du Canal du Midi. Nous sélectionnons les meilleurs hôtels, péniches et itinéraires de randonnée à vélo.",
                    "address" => [
                        "@type" => "PostalAddress",
                        "addressLocality" => "Toulouse",
                        "addressRegion" => "Occitanie",
                        "addressCountry" => "FR"
                    ],
                    "areaServed" => [
                        "@type" => "AdministrativeArea",
                        "name" => "Canal du Midi"
                    ],
                    "knowsAbout" => ["Slow Travel", "Canal du Midi", "Navigation fluviale", "Patrimoine UNESCO"],
                    "contactPoint" => [
                        "@type" => "ContactPoint",
                        "telephone" => "+33-5-00-00-00-00",
                        "contactType" => "customer service",
                        "email" => "bonjour@canaldumidi.local"
                    ]
                ],
                [
                    "@type" => "WebSite",
                    "@id" => BASE_URL . "#website",
                    "url" => BASE_URL,
                    "name" => "Canal du Midi",
                    "publisher" => ["@id" => BASE_URL . "#organization"],
                    "potentialAction" => [
                        "@type" => "SearchAction",
                        "target" => BASE_URL . $lang . "/search?q={search_term_string}",
                        "query-input" => "required name=search_term_string"
                    ]
                ]
            ]
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public static function generate(Service $service, string $lang = 'fr'): string {
        // Determinamos el tipo de Schema según la categoría
        $type = ($service->type === 'hotel') ? 'Hotel' : 'Product';
        
        $schema = [
            "@context" => "https://schema.org",
            "@type" => $type,
            "name" => $service->translations['title'],
            "description" => $service->translations['description'],
            "image" => $service->imageUrl,
            "url" => BASE_URL . $lang . "/service/" . $service->id,
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $service->contact['address'],
                "addressLocality" => "Canal du Midi",
                "addressRegion" => "Occitanie",
                "addressCountry" => "FR"
            ],
            "offers" => [
                "@type" => "Offer",
                "price" => $service->price,
                "priceCurrency" => "EUR",
                "availability" => "https://schema.org/InStock"
            ]
        ];

        // Si es un hotel, añadimos detalles específicos
        if ($type === 'Hotel') {
            $schema['numberOfRooms'] = $service->features['rooms_count'] ?? 0;
            $schema['amenityFeature'] = array_map(function($a) {
                return [
                    "@type" => "LocationFeatureSpecification",
                    "name" => $a['slug'],
                    "value" => true
                ];
            }, $service->amenities);
        }

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}