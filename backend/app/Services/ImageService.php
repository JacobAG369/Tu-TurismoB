<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ImageService
{
    /**
     * Allowed MIME types for images
     */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Allowed file extensions
     */
    private const ALLOWED_EXTENSIONS = [
        'jpeg',
        'jpg',
        'png',
        'webp',
    ];

    /**
     * Maximum file size in bytes (2MB)
     */
    private const MAX_SIZE = 2 * 1024 * 1024;

    /**
     * Allowed storage directories
     */
    private const ALLOWED_DIRECTORIES = [
        'lugares',
        'eventos',
        'restaurantes',
        'usuarios',
        'categorias',
    ];

    /**
     * Store an image file with security validations.
     *
     * @param UploadedFile $file     The uploaded file
     * @param string       $directory Storage subdirectory (lugares, eventos, etc.)
     * @return string                 Complete public URL to the stored image
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function store(UploadedFile $file, string $directory): string
    {
        // 1. Validate directory
        $this->validateDirectory($directory);

        // 2. Validate file
        $this->validateFile($file);

        // 3. Generate secure filename
        $filename = $this->generateSecureFilename($file);

        // 4. Store file
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

        // 5. Return complete public URL
        return (string) url(Storage::disk('public')->url($path));
    }

    /**
     * Delete an image file from storage.
     *
     * @param string $url Complete URL or path of the image
     * @return bool       True if deleted successfully
     */
    public function delete(string $url): bool
    {
        // Extract relative path from URL
        $path = $this->extractPathFromUrl($url);

        if (empty($path) || !Storage::disk('public')->exists($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /**
     * Validate that the directory is whitelisted.
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
     * Validate file MIME type, extension, and size.
     *
     * @throws InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException(
                'Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y WebP.'
            );
        }

        // Check extension (additional safety)
        $extension = mb_strtolower(trim((string) $file->getClientOriginalExtension()));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException(
                'Extensión de archivo no permitida. Solo se aceptan: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // Check file size
        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidArgumentException(
                sprintf(
                    'La imagen es demasiado grande. Tamaño máximo: %dMB.',
                    self::MAX_SIZE / (1024 * 1024)
                )
            );
        }

        // Ensure file is valid
        if (!$file->isValid()) {
            throw new InvalidArgumentException('El archivo cargado no es válido.');
        }
    }

    /**
     * Generate a secure, unique filename to prevent overwrites and path traversal.
     *
     * @return string Secure filename (e.g., img_123abc_1710873600.jpg)
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        // Get original extension safely
        $extension = mb_strtolower(trim((string) $file->getClientOriginalExtension()));

        // Sanitize original filename (for logging purposes if needed)
        $originalName = $this->sanitizeFilename($file->getClientOriginalName());

        // Generate unique identifier
        $uniqueId = uniqid('img_', true); // e.g., img_65c4e123a4567.8901
        $timestamp = (string) time();

        // Combine into secure filename
        return sprintf(
            '%s_%s.%s',
            $uniqueId,
            $timestamp,
            $extension
        );
    }

    /**
     * Sanitize original filename by removing special characters.
     *
     * @return string Cleaned filename (for logging/metadata)
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Remove special characters, keep only alphanumeric, hyphens, underscores
        $sanitized = (string) preg_replace('/[^a-zA-Z0-9\-_]/', '', $name);

        // Limit length to 50 chars
        return mb_substr($sanitized, 0, 50);
    }

    /**
     * Extract relative path from a complete public URL.
     *
     * @param string $url Complete URL (e.g., http://localhost/storage/lugares/img_xyz.jpg)
     * @return string      Relative path (e.g., lugares/img_xyz.jpg)
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
