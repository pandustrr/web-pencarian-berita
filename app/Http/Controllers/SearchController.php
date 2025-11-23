<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index()
    {
        $systemStatus = $this->searchService->getSystemStatus();

        return view('search.index', [
            'system_status' => $systemStatus
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:255',
            'top_k' => 'sometimes|integer|min:1|max:50',
            'category' => 'sometimes|string|max:100'
        ]);

        $query = $request->input('q');
        $topK = $request->input('top_k', 10);
        $category = $request->input('category', '');

        Log::info('Search request received', [
            'query' => $query,
            'topK' => $topK,
            'category' => $category
        ]);

        $searchResult = $this->searchService->searchNews($query, $topK, $category);

        if ($request->wantsJson()) {
            return response()->json($searchResult);
        }

        return view('search.results', array_merge($searchResult, [
            'query' => $query,
            'selected_category' => $category
        ]));
    }

    public function health()
    {
        $systemStatus = $this->searchService->getSystemStatus();

        return response()->json([
            'laravel' => 'healthy',
            'system_status' => $systemStatus,
            'timestamp' => now()->toISOString()
        ]);
    }

    public function initPython()
    {
        $pythonService = app(\App\Services\PythonIntegrationService::class);
        $result = $pythonService->initializeModel();

        return response()->json($result);
    }

    public function testPython()
    {
        $pythonService = app(\App\Services\PythonIntegrationService::class);
        $result = $pythonService->testSearch();

        return response()->json($result);
    }
}
