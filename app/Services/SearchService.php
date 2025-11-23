<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SearchService
{
    private $pythonService;

    public function __construct(PythonIntegrationService $pythonService)
    {
        $this->pythonService = $pythonService;
    }

    public function searchNews($query, $topK = 10)
    {
        Log::info('Starting search process', ['query' => $query, 'topK' => $topK]);

        // Check Python service health
        $health = $this->pythonService->healthCheck();

        if (!$health) {
            $errorMsg = 'Python IR service is not running. Please start the Python service first.';
            Log::error($errorMsg);
            return [
                'success' => false,
                'error' => $errorMsg,
                'results' => collect(),
                'query' => $query
            ];
        }

        Log::info('Python service health check passed', $health);

        // If model is not loaded, try to initialize it
        if (!($health['model_loaded'] ?? false)) {
            Log::info('Python model not loaded, attempting initialization...');
            $initResult = $this->pythonService->initializeModel();

            if (!$initResult['success']) {
                $errorMsg = 'Failed to initialize search model: ' . ($initResult['error'] ?? 'Unknown error');
                Log::error($errorMsg);
                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'results' => collect(),
                    'query' => $query
                ];
            }

            Log::info('Python model initialized successfully');
        }

        // Perform the search
        $searchResult = $this->pythonService->search($query, $topK);

        if (!$searchResult['success']) {
            Log::error('Search failed', $searchResult);
            return [
                'success' => false,
                'error' => $searchResult['error'] ?? 'Search failed',
                'results' => collect(),
                'query' => $query
            ];
        }

        Log::info('Search completed successfully', [
            'query' => $searchResult['query'],
            'processed_query' => $searchResult['processed_query'],
            'total_results' => $searchResult['total_results']
        ]);

        return $searchResult;
    }

    public function getSystemStatus()
    {
        $health = $this->pythonService->healthCheck();

        return [
            'python_service' => $health !== false,
            'model_loaded' => $health['model_loaded'] ?? false,
            'data_loaded' => $health['data_loaded'] ?? false,
            'details' => $health
        ];
    }
}
