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
}
