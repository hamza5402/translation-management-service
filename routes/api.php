<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TranslationKeyController;
use App\Http\Controllers\Api\TranslationExportController;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Controllers\Api\Auth\AuthController;


Route::prefix('v1')
    ->middleware([
        ForceJsonResponse::class,
    ])
    ->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::prefix('v1')
    ->middleware([
        'auth:sanctum',
        ForceJsonResponse::class,
    ])
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // Locales
        Route::get('/locales', [LocaleController::class, 'index']);
        Route::post('/locales', [LocaleController::class, 'store']);

        // Keys
        Route::get('/keys', [TranslationKeyController::class, 'index']);
        Route::post('/keys', [TranslationKeyController::class, 'store']);
        Route::get('/keys/{id}', [TranslationKeyController::class, 'show']);
        Route::put('/keys/{id}', [TranslationKeyController::class, 'update']);
      

        // Export
        Route::get('/translations/export', [TranslationExportController::class, 'export']);
    });