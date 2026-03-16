<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a standardised success JSON response.
     */
    public function success(
        mixed $data,
        ?string $message = null,
        int $code = 200,
    ): JsonResponse {
        $payload = [
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ];

        return response()->json($payload, $code);
    }

    /**
     * Return a standardised error JSON response.
     *
     * @param  array<string, mixed>  $details
     */
    public function error(
        string $message,
        int $code,
        array $details = [],
    ): JsonResponse {
        $payload = [
            'status'  => 'error',
            'message' => $message,
        ];

        if ($details !== []) {
            $payload['details'] = $details;
        }

        return response()->json($payload, $code);
    }
}
