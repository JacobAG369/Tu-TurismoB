<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\LugarController;
use App\Http\Controllers\Api\V1\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Tu-Turismo
|--------------------------------------------------------------------------
| All routes here are prefixed with /api/v1 (configured in bootstrap/app.php)
| and protected by the "api" middleware group.
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
});

// ──────────────────────────────────────────────────────────────────────────
// Protected auth routes  (Sanctum token + Vigenere session validation)
// ──────────────────────────────────────────────────────────────────────────
Route::prefix('auth')
    ->middleware(['auth:sanctum', 'vigenere.session'])
    ->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me',     [AuthController::class, 'me'])->name('auth.me');
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
});

// ──────────────────────────────────────────────────────────────────────────
// Protected resource routes  (Sanctum token + Vigenere session validation)
// ──────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'vigenere.session'])->group(function (): void {

    // Categorias — admin mutations
    Route::post('/categorias',      [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{id}',  [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    // Lugares — admin mutations
    Route::post('/lugares',       [LugarController::class, 'store'])->name('lugares.store');
    Route::put('/lugares/{id}',   [LugarController::class, 'update'])->name('lugares.update');
    Route::delete('/lugares/{id}', [LugarController::class, 'destroy'])->name('lugares.destroy');
});
