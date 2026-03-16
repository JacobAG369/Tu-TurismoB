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
 * @property string    $user_id
 * @property string    $encrypted_token
 * @property string    $ip
 * @property string    $device
 * @property \Carbon\Carbon $expires_at
 */
class Sesion extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'sesiones';

    protected $fillable = [
        'user_id',
        'encrypted_token',
        'ip',
        'device',
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
}
