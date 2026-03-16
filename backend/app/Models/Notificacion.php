<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $usuario_id
 * @property string $titulo
 * @property string $mensaje
 * @property bool   $leido
 */
class Notificacion extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'titulo',
        'mensaje',
        'leido',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'leido'      => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * A notification belongs to a user.
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
