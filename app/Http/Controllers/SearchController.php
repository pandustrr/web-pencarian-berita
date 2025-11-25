<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    private $csvController;
    private $fallbackController;

    public function __construct()
    {
        $this->csvController = new CsvDataController();
        $this->fallbackController = new FallbackSearchController();
    }

    /**
     * Display homepage
     */
    public function index()
    {
        $totalNews = $this->csvController->getTotalRecords();

        return view('search.index', [
            'totalNews' => $totalNews
        ]);
    }

    /**
     * Handle search request
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:100',
            'top_k' => 'sometimes|integer|min:1|max:50'
        ]);

        $query = $request->input('query');
        $topK = $request->input('top_k', 10);

        Log::info("🔍 SEARCH STARTED", ['query' => $query, 'top_k' => $topK]);

        try {
            // Use simple search for now
            $results = $this->fallbackController->search($query, $topK);

            Log::info("✅ SEARCH COMPLETED", [
                'query' => $query,
                'results_found' => $results->count()
            ]);

            return view('search.results', [
                'query' => $query,
                'results' => $results,
                'totalFound' => $results->count()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ SEARCH ERROR", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Terjadi kesalahan dalam pencarian: ' . $e->getMessage()]);
        }
    }

    /**
     * Show news detail
     */
    public function show($id)
    {
        try {
            $news = $this->csvController->getNewsById($id);
            return view('search.detail', compact('news'));

        } catch (\Exception $e) {
            Log::error("DETAIL ERROR", ['id' => $id, 'error' => $e->getMessage()]);
            abort(404, 'Berita tidak ditemukan');
        }
    }
}
