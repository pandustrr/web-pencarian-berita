@extends('layouts.app')

@section('title', 'Hasil: ' . $query)

@section('content')
<div class="max-w-4xl mx-auto py-6">
    <!-- Header dengan Info Singkat -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">
            Hasil untuk: <span class="text-blue-600">"{{ $query }}"</span>
        </h2>
        <div class="flex flex-wrap gap-2 text-sm text-gray-600">
            <span><strong class="text-green-600">{{ $searchStats['total_results'] ?? count($results) }}</strong> hasil ditemukan</span>
            <span>•</span>
            <span>Rata² Relevansi: <strong class="text-green-600">{{ number_format($searchStats['average_similarity'] * 100, 1) }}%</strong></span>
            <span>•</span>
            <span>Spesifisitas: <strong>{{ $searchStats['keyword_count'] ?? 0 }} kata</strong></span>
        </div>
    </div>

    <!-- Filter Results per Page -->
    @if(count($results) > 0 || $topK === 'all')
    <div class="bg-white p-4 rounded-lg shadow border mb-6">
        <div class="flex flex-col sm:flex-row items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Tampilkan:</label>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('search', ['query' => $query, 'top_k' => 10]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $topK == 10 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    10 Hasil
                </a>
                <a href="{{ route('search', ['query' => $query, 'top_k' => 20]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $topK == 20 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    20 Hasil
                </a>
                <a href="{{ route('search', ['query' => $query, 'top_k' => 30]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $topK == 30 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    30 Hasil
                </a>
                <a href="{{ route('search', ['query' => $query, 'top_k' => 50]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $topK == 50 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    50 Hasil
                </a>
                <a href="{{ route('search', ['query' => $query, 'top_k' => 'all']) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $topK === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    Semua Hasil
                </a>
            </div>
        </div>
    </div>
    @endif

@if(count($results) > 0)
    <!-- Results List (Google Style - Simple Lines) -->
    <div class="space-y-6">
        @foreach($results as $result)
        <div class="border-b pb-6 last:border-b-0">
            <!-- Rank Number -->
            <div class="text-sm text-gray-500 mb-1">
                Hasil #{{ $loop->iteration }}
                <span class="ml-2 inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">
                    {{ number_format(($result['similarity'] ?? $result['score'] ?? 0) * 100, 1) }}%
                </span>
            </div>

            <!-- Title/Link (seperti Google) -->
            <h3 class="mb-1">
                <a href="{{ route('news.show', $result['id'] ?? $result['index'] ?? 0) }}"
                   class="text-lg text-blue-600 hover:underline font-medium">
                    @php
                        $text = $result['original_text'] ?? $result['text'] ?? '';
                        $title = strlen($text) > 100 ? substr($text, 0, 100) . '...' : $text;
                    @endphp
                    {{ $title }}
                </a>
            </h3>

            <!-- Description (Green URL-like text) -->
            <div class="text-sm text-green-600 mb-2 font-medium">
                berita.com › result #{{ $result['id'] ?? $result['index'] ?? 0 }}
            </div>

            <!-- Content Preview (Gray text seperti Google) -->
            <p class="text-gray-600 text-sm leading-relaxed">
                @php
                    $preview = $result['original_text'] ?? $result['text'] ?? '';
                    $preview = strlen($preview) > 150 ? substr($preview, 0, 150) . '...' : $preview;
                    // Highlight query terms
                    $keywords = explode(' ', strtolower($query));
                    foreach ($keywords as $keyword) {
                        if (strlen($keyword) > 2) {
                            $preview = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<mark class="bg-yellow-200">$1</mark>', $preview);
                        }
                    }
                @endphp
                {!! $preview !!}
            </p>

            <!-- Meta Info -->
            <div class="text-xs text-gray-500 mt-2 space-x-3 flex flex-wrap gap-2">
                @if(isset($result['category']) && $result['category'])
                    <span><i class="fas fa-tag mr-1"></i>{{ $result['category'] }}</span>
                @endif
                <span><i class="fas fa-percent mr-1"></i>Score: {{ number_format(($result['similarity'] ?? $result['score'] ?? 0), 3) }}</span>
            </div>

            <!-- Action Button -->
            <div class="mt-3">
                <a href="{{ route('news.show', $result['id'] ?? $result['index'] ?? 0) }}"
                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    <i class="fas fa-arrow-right mr-1"></i>Lihat Detail
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination Info -->
    <div class="mt-8 text-center text-gray-600 py-6">
        <p class="text-sm">
            Menampilkan <strong>{{ count($results) }}</strong> hasil untuk "{{ $query }}"
        </p>
        <p class="text-xs text-gray-500 mt-2">
            Auto-filter dengan threshold: <strong>{{ number_format($autoThreshold ?? 0.1, 2) }}</strong>
            | Algoritma: <strong>{{ $engine === 'python' ? 'Python TF-IDF' : 'PHP Fallback' }}</strong>
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3 justify-center mt-8 pt-6 border-t">
        <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium text-center transition">
            <i class="fas fa-search mr-2"></i>Pencarian Baru
        </a>
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition">
            <i class="fas fa-arrow-up mr-2"></i>Ke Atas
        </button>
    </div>
@else
    <!-- No Results State -->
    <div class="text-center py-12">
        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Tidak ada hasil untuk "{{ $query }}"</h3>
        <p class="text-gray-600 mb-6">
            Tidak ditemukan berita yang cukup relevan dengan pencarian Anda.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-home mr-2"></i>Kembali ke Pencarian
            </a>
            <a href="{{ route('search', ['query' => $query, 'top_k' => 'all']) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-list mr-2"></i>Lihat Semua Hasil
            </a>
        </div>

        <!-- Suggestions -->
        <div class="mt-8 pt-8 border-t">
            <p class="text-sm text-gray-600 mb-3">Saran pencarian:</p>
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ route('search', ['query' => 'gempa']) }}" class="text-blue-600 hover:underline text-sm">Gempa</a>
                <a href="{{ route('search', ['query' => 'teknologi']) }}" class="text-blue-600 hover:underline text-sm">Teknologi</a>
                <a href="{{ route('search', ['query' => 'ekonomi']) }}" class="text-blue-600 hover:underline text-sm">Ekonomi</a>
                <a href="{{ route('search', ['query' => 'kesehatan']) }}" class="text-blue-600 hover:underline text-sm">Kesehatan</a>
            </div>
        </div>
    </div>
@endif
@endsection
