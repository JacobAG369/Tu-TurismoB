<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Sesion;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValidateVigenereSession
 *
 * After Sanctum authenticates the token, this middleware verifies that a
 * corresponding sesiones record still exists for the user — ensuring that
 * tokens revoked server-side (e.g. after logout or re-login) are rejected
 * even if Sanctum's personal_access_tokens entry somehow persists.
 */
class ValidateVigenereSession
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autenticado.',
            ], 401);
        }

        $sessionExists = Sesion::where('user_id', (string) $user->_id)
            ->where('expires_at', '>', now())
            ->exists();

        if (! $sessionExists) {
            return $this->error(
                message: 'Sesión inválida o expirada. Por favor inicia sesión nuevamente.',
                code: 401,
            );
        }

        return $next($request);
    }
}
