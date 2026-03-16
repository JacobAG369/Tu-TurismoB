<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string             $nombre
 * @property string             $descripcion
 * @property \Carbon\Carbon     $fecha
 * @property array              $ubicacion  GeoJSON Point: { type: "Point", coordinates: [lng, lat] }
 * @property string             $imagen
 */
class Evento extends Model
{
    use LogsActivity;

    protected $connection = 'mongodb';

    protected $collection = 'eventos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha',
        'ubicacion',
        'imagen',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ubicacion'  => 'array',
            'fecha'      => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
