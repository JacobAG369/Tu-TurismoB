<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Eloquent\UsuarioRepository;
use App\Repositories\UsuarioRepositoryInterface;

use App\Repositories\Eloquent\LugarRepository;
use App\Repositories\LugarRepositoryInterface;
use App\Repositories\Eloquent\EventoRepository;
use App\Repositories\EventoRepositoryInterface;
use App\Repositories\Eloquent\RestauranteRepository;
use App\Repositories\RestauranteRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UsuarioRepositoryInterface::class, UsuarioRepository::class);

        $this->app->bind(LugarRepositoryInterface::class, LugarRepository::class);
        $this->app->bind(EventoRepositoryInterface::class, EventoRepository::class);
        $this->app->bind(RestauranteRepositoryInterface::class, RestauranteRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
