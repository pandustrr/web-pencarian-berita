<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    private $csvController;
    private $tfidfController;
    private $fallbackController;

    public function __construct()
    {
        $this->csvController = new CsvDataController();
        $this->tfidfController = new TfidfSearchController();
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
     * Handle search request dengan TF-IDF sebagai primary
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:100',
            'top_k' => 'sometimes|integer|min:1|max:50'
        ]);

        $query = $request->input('query');
        $topK = $request->input('top_k', 10);

        Log::info("🔍 SEARCH STARTED", ['query' => $query, 'top_k' => $topK, 'algorithm' => 'TF-IDF']);

        try {
            $results = collect();

            // Priority 1: Gunakan TF-IDF + Cosine Similarity
            $tfidfResults = $this->tfidfController->search($query, $topK);
            if ($tfidfResults->isNotEmpty()) {
                $results = $tfidfResults;
                Log::info("✅ TF-IDF SEARCH SUCCESS", ['results' => $results->count()]);
            }

            // Priority 2: Fallback ke simple search jika TF-IDF tidak menghasilkan hasil
            if ($results->isEmpty()) {
                $fallbackResults = $this->fallbackController->search($query, $topK);
                if ($fallbackResults->isNotEmpty()) {
                    $results = $fallbackResults;
                    Log::info("🔄 FALLBACK SEARCH USED", ['results' => $results->count()]);
                }
            }

            Log::info("🎯 SEARCH COMPLETED", [
                'query' => $query,
                'final_results' => $results->count(),
                'algorithm' => $results->isNotEmpty() ? 'TF-IDF' : 'Fallback',
                'max_score' => $results->max('score') ?? 0
            ]);

            return view('search.results', [
                'query' => $query,
                'results' => $results,
                'totalFound' => $results->count(),
                'algorithm' => $results->isNotEmpty() ? 'TF-IDF + Cosine Similarity' : 'Simple Matching'
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
