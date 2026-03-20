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
 * @property string $web
 * @property array  $imagenes
 * @property float  $rating
 * @property float  $rating_promedio
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
        'web',
        'imagenes',
        'rating',
        'rating_promedio',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'imagenes'        => 'array',
            'rating'          => 'float',
            'rating_promedio' => 'float',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }
}
