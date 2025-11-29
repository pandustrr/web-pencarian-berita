<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    private $pythonUrl = 'http://127.0.0.1:5000';

    /**
     * Homepage - dengan stats
     */
    public function index()
    {
        $stats = $this->getSystemStats();

        return view('search.index', [
            'stats' => $stats
        ]);
    }

    /**
     * Get system statistics
     */
    private function getSystemStats()
    {
        $stats = [
            'total_documents' => 0,
            'python_connected' => false,
            'csv_exists' => false,
            'csv_path' => '',
            'vocabulary_size' => 0,
            'last_updated' => now()->format('d M Y')
        ];

        // Cek CSV file - PATH YANG BENAR
        $csvPath = base_path('python_app/preprocessed_news.csv');
        $stats['csv_path'] = $csvPath;
        $stats['csv_exists'] = file_exists($csvPath);

        // Cek Python connection dan stats
        try {
            $response = Http::timeout(3)->get("{$this->pythonUrl}/stats");
            if ($response->successful()) {
                $pythonStats = $response->json();
                if (isset($pythonStats['stats'])) {
                    $stats['total_documents'] = $pythonStats['stats']['total_documents'] ?? 0;
                    $stats['vocabulary_size'] = $pythonStats['stats']['vocabulary_size'] ?? 0;
                    $stats['python_connected'] = true;
                }
            }
        } catch (\Exception $e) {
            // Fallback: hitung manual dari CSV jika Python tidak connect
            if ($stats['csv_exists']) {
                $stats['total_documents'] = $this->countCSVLines($csvPath) - 1; // minus header
            }
        }

        // Jika Python connected tapi total_documents masih 0, ambil dari CSV
        if ($stats['python_connected'] && $stats['total_documents'] == 0 && $stats['csv_exists']) {
            $stats['total_documents'] = $this->countCSVLines($csvPath) - 1;
        }

        return $stats;
    }

    /**
     * Count lines in CSV (simple method)
     */
    private function countCSVLines($filePath)
    {
        try {
            $file = fopen($filePath, 'r');
            $count = 0;
            while (fgets($file) !== false) {
                $count++;
            }
            fclose($file);
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Handle search - dengan stats
     */
    public function search(Request $request)
    {
        $query = $request->input('query', '');
        $topK = $request->input('top_k', 10);

        if (empty($query)) {
            return back()->with('error', 'Masukkan kata kunci pencarian');
        }

        $results = [];
        $algorithm = 'TF-IDF';
        $engine = 'python';

        // Coba Python dulu
        try {
            $response = Http::timeout(10)->get("{$this->pythonUrl}/search", [
                'query' => $query,
                'top_k' => $topK
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    $results = $data['results'];
                    $algorithm = 'Python TF-IDF';
                }
            }
        } catch (\Exception $e) {
            // Fallback ke CSV sederhana
            $results = $this->simpleSearch($query, $topK);
            $algorithm = 'Simple Matching';
            $engine = 'php';
        }

        // Get stats untuk results page
        $stats = $this->getSystemStats();

        return view('search.results', [
            'query' => $query,
            'results' => $results,
            'algorithm' => $algorithm,
            'engine' => $engine,
            'stats' => $stats
        ]);
    }

    /**
     * Simple fallback search
     */
    private function simpleSearch($query, $topK)
    {
        $results = [];
        $csvPath = base_path('python_app/preprocessed_news.csv');

        if (!file_exists($csvPath)) {
            return $results;
        }

        try {
            $file = fopen($csvPath, 'r');
            $header = fgetcsv($file);

            $count = 0;
            $queryLower = strtolower($query);

            while (($row = fgetcsv($file)) !== FALSE && $count < 100) {
                if (count($header) === count($row)) {
                    $record = array_combine($header, $row);
                    $text = strtolower($record['text'] ?? '');

                    if (strpos($text, $queryLower) !== false) {
                        $results[] = [
                            'index' => $count,
                            'score' => 0.5,
                            'original_text' => $record['text'] ?? '',
                            'processed_text' => $record['processed'] ?? '',
                            'category' => $record['category'] ?? 'General',
                            'source' => $record['source'] ?? 'CSV'
                        ];
                        $count++;

                        if ($count >= $topK) break;
                    }
                }
            }
            fclose($file);
        } catch (\Exception $e) {
            // Silent fail
        }

        return $results;
    }

    /**
     * Show news detail
     */
    public function show($id)
    {
        try {
            $response = Http::timeout(5)->get("{$this->pythonUrl}/document/{$id}");
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['document'])) {
                    $news = (object) $data['document'];
                    return view('search.detail', compact('news'));
                }
            }
        } catch (\Exception $e) {
            $news = (object) [
                'id' => $id,
                'title' => 'Berita #' . $id,
                'original_text' => 'Detail berita tidak tersedia.',
                'category' => 'General',
                'source' => 'System'
            ];
            return view('search.detail', compact('news'));
        }

        abort(404, 'Berita tidak ditemukan');
    }

    /**
     * Debug info - dengan stats lengkap
     */
    public function debug()
    {
        $stats = $this->getSystemStats();

        // Debug info tambahan
        $debugInfo = [
            'csv_absolute_path' => $stats['csv_path'],
            'csv_file_exists' => file_exists($stats['csv_path']),
            'csv_file_size' => file_exists($stats['csv_path']) ? filesize($stats['csv_path']) : 0,
            'python_url' => $this->pythonUrl,
            'current_time' => now()->toISOString()
        ];

        return view('debug.info', [
            'stats' => $stats,
            'debugInfo' => $debugInfo
        ]);
    }
}
