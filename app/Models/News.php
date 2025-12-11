<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title', 'original_text', 'translated_text', 'processed_text', 'category', 'source'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope untuk pencarian dengan relevansi
     */
    public function scopeRelevantTo($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('original_text', 'LIKE', "%{$searchTerm}%")
              ->orWhere('translated_text', 'LIKE', "%{$searchTerm}%")
              ->orWhere('processed_text', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Hitung similarity score sederhana
     */
    public function calculateSimilarity($query)
    {
        $text = strtolower($this->original_text . ' ' . $this->processed_text);
        $query = strtolower($query);

        $textWords = explode(' ', $text);
        $queryWords = explode(' ', $query);

        $matches = 0;
        foreach ($queryWords as $word) {
            if (strlen($word) > 2 && in_array($word, $textWords)) {
                $matches++;
            }
        }

        return $matches / max(count($queryWords), 1);
    }

    /**
     * Check if document is duplicate of another
     */
    public function isDuplicateOf(News $otherNews, $threshold = 0.8)
    {
        // Simple duplicate detection based on text similarity
        $similarity = 0;

        // Check title similarity
        if ($this->title && $otherNews->title) {
            similar_text(strtolower($this->title), strtolower($otherNews->title), $titleSimilarity);
            $similarity += $titleSimilarity * 0.4;
        }

        // Check processed text similarity
        if ($this->processed_text && $otherNews->processed_text) {
            similar_text($this->processed_text, $otherNews->processed_text, $textSimilarity);
            $similarity += $textSimilarity * 0.6;
        }

        return $similarity >= $threshold;
    }

    /**
     * Get relevance level based on similarity score
     */
    public function getRelevanceLevel($similarityScore)
    {
        if ($similarityScore >= 0.7) return 'Sangat Relevan';
        if ($similarityScore >= 0.5) return 'Relevan';
        if ($similarityScore >= 0.3) return 'Cukup Relevan';
        if ($similarityScore >= 0.1) return 'Sedikit Relevan';
        return 'Tidak Relevan';
    }
}
