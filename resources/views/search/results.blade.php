@extends('layouts.app')

@section('title', 'Hasil Pencarian - ' . request('query'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Search Results Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="mb-4 md:mb-0">
                <h1 class="text-2xl font-bold text-gray-900">Hasil Pencarian</h1>
                <div class="flex items-center mt-2 space-x-4">
                    <p class="text-gray-600">
                        <span class="font-semibold">Kata kunci:</span>
                        <span class="text-blue-600 bg-blue-50 px-2 py-1 rounded-md">"{{ $query }}"</span>
                    </p>
                    <span class="text-gray-400">•</span>
                    <p class="text-gray-600">{{ $totalFound }} hasil ditemukan</p>
                </div>
            </div>

            <a href="{{ route('search.index') }}"
               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Pencarian
            </a>
        </div>

        <!-- Search Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                <div>
                    <p class="text-blue-800 text-sm">
                        Hasil diurutkan berdasarkan tingkat kemiripan menggunakan algoritma
                        <span class="font-semibold">Cosine Similarity</span> dengan pembobotan
                        <span class="font-semibold">TF-IDF</span>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Results -->
    @if($results->count() > 0)
        <div class="space-y-6">
            @foreach($results as $result)
                <div class="news-card bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <!-- Content -->
                            <div class="flex-1">
                                <!-- Score Badge -->
                                <div class="inline-flex items-center
                                    @if($result['score'] > 0.7) bg-gradient-to-r from-green-500 to-emerald-600
                                    @elseif($result['score'] > 0.3) bg-gradient-to-r from-yellow-500 to-orange-500
                                    @else bg-gradient-to-r from-blue-500 to-blue-600
                                    @endif text-white px-3 py-1 rounded-full text-sm font-semibold mb-3">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    {{ number_format($result['score'] * 100, 1) }}% Match
                                </div>

                                <!-- News Content -->
                                <div class="mb-4">
                                    <p class="text-gray-700 leading-relaxed search-result-text"
                                       data-original-text="{{ $result['news']->original_text }}">
                                        {{ Str::limit($result['news']->original_text, 300) }}
                                    </p>
                                </div>

                                <!-- Metadata -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                    @if($result['news']->category)
                                        <span class="inline-flex items-center bg-gray-100 px-2 py-1 rounded-md">
                                            <i class="fas fa-tag mr-1 text-xs"></i>
                                            {{ $result['news']->category }}
                                        </span>
                                    @endif
                                    @if($result['news']->source)
                                        <span class="inline-flex items-center">
                                            <i class="fas fa-source mr-1"></i>
                                            {{ $result['news']->source }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center">
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ $result['news']->created_at->format('d M Y') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="lg:pl-4">
                                <a href="{{ route('search.show', $result['news']->id) }}"
                                   class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200 transform hover:scale-105 shadow-md">
                                    <i class="fas fa-eye mr-2"></i>
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <!-- No Results -->
        <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
            <div class="text-6xl mb-4 text-gray-300">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="text-2xl font-semibold text-gray-900 mb-4">Tidak ada hasil ditemukan untuk "{{ $query }}"</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                Coba gunakan strategi pencarian berikut:
            </p>

            <div class="max-w-lg mx-auto text-left mb-6">
                <ul class="text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        Gunakan kata kunci yang lebih spesifik
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        Coba kata kunci dalam bahasa Indonesia
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        Periksa ejaan kata kunci
                    </li>
                </ul>
            </div>

            <div class="space-y-3">
                <a href="{{ route('search.index') }}"
                   class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition duration-200">
                    <i class="fas fa-home mr-2"></i>Kembali ke Pencarian
                </a>
                <br>
                <a href="{{ route('debug.info') }}"
                   class="inline-block bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition duration-200 text-sm">
                    <i class="fas fa-bug mr-2"></i>Debug Information
                </a>
            </div>
        </div>
    @endif
</div>

<script>
    // Apply highlighting when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const query = "{{ $query }}";
        if (query) {
            document.querySelectorAll('.search-result-text').forEach(element => {
                const originalText = element.getAttribute('data-original-text') || element.textContent;
                element.innerHTML = highlightText(originalText, query);
            });
        }
    });
</script>
@endsection
