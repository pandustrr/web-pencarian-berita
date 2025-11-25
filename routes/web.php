<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SystemController;

// Main search routes
Route::get('/', [SearchController::class, 'index'])->name('search.index');
Route::get('/search', [SearchController::class, 'search'])->name('search.execute');
Route::get('/news/{id}', [SearchController::class, 'show'])->name('search.show');

// System routes
Route::get('/import-csv', [SystemController::class, 'importCSV'])->name('csv.import');
Route::get('/system-info', [SystemController::class, 'systemInfo'])->name('system.info');
Route::get('/debug', [SystemController::class, 'debugInfo'])->name('debug.info');
Route::get('/test-search/{query?}', [SystemController::class, 'testSearch'])->name('test.search');
