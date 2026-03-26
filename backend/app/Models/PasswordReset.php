<?php

// modelo para los tokens de recuperación de contraseña — vive 15 minutos y desaparece.

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * PasswordReset
 *
 * Almacena temporalmente el código de recuperación de contraseña.
 * El registro se elimina automáticamente al completar el reset o al verificarlo.
 *
 * @property string         $email
 * @property string         $code       6 dígitos numéricos
 * @property \Carbon\Carbon $expires_at
 */
class PasswordReset extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'password_resets';

    protected $fillable = [
        'email',
        'code',
        'expires_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Comprueba si el código de recuperación ya expiró.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
