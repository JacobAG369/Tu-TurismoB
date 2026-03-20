<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $nombre
 * @property string $descripcion
 * @property string $categoria_id
 * @property array  $ubicacion   GeoJSON Point: { type: "Point", coordinates: [lng, lat] }
 * @property string $direccion
 * @property array  $imagenes
 * @property float  $rating
 * @property float  $rating_promedio
 */
class Lugar extends Model
{
    use LogsActivity;

    protected $connection = 'mongodb';

    protected $collection = 'lugares';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria_id',
        'ubicacion',
        'direccion',
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

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * A place belongs to one category.
     */
    public function categoria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * A place has many reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'lugar_id');
    }

    /**
     * A place has many favorites.
     */
    public function favoritos(): HasMany
    {
        return $this->hasMany(Favorito::class, 'referencia_id');
    }
}
