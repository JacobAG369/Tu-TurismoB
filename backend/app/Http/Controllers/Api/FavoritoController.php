<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleFavoritoRequest;
use App\Services\FavoritoService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoritoController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FavoritoService $favoritoService
    ) {}

    /**
     * Get user's favorites.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = (string) auth()->id();
            
            if (!$userId) {
                return $this->error('Usuario no autenticado.', 401);
            }

            $favoritos = $this->favoritoService->getFavoritesByUser($userId);
            
            return $this->success($favoritos, 'Favoritos obtenidos con éxito.');
        } catch (\Exception $e) {
            return $this->error('Error al obtener los favoritos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle a favorite.
     */
    public function toggle(ToggleFavoritoRequest $request): JsonResponse
    {
        try {
            $userId = (string) auth()->id();
            
            if (!$userId) {
                return $this->error('Usuario no autenticado.', 401);
            }

            $data = $request->validated();
            
            $result = $this->favoritoService->toggle(
                $userId,
                $data['tipo'],
                $data['referencia_id']
            );
            
            $message = $result['status'] === 'added' 
                ? 'Agregado a favoritos exitosamente.' 
                : 'Removido de favoritos exitosamente.';
                
            return $this->success(['status' => $result['status']], $message);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->error('Error al modificar favorito: ' . $e->getMessage(), 500);
        }
    }
}
