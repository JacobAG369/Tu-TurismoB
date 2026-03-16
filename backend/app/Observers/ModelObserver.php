<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LogSistema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ModelObserver
{
    /**
     * Actions we capture and the corresponding observer method.
     */
    private function log(Model $model, string $action): void
    {
        LogSistema::create([
            'user_id' => Auth::id() ?? null,
            'action'  => $action,
            'model'   => $model::class,
            'details' => $model->toArray(),
            'ip'      => Request::ip(),
        ]);
    }

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->log($model, 'created');
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->log($model, 'updated');
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted');
    }
}
