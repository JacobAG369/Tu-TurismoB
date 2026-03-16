<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $nombre
 * @property string $descripcion
 * @property string $icono
 */
class Categoria extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'categorias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * A category has many places.
     */
    public function lugares(): HasMany
    {
        return $this->hasMany(Lugar::class, 'categoria_id');
    }
}
