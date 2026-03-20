<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Favorito;
use App\Repositories\FavoritoRepositoryInterface;
use Illuminate\Support\Collection;

class FavoritoRepository extends BaseRepository implements FavoritoRepositoryInterface
{
    public function __construct(Favorito $model)
    {
        parent::__construct($model);
    }

    /**
     * @return Collection<int, Favorito>
     */
    public function getFavoritesByUser(string $userId): Collection
    {
        return $this->model
            ->where('usuario_id', $userId)
            ->orderByDesc('fecha_guardado')
            ->get();
    }

    /**
     * Find a specific favorite by user and reference.
     *
     * @param string $userId
     * @param string $tipo
     * @param string $referenciaId
     * @return Favorito|null
     */
    public function findByUserAndReference(string $userId, string $tipo, string $referenciaId): ?Favorito
    {
        return $this->model
            ->where('usuario_id', $userId)
            ->where('tipo', $tipo)
            ->where('referencia_id', $referenciaId)
            ->first();
    }

    public function findByUserAndResourceId(string $userId, string $referenciaId): ?Favorito
    {
        return $this->model
            ->where('usuario_id', $userId)
            ->where('referencia_id', $referenciaId)
            ->first();
    }

    public function deleteByUserAndResourceId(string $userId, string $referenciaId): bool
    {
        $favorito = $this->findByUserAndResourceId($userId, $referenciaId);

        if ($favorito === null) {
            return false;
        }

        return (bool) $favorito->delete();
    }
}
