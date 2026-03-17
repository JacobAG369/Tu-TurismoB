<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use MongoDB\Laravel\Eloquent\DocumentModel;

class PersonalAccessToken extends SanctumPersonalAccessToken implements HasAbilities
{
    use DocumentModel;

    protected $connection = 'mongodb';

    protected $collection = 'personal_access_tokens';

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
    ];

    protected $hidden = [
        'token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'abilities'    => 'array',
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }

    public static function findToken($token)
    {
        if (strpos($token, '|') === false) {
            return static::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);

        $instance = static::find($id);

        if ($instance === null) {
            return null;
        }

        return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
    }

    public function can($ability): bool
    {
        $abilities = is_array($this->abilities) ? $this->abilities : [];

        return in_array('*', $abilities, true) || array_key_exists($ability, array_flip($abilities));
    }

    public function cant($ability): bool
    {
        return ! $this->can($ability);
    }
}
