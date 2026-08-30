<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PhotoController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('auth')->group(function () {
    Route::get('google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('photos', PhotoController::class);

    Route::prefix('admin')->middleware('can:admin')->group(function () {
        Route::get('pending-users', [AdminController::class, 'pendingUsers']);
        Route::post('users/{user}/approve', [AdminController::class, 'approve']);
        Route::post('users/{user}/reject', [AdminController::class, 'reject']);
        Route::post('users/{user}/suspend', [AdminController::class, 'suspend']);
        Route::get('users', [AdminController::class, 'users']);
    });
});

Route::get("debug-env", function () {
    return [
        "env_GOOGLE_CLIENT_ID" => getenv("GOOGLE_CLIENT_ID"),
        "config_client_id" => config("services.google.client_id"),
        "config_redirect" => config("services.google.redirect"),
        "_ENV" => $_ENV["GOOGLE_CLIENT_ID"] ?? "NOT SET",
        "server" => $_SERVER["GOOGLE_CLIENT_ID"] ?? "NOT SET",
    ];
});

