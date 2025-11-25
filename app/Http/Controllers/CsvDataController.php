<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Illuminate\Support\Facades\Log;

class CsvDataController extends Controller
{
    private $csvData = [];

    /**
     * Load CSV data from storage
     */
    public function loadCSVData()
    {
        if (!empty($this->csvData)) {
            return;
        }

        // Gunakan path absolut langsung
        $csvPath = base_path('storage/app/python_data/preprocessed_news.csv');

        Log::info("📂 ATTEMPTING TO LOAD CSV", ['path' => $csvPath]);

        if (!file_exists($csvPath)) {
            Log::error("❌ CSV FILE NOT FOUND", ['path' => $csvPath]);
            $this->csvData = [];
            return;
        }

        try {
            Log::info("📖 READING CSV FILE", [
                'full_path' => $csvPath,
                'file_size' => filesize($csvPath)
            ]);

            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);

            $this->csvData = iterator_to_array($csv->getRecords());

            // Add default values for missing columns
            foreach ($this->csvData as &$record) {
                if (!isset($record['category'])) {
                    $record['category'] = 'General';
                }
                if (!isset($record['source'])) {
                    $record['source'] = 'Berita Online';
                }
            }

            Log::info("✅ CSV LOADED SUCCESSFULLY", [
                'records' => count($this->csvData),
                'first_record' => isset($this->csvData[0]['text']) ? Str::limit($this->csvData[0]['text'], 100) : 'empty'
            ]);

        } catch (\Exception $e) {
            Log::error("❌ CSV READ ERROR", [
                'path' => $csvPath,
                'error' => $e->getMessage()
            ]);
            $this->csvData = [];
        }
    }

    /**
     * Get all CSV data
     */
    public function getAllData()
    {
        $this->loadCSVData();
        return $this->csvData;
    }

    /**
     * Get news by ID
     */
    public function getNewsById($id)
    {
        $this->loadCSVData();

        // Coba database dulu
        $news = News::find($id);
        if ($news) {
            return $news;
        }

        // Fallback ke CSV
        if (isset($this->csvData[$id])) {
            $record = $this->csvData[$id];
            return (object) [
                'id' => $id,
                'title' => Str::limit($record['text'] ?? '', 200),
                'original_text' => $record['text'] ?? '',
                'translated_text' => $record['translated'] ?? $record['text'] ?? '',
                'processed_text' => $record['processed'] ?? '',
                'category' => $record['category'] ?? 'General',
                'source' => $record['source'] ?? 'Berita Online',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        throw new \Exception("News not found");
    }

    /**
     * Get total records count
     */
    public function getTotalRecords()
    {
        $dbCount = News::count();
        if ($dbCount > 0) {
            return $dbCount;
        }

        $this->loadCSVData();
        return count($this->csvData);
    }

    /**
     * Get documents for TF-IDF processing
     */
    public function getDocumentsForTfidf()
    {
        $this->loadCSVData();

        $documents = [];
        foreach ($this->csvData as $index => $record) {
            $documents[$index] = $record['processed'] ?? $record['text'] ?? '';
        }

        return $documents;
    }

    /**
     * Get sample records for debugging
     */
    public function getSampleRecords($limit = 5)
    {
        $this->loadCSVData();
        return array_slice($this->csvData, 0, $limit);
    }
}
