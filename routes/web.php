<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Http;

// ==================== MAIN ROUTES ====================
Route::get('/', [SearchController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/news/{id}', [SearchController::class, 'show'])->name('news.show');
Route::get('/debug', [SearchController::class, 'debug'])->name('debug');

// ==================== SYSTEM ROUTES ====================
Route::get('/system-info', [SystemController::class, 'systemInfo']);
Route::get('/import-csv', [SystemController::class, 'importCSV']);

// ==================== TEST ROUTES ====================
Route::get('/test-python', function() {
    try {
        $response = Http::timeout(5)->get('http://127.0.0.1:5000/health');
        return response()->json([
            'python_connected' => $response->successful(),
            'status' => $response->successful() ? 'connected' : 'failed',
            'response' => $response->json()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'python_connected' => false,
            'status' => 'disconnected',
            'error' => $e->getMessage()
        ]);
    }
});

// Test route untuk debug search dengan semua hasil
Route::get('/test-search-all', function(Request $request) {
    try {
        $query = $request->get('query', 'gempa');
        $response = Http::timeout(30)->get('http://127.0.0.1:5000/search', [
            'query' => $query,
            'top_k' => 'all'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'query' => $query,
                'total_results' => $data['results_count'] ?? 0,
                'top_k_requested' => 'all',
                'sample_results' => array_slice($data['results'] ?? [], 0, 5)
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Python API error'
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Test CSV count
Route::get('/test-csv', function() {
    $csvPath = base_path('python_app/preprocessed_news.csv');

    $info = [
        'csv_path' => $csvPath,
        'file_exists' => file_exists($csvPath),
        'file_size' => file_exists($csvPath) ? filesize($csvPath) : 0,
        'is_readable' => file_exists($csvPath) ? is_readable($csvPath) : false,
        'absolute_path' => realpath($csvPath) ?: 'Not found'
    ];

    // Coba baca beberapa baris
    if (file_exists($csvPath)) {
        $file = fopen($csvPath, 'r');
        $header = fgetcsv($file);
        $firstRow = fgetcsv($file);
        fclose($file);

        $info['header'] = $header;
        $info['first_row_sample'] = $firstRow ? array_slice($firstRow, 0, 2) : null;
        $info['total_lines'] = count(file($csvPath));
    }

    return response()->json($info);
});
