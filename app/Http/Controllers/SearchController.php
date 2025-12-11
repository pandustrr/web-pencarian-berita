<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\PythonSearchService;
use App\Models\News;

class SearchController extends Controller
{
    private $pythonUrl = 'http://127.0.0.1:5000';
    private $pythonService;

    public function __construct()
    {
        $this->pythonService = new PythonSearchService();
    }

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
            'last_updated' => now()->format('d M Y'),
            'search_params' => [
                'default_min_similarity' => 0.1,
                'default_top_k' => 10,
                'deduplication_enabled' => true
            ]
        ];

        // Cek CSV file
        $csvPath = base_path('python_app/preprocessed_news.csv');
        $stats['csv_path'] = $csvPath;
        $stats['csv_exists'] = file_exists($csvPath);

        // Cek Python connection dan stats
        try {
            $pythonStats = $this->pythonService->getStats();
            if ($pythonStats && isset($pythonStats['stats'])) {
                $stats['total_documents'] = $pythonStats['stats']['total_documents'] ?? 0;
                $stats['vocabulary_size'] = $pythonStats['stats']['vocabulary_size'] ?? 0;
                $stats['python_connected'] = true;
                $stats['search_params']['default_min_similarity'] = $pythonStats['stats']['default_min_similarity'] ?? 0.1;
            }
        } catch (\Exception $e) {
            // Fallback: hitung manual dari CSV jika Python tidak connect
            if ($stats['csv_exists']) {
                $stats['total_documents'] = $this->countCSVLines($csvPath) - 1;
            }
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
     * Handle search dengan auto-filtering berdasarkan specificity
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:255',
            'top_k' => 'sometimes|string'
        ]);

        $query = $validated['query'];
        $topK = $validated['top_k'] ?? 10;
        $deduplicate = true;

        // Calculate auto threshold based on query specificity
        $autoThreshold = $this->calculateAutoThreshold($query);

        $results = [];
        $algorithm = 'TF-IDF + Auto-Filter Relevansi';
        $engine = 'python';
        $searchStats = [
            'total_found' => 0,
            'filtered_out' => 0,
            'average_similarity' => 0
        ];

        // Cari dengan Python engine
        try {
            // Selalu gunakan auto-threshold berdasarkan specificity
            $autoThreshold = $this->calculateAutoThreshold($query);
            $thresholdToUse = $autoThreshold;
            $limitToUse = ($topK === 'all') ? 500 : 100;

            $searchResult = $this->pythonService->search($query, $limitToUse, $thresholdToUse, $deduplicate);

            if (!empty($searchResult['results'])) {
                $results = $searchResult['results'];
                $searchStats = $searchResult['stats'] ?? $searchStats;
                $algorithm = 'Python TF-IDF + Cosine Similarity (Auto-filtered)';

                // Filter hasil berdasarkan top_k (jika bukan 'all')
                if ($topK !== 'all' && (int)$topK > 0) {
                    $results = array_slice($results, 0, (int)$topK);
                }

                Log::info("Search dengan auto-filter", [
                    'query' => $query,
                    'auto_threshold' => $autoThreshold,
                    'results_count' => count($results),
                    'deduplicate' => $deduplicate
                ]);
            }
        } catch (\Exception $e) {
            // Fallback ke simple search
            $results = $this->simpleSearch($query, $topK);
            $algorithm = 'Simple Matching';
            $engine = 'php';

            Log::warning("Python search failed, using fallback", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
        }

        // Hitung stats tambahan
        $searchStats['average_similarity'] = $this->calculateAverageSimilarity($results);
        $searchStats['total_results'] = count($results);
        $searchStats['query_length'] = strlen($query);
        $searchStats['keyword_count'] = count(array_filter(explode(' ', trim($query))));

        // Get system stats
        $stats = $this->getSystemStats();

        return view('search.results', [
            'query' => $query,
            'results' => $results,
            'algorithm' => $algorithm,
            'engine' => $engine,
            'stats' => $stats,
            'searchStats' => $searchStats,
            'topK' => $topK,
            'autoThreshold' => $autoThreshold,
            'deduplicate' => $deduplicate
        ]);
    }

    /**
     * Calculate auto threshold based on query specificity
     * Query pendek/umum (1 kata) = threshold RENDAH untuk hasil BANYAK
     * Query panjang/spesifik (3+ kata) = threshold TINGGI untuk hasil SEDIKIT
     */
    private function calculateAutoThreshold($query)
    {
        $keywords = array_filter(explode(' ', strtolower(trim($query))));
        $keywordCount = count($keywords);

        // Hitung rata-rata panjang keyword
        $avgKeywordLength = array_sum(array_map('strlen', $keywords)) / max($keywordCount, 1);

        // Logika auto-threshold - semakin spesifik query, semakin tinggi threshold
        if ($keywordCount === 1) {
            return 0.05;  //  untuk 1 kata
        } elseif ($keywordCount === 2) {
            return 0.10;  //  untuk 2 kata
        } elseif ($keywordCount === 3) {
            return 0.15;  //  untuk 3 kata
        } else {
            return 0.35;  //  untuk 4+ kata
        }
    }

    /**
     * Simple fallback search
     */
    private function simpleSearch($query, $topK)
    {
        $limit = ($topK === 'all') ? 1000 : (int)($topK ?? 10);

        // Fallback: search di database dengan LIKE query
        $results = News::where(function($q) use ($query) {
            $q->whereRaw("LOWER(original_text) LIKE ?", ['%' . strtolower($query) . '%'])
              ->orWhereRaw("LOWER(title) LIKE ?", ['%' . strtolower($query) . '%'])
              ->orWhereRaw("LOWER(translated_text) LIKE ?", ['%' . strtolower($query) . '%']);
        })
        ->limit($limit)
        ->get()
        ->map(function($news) {
            return [
                'id' => $news->id,
                'title' => $news->title ?? 'Berita #' . $news->id,
                'text' => $news->original_text ?? '',
                'similarity' => 0.5, // Default similarity untuk fallback
                'category' => $news->category ?? '',
                'source' => $news->source ?? '',
                'processed' => $news->processed_text ?? ''
            ];
        })
        ->toArray();

        return $results;
    }

    /**
     * Calculate average similarity dari results
     */
    private function calculateAverageSimilarity(array $results): float
    {
        if (empty($results)) {
            return 0;
        }

        $total = 0;
        $count = 0;

        foreach ($results as $result) {
            if (isset($result['similarity'])) {
                $total += $result['similarity'];
                $count++;
            }
        }

        return $count > 0 ? $total / $count : 0;
    }

    /**
     * Show news detail
     */
    public function show($id)
    {
        // Langsung cari di database (lebih cepat)
        $news = News::find($id);
        if ($news) {
            return view('search.detail', compact('news'));
        }

        abort(404, 'Berita tidak ditemukan');
    }

    /**
     * Get document from CSV fallback
    /**
     * Debug info - dengan stats lengkap
     */
    public function debug()
    {
        $stats = $this->getSystemStats();

        // Test Python connection
        $pythonHealth = $this->pythonService->healthCheck();

        // Debug info tambahan
        $debugInfo = [
            'csv_absolute_path' => $stats['csv_path'],
            'csv_file_exists' => file_exists($stats['csv_path']),
            'csv_file_size' => file_exists($stats['csv_path']) ? filesize($stats['csv_path']) : 0,
            'python_health' => $pythonHealth,
            'current_time' => now()->toISOString(),
            'search_params_default' => $stats['search_params']
        ];

        return view('debug.info', [
            'stats' => $stats,
            'debugInfo' => $debugInfo
        ]);
    }

    /**
     * Rebuild search engine
     */
    public function rebuild()
    {
        try {
            $result = $this->pythonService->rebuildEngine();

            if ($result) {
                return back()->with('success', 'Search engine berhasil di-rebuild');
            } else {
                return back()->with('error', 'Gagal rebuild search engine');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
