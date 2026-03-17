<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $nombre
 * @property string $apellido
 * @property string $email
 * @property string $password
 * @property string $rol          admin|turista
 * @property string $imagen_perfil
 */
class User extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, Notifiable;

    protected $connection = 'mongodb';

    protected $collection = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'imagen_perfil',
        'telefono',
        'direccion',
        'idioma',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * A user has many reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'usuario_id');
    }

    /**
     * A user has many favorites.
     */
    public function favoritos(): HasMany
    {
        return $this->hasMany(Favorito::class, 'usuario_id');
    }

    /**
     * A user has many notifications.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }
}
