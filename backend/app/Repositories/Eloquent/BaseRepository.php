<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected readonly Model $model,
    ) {}

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(string|int $id): ?Model
    {
        /** @var Model|null */
        return $this->model->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string|int $id, array $data): ?Model
    {
        $record = $this->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->fresh();
    }

    public function delete(string|int $id): bool
    {
        $record = $this->find($id);

        if ($record === null) {
            return false;
        }

        return (bool) $record->delete();
    }
}
