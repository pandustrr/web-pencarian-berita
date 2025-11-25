<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TfidfSearchController extends Controller
{
    private $tfidfMatrix = null;
    private $featureNames = [];
    private $vocabulary = [];

    private $csvController;
    private $fallbackController;

    public function __construct()
    {
        $this->csvController = new CsvDataController();
        $this->fallbackController = new FallbackSearchController();
    }

    /**
     * Main search method
     */
    public function search($query, $topK = 10)
    {
        $results = collect();

        // Strategy 1: Try TF-IDF search
        $tfidfResults = $this->searchWithTfidf($query, $topK);
        if ($tfidfResults->isNotEmpty()) {
            $results = $tfidfResults;
            Log::info("✅ TF-IDF SEARCH SUCCESS", ['results' => $results->count()]);
        }

        // Strategy 2: Try fallback search if TF-IDF fails
        if ($results->isEmpty()) {
            $fallbackResults = $this->fallbackController->search($query, $topK);
            if ($fallbackResults->isNotEmpty()) {
                $results = $fallbackResults;
                Log::info("✅ FALLBACK SEARCH SUCCESS", ['results' => $results->count()]);
            }
        }

        return $results;
    }

    /**
     * Search using TF-IDF algorithm
     */
    private function searchWithTfidf($query, $topK)
    {
        $documents = $this->csvController->getDocumentsForTfidf();

        if (empty($documents)) {
            Log::warning("No documents available for TF-IDF search");
            return collect();
        }

        Log::debug("🏗️ BUILDING TF-IDF MATRIX", [
            'documents_count' => count($documents),
            'query' => $query
        ]);

        $this->buildTfidfMatrix(array_values($documents));
        $results = $this->performSearch($query, $topK);

        return $this->formatResults($results, $documents);
    }

    /**
     * Build TF-IDF matrix from documents
     */
    private function buildTfidfMatrix($documents)
    {
        $this->vocabulary = $this->buildVocabulary($documents);
        $this->featureNames = array_keys($this->vocabulary);

        $this->tfidfMatrix = [];
        foreach ($documents as $docIndex => $document) {
            $tfidfVector = $this->calculateTfIdf($document, $this->vocabulary, $documents);
            $this->tfidfMatrix[$docIndex] = $tfidfVector;
        }

        Log::debug("TF-IDF MATRIX BUILT", [
            'vocabulary_size' => count($this->vocabulary),
            'documents_count' => count($documents)
        ]);
    }

    /**
     * Build vocabulary from documents
     */
    private function buildVocabulary($documents)
    {
        $vocabulary = [];
        $docCount = count($documents);

        foreach ($documents as $document) {
            $terms = $this->tokenizeText($document);
            $uniqueTerms = array_unique($terms);

            foreach ($uniqueTerms as $term) {
                if (!isset($vocabulary[$term])) {
                    $vocabulary[$term] = 0;
                }
                $vocabulary[$term]++;
            }
        }

        // Filter terms
        $vocabulary = array_filter($vocabulary, function($count) use ($docCount) {
            return $count >= 1 && $count <= $docCount * 0.95;
        });

        return $vocabulary;
    }

    /**
     * Calculate TF-IDF for a document
     */
    private function calculateTfIdf($document, $vocabulary, $documents)
    {
        $terms = $this->tokenizeText($document);
        $termCount = count($terms);
        $tfidfVector = [];

        foreach (array_keys($vocabulary) as $term) {
            $tf = $this->termFrequency($term, $terms, $termCount);
            $idf = $this->inverseDocumentFrequency($term, $documents);
            $tfidfVector[] = $tf * $idf;
        }

        return $tfidfVector;
    }

    /**
     * Calculate term frequency
     */
    private function termFrequency($term, $terms, $totalTerms)
    {
        $count = 0;
        foreach ($terms as $t) {
            if ($t === $term) {
                $count++;
            }
        }
        return $totalTerms > 0 ? $count / $totalTerms : 0;
    }

    /**
     * Calculate inverse document frequency
     */
    private function inverseDocumentFrequency($term, $documents)
    {
        $docCount = count($documents);
        $containingDocs = 0;

        foreach ($documents as $document) {
            if (stripos($document, $term) !== false) {
                $containingDocs++;
            }
        }

        return $containingDocs > 0 ? log($docCount / $containingDocs) + 1 : 1;
    }

    /**
     * Perform search using cosine similarity
     */
    private function performSearch($query, $topK)
    {
        if ($this->tfidfMatrix === null || empty($this->featureNames)) {
            return [];
        }

        $queryVector = $this->calculateQueryVector($query);
        $scores = [];

        foreach ($this->tfidfMatrix as $docIndex => $docVector) {
            $similarity = $this->cosineSimilarity($queryVector, $docVector);
            if ($similarity > 0.001) {
                $scores[$docIndex] = $similarity;
            }
        }

        arsort($scores);

        Log::debug("SEARCH PERFORMED", [
            'query' => $query,
            'documents_searched' => count($this->tfidfMatrix),
            'meaningful_results' => count($scores),
            'top_score' => !empty($scores) ? max($scores) : 0
        ]);

        return array_slice($scores, 0, $topK, true);
    }

    /**
     * Calculate query vector
     */
    private function calculateQueryVector($query)
    {
        $queryTerms = $this->tokenizeText($query);
        $vector = array_fill(0, count($this->featureNames), 0);

        foreach ($queryTerms as $term) {
            $index = array_search($term, $this->featureNames);
            if ($index !== false) {
                $vector[$index] += 1;
            }
        }

        return $vector;
    }

    /**
     * Calculate cosine similarity
     */
    private function cosineSimilarity($vecA, $vecB)
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($vecA); $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Tokenize text for Indonesian language
     */
    private function tokenizeText($text)
    {
        if (empty($text)) return [];

        $text = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $text);
        $text = mb_strtolower($text);

        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $tokens = array_filter($tokens, function($token) {
            if (strlen($token) >= 2) return true;

            $shortWords = ['ai', 'pc', 'tv', 'cd', 'dvd', 'us', 'uk', 'id', 'no', 'ok', 'm', 'kg'];
            return in_array($token, $shortWords);
        });

        return $tokens;
    }

    /**
     * Format search results
     */
    private function formatResults($results, $documents)
    {
        $newsItems = collect();

        foreach ($results as $docIndex => $score) {
            if ($score < 0.01) continue;

            $recordIndex = array_keys($documents)[$docIndex];

            try {
                $news = $this->csvController->getNewsById($recordIndex);

                $newsItems->push([
                    'news' => $news,
                    'score' => $score
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to get news by ID", [
                    'index' => $recordIndex,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $newsItems;
    }

    /**
     * Get search statistics for debugging
     */
    public function getSearchStats()
    {
        return [
            'vocabulary_size' => count($this->vocabulary),
            'matrix_size' => $this->tfidfMatrix ? count($this->tfidfMatrix) : 0,
            'feature_names_sample' => array_slice($this->featureNames, 0, 10)
        ];
    }
}
