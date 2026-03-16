<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
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
