<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\Auth\RegisterController;
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
});
