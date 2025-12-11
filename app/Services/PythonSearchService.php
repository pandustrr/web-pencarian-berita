<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PythonSearchService
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('PYTHON_API_URL', 'http://127.0.0.1:5000');
    }

    /**
     * Health check yang sangat cepat
     */
    public function healthCheck()
    {
        try {
            // Timeout sangat singkat - 1.5 detik saja
            $response = Http::timeout(1.5)->get("{$this->baseUrl}/health");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'connected' => true,
                    'engine_initialized' => $data['engine_initialized'] ?? false,
                    'csv_exists' => $data['csv_file_exists'] ?? false,
                    'status' => $data['status'] ?? 'unknown'
                ];
            }

            return ['connected' => false, 'status' => 'http_error'];

        } catch (Exception $e) {
            // Silent fail - jangan log error untuk health check
            return ['connected' => false, 'status' => 'timeout'];
        }
    }

    /**
     * Search dengan filter relevansi dan anti-duplikat
     */
    public function search(string $query, int $topK = 10, float $minSimilarity = 0.1, bool $deduplicate = true)
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/search", [
                'query' => $query,
                'top_k' => $topK,
                'min_similarity' => $minSimilarity,
                'deduplicate' => $deduplicate ? 'true' : 'false'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Tambahkan post-processing di Laravel side
                if (!empty($data['results'])) {
                    $data['results'] = $this->postProcessResults($data['results'], $query);
                }

                return $data;
            }

            return ['results' => [], 'stats' => ['total_found' => 0]];

        } catch (Exception $e) {
            Log::warning("Python search timeout", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return ['results' => [], 'stats' => ['total_found' => 0]];
        }
    }

    /**
     * Post-process results untuk filter tambahan
     */
    private function postProcessResults(array $results, string $query): array
    {
        $processed = [];
        $queryKeywords = $this->extractKeywords($query);

        foreach ($results as $result) {
            // Hitung relevance score tambahan
            $relevanceScore = $this->calculateRelevanceScore($result, $queryKeywords);
            $result['relevance_score'] = $relevanceScore;

            // Filter berdasarkan relevance score minimum
            if ($relevanceScore >= 0.2) {
                $processed[] = $result;
            }
        }

        // Urutkan berdasarkan relevance score
        usort($processed, function($a, $b) {
            return ($b['relevance_score'] ?? 0) <=> ($a['relevance_score'] ?? 0);
        });

        return $processed;
    }

    /**
     * Ekstrak keywords dari query
     */
    private function extractKeywords(string $query): array
    {
        $stopwords = ['dan', 'atau', 'dengan', 'pada', 'untuk', 'dari', 'yang', 'di', 'ke'];
        $words = preg_split('/\s+/', strtolower($query));

        return array_filter($words, function($word) use ($stopwords) {
            return !in_array($word, $stopwords) && strlen($word) > 2;
        });
    }

    /**
     * Hitung relevance score tambahan
     */
    private function calculateRelevanceScore(array $result, array $queryKeywords): float
    {
        $text = strtolower($result['original_text'] ?? $result['text'] ?? '');
        $score = 0;

        foreach ($queryKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $score += 0.2; // Bonus untuk exact match
            }
        }

        // Tambahkan similarity score dari Python
        $score += ($result['similarity'] ?? 0) * 0.8;

        return min($score, 1.0);
    }

    public function getStats()
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/stats");
            return $response->successful() ? $response->json() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getDocument($docId)
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/document/{$docId}");
            return $response->successful() ? $response->json() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Rebuild search engine dengan parameter baru
     */
    public function rebuildEngine()
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/rebuild");
            return $response->successful() ? $response->json() : null;
        } catch (Exception $e) {
            Log::error("Failed to rebuild engine", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
