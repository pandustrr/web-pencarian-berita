<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SystemController extends Controller
{
    private $csvController;

    public function __construct()
    {
        $this->csvController = new CsvDataController();
    }

    /**
     * Import CSV data to database
     */
    public function importCSV()
    {
        try {
            $csvData = $this->csvController->getAllData();

            if (empty($csvData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data CSV yang dapat diimport'
                ], 404);
            }

            $imported = 0;
            $skipped = 0;

            foreach ($csvData as $index => $record) {
                if (empty($record['text']) && empty($record['processed'])) {
                    $skipped++;
                    continue;
                }

                try {
                    News::create([
                        'title' => Str::limit($record['text'] ?? '', 200),
                        'original_text' => $record['text'] ?? '',
                        'translated_text' => $record['translated'] ?? $record['text'] ?? '',
                        'processed_text' => $record['processed'] ?? '',
                        'category' => $record['category'] ?? 'General',
                        'source' => $record['source'] ?? 'Berita Online',
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    $skipped++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimport {$imported} data berita ({$skipped} dilewati)",
                'imported' => $imported,
                'skipped' => $skipped
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * System information
     */
    public function systemInfo()
    {
        $dbCount = News::count();
        $csvData = $this->csvController->getAllData();
        $csvCount = count($csvData);

        $csvPath = base_path('storage/app/python_data/preprocessed_news.csv');
        $csvExists = file_exists($csvPath);

        return response()->json([
            'database' => [
                'news_count' => $dbCount,
                'status' => $dbCount > 0 ? 'active' : 'empty'
            ],
            'csv_file' => [
                'exists' => $csvExists,
                'news_count' => $csvCount,
                'path' => $csvPath
            ],
            'total_records' => $dbCount + $csvCount,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Debug information page
     */
    public function debugInfo()
    {
        $csvPath = base_path('storage/app/python_data/preprocessed_news.csv');
        $csvExists = file_exists($csvPath);
        $csvSize = $csvExists ? filesize($csvPath) : 0;

        $sampleRecords = $this->csvController->getSampleRecords(5);
        $dbCount = News::count();
        $sampleDbRecords = News::limit(5)->get();

        // Test search
        $testQueries = ['gempa', 'polisi', 'teknologi', 'ekonomi', 'kesehatan'];
        $testResults = [];

        $searchController = new FallbackSearchController();
        foreach ($testQueries as $testQuery) {
            $results = $searchController->search($testQuery, 3);
            $testResults[$testQuery] = [
                'count' => $results->count(),
                'scores' => $results->pluck('score')->toArray()
            ];
        }

        return view('debug.info', [
            'csvExists' => $csvExists,
            'csvPath' => $csvPath,
            'csvSize' => $csvSize,
            'csvCount' => count($sampleRecords) > 0 ? 1000 : 0, // Estimate
            'dbCount' => $dbCount,
            'sampleRecords' => $sampleRecords,
            'sampleDbRecords' => $sampleDbRecords,
            'testResults' => $testResults,
            'fullPath' => $csvExists ? $csvPath : 'Not found'
        ]);
    }

    /**
     * Test search endpoint
     */
    public function testSearch($query = 'gempa')
    {
        try {
            $searchController = new FallbackSearchController();
            $results = $searchController->search($query, 5);

            return response()->json([
                'query' => $query,
                'results_count' => $results->count(),
                'results' => $results->map(function($item) {
                    return [
                        'score' => round($item['score'], 4),
                        'title' => Str::limit($item['news']->original_text, 100),
                        'text' => Str::limit($item['news']->original_text, 200)
                    ];
                })->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
