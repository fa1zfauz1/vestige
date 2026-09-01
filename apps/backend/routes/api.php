<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:auth'])->prefix('auth')->group(function () {
    Route::get('google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('appeal', [AuthController::class, 'appeal']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('profile', [ProfileController::class, 'update']);
    Route::post('profile/remove-avatar', [ProfileController::class, 'removeAvatar']);
});

Route::middleware(['auth:sanctum', 'approved', 'throttle:api'])->group(function () {
    Route::get('approved-users', [AuthController::class, 'approvedUsers']);
    Route::apiResource('photos', PhotoController::class);
    Route::post('photos/{photo}/rotate', [PhotoController::class, 'rotate']);
    Route::post('photos/{photo}/share', [PhotoController::class, 'share']);

    Route::prefix('admin')->middleware('can:admin')->group(function () {
        Route::get('pending-users', [AdminController::class, 'pendingUsers']);
        Route::post('users/{user}/approve', [AdminController::class, 'approve']);
        Route::post('users/{user}/reject', [AdminController::class, 'reject']);
        Route::post('users/{user}/suspend', [AdminController::class, 'suspend']);
        Route::post('users/{user}/role', [AdminController::class, 'updateRole']);
        Route::delete('users/{user}', [AdminController::class, 'destroy']);
        Route::get('users', [AdminController::class, 'users']);
        Route::get('activity-logs', [AdminController::class, 'activityLogs']);
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

