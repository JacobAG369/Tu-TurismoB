<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Lugar;
use App\Repositories\LugarRepositoryInterface;

class LugarRepository extends BaseRepository implements LugarRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Lugar());
    }
}
