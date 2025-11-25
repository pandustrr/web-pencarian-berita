<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CsvDataController extends Controller
{
    private $csvData = null; // Ubah jadi null, bukan array kosong

    /**
     * Load CSV data from storage - NO CACHE VERSION
     */
    public function loadCSVData()
    {
        // Always reload fresh data
        $this->csvData = null;

        $csvPath = base_path('storage/app/python_data/preprocessed_news.csv');

        if (!file_exists($csvPath)) {
            Log::error("❌ CSV FILE NOT FOUND", ['path' => $csvPath]);
            $this->csvData = [];
            return;
        }

        try {
            $file = fopen($csvPath, 'r');
            $header = fgetcsv($file); // Baca header

            $data = [];
            $count = 0;
            $maxRecords = 5000; // ⭐⭐ INI 5000 ⭐⭐

            while (($row = fgetcsv($file)) !== FALSE && $count < $maxRecords) {
                if (count($header) === count($row)) { // Pastikan header dan row match
                    $record = array_combine($header, $row);

                    // Add default values for missing columns
                    if (!isset($record['category'])) {
                        $record['category'] = 'General';
                    }
                    if (!isset($record['source'])) {
                        $record['source'] = 'Berita Online';
                    }

                    $data[] = $record;
                    $count++;
                }
            }

            fclose($file);

            $this->csvData = $data;

            Log::info("✅ CSV LOADED FRESH", [
                'records' => count($this->csvData),
                'limit' => $maxRecords,
                'sample' => Str::limit($this->csvData[0]['text'] ?? 'No text', 50)
            ]);

        } catch (\Exception $e) {
            Log::error("❌ CSV READ ERROR", ['error' => $e->getMessage()]);
            $this->csvData = [];
        }
    }

    /**
     * Get all CSV data - ALWAYS FRESH
     */
    public function getAllData()
    {
        $this->loadCSVData(); // Always reload
        return $this->csvData ?? [];
    }

    /**
     * Get news by ID
     */
    public function getNewsById($id)
    {
        $this->loadCSVData(); // Always reload

        // Coba database dulu
        $news = News::find($id);
        if ($news) {
            return $news;
        }

        // Fallback ke CSV
        $data = $this->csvData ?? [];
        if (isset($data[$id])) {
            $record = $data[$id];
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

        $this->loadCSVData(); // Always reload
        return count($this->csvData ?? []);
    }

    /**
     * Get sample records for debugging
     */
    public function getSampleRecords($limit = 5)
    {
        $this->loadCSVData(); // Always reload
        $data = $this->csvData ?? [];
        return array_slice($data, 0, $limit);
    }

    /**
     * Force reload and get count
     */
    public function forceReload()
    {
        $this->loadCSVData();
        return count($this->csvData ?? []);
    }

    public function getDocumentsForTfidf()
    {
        $this->loadCSVData();

        $documents = [];
        foreach ($this->csvData as $index => $record) {
            // Gunakan processed_text untuk TF-IDF, fallback ke original text
            $documents[$index] = $record['processed'] ?? $record['text'] ?? '';
        }

        return $documents;
    }
}
