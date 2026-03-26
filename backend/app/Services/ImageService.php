<?php

// sube imágenes, las valida y genera nombres únicos. esto falla si el disco falla. no hay plan B.

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

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
     * Tamaño máximo en bytes (2MB)
     */
    private const MAX_SIZE = 2 * 1024 * 1024;

    /**
     * Directorios de almacenamiento permitidos
     */
    private const ALLOWED_DIRECTORIES = [
        'lugares',
        'eventos',
        'restaurantes',
        'usuarios',
        'categorias',
    ];

    /**
     * Guarda una imagen con validaciones de seguridad.
     *
     * @param UploadedFile $file      Archivo subido
     * @param string       $directory Subdirectorio de almacenamiento
     * @return string                 URL pública completa de la imagen guardada
     *
     * @throws InvalidArgumentException Si la validación falla
     */
    public function store(UploadedFile $file, string $directory): string
    {
        // 1. Validar directorio
        $this->validateDirectory($directory);

        // 2. Validar archivo
        $this->validateFile($file);

        // 3. Generar nombre de archivo seguro
        $filename = $this->generateSecureFilename($file);

        // 4. Guardar archivo
        $storagePath = sprintf('%s/%s', $directory, $filename);
        $path = Storage::disk('public')->putFileAs(
            $directory,
            $file,
            $filename,
            'public'
        );

        if ($path === false) {
            throw new InvalidArgumentException('Error al guardar la imagen en el servidor.');
        }

        // 5. Retornar URL pública completa
        return (string) url(Storage::disk('public')->url($path));
    }

    /**
     * Elimina una imagen del almacenamiento.
     *
     * @param string $url URL completa o ruta de la imagen
     * @return bool       True si se eliminó correctamente
     */
    public function delete(string $url): bool
    {
        // extraer ruta relativa desde la URL
        $path = $this->extractPathFromUrl($url);

        if (empty($path) || !Storage::disk('public')->exists($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
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

        // verificar extensión (por si las dudas)
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
     * Genera un nombre de archivo seguro y único para evitar colisiones y path traversal.
     *
     * @return string Nombre seguro (ej: img_123abc_1710873600.jpg)
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = mb_strtolower(trim((string) $file->getClientOriginalExtension()));

        $originalName = $this->sanitizeFilename($file->getClientOriginalName());

        $uniqueId = uniqid('img_', true);
        $timestamp = (string) time();

        return sprintf(
            '%s_%s.%s',
            $uniqueId,
            $timestamp,
            $extension
        );
    }

    /**
     * Sanitiza el nombre original del archivo eliminando caracteres especiales.
     *
     * @return string Nombre limpio (para logs/metadatos)
     */
    private function sanitizeFilename(string $filename): string
    {
        // quitar extensión
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // solo alfanuméricos, guiones y guiones bajos
        $sanitized = (string) preg_replace('/[^a-zA-Z0-9\-_]/', '', $name);

        // limitar a 50 caracteres
        return mb_substr($sanitized, 0, 50);
    }

    /**
     * Extrae la ruta relativa desde una URL pública completa.
     *
     * @param string $url URL completa (ej: http://localhost/storage/lugares/img_xyz.jpg)
     * @return string      Ruta relativa (ej: lugares/img_xyz.jpg)
     */
    private function extractPathFromUrl(string $url): string
    {
        $storageUrl = Storage::disk('public')->url('/');
        $baseUrl = url($storageUrl);

        if (str_starts_with($url, $baseUrl)) {
            return (string) str_replace($baseUrl, '', $url);
        }

        return '';
    }
}
