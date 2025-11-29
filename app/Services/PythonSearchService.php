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
     * Search dengan timeout reasonable
     */
    public function search(string $query, int $topK = 10)
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/search", [
                'query' => $query,
                'top_k' => $topK
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (Exception $e) {
            Log::warning("Python search timeout", ['query' => $query]);
            return null;
        }
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
}
