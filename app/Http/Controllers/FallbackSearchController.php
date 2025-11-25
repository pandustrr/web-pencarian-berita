<?php

namespace App\Http\Controllers;

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
     * Simple search - TEXT MATCHING ONLY
     */
    public function search($query, $topK = 10)
    {
        $csvData = $this->csvController->getAllData();
        $matches = [];
        $queryLower = strtolower($query);

        foreach ($csvData as $index => $record) {
            $text = strtolower($record['text'] ?? '');

            // SIMPLE MATCHING - cek apakah query ada di text
            if (strpos($text, $queryLower) !== false) {
                $score = $this->calculateSimpleScore($text, $queryLower);
                $matches[] = [
                    'news' => (object) [
                        'id' => $index,
                        'title' => Str::limit($record['text'] ?? '', 100),
                        'original_text' => $record['text'] ?? '',
                        'translated_text' => $record['translated'] ?? $record['text'] ?? '',
                        'processed_text' => $record['processed'] ?? '',
                        'category' => $record['category'] ?? 'General',
                        'source' => $record['source'] ?? 'CSV',
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    'score' => $score
                ];
            }

            // Stop jika sudah cukup results
            if (count($matches) >= $topK * 2) {
                break;
            }
        }

        // Sort by score (highest first)
        usort($matches, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Return top K results
        return collect(array_slice($matches, 0, $topK));
    }

    /**
     * Simple score calculation
     */
    private function calculateSimpleScore($text, $query)
    {
        // Hitung berapa kali query muncul di text
        $count = substr_count($text, $query);

        // Base score based on occurrence count
        $score = $count * 0.2;

        // Bonus jika query di awal text
        if (strpos($text, $query) === 0) {
            $score += 0.3;
        }

        return min($score, 0.95);
    }
}
