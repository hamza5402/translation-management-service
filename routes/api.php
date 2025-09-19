<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TranslationKeyController;
use App\Http\Controllers\Api\TranslationExportController;
use App\Http\Controllers\Api\LocaleController;

Route::prefix('v1')->group(function () {

    Route::apiResource('keys', TranslationKeyController::class); 
    Route::get('translations/export', [TranslationExportController::class, 'index']); 
    Route::apiResource('locales', LocaleController::class)->only(['index','store']);
});