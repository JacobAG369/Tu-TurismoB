<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $usuario_id
 * @property string $lugar_id
 * @property int    $rating      1–5
 * @property string $comentario
 */
class Review extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'reviews';

    protected $fillable = [
        'usuario_id',
        'lugar_id',
        'rating',
        'comentario',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rating'     => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * A review belongs to a user.
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * A review belongs to a place.
     */
    public function lugar(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lugar::class, 'lugar_id');
    }
}
