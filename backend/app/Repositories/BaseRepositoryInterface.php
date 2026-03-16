<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /** Return all records. */
    public function all(): Collection;

    /** Find a single record by primary key. */
    public function find(string|int $id): ?Model;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string|int $id, array $data): ?Model;

    /** Delete a record by primary key. */
    public function delete(string|int $id): bool;
}
