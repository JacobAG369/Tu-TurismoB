<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    use ApiResponse;

    // ──────────────────────────────────────────────
    // GET /api/v1/notificaciones
    // ──────────────────────────────────────────────

    /**
     * Devuelve todas las notificaciones del usuario autenticado,
     * ordenadas de más reciente a más antigua.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notificaciones = Notificacion::where('usuario_id', (string) $user->_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(
            data: $notificaciones,
            message: 'Notificaciones obtenidas correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // PATCH /api/v1/notificaciones/{id}/read
    // ──────────────────────────────────────────────

    /**
     * Marca una notificación específica como leída.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notificacion = Notificacion::where('_id', $id)
            ->where('usuario_id', (string) $user->_id)
            ->first();

        if (!$notificacion) {
            return $this->error('Notificación no encontrada.', 404);
        }

        $notificacion->update(['leido' => true]);

        return $this->success(
            data: $notificacion,
            message: 'Notificación marcada como leída.',
        );
    }

    // ──────────────────────────────────────────────
    // PATCH /api/v1/notificaciones/read-all
    // ──────────────────────────────────────────────

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        Notificacion::where('usuario_id', (string) $user->_id)
            ->where('leido', false)
            ->update(['leido' => true]);

        return $this->success(
            data: null,
            message: 'Todas las notificaciones marcadas como leídas.',
        );
    }

    // ──────────────────────────────────────────────
    // DELETE /api/v1/notificaciones/{id}
    // ──────────────────────────────────────────────

    /**
     * Elimina una notificación específica del usuario.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $deleted = Notificacion::where('_id', $id)
            ->where('usuario_id', (string) $user->_id)
            ->delete();

        if (!$deleted) {
            return $this->error('Notificación no encontrada.', 404);
        }

        return $this->success(
            data: null,
            message: 'Notificación eliminada correctamente.',
        );
    }

    // ──────────────────────────────────────────────
    // DELETE /api/v1/notificaciones
    // ──────────────────────────────────────────────

    /**
     * Elimina todas las notificaciones del usuario autenticado.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        Notificacion::where('usuario_id', (string) $user->_id)->delete();

        return $this->success(
            data: null,
            message: 'Todas las notificaciones eliminadas correctamente.',
        );
    }
}
