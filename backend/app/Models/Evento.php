<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string             $nombre
 * @property string             $descripcion
 * @property \Carbon\Carbon     $fecha
 * @property \Carbon\Carbon     $fecha_inicio
 * @property array              $ubicacion  GeoJSON Point: { type: "Point", coordinates: [lng, lat] }
 * @property string             $lugar_nombre
 * @property string             $estado
 * @property array              $imagenes
 * @property float              $rating
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
        'fecha_inicio',
        'ubicacion',
        'lugar_nombre',
        'estado',
        'imagenes',
        'rating',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha'        => 'datetime',
            'fecha_inicio' => 'datetime',
            'imagenes'     => 'array',
            'rating'       => 'float',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }
}
