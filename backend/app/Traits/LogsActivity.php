<?php

declare(strict_types=1);

namespace App\Traits;

use App\Observers\ModelObserver;

/**
 * Add this trait to any Eloquent / MongoDB model to automatically
 * log created, updated, and deleted events to `logs_sistema`.
 *
 * Usage:
 *   use App\Traits\LogsActivity;
 *
 *   class MyModel extends Model
 *   {
 *       use LogsActivity;
 *   }
 */
trait LogsActivity
{
    /**
     * Boot the trait and attach the observer to the model.
     */
    public static function bootLogsActivity(): void
    {
        static::observe(ModelObserver::class);
    }
}
