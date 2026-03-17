<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Sesion
 *
 * Represents a validated session stored in MongoDB.
 * The token stored here is the Vigenere-encrypted version;
 * the plain token lives exclusively with the Sanctum client.
 *
 * @property string    $usuario_id
 * @property string    $token_id
 * @property string    $token_vigenere
 * @property string    $ip
 * @property string    $dispositivo
 * @property \Carbon\Carbon $expira_en
 * @property \Carbon\Carbon $ultimo_acceso
 */
class Sesion extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'sesiones';

    protected $fillable = [
        'usuario_id',
        'token_id',
        'token_vigenere',
        'ip',
        'dispositivo',
        'expira_en',
        'ultimo_acceso',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
