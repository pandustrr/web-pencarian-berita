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
Route::get('/info', [SearchController::class, 'index'])->name('info');
Route::get('/rebuild', [SearchController::class, 'rebuild'])->name('rebuild');

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
            'response' => $response->json(),
            'engine_features' => [
                'relevance_filtering' => true,
                'deduplication' => true,
                'min_similarity' => 0.1
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'python_connected' => false,
            'status' => 'disconnected',
            'error' => $e->getMessage()
        ]);
    }
});

// Test route dengan parameter filter
Route::get('/test-search-filtered', function(Request $request) {
    try {
        $query = $request->get('query', 'gempa');
        $minSimilarity = $request->get('min_similarity', 0.1);
        $deduplicate = $request->get('deduplicate', true);

        $response = Http::timeout(30)->get('http://127.0.0.1:5000/search', [
            'query' => $query,
            'top_k' => 10,
            'min_similarity' => $minSimilarity,
            'deduplicate' => $deduplicate ? 'true' : 'false'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'query' => $query,
                'min_similarity' => $minSimilarity,
                'deduplicate' => $deduplicate,
                'total_results' => $data['results_count'] ?? 0,
                'filtered_results' => count($data['results'] ?? []),
                'stats' => $data['stats'] ?? [],
                'sample_results' => array_slice($data['results'] ?? [], 0, 3)
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

// Test CSV dengan filter
Route::get('/test-csv-filter', function() {
    $csvPath = base_path('python_app/preprocessed_news.csv');

    $info = [
        'csv_path' => $csvPath,
        'file_exists' => file_exists($csvPath),
        'file_size' => file_exists($csvPath) ? filesize($csvPath) : 0,
        'is_readable' => file_exists($csvPath) ? is_readable($csvPath) : false,
        'total_lines' => file_exists($csvPath) ? count(file($csvPath)) : 0
    ];

    // Cek apakah CSV mengandung kolom yang diperlukan
    if (file_exists($csvPath)) {
        $file = fopen($csvPath, 'r');
        $header = fgetcsv($file);
        fclose($file);

        $requiredColumns = ['text', 'processed', 'translated'];
        $info['header'] = $header;
        $info['has_required_columns'] = count(array_intersect($requiredColumns, $header)) === count($requiredColumns);
        $info['missing_columns'] = array_diff($requiredColumns, $header);
    }

    return response()->json($info);
});

// Route untuk reset search parameters ke default
Route::get('/search/reset', function(Request $request) {
    return redirect()->route('search', [
        'query' => $request->get('query', ''),
        'top_k' => 10,
        'min_similarity' => 0.1,
        'deduplicate' => true
    ]);
});
