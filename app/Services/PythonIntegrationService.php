<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PythonIntegrationService
{
    private $pythonApiUrl;

    public function __construct()
    {
        $this->pythonApiUrl = env('PYTHON_API_URL', 'http://localhost:5000');
    }

    public function initializeModel($dataPath = null)
    {
        try {
            Log::info('Initializing Python model with final dataset...', ['url' => $this->pythonApiUrl]);

            $payload = [];
            if ($dataPath) {
                $payload['data_path'] = $dataPath;
            }

            $response = Http::timeout(120)->post("{$this->pythonApiUrl}/init", $payload);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['success'] ?? false) {
                    Log::info('Python model initialized successfully', $result);
                    return [
                        'success' => true,
                        'message' => $result['message'] ?? 'Model initialized',
                        'statistics' => $result['statistics'] ?? [],
                        'data' => $result
                    ];
                } else {
                    Log::error('Python model initialization failed', $result);
                    return [
                        'success' => false,
                        'error' => $result['error'] ?? 'Initialization failed'
                    ];
                }
            } else {
                Log::error('Python service HTTP error', ['status' => $response->status()]);
                return [
                    'success' => false,
                    'error' => 'Python service returned HTTP ' . $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error initializing Python model: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Cannot connect to Python service: ' . $e->getMessage()
            ];
        }
    }

    public function search($query, $topK = 10, $category = '')
    {
        try {
            Log::info('Sending search to Python service', [
                'query' => $query,
                'topK' => $topK,
                'category' => $category
            ]);

            $payload = [
                'query' => $query,
                'top_k' => $topK
            ];

            if ($category) {
                $payload['category'] = $category;
            }

            $response = Http::timeout(30)->post("{$this->pythonApiUrl}/search", $payload);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Python search response received', [
                    'success' => $result['success'] ?? false,
                    'total_results' => $result['total_results'] ?? 0,
                    'filters' => $result['filters'] ?? []
                ]);

                if (isset($result['success']) && $result['success']) {
                    return [
                        'success' => true,
                        'query' => $result['query'],
                        'processed_query' => $result['processed_query'] ?? '',
                        'total_results' => $result['total_results'],
                        'filters' => $result['filters'] ?? [],
                        'results' => collect($result['results'])
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $result['error'] ?? 'Search failed in Python service'
                    ];
                }
            } else {
                Log::error('Python service HTTP error', ['status' => $response->status()]);
                return [
                    'success' => false,
                    'error' => 'Python service returned HTTP ' . $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Python search exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Cannot connect to Python service. Please make sure Python service is running on localhost:5000. Error: ' . $e->getMessage()
            ];
        }
    }

    public function getStatistics()
    {
        try {
            $response = Http::timeout(30)->get("{$this->pythonApiUrl}/stats");

            if ($response->successful()) {
                $result = $response->json();
                return $result;
            }

            return ['success' => false, 'error' => 'HTTP ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function healthCheck()
    {
        try {
            $response = Http::timeout(10)->get("{$this->pythonApiUrl}/health");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Python health check failed', ['status' => $response->status()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Python health check exception: ' . $e->getMessage());
            return false;
        }
    }

    public function testSearch()
    {
        try {
            $response = Http::timeout(30)->get("{$this->pythonApiUrl}/test");

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'error' => 'HTTP ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
