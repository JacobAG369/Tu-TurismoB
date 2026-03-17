<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Favorito;
use App\Repositories\FavoritoRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FavoritoRepository extends BaseRepository implements FavoritoRepositoryInterface
{
    /**
     * Constructor to bind model to repository.
     *
     * @param Favorito $model
     */
    public function __construct(Favorito $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all favorites for a specific user.
     *
     * @param string $userId
     * @param int $perPage
     * @return LengthAwarePaginator|Collection
     */
    public function getFavoritesByUser(string $userId, int $perPage = 15)
    {
        $query = $this->model->where('usuario_id', $userId)->latest();
        
        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
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
}
