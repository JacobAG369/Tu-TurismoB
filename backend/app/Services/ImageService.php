<?php

// Sube imágenes a Cloudinary (almacenamiento persistente en la nube).
// Las URLs que se guardan en MongoDB son URLs de Cloudinary y nunca se pierden.

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ImageService
{
    /**
     * Tipos MIME permitidos
     */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Extensiones permitidas
     */
    private const ALLOWED_EXTENSIONS = [
        'jpeg',
        'jpg',
        'png',
        'webp',
    ];

    /**
     * Tamaño máximo en bytes (5MB — Cloudinary lo puede manejar)
     */
    private const MAX_SIZE = 5 * 1024 * 1024;

    /**
     * Directorios (carpetas en Cloudinary) permitidos
     */
    private const ALLOWED_DIRECTORIES = [
        'lugares',
        'eventos',
        'restaurantes',
        'usuarios',
        'categorias',
    ];

    /**
     * Sube una imagen a Cloudinary.
     *
     * @param UploadedFile $file      Archivo subido
     * @param string       $directory Carpeta en Cloudinary (ej: 'lugares')
     * @return string                 URL pública de la imagen en Cloudinary
     *
     * @throws InvalidArgumentException Si la validación falla
     */
    public function store(UploadedFile $file, string $directory): string
    {
        // 1. Validar directorio
        $this->validateDirectory($directory);

        // 2. Validar archivo
        $this->validateFile($file);

        // 3. Subir a Cloudinary en la carpeta correspondiente
        $result = Cloudinary::upload($file->getRealPath(), [
            'folder'         => "tu-turismo/{$directory}",
            'resource_type'  => 'image',
            'transformation' => [
                ['quality' => 'auto', 'fetch_format' => 'auto'],
            ],
        ]);

        $url = $result->getSecurePath();

        if (empty($url)) {
            throw new InvalidArgumentException('Cloudinary no devolvió una URL válida.');
        }

        return $url;
    }

    /**
     * Elimina una imagen de Cloudinary por su URL pública.
     *
     * @param string $url URL de Cloudinary
     * @return bool       True si se eliminó correctamente
     */
    public function delete(string $url): bool
    {
        try {
            $publicId = $this->extractPublicIdFromUrl($url);

            if (empty($publicId)) {
                return false;
            }

            $result = Cloudinary::destroy($publicId);

            return ($result['result'] ?? '') === 'ok';
        } catch (\Throwable $e) {
            Log::warning("No se pudo eliminar imagen de Cloudinary: {$e->getMessage()}", [
                'url' => $url,
            ]);
            return false;
        }
    }

    /**
     * Valida que el directorio esté en la lista blanca.
     *
     * @throws InvalidArgumentException
     */
    private function validateDirectory(string $directory): void
    {
        $sanitized = trim($directory);

        if (empty($sanitized) || !in_array($sanitized, self::ALLOWED_DIRECTORIES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Directorio no permitido. Debe ser uno de: %s',
                    implode(', ', self::ALLOWED_DIRECTORIES)
                )
            );
        }
    }

    /**
     * Valida MIME, extensión y tamaño del archivo.
     *
     * @throws InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        // verificar tipo MIME
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException(
                'Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y WebP.'
            );
        }

        // verificar extensión
        $extension = mb_strtolower(trim((string) $file->getClientOriginalExtension()));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException(
                'Extensión de archivo no permitida. Solo se aceptan: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // verificar tamaño
        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidArgumentException(
                sprintf(
                    'La imagen es demasiado grande. Tamaño máximo: %dMB.',
                    self::MAX_SIZE / (1024 * 1024)
                )
            );
        }

        // verificar que el archivo es válido
        if (!$file->isValid()) {
            throw new InvalidArgumentException('El archivo cargado no es válido.');
        }
    }

    /**
     * Extrae el public_id de Cloudinary desde la URL completa.
     * Ejemplo URL: https://res.cloudinary.com/cloud_name/image/upload/v123456/tu-turismo/lugares/img_abc.jpg
     * Public ID:   tu-turismo/lugares/img_abc
     */
    private function extractPublicIdFromUrl(string $url): string
    {
        // Eliminar query string si lo hay
        $url = strtok($url, '?');

        // Buscar el segmento después de /upload/ y quitar la versión (v12345/)
        if (preg_match('#/upload/(?:v\d+/)?(.+?)(?:\.[a-z]+)?$#i', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
