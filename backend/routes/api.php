<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Tu-Turismo
|--------------------------------------------------------------------------
| All routes here are prefixed with /api/v1 (configured in bootstrap/app.php)
| and protected by the "api" middleware group.
*/

Route::get('/', static function (): \Illuminate\Http\JsonResponse {
    return response()->json([
        'status'  => 'ok',
        'api'     => 'Tu-Turismo',
        'version' => 'v1',
    ]);
});
