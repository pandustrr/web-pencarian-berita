<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class FallbackSearchController extends Controller
{
    private $csvController;

    public function __construct()
    {
        $this->csvController = new CsvDataController();
    }

    /**
     * Fallback search when TF-IDF fails
     */
    public function search($query, $topK = 10)
    {
        $results = collect();

        // Try database first
        $dbResults = $this->searchInDatabase($query, $topK);
        if ($dbResults->isNotEmpty()) {
            return $dbResults;
        }

        // Try CSV search
        $csvResults = $this->searchInCSV($query, $topK);
        if ($csvResults->isNotEmpty()) {
            return $csvResults;
        }

        return $results;
    }

    /**
     * Search in database with simple matching
     */
    private function searchInDatabase($query, $topK)
    {
        $results = News::where(function($q) use ($query) {
                $q->where('original_text', 'like', "%{$query}%")
                  ->orWhere('processed_text', 'like', "%{$query}%")
                  ->orWhere('title', 'like', "%{$query}%");
            })
            ->limit($topK)
            ->get()
            ->map(function($news) use ($query) {
                $score = $this->calculateSimpleScore($news->original_text, $query);
                return [
                    'news' => $news,
                    'score' => $score
                ];
            });

        return $results->sortByDesc('score')->values();
    }

    /**
     * Search in CSV with simple matching
     */
    private function searchInCSV($query, $topK)
    {
        $csvData = $this->csvController->getAllData();
        $matches = [];

        foreach ($csvData as $index => $record) {
            $text = $record['text'] ?? '';
            $score = $this->calculateSimpleScore($text, $query);

            if ($score > 0.1) {
                $matches[] = [
                    'news' => $this->csvController->getNewsById($index),
                    'score' => $score
                ];
            }
        }

        usort($matches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return collect(array_slice($matches, 0, $topK));
    }

    /**
     * Calculate simple relevance score
     */
    private function calculateSimpleScore($text, $query)
    {
        if (empty($text)) return 0;

        $text = strtolower($text);
        $query = strtolower($query);
        $queryWords = $this->tokenizeText($query);

        $score = 0;
        foreach ($queryWords as $word) {
            if (strlen($word) > 1) {
                $count = substr_count($text, $word);
                $score += $count * (strlen($word) / 10);
            }
        }

        return min($score / 5, 0.95);
    }

    /**
     * Simple tokenization
     */
    private function tokenizeText($text)
    {
        if (empty($text)) return [];

        $text = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $text);
        $text = mb_strtolower($text);

        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($tokens, function($token) {
            return strlen($token) >= 2;
        });
    }
}
