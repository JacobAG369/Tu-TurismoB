<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use InvalidArgumentException;

class ImageServiceTest extends TestCase
{
    private ImageService $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->imageService = new ImageService();
    }

    /**
     * Test that oversized image is rejected
     */
    public function test_oversized_image_is_rejected(): void
    {
        // Create a file larger than 2MB
        $file = $this->createOversizedFile();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('demasiado grande');

        $this->imageService->store($file, 'lugares');
    }

    /**
     * Test that non-image file is rejected
     */
    public function test_non_image_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no permitido');

        $this->imageService->store($file, 'lugares');
    }

    /**
     * Test that invalid directory is rejected
     */
    public function test_invalid_directory_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 500, 'image/jpeg');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no permitido');

        $this->imageService->store($file, 'invalid_directory');
    }

    /**
     * Test that directory validation prevents path traversal
     */
    public function test_path_traversal_is_prevented(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 500, 'image/jpeg');

        $this->expectException(InvalidArgumentException::class);

        // Attempt path traversal
        $this->imageService->store($file, '../../etc/passwd');
    }

    /**
     * Test that valid JPEG file with correct MIME is accepted
     */
    public function test_valid_jpeg_file_with_correct_mime(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 500, 'image/jpeg');

        $result = $this->imageService->store($file, 'lugares');

        $this->assertIsString($result);
        $this->assertStringContainsString('/storage/lugares/', $result);
        $this->assertStringContainsString('.jpg', $result);
    }

    /**
     * Test that valid PNG file with correct MIME is accepted
     */
    public function test_valid_png_file_with_correct_mime(): void
    {
        $file = UploadedFile::fake()->create('test.png', 500, 'image/png');

        $result = $this->imageService->store($file, 'eventos');

        $this->assertIsString($result);
        $this->assertStringContainsString('/storage/eventos/', $result);
        $this->assertStringContainsString('.png', $result);
    }

    /**
     * Test that valid WebP file with correct MIME is accepted
     */
    public function test_valid_webp_file_with_correct_mime(): void
    {
        $file = UploadedFile::fake()->create('test.webp', 500, 'image/webp');

        $result = $this->imageService->store($file, 'restaurantes');

        $this->assertIsString($result);
        $this->assertStringContainsString('/storage/restaurantes/', $result);
        $this->assertStringContainsString('.webp', $result);
    }

    /**
     * Test that file extension mismatch is rejected
     */
    public function test_extension_mismatch_is_rejected(): void
    {
        // Create a file with .pdf extension but claiming to be image
        $file = UploadedFile::fake()->create('test.pdf', 500, 'image/jpeg');

        $this->expectException(InvalidArgumentException::class);

        $this->imageService->store($file, 'lugares');
    }

    private function createOversizedFile(): UploadedFile
    {
        // Create a file larger than 2MB
        $size = 3 * 1024 * 1024; // 3MB
        return UploadedFile::fake()->create('large.jpg', $size, 'image/jpeg');
    }
}
