<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Http;

// Routes utama
Route::get('/', [SearchController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/news/{id}', [SearchController::class, 'show'])->name('news.show');

// Debug routes
Route::get('/debug', [SearchController::class, 'debug'])->name('debug');
Route::get('/system-info', [SystemController::class, 'systemInfo']);
Route::get('/import-csv', [SystemController::class, 'importCSV']);

// Test Python
Route::get('/test-python', function() {
    try {
        $response = Http::timeout(3)->get('http://127.0.0.1:5000/health');
        return response()->json([
            'python_connected' => $response->successful(),
            'status' => $response->successful() ? 'connected' : 'failed'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'python_connected' => false,
            'status' => 'disconnected',
            'error' => $e->getMessage()
        ]);
    }
});
