<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ImageUploadException extends Exception
{
    /**
     * Render the exception as a JSON response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $this->message,
            'errors'  => [
                'imagen' => [$this->message],
            ],
        ], 422);
    }
}
