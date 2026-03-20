<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (($user->rol ?? null) !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado. Se requiere rol de administrador.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
