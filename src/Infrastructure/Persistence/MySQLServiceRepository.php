<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\ServiceRepository;
use App\Domain\Models\Service;
use App\Config\Database;
use PDO;

class MySQLServiceRepository implements ServiceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // =========================================================================
    // Métodos públicos de la interfaz ServiceRepository
    // =========================================================================

    public function findById(int $id, string $lang): ?Service
    {
        $sql = "SELECT l.*,
                       GROUP_CONCAT(c.id   ORDER BY c.name SEPARATOR ',') AS cat_ids,
                       GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR '|') AS cat_names,
                       GROUP_CONCAT(c.slug ORDER BY c.name SEPARATOR ',') AS cat_slugs
                FROM listings l
                LEFT JOIN listing_categories lc ON l.id = lc.listing_id
                LEFT JOIN categories         c  ON lc.category_id = c.id
                WHERE l.id = :id
                  AND l.status = 'publish'
                GROUP BY l.id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->rowToService($row) : null;
    }

    public function findAll(string $lang, bool $withDetails = false): array
    {
        return $this->search('', '', '', $lang);
    }

    public function findBySlug(string $slug): ?Service
    {
        $normalizedSlug = trim(rawurldecode($slug));
        if ($normalizedSlug === '') {
            return null;
        }

        $sql = "SELECT l.*,
                       GROUP_CONCAT(c.id   ORDER BY c.name SEPARATOR ',') AS cat_ids,
                       GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR '|') AS cat_names,
                       GROUP_CONCAT(c.slug ORDER BY c.name SEPARATOR ',') AS cat_slugs
                FROM listings l
                LEFT JOIN listing_categories lc ON l.id = lc.listing_id
                LEFT JOIN categories         c  ON lc.category_id = c.id
                WHERE l.slug   = :slug
                  AND l.status = 'publish'
                GROUP BY l.id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $normalizedSlug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->rowToService($row) : null;
    }

    public function searchServices(string $query, string $lang): array
    {
        return $this->search($query, '', '', $lang);
    }

    public function search(string $query, string $city, string $type, string $lang): array
    {
        $types = $type !== '' ? [$type] : [];
        return $this->searchListings($query, $city, $types);
    }

    public function searchListings(string $query = '', string $city = '', array $types = [], array $categoryIds = []): array
    {
        $where  = ['l.status = :status'];
        $params = ['status' => 'publish'];

        // ── Filtro por tipo de listado
        if (!empty($types)) {
            $typeConditions = [];
            foreach ($types as $i => $type) {
                $type = trim((string) $type);
                if ($type === '') continue;
                $key               = 'type_' . $i;
                $typeConditions[]  = "l.listing_type LIKE :$key";
                $params[$key]      = '%' . $this->escapeLike($type) . '%';
            }
            if (!empty($typeConditions)) {
                $where[] = '(' . implode(' OR ', $typeConditions) . ')';
            }
        }

        // ── Filtro de búsqueda por texto
        if ($query !== '') {
            $where[]      = "(l.title       LIKE :q
                           OR l.description LIKE :q
                           OR l.address     LIKE :q
                           OR l.postal_code LIKE :q
                           OR l.phone       LIKE :q
                           OR l.mobile      LIKE :q
                           OR l.email       LIKE :q
                           OR l.website     LIKE :q
                           OR l.city        LIKE :q)";
            $params['q']  = '%' . $this->escapeLike($query) . '%';
        }

        // ── Filtro por ciudad
        if ($city !== '') {
            $where[]         = "(l.city LIKE :city OR l.address LIKE :city)";
            $params['city']  = '%' . $this->escapeLike($city) . '%';
        }

        $whereClause = implode(' AND ', $where);

        $categoryFilter = [];
        foreach (array_values(array_unique(array_map('intval', $categoryIds))) as $index => $categoryId) {
            if ($categoryId <= 0) {
                continue;
            }

            $key = 'category_' . $index;
            $categoryFilter[] = ':' . $key;
            $params[$key] = $categoryId;
        }

        if (!empty($categoryFilter)) {
            $sql = "SELECT l.*,
                           (
                               SELECT GROUP_CONCAT(c2.id ORDER BY c2.name SEPARATOR ',')
                               FROM listing_categories lc2
                               LEFT JOIN categories c2 ON lc2.category_id = c2.id
                               WHERE lc2.listing_id = l.id
                           ) AS cat_ids,
                           (
                               SELECT GROUP_CONCAT(c2.name ORDER BY c2.name SEPARATOR '|')
                               FROM listing_categories lc2
                               LEFT JOIN categories c2 ON lc2.category_id = c2.id
                               WHERE lc2.listing_id = l.id
                           ) AS cat_names,
                           (
                               SELECT GROUP_CONCAT(c2.slug ORDER BY c2.name SEPARATOR ',')
                               FROM listing_categories lc2
                               LEFT JOIN categories c2 ON lc2.category_id = c2.id
                               WHERE lc2.listing_id = l.id
                           ) AS cat_slugs,
                           lc_filter.category_id AS matched_category_id
                    FROM listings l
                    INNER JOIN listing_categories lc_filter ON lc_filter.listing_id = l.id
                    WHERE $whereClause
                      AND lc_filter.category_id IN (" . implode(',', $categoryFilter) . ")
                    ORDER BY l.title ASC, lc_filter.category_id ASC";
        } else {
            $sql = "SELECT l.*,
                           GROUP_CONCAT(c.id   ORDER BY c.name SEPARATOR ',') AS cat_ids,
                           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR '|') AS cat_names,
                           GROUP_CONCAT(c.slug ORDER BY c.name SEPARATOR ',') AS cat_slugs
                    FROM listings l
                    LEFT JOIN listing_categories lc ON l.id = lc.listing_id
                    LEFT JOIN categories         c  ON lc.category_id = c.id
                    WHERE $whereClause
                    GROUP BY l.id
                    ORDER BY l.title ASC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->rowToService($row), $rows);
    }

    public function getAllCategories(string $lang): array
    {
        return $this->getCategories();
    }

    public function getCategoriesWithCount(string $lang): array
    {
        return $this->getCategories();
    }

    public function getCategories(): array
    {
        $this->ensureCategoryCountsCache();

        $sql = "SELECT
                    c.id,
                    c.name,
                    c.slug,
                    c.taxonomy,
                    c.parent_id,
                    COALESCE(cc.offers_count, 0) AS offers_count
                FROM categories c
                LEFT JOIN category_counts_cache cc ON c.id = cc.category_id
                ORDER BY c.parent_id ASC, c.name ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCities(): array
    {
        $sql = "SELECT DISTINCT city
                FROM listings
                WHERE status = 'publish'
                  AND city IS NOT NULL
                  AND city != ''
                ORDER BY city ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function refreshCategoryCountsCache(): void
    {
        $this->ensureCategoryCountsCache();
        $this->rebuildCategoryCountsCache();
    }

    // ── Métodos que no dependen de listings (sin cambios) ────────────────────

    public function saveReview(array $data): bool
    {
        $sql = "INSERT INTO service_reviews (service_id, booking_id, customer_name, rating, comment, is_approved)
                VALUES (:service_id, :booking_id, :customer_name, :rating, :comment, 1)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'service_id'    => $data['service_id'],
            'booking_id'    => $data['booking_id'],
            'customer_name' => $data['customer_name'],
            'rating'        => $data['rating'],
            'comment'       => $data['comment'],
        ]);
    }

    public function getNearbyPOIs(float $lat, float $lng, string $lang, float $radiusKm = 10): array
    {
        $sql = "SELECT p.*, t.name, p.image_url
                FROM points_of_interest p
                JOIN poi_translations t ON p.id = t.poi_id AND t.lang_code = :lang
                WHERE (6371 * acos(
                    cos(radians(:lat)) * cos(radians(p.lat)) * cos(radians(p.lng) - radians(:lng))
                    + sin(radians(:lat)) * sin(radians(p.lat))
                )) < :radius
                ORDER BY (ABS(p.lat - :lat2) + ABS(p.lng - :lng2)) ASC
                LIMIT 4";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'lat'    => $lat,
            'lng'    => $lng,
            'lang'   => $lang,
            'radius' => $radiusKm,
            'lat2'   => $lat,
            'lng2'   => $lng,
        ]);

        $pois = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $poi = new \App\Domain\Models\POI(
                $row['id'], $row['type'], $row['name'],
                $row['lat'], $row['lng'], $row['image_url']
            );
            $poi->calculateDistanceFrom($lat, $lng);
            $pois[] = $poi;
        }

        return $pois;
    }

    public function findPoiById(int $id, string $lang): ?\App\Domain\Models\POI
    {
        $sql = "SELECT p.*, t.name, t.description
                FROM points_of_interest p
                JOIN poi_translations t ON p.id = t.poi_id AND t.lang_code = :lang
                WHERE p.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'lang' => $lang]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $poi = new \App\Domain\Models\POI(
            $row['id'], $row['type'], $row['name'],
            $row['lat'], $row['lng'], $row['image_url']
        );
        $poi->description = $row['description'];
        return $poi;
    }

    public function getServicesNearPoi(float $lat, float $lng, string $lang, float $radiusKm = 10): array
    {
        $sql = "SELECT l.id, l.title, l.listing_type, l.lat, l.lng, l.cover, l.gallery
                FROM listings l
                WHERE l.status = 'publish'
                  AND l.lat IS NOT NULL
                  AND l.lng IS NOT NULL
                  AND (6371 * acos(
                      cos(radians(:lat)) * cos(radians(l.lat)) * cos(radians(l.lng) - radians(:lng))
                      + sin(radians(:lat)) * sin(radians(l.lat))
                  )) < :radius
                LIMIT 3";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lat' => $lat, 'lng' => $lng, 'radius' => $radiusKm]);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new Service(
                id:           (int) $row['id'],
                type:         $this->resolveType($row['listing_type'] ?? ''),
                price:        0,
                imageUrl:     $this->resolveGalleryFirst($row),
                translations: ['title' => $row['title'] ?? 'Sans titre'],
                contact:      [],
                amenities:    [],
                gallery:      [],
                features:     [],
                lat:          (float) ($row['lat'] ?? 0),
                lng:          (float) ($row['lng'] ?? 0),
            );
        }

        return $results;
    }

    public function getActiveFeatures(string $lang): array
    {
        $sql = "SELECT f.icon_class, t.title, t.content
                FROM site_features f
                JOIN feature_translations t ON f.id = t.feature_id
                WHERE t.lang_code = :lang AND f.is_active = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lang' => $lang]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestArticles(string $lang, int $limit = 3): array
    {
        $sql = "SELECT a.*, t.title
                FROM articles a
                JOIN article_translations t ON a.id = t.article_id
                WHERE t.lang_code = :lang AND a.is_active = 1
                ORDER BY a.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lang', $lang);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // Métodos privados
    // =========================================================================

    /**
     * Convierte una fila de la tabla listings (con cat_ids/cat_names/cat_slugs
     * agregados por GROUP_CONCAT) en un objeto Service.
     */
    private function rowToService(array $row): Service
    {
        // ── Categorías (vienen como strings concatenados por GROUP_CONCAT)
        $categories = $this->parseConcatCategories(
            (string) ($row['cat_ids']   ?? ''),
            (string) ($row['cat_names'] ?? ''),
            (string) ($row['cat_slugs'] ?? '')
        );

        // ── Galería (guardada como JSON en la nueva tabla)
        $gallery = [];
        if (!empty($row['gallery'])) {
            $decoded = json_decode((string) $row['gallery'], true);
            if (is_array($decoded)) {
                $gallery = array_values(array_filter($decoded));
            }
        }

        // ── Imagen principal
        $heroImage = '';
        if (!empty($row['cover'])) {
            $heroImage = $this->normalizeMediaUrl((string) $row['cover']);
        }
        if ($heroImage === '' && !empty($gallery)) {
            $heroImage = $gallery[0];
        }
        // Fallback a disco si existe
        if ($heroImage === '' && !empty($row['id'])) {
            $diskMedia = $this->getMediaFromDisk((int) $row['id']);
            $heroImage = $diskMedia['presentation'];
            if (empty($gallery)) {
                $gallery = $diskMedia['gallery'];
            }
        }

        // ── Vídeos (guardados como JSON)
        $videos = [];
        if (!empty($row['videos'])) {
            $decoded = json_decode((string) $row['videos'], true);
            if (is_array($decoded)) {
                $videos = array_values(array_filter($decoded));
            }
        }

        // ── Redes sociales (guardadas como JSON)
        $socials = [];
        if (!empty($row['socials'])) {
            $decoded = json_decode((string) $row['socials'], true);
            if (is_array($decoded)) {
                $socials = array_filter($decoded);
            }
        }

        // ── Dirección
        $address = trim((string) ($row['address'] ?? ''));
        if (!empty($row['address2'])) {
            $address = trim($address . ' ' . $row['address2']);
        }

        return new Service(
            id:           (int) $row['id'],
            type:         $this->resolveType((string) ($row['listing_type'] ?? '')),
            price:        0,
            imageUrl:     $heroImage,
            translations: [
                'title'       => $row['title'] ?? 'Sans titre',
                'description' => trim((string) ($row['description'] ?? '')),
                'tag'         => trim((string) ($row['listing_type'] ?? '')),
            ],
            contact: [
                'phone'   => trim((string) ($row['phone']       ?? '')),
                'mobile'  => trim((string) ($row['mobile']      ?? '')),
                'email'   => trim((string) ($row['email']       ?? '')),
                'website' => trim((string) ($row['website']     ?? '')),
                'facebook'=> trim((string) ($row['facebook']    ?? '')),
                'address' => $address,
                'cp'      => trim((string) ($row['postal_code'] ?? '')),
                'ville'   => trim((string) ($row['city']        ?? '')),
            ],
            amenities:   [],
            gallery:     $gallery,
            features:    [],
            lat:         (float) ($row['lat'] ?? 0),
            lng:         (float) ($row['lng'] ?? 0),
            responsable: '',
            raison:      '',
            label:       trim((string) ($row['label']        ?? '')),
            zone:        trim((string) ($row['zone']         ?? '')),
            actualite:   trim((string) ($row['actualite']    ?? '')),
            categories:  $categories,
            equipments:  [],
            socials:     $socials,
            videos:      $videos,
            slug:        trim((string) ($row['slug']         ?? '')),
        );
    }

    /**
     * Reconstruye el array de categorías a partir de los strings
     * que devuelve GROUP_CONCAT.
     */
    private function parseConcatCategories(string $ids, string $names, string $slugs): array
    {
        if ($ids === '') return [];

        $idArr    = explode(',', $ids);
        $nameArr  = explode('|', $names);
        $slugArr  = explode(',', $slugs);
        $categories = [];

        foreach ($idArr as $i => $id) {
            $id = trim($id);
            if ($id === '') continue;
            $categories[] = [
                'id'   => (int) $id,
                'name' => $nameArr[$i] ?? '',
                'slug' => $slugArr[$i] ?? '',
            ];
        }

        return $categories;
    }

    private function ensureCategoryCountsCache(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS category_counts_cache (
            category_id INT UNSIGNED NOT NULL PRIMARY KEY,
            offers_count INT UNSIGNED NOT NULL DEFAULT 0,
            calculated_at DATETIME NOT NULL,
            INDEX idx_calculated_at (calculated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $stmt = $this->db->query("SELECT MAX(calculated_at) FROM category_counts_cache");
        $lastCalculatedAt = $stmt ? trim((string)$stmt->fetchColumn()) : '';

        if ($lastCalculatedAt === '' || substr($lastCalculatedAt, 0, 10) !== date('Y-m-d')) {
            $this->rebuildCategoryCountsCache();
        }
    }

    private function rebuildCategoryCountsCache(): void
    {
        $categories = $this->db->query("SELECT id, parent_id FROM categories ORDER BY parent_id ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($categories)) {
            $this->db->exec("DELETE FROM category_counts_cache");
            return;
        }

        $parentByCategory = [];
        $childrenByParent = [];
        foreach ($categories as $category) {
            $categoryId = (int)($category['id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }

            $parentId = isset($category['parent_id']) && $category['parent_id'] !== null
                ? (int)$category['parent_id']
                : 0;

            $parentByCategory[$categoryId] = $parentId;
            if ($parentId > 0) {
                $childrenByParent[$parentId][] = $categoryId;
            }
        }

        $categoryCounts = [];
        $listingCategories = $this->db->query("SELECT DISTINCT lc.listing_id, lc.category_id
            FROM listing_categories lc
            INNER JOIN listings l ON l.id = lc.listing_id AND l.status = 'publish'")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($listingCategories as $row) {
            $categoryId = (int)($row['category_id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }

            foreach ($this->collectAncestorCategoryIds($categoryId, $parentByCategory) as $ancestorId) {
                $categoryCounts[$ancestorId] = ($categoryCounts[$ancestorId] ?? 0) + 1;
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->beginTransaction();

        try {
            $this->db->exec("DELETE FROM category_counts_cache");
            $stmt = $this->db->prepare("INSERT INTO category_counts_cache (category_id, offers_count, calculated_at) VALUES (:category_id, :offers_count, :calculated_at)");

            foreach ($parentByCategory as $categoryId => $_parentId) {
                $stmt->execute([
                    'category_id' => $categoryId,
                    'offers_count' => $categoryCounts[$categoryId] ?? 0,
                    'calculated_at' => $now,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function collectAncestorCategoryIds(int $categoryId, array $parentByCategory): array
    {
        $ancestors = [];
        $currentId = $categoryId;

        while ($currentId > 0 && !in_array($currentId, $ancestors, true)) {
            $ancestors[] = $currentId;
            $currentId = $parentByCategory[$currentId] ?? 0;
        }

        return $ancestors;
    }

    /**
     * Devuelve el tipo legible a partir del listing_type de la nueva tabla.
     * Equivalente al antiguo detectJobListingType().
     */
    private function resolveType(string $listingType): string
    {
        $normalized = strtolower(trim($listingType));

        return match(true) {
            str_contains($normalized, 'prestataire')  => 'Prestataire',
            str_contains($normalized, 'hebergement')  => 'Hébergement',
            str_contains($normalized, 'restaurant')   => 'Restaurant',
            str_contains($normalized, 'croisiere')    => 'Croisière',
            str_contains($normalized, 'tour')         => 'Tour',
            str_contains($normalized, 'location')     => 'Location',
            $normalized !== ''                        => ucfirst($normalized),
            default                                   => 'Établissement',
        };
    }

    /**
     * Devuelve la primera imagen de la galería JSON como URL normalizada.
     */
    private function resolveGalleryFirst(array $row): string
    {
        if (!empty($row['cover'])) {
            return $this->normalizeMediaUrl((string) $row['cover']);
        }
        if (!empty($row['gallery'])) {
            $decoded = json_decode((string) $row['gallery'], true);
            if (is_array($decoded) && !empty($decoded[0])) {
                return $this->normalizeMediaUrl($decoded[0]);
            }
        }
        return '';
    }

    private function normalizeMediaUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (preg_match('#^https?://#i', $url)) return $url;
        if (defined('BASE_URL')) {
            return rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }

    private function getMediaFromDisk(int $id): array
    {
        $clientDir = dirname(__DIR__, 3) . '/public/clients_images/' . $id;
        if (!is_dir($clientDir)) {
            return ['presentation' => '', 'gallery' => []];
        }

        $files = glob($clientDir . '/img*.*') ?: [];
        if (empty($files)) {
            return ['presentation' => '', 'gallery' => []];
        }

        natsort($files);
        $gallery = [];
        foreach ($files as $filePath) {
            $gallery[] = $this->normalizeMediaUrl(
                '/public/clients_images/' . $id . '/' . basename($filePath)
            );
        }

        return ['presentation' => $gallery[0] ?? '', 'gallery' => $gallery];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}