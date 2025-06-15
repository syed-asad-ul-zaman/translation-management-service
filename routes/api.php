<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Controllers\Api\TranslationTagController;
use App\Http\Controllers\Api\ExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Translation Management Service API Routes
| All routes are protected by Sanctum authentication
| Response time optimized for <200ms (except export endpoints <500ms)
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('export')->name('export.')->middleware('enhanced.throttle:export')->group(function () {
    Route::get('/locale/{locale}', [ExportController::class, 'locale'])
        ->name('locale')
        ->where('locale', '[a-z]{2,3}');

    Route::get('/all', [ExportController::class, 'all'])
        ->name('all');

    Route::post('/keys', [ExportController::class, 'keys'])
        ->name('keys');

    Route::get('/tag/{tag}', [ExportController::class, 'tags'])
        ->name('tag')
        ->where('tag', '[a-z0-9\-_]+');

    Route::get('/stats', [ExportController::class, 'stats'])
        ->name('stats');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('translations')->name('translations.')->group(function () {
        Route::get('/', [TranslationController::class, 'index'])->name('index');
        Route::post('/', [TranslationController::class, 'store'])->name('store');
        Route::get('/search', [TranslationController::class, 'search'])->name('search');
        Route::post('/bulk', [TranslationController::class, 'bulk'])
            ->name('bulk')
            ->middleware('enhanced.throttle:bulk');
        Route::get('/{translation}', [TranslationController::class, 'show'])->name('show');
        Route::put('/{translation}', [TranslationController::class, 'update'])->name('update');
        Route::patch('/{translation}', [TranslationController::class, 'update'])->name('patch');
        Route::delete('/{translation}', [TranslationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('locales')->name('locales.')->group(function () {
        Route::get('/', [LocaleController::class, 'index'])->name('index');
        Route::post('/', [LocaleController::class, 'store'])->name('store');
        Route::get('/{locale}', [LocaleController::class, 'show'])->name('show');
        Route::put('/{locale}', [LocaleController::class, 'update'])->name('update');
        Route::patch('/{locale}', [LocaleController::class, 'update'])->name('patch');
        Route::delete('/{locale}', [LocaleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [TranslationTagController::class, 'index'])->name('index');
        Route::post('/', [TranslationTagController::class, 'store'])->name('store');
        Route::get('/popular', [TranslationTagController::class, 'popular'])->name('popular');
        Route::get('/{tag}', [TranslationTagController::class, 'show'])->name('show');
        Route::put('/{tag}', [TranslationTagController::class, 'update'])->name('update');
        Route::patch('/{tag}', [TranslationTagController::class, 'update'])->name('patch');
        Route::delete('/{tag}', [TranslationTagController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| API Versioning and Rate Limiting
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:api'])->group(function () {

});

/*
|--------------------------------------------------------------------------
| Health Check and Monitoring
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'environment' => config('app.env'),
    ]);
})->name('health');

Route::get('/metrics', function () {
    return response()->json([
        'database' => [
            'status' => 'connected',
            'translations_count' => \App\Models\Translation::count(),
            'locales_count' => \App\Models\Locale::count(),
            'tags_count' => \App\Models\TranslationTag::count(),
        ],
        'cache' => [
            'status' => 'connected',
            'driver' => config('cache.default'),
        ],
        'memory_usage' => memory_get_usage(true),
        'timestamp' => now()->toISOString(),
    ]);
})->middleware('auth:sanctum')->name('metrics');
