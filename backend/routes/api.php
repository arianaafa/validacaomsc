<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\MscRuleController;
use App\Http\Controllers\MscUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'database' => config('database.default'),
    ]);
});

Route::post('/login', LoginController::class);
Route::post('/register', RegisterController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', MeController::class);
    Route::post('/logout', LogoutController::class);
    Route::post('/refresh', RefreshTokenController::class);

    Route::get('/msc/uploads', [MscUploadController::class, 'index']);
    Route::get('/msc/uploads/{upload}', [MscUploadController::class, 'show']);
    Route::post('/msc/uploads', [MscUploadController::class, 'store']);

    Route::get('/v1/msc-rules', [MscRuleController::class, 'index']);
});
