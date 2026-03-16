<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $nombre
 * @property string $descripcion
 * @property string $departamento
 * @property string $municipio
 * @property array  $coordenadas
 * @property array  $imagenes
 */
class Lugar extends Model
{
    use LogsActivity;

    protected $connection = 'mongodb';

    protected $collection = 'lugares';

    protected $fillable = [
        'nombre',
        'descripcion',
        'departamento',
        'municipio',
        'coordenadas',
        'imagenes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'coordenadas' => 'array',
            'imagenes'    => 'array',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }
}
