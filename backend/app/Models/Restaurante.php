<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $nombre
 * @property string $descripcion
 * @property string $direccion
 * @property string $telefono
 * @property string $horario
 * @property array  $ubicacion  GeoJSON Point: { type: "Point", coordinates: [lng, lat] }
 * @property float  $rating
 */
class Restaurante extends Model
{
    use LogsActivity;

    protected $connection = 'mongodb';

    protected $collection = 'restaurantes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'direccion',
        'telefono',
        'horario',
        'ubicacion',
        'rating',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ubicacion'  => 'array',
            'rating'     => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
