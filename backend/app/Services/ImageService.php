<?php

// Sube imágenes a Cloudinary usando el SDK directo con variables de entorno individuales.
// Evita problemas de parsing de CLOUDINARY_URL en el ServiceProvider.

declare(strict_types=1);

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ImageService
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const ALLOWED_EXTENSIONS = [
        'jpeg',
        'jpg',
        'png',
        'webp',
    ];

    private const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    private const ALLOWED_DIRECTORIES = [
        'lugares',
        'eventos',
        'restaurantes',
        'usuarios',
        'categorias',
    ];

    /**
     * Sube una imagen a Cloudinary y retorna la URL segura.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $this->validateDirectory($directory);
        $this->validateFile($file);

        $cloudinary = $this->buildCloudinary();

        $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder'        => "tu-turismo/{$directory}",
            'resource_type' => 'image',
            'transformation' => [
                ['quality' => 'auto', 'fetch_format' => 'auto'],
            ],
        ]);

        $url = $result['secure_url'] ?? null;

        if (empty($url)) {
            throw new InvalidArgumentException('Cloudinary no devolvió una URL válida.');
        }

        return $url;
    }

    /**
     * Elimina una imagen de Cloudinary usando su URL pública.
     */
    public function delete(string $url): bool
    {
        try {
            $publicId = $this->extractPublicIdFromUrl($url);

            if (empty($publicId)) {
                return false;
            }

            $cloudinary = $this->buildCloudinary();
            $result     = $cloudinary->uploadApi()->destroy($publicId);

            return ($result['result'] ?? '') === 'ok';
        } catch (\Throwable $e) {
            Log::warning("No se pudo eliminar imagen de Cloudinary: {$e->getMessage()}", ['url' => $url]);
            return false;
        }
    }

    /**
     * Construye la instancia de Cloudinary con las variables de entorno individuales.
     * Soporta CLOUDINARY_URL (combinada) o variables separadas.
     */
    private function buildCloudinary(): Cloudinary
    {
        $cloudinaryUrl = env('CLOUDINARY_URL', '');

        // Si la URL contiene el prefijo duplicado (error común al copiar), lo limpia
        if (str_starts_with($cloudinaryUrl, 'CLOUDINARY_URL=')) {
            $cloudinaryUrl = substr($cloudinaryUrl, strlen('CLOUDINARY_URL='));
        }

        // Si tenemos URL válida, usarla directamente
        if (!empty($cloudinaryUrl) && str_starts_with($cloudinaryUrl, 'cloudinary://')) {
            $config = Configuration::instance($cloudinaryUrl);
            return new Cloudinary($config);
        }

        // Fallback: variables individuales
        $cloudName = env('CLOUDINARY_CLOUD_NAME', '');
        $apiKey    = env('CLOUDINARY_API_KEY', '');
        $apiSecret = env('CLOUDINARY_API_SECRET', '');

        if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
            throw new InvalidArgumentException(
                'Cloudinary no está configurado. Define CLOUDINARY_URL o CLOUDINARY_CLOUD_NAME + CLOUDINARY_API_KEY + CLOUDINARY_API_SECRET.'
            );
        }

        return new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url'   => ['secure' => true],
        ]);
    }

    private function validateDirectory(string $directory): void
    {
        $sanitized = trim($directory);
        if (empty($sanitized) || !in_array($sanitized, self::ALLOWED_DIRECTORIES, true)) {
            throw new InvalidArgumentException(
                sprintf('Directorio no permitido. Debe ser uno de: %s', implode(', ', self::ALLOWED_DIRECTORIES))
            );
        }
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException('Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y WebP.');
        }

        $extension = mb_strtolower(trim((string) $file->getClientOriginalExtension()));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Extensión de archivo no permitida. Solo se aceptan: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidArgumentException(sprintf('La imagen es demasiado grande. Tamaño máximo: %dMB.', self::MAX_SIZE / (1024 * 1024)));
        }

        if (!$file->isValid()) {
            throw new InvalidArgumentException('El archivo cargado no es válido.');
        }
    }

    /**
     * Extrae el public_id de Cloudinary desde su URL.
     * URL: https://res.cloudinary.com/cloud/image/upload/v123/tu-turismo/lugares/img.jpg
     * ID:  tu-turismo/lugares/img
     */
    private function extractPublicIdFromUrl(string $url): string
    {
        $url = strtok($url, '?');
        if (preg_match('#/upload/(?:v\d+/)?(.+?)(?:\.[a-z]+)?$#i', $url, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
