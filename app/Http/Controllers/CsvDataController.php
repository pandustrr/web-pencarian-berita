<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CsvDataController extends Controller
{
    private $csvData = [];

    /**
     * Load CSV data from storage - SIMPLE VERSION
     */
    public function loadCSVData()
    {
        if (!empty($this->csvData)) {
            return;
        }

        $csvPath = base_path('storage/app/python_data/preprocessed_news.csv');

        if (!file_exists($csvPath)) {
            Log::error("❌ CSV FILE NOT FOUND", ['path' => $csvPath]);
            $this->csvData = [];
            return;
        }

        try {
            $file = fopen($csvPath, 'r');
            $header = fgetcsv($file);

            $this->csvData = [];
            $count = 0;
            $maxRecords = 1000;

            while (($row = fgetcsv($file)) !== FALSE && $count < $maxRecords) {
                $record = array_combine($header, $row);

                // Add default values for missing columns
                if (!isset($record['category'])) {
                    $record['category'] = 'General';
                }
                if (!isset($record['source'])) {
                    $record['source'] = 'Berita Online';
                }

                $this->csvData[] = $record;
                $count++;
            }

            fclose($file);

            Log::info("✅ CSV LOADED", [
                'records' => count($this->csvData),
                'sample' => Str::limit($this->csvData[0]['text'] ?? 'No text', 50)
            ]);

        } catch (\Exception $e) {
            Log::error("❌ CSV READ ERROR", ['error' => $e->getMessage()]);
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
     * Get sample records for debugging
     */
    public function getSampleRecords($limit = 5)
    {
        $this->loadCSVData();
        return array_slice($this->csvData, 0, $limit);
    }
}
