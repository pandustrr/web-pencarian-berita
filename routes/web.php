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

// Test routes
Route::get('/test-simple', function() {
    $controller = new App\Http\Controllers\CsvDataController();
    $data = $controller->getAllData();

    return response()->json([
        'total_records' => count($data),
        'first_record' => $data[0] ?? 'No data',
        'status' => 'OK'
    ]);
});

Route::get('/test-csv', function() {
    $path = base_path('storage/app/python_data/preprocessed_news.csv');

    if (!file_exists($path)) {
        return "❌ FILE NOT FOUND: " . $path;
    }

    $file = fopen($path, 'r');
    $result = "✅ CSV FILE FOUND: " . $path . "\n\n";
    $result .= "File size: " . filesize($path) . " bytes\n\n";

    for ($i = 0; $i < 5; $i++) {
        $line = fgets($file);
        if ($line === false) break;
        $result .= "Line " . ($i+1) . ": " . $line . "\n";
    }
    fclose($file);

    return response($result)->header('Content-Type', 'text/plain');
});
