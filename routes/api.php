<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TranslationKeyController;
use App\Http\Controllers\Api\TranslationExportController;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Middleware\ForceJsonResponse;

Route::prefix('v1')
->middleware([ForceJsonResponse::class])
->group(function () {

    Route::apiResource('keys', TranslationKeyController::class); 
    Route::get('translations/export', [TranslationExportController::class, 'index']); 
    Route::apiResource('locales', LocaleController::class)->only(['index','store']);
});