<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'index'])->name('search.index');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/health', [SearchController::class, 'health']);
Route::get('/init-python', [SearchController::class, 'initPython']);

// API Routes for testing
Route::prefix('api')->group(function () {
    Route::get('/health', [SearchController::class, 'health']);
    Route::get('/init', [SearchController::class, 'initPython']);
    Route::get('/search', [SearchController::class, 'search']);
});
