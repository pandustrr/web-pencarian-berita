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
    private $documents = [];

    private $csvController;

    public function __construct()
    {
        $this->csvController = new CsvDataController();
    }

    /**
     * Main search method dengan TF-IDF + Cosine Similarity
     */
    public function search($query, $topK = 10)
    {
        Log::info("🎯 TF-IDF SEARCH STARTED", ['query' => $query, 'top_k' => $topK]);

        try {
            // Dapatkan documents untuk TF-IDF
            $this->documents = $this->csvController->getDocumentsForTfidf();

            if (empty($this->documents)) {
                Log::warning("No documents available for TF-IDF");
                return collect();
            }

            // Bangun TF-IDF matrix
            $this->buildTfidfMatrix();

            // Lakukan pencarian dengan Cosine Similarity
            $results = $this->performCosineSimilaritySearch($query, $topK);

            Log::info("✅ TF-IDF SEARCH COMPLETED", [
                'query' => $query,
                'results_found' => count($results),
                'vocabulary_size' => count($this->vocabulary)
            ]);

            return $this->formatResults($results);

        } catch (\Exception $e) {
            Log::error("❌ TF-IDF SEARCH ERROR", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Bangun TF-IDF Matrix sesuai rumus di laporan
     */
    private function buildTfidfMatrix()
    {
        // 1. Bangun vocabulary
        $this->buildVocabulary();

        // 2. Hitung TF-IDF untuk setiap document
        $this->tfidfMatrix = [];
        $documentsArray = array_values($this->documents);

        foreach ($documentsArray as $docIndex => $document) {
            $tfidfVector = $this->calculateTfIdfVector($document, $documentsArray);
            $this->tfidfMatrix[$docIndex] = $tfidfVector;
        }

        Log::debug("🏗️ TF-IDF MATRIX BUILT", [
            'vocabulary_size' => count($this->vocabulary),
            'documents_count' => count($this->documents),
            'matrix_shape' => [count($this->tfidfMatrix), count($this->featureNames)]
        ]);
    }

    /**
     * Bangun vocabulary dari semua documents
     */
    private function buildVocabulary()
    {
        $vocabulary = [];
        $docCount = count($this->documents);

        foreach ($this->documents as $document) {
            $terms = $this->preprocessText($document);
            $uniqueTerms = array_unique($terms);

            foreach ($uniqueTerms as $term) {
                if (!isset($vocabulary[$term])) {
                    $vocabulary[$term] = 0;
                }
                $vocabulary[$term]++;
            }
        }

        // Filter terms yang terlalu umum atau terlalu jarang
        $this->vocabulary = array_filter($vocabulary, function($docFreq) use ($docCount) {
            return $docFreq >= 2 && $docFreq <= $docCount * 0.8;
        });

        $this->featureNames = array_keys($this->vocabulary);
    }

    /**
     * Hitung vektor TF-IDF untuk sebuah document
     */
    private function calculateTfIdfVector($document, $allDocuments)
    {
        $terms = $this->preprocessText($document);
        $totalTerms = count($terms);
        $tfidfVector = [];

        foreach ($this->featureNames as $term) {
            // Term Frequency (TF)
            $tf = $this->calculateTermFrequency($term, $terms, $totalTerms);

            // Inverse Document Frequency (IDF)
            $idf = $this->calculateInverseDocumentFrequency($term, $allDocuments);

            // TF-IDF = TF * IDF
            $tfidfVector[] = $tf * $idf;
        }

        return $tfidfVector;
    }

    /**
     * Hitung Term Frequency (TF) sesuai rumus laporan
     */
    private function calculateTermFrequency($term, $terms, $totalTerms)
    {
        $count = 0;
        foreach ($terms as $t) {
            if ($t === $term) {
                $count++;
            }
        }
        // TF(t,d) = f_t,d / Σf_i,d
        return $totalTerms > 0 ? $count / $totalTerms : 0;
    }

    /**
     * Hitung Inverse Document Frequency (IDF) sesuai rumus laporan
     */
    private function calculateInverseDocumentFrequency($term, $allDocuments)
    {
        $docCount = count($allDocuments);
        $containingDocs = 0;

        foreach ($allDocuments as $document) {
            $docTerms = $this->preprocessText($document);
            if (in_array($term, $docTerms)) {
                $containingDocs++;
            }
        }

        // IDF(t) = log(N / df_t) + 1
        return $containingDocs > 0 ? log($docCount / $containingDocs) + 1 : 1;
    }

    /**
     * Preprocessing text sesuai metodologi laporan
     */
    private function preprocessText($text)
    {
        if (empty($text)) return [];

        // 1. Case Folding
        $text = mb_strtolower($text);

        // 2. Cleaning (hapus karakter non-alfanumerik)
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        // 3. Tokenizing
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // 4. Stopword Removal (sederhana)
        $stopwords = ['yang', 'dan', 'di', 'ke', 'dari', 'pada', 'untuk', 'dengan', 'ini', 'itu'];
        $tokens = array_filter($tokens, function($token) use ($stopwords) {
            return !in_array($token, $stopwords) && strlen($token) > 1;
        });

        // 5. Stemming (sederhana - bisa diganti dengan Sastrawi nanti)
        $tokens = array_map(function($token) {
            // Simple stemming untuk demonstrasi
            if (strpos($token, 'ber') === 0 && strlen($token) > 5) {
                return substr($token, 3);
            }
            if (strpos($token, 'ter') === 0 && strlen($token) > 5) {
                return substr($token, 3);
            }
            if (strpos($token, 'me') === 0 && strlen($token) > 4) {
                return substr($token, 2);
            }
            return $token;
        }, $tokens);

        return array_values($tokens);
    }

    /**
     * Pencarian dengan Cosine Similarity
     */
    private function performCosineSimilaritySearch($query, $topK)
    {
        // Preprocess query
        $queryTerms = $this->preprocessText($query);

        // Hitung vektor query
        $queryVector = $this->calculateQueryVector($queryTerms);

        $scores = [];

        // Hitung cosine similarity dengan setiap document
        foreach ($this->tfidfMatrix as $docIndex => $docVector) {
            $similarity = $this->calculateCosineSimilarity($queryVector, $docVector);
            if ($similarity > 0.001) { // Hanya simpan yang meaningful
                $scores[$docIndex] = $similarity;
            }
        }

        // Urutkan berdasarkan similarity score (descending)
        arsort($scores);

        Log::debug("🔍 COSINE SIMILARITY SEARCH", [
            'query_terms' => $queryTerms,
            'query_vector' => $queryVector,
            'documents_searched' => count($this->tfidfMatrix),
            'meaningful_results' => count($scores)
        ]);

        return array_slice($scores, 0, $topK, true);
    }

    /**
     * Hitung vektor query untuk TF-IDF
     */
    private function calculateQueryVector($queryTerms)
    {
        $vector = array_fill(0, count($this->featureNames), 0);

        foreach ($queryTerms as $term) {
            $index = array_search($term, $this->featureNames);
            if ($index !== false) {
                $vector[$index] += 1; // Binary representation untuk query
            }
        }

        return $vector;
    }

    /**
     * Hitung Cosine Similarity antara dua vektor
     */
    private function calculateCosineSimilarity($vecA, $vecB)
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

        // Cosine Similarity = A·B / (||A|| * ||B||)
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Format results untuk ditampilkan
     */
    private function formatResults($results)
    {
        $newsItems = collect();

        foreach ($results as $docIndex => $score) {
            $recordIndex = array_keys($this->documents)[$docIndex];

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
     * Get TF-IDF statistics untuk debugging
     */
    public function getTfidfStats()
    {
        return [
            'vocabulary_size' => count($this->vocabulary),
            'documents_count' => count($this->documents),
            'feature_names_sample' => array_slice($this->featureNames, 0, 10),
            'matrix_shape' => $this->tfidfMatrix ? [count($this->tfidfMatrix), count($this->featureNames)] : null
        ];
    }
}
