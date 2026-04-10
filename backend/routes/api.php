<?php

// todas las rutas del proyecto. si algo no responde, probablemente está aquí abajo.

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\LugarController;
use App\Http\Controllers\Api\V1\UsuarioController;
use App\Http\Controllers\Api\V1\EventoController;
use App\Http\Controllers\Api\V1\RestauranteController;
use App\Http\Controllers\Api\V1\MapaController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PasswordRecoveryController;
use App\Http\Controllers\Api\FavoritoController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Tu-Turismo
|--------------------------------------------------------------------------
| All routes here are prefixed with /api/v1 (configured in bootstrap/app.php)
| and protected by the "api" middleware group.
|--------------------------------------------------------------------------
*/

// Health-check
Route::get('/', static function (): \Illuminate\Http\JsonResponse {
    return response()->json([
        'status'  => 'ok',
        'api'     => 'Tu-Turismo',
        'version' => 'v1',
    ]);
});

// ──────────────────────────────────────────────────────────────────────────
// Public auth routes  (no token required)
// ──────────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');

    // ── Recuperación de contraseña (throttle: 5 req/min) ──────────────────
    Route::middleware('throttle:5,1')->group(function (): void {
        Route::post('/password/send-code',   [PasswordRecoveryController::class, 'sendCode'])->name('auth.password.send-code');
        Route::post('/password/verify-code', [PasswordRecoveryController::class, 'verifyCode'])->name('auth.password.verify-code');
        Route::post('/password/reset',       [PasswordRecoveryController::class, 'resetPassword'])->name('auth.password.reset');
    });
});

// ──────────────────────────────────────────────────────────────────────────
// Protected auth routes  (Sanctum token + Vigenere session validation)
// ──────────────────────────────────────────────────────────────────────────
Route::prefix('auth')
    ->middleware(['auth:sanctum', 'vigenere.session'])
    ->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me',     [AuthController::class, 'me'])->name('auth.me');
        Route::get('/session', [AuthController::class, 'session'])->name('auth.session');
    });

// ──────────────────────────────────────────────────────────────────────────
// Protected user-profile routes  (Sanctum token + Vigenere session validation)
// ──────────────────────────────────────────────────────────────────────────
Route::prefix('user')
    ->middleware(['auth:sanctum', 'vigenere.session'])
    ->group(function (): void {
        Route::get('/profile',  [UsuarioController::class, 'profile'])->name('user.profile.show');
        Route::put('/profile',  [UsuarioController::class, 'updateProfile'])->name('user.profile.update');
        Route::put('/password', [UsuarioController::class, 'updatePassword'])->name('user.password.update');
    });

// ──────────────────────────────────────────────────────────────────────────
// Public resource routes  (no token required)
// ──────────────────────────────────────────────────────────────────────────

// Categorias — public read
Route::prefix('categorias')->group(function (): void {
    Route::get('/',     [CategoriaController::class, 'index'])->name('categorias.index');
    Route::get('/{id}', [CategoriaController::class, 'show'])->name('categorias.show');
});

// Lugares — public read (includes radius search via ?lat=&lng=&radio=)
Route::prefix('lugares')->group(function (): void {
    Route::get('/',     [LugarController::class, 'index'])->name('lugares.index');
    Route::get('/{id}', [LugarController::class, 'show'])->name('lugares.show');
    Route::get('/{id}/reviews', [ReviewController::class, 'getByLugar'])->name('lugares.reviews');
});

// Eventos — public read (includes radius search via ?lat=&lng=&radio=)
Route::prefix('eventos')->group(function (): void {
    Route::get('/',     [EventoController::class, 'index'])->name('eventos.index');
    Route::get('/{id}', [EventoController::class, 'show'])->name('eventos.show');
});

// Restaurantes — public read (includes radius search via ?lat=&lng=&radio=)
Route::prefix('restaurantes')->group(function (): void {
    Route::get('/',     [RestauranteController::class, 'index'])->name('restaurantes.index');
    Route::get('/{id}', [RestauranteController::class, 'show'])->name('restaurantes.show');
});

// Mapa — public endpoints
Route::prefix('mapa')->group(function (): void {
    Route::get('/marcadores', [MapaController::class, 'getMarkers'])->name('mapa.marcadores');
    Route::get('/cercanos',   [MapaController::class, 'getNearby'])->name('mapa.cercanos');
});

// ──────────────────────────────────────────────────────────────────────────
// Protected resource routes  (Sanctum token + Vigenere session validation)
// ──────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'vigenere.session'])->group(function (): void {

    // Favoritos
    Route::get('/favoritos', [FavoritoController::class, 'index'])->name('favoritos.index');
    Route::post('/favoritos', [FavoritoController::class, 'store'])->name('favoritos.store');
    Route::delete('/favoritos/{referencia_id}', [FavoritoController::class, 'destroy'])->name('favoritos.destroy');

    // Reviews (User mutations)
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Notificaciones — cada usuario gestiona las suyas
    Route::prefix('notificaciones')->group(function (): void {
        Route::get('/',                 [NotificacionController::class, 'index'])->name('notificaciones.index');
        Route::patch('/read-all',       [NotificacionController::class, 'markAllAsRead'])->name('notificaciones.read-all');
        Route::patch('/{id}/read',      [NotificacionController::class, 'markAsRead'])->name('notificaciones.read');
        Route::delete('/',              [NotificacionController::class, 'destroyAll'])->name('notificaciones.destroy-all');
        Route::delete('/{id}',          [NotificacionController::class, 'destroy'])->name('notificaciones.destroy');
    });
});

Route::middleware(['auth:sanctum', 'vigenere.session', 'is.admin'])->group(function (): void {
    Route::get('/admin/stats', [AdminController::class, 'stats'])->name('admin.stats');
    Route::post('/admin/backup', [AdminController::class, 'backup'])->name('admin.backup');
    Route::get('/admin/backups', [AdminController::class, 'backups'])->name('admin.backups');
    Route::get('/admin/backup/{filename}/download', [AdminController::class, 'downloadBackup'])->name('admin.backup.download');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    Route::post('/categorias',      [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{id}',  [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    Route::post('/lugares',       [LugarController::class, 'store'])->name('lugares.store');
    Route::put('/lugares/{id}',   [LugarController::class, 'update'])->name('lugares.update');
    Route::delete('/lugares/{id}', [LugarController::class, 'destroy'])->name('lugares.destroy');

    Route::post('/eventos',       [EventoController::class, 'store'])->name('eventos.store');
    Route::put('/eventos/{id}',   [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{id}', [EventoController::class, 'destroy'])->name('eventos.destroy');

    Route::post('/restaurantes',       [RestauranteController::class, 'store'])->name('restaurantes.store');
    Route::put('/restaurantes/{id}',   [RestauranteController::class, 'update'])->name('restaurantes.update');
    Route::delete('/restaurantes/{id}', [RestauranteController::class, 'destroy'])->name('restaurantes.destroy');
});
