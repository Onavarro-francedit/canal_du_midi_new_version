<?php
// src/Infrastructure/Services/ListingUploader.php
namespace App\Infrastructure\Services;

class ListingUploader
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public function store(int $listingId, array $file): string
    {
        if (!isset($file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Archivo no recibido correctamente.');
        }

        if ((int) $file['size'] <= 0 || (int) $file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('La imagen debe pesar menos de 5MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIME[$realMime])) {
            throw new \RuntimeException('Formato de imagen no permitido (solo JPG, PNG o WEBP).');
        }

        $extension = self::ALLOWED_MIME[$realMime];
        $targetDir = __DIR__ . '/../../../public/uploads/listings/' . $listingId;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('No se pudo crear el directorio de destino.');
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        return 'public/uploads/listings/' . $listingId . '/' . $filename;
    }

    /**
     * Borra físicamente una foto subida por el propio backoffice. Best-effort:
     * nunca lanza excepción hacia el usuario (TASK-026). Doble comprobación de
     * pertenencia: (1) prefijo del path del listing, (2) realpath() dentro del
     * directorio real de ese listing — evita borrar fuera de su carpeta
     * (seeds en public/clients_images/, URLs externas, otro listing).
     */
    public function delete(int $listingId, string $storedPath): void
    {
        $path = $storedPath;
        if (defined('BASE_URL') && str_starts_with($path, rtrim(BASE_URL, '/'))) {
            $path = substr($path, strlen(rtrim(BASE_URL, '/')));
        }
        $path = ltrim($path, '/');

        $prefix = 'public/uploads/listings/' . $listingId . '/';
        if (!str_starts_with($path, $prefix)) {
            return;
        }

        $listingDir = realpath(__DIR__ . '/../../../public/uploads/listings/' . $listingId);
        $fullPath = realpath(__DIR__ . '/../../../' . $path);

        if ($listingDir === false || $fullPath === false || !str_starts_with($fullPath, $listingDir)) {
            return;
        }

        if (!@unlink($fullPath)) {
            error_log('ListingUploader::delete — impossible de supprimer ' . $fullPath);
        }
    }
}
