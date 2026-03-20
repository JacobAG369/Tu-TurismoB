<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $usuario_id
 * @property string $tipo          lugar|evento|restaurante
 * @property string $referencia_id
 * @property \Carbon\Carbon|null $fecha_guardado
 */
class Favorito extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'favoritos';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'referencia_id',
        'fecha_guardado',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_guardado' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * A favorite belongs to a user.
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
