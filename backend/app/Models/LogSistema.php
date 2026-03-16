<?php

declare(strict_types=1);

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string|null $user_id
 * @property string      $action
 * @property string      $model
 * @property array       $details
 * @property string|null $ip
 */
class LogSistema extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'logs_sistema';

    protected $fillable = [
        'user_id',
        'action',
        'model',
        'details',
        'ip',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'details'    => 'array',
            'created_at' => 'datetime',
        ];
    }
}
