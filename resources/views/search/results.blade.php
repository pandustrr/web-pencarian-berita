@extends('layouts.app')

@section('title', 'Hasil Pencarian: ' . $query)

@section('content')
<div class="mb-6">
    <!-- Header Info -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Hasil Pencarian</h1>
            <div class="flex flex-wrap items-center gap-3 text-gray-600">
                <div class="flex items-center bg-white px-3 py-1 rounded-full border">
                    <i class="fas fa-search mr-2 text-blue-500"></i>
                    <span class="font-medium text-blue-600">"{{ $query }}"</span>
                </div>
                <div class="flex items-center bg-white px-3 py-1 rounded-full border">
                    <i class="fas fa-calculator mr-2 text-green-500"></i>
                    <span>{{ $algorithm }}</span>
                </div>
                <div class="flex items-center bg-white px-3 py-1 rounded-full border">
                    <i class="fas fa-list mr-2 text-purple-500"></i>
                    <span>{{ count($results) }} hasil</span>
                </div>
            </div>
        </div>

        <!-- Results Filter -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="bg-white rounded-lg border p-3">
                <form action="{{ route('search') }}" method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="query" value="{{ $query }}">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Tampilkan:</label>
                    <select name="top_k" onchange="this.form.submit()"
                            class="border border-gray-300 rounded px-3 py-1 text-sm bg-white">
                        <option value="10" {{ $topK == 10 ? 'selected' : '' }}>10 hasil</option>
                        <option value="30" {{ $topK == 30 ? 'selected' : '' }}>30 hasil</option>
                        <option value="50" {{ $topK == 50 ? 'selected' : '' }}>50 hasil</option>
                        <option value="all" {{ $topK == 'all' ? 'selected' : '' }}>Semua hasil</option>
                    </select>
                </form>
            </div>

            <!-- New Search Button -->
            <a href="{{ route('home') }}"
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition duration-200 flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i>
                <span class="hidden sm:inline">Pencarian Baru</span>
                <span class="sm:hidden">Baru</span>
            </a>
        </div>
    </div>

    <!-- Engine Status -->
    @if($engine === 'python')
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center">
                <i class="fab fa-python text-green-500 text-xl mr-3"></i>
                <div>
                    <span class="text-green-800 font-semibold">✅ Powered by Python TF-IDF Engine</span>
                    <div class="text-green-700 text-sm mt-1">
                        Vocabulary: {{ number_format($stats['vocabulary_size']) }} kata •
                        Dataset: {{ number_format($stats['total_documents']) }} berita
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                    High Accuracy
                </span>
            </div>
        </div>
    </div>
    @else
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i class="fab fa-php text-blue-500 text-xl mr-3"></i>
            <div>
                <span class="text-blue-800 font-semibold">🔄 Using PHP Fallback Engine</span>
                <div class="text-blue-700 text-sm mt-1">Python engine tidak tersedia</div>
            </div>
        </div>
    </div>
    @endif
</div>

@if(count($results) > 0)
    <!-- Results Count -->
    <div class="bg-white rounded-lg border p-4 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between">
            <div class="text-gray-700">
                Menampilkan <strong>{{ count($results) }}</strong> dari total <strong>{{ number_format($stats['total_documents']) }}</strong> berita
                @if($topK === 'all')
                    <span class="text-green-600 font-medium">(Semua hasil)</span>
                @else
                    <span class="text-blue-600 font-medium">(Top {{ $topK }})</span>
                @endif
            </div>
            <div class="text-sm text-gray-500 mt-2 sm:mt-0">
                Diurutkan berdasarkan relevansi
            </div>
        </div>
    </div>

    <!-- Results Grid -->
    <div class="space-y-4">
        @foreach($results as $result)
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-3">
                <div class="flex-1">
                    <h3 class="font-semibold text-lg text-gray-900 leading-relaxed">
                        @if(isset($result['news']))
                            {{ Str::limit($result['news']->original_text, 150) }}
                        @else
                            {{ Str::limit($result['original_text'], 150) }}
                        @endif
                    </h3>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-3 py-2 rounded-full text-sm font-semibold shadow-sm">
                        <i class="fas fa-chart-line mr-1"></i>
                        {{ number_format(($result['score'] ?? 0) * 100, 1) }}%
                    </span>
                </div>
            </div>

            <p class="text-gray-700 mb-4 leading-relaxed text-sm sm:text-base">
                @if(isset($result['news']))
                    {{ Str::limit($result['news']->original_text, 300) }}
                @else
                    {{ Str::limit($result['original_text'], 300) }}
                @endif
            </p>

            <div class="flex flex-wrap gap-2 text-xs sm:text-sm mb-4">
                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full flex items-center">
                    <i class="fas fa-tag mr-1"></i>
                    {{ $result['category'] ?? 'General' }}
                </span>
                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full flex items-center">
                    <i class="fas fa-source mr-1"></i>
                    {{ $result['source'] ?? 'CSV' }}
                </span>
                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full flex items-center">
                    <i class="fas fa-hashtag mr-1"></i>
                    ID: {{ $result['index'] ?? 0 }}
                </span>
            </div>

            <div class="flex justify-between items-center">
                <a href="{{ route('news.show', $result['index'] ?? 0) }}"
                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold text-sm sm:text-base">
                    <i class="fas fa-eye mr-2"></i>
                    Baca Detail Lengkap
                </a>
                <span class="text-xs text-gray-500">
                    Relevansi: {{ number_format(($result['score'] ?? 0) * 100, 1) }}%
                </span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Results Summary -->
    <div class="mt-8 bg-gradient-to-r from-gray-50 to-blue-50 p-6 rounded-lg border border-gray-200">
        <h3 class="font-semibold text-gray-900 mb-4 text-center text-lg">Ringkasan Pencarian</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div class="bg-white p-4 rounded-lg border">
                <div class="text-2xl font-bold text-blue-600">{{ count($results) }}</div>
                <div class="text-gray-600 text-sm">Hasil Ditemukan</div>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <div class="text-lg font-bold text-green-600 truncate">{{ $algorithm }}</div>
                <div class="text-gray-600 text-sm">Algoritma</div>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <div class="text-lg font-bold text-purple-600">{{ $engine }}</div>
                <div class="text-gray-600 text-sm">Engine</div>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <div class="text-lg font-bold text-orange-600">{{ $topK === 'all' ? 'All' : $topK }}</div>
                <div class="text-gray-600 text-sm">Jumlah Tampil</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
            <a href="{{ route('home') }}"
               class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition duration-200 text-center">
                <i class="fas fa-search mr-2"></i>Pencarian Baru
            </a>
            <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition duration-200 text-center">
                <i class="fas fa-arrow-up mr-2"></i>Ke Atas
            </button>
        </div>
    </div>
@else
    <!-- No Results -->
    <div class="bg-yellow-50 p-8 rounded-lg border border-yellow-200 text-center">
        <i class="fas fa-search text-5xl text-yellow-500 mb-4"></i>
        <h3 class="text-xl font-semibold text-yellow-800 mb-2">Tidak ada hasil ditemukan</h3>
        <p class="text-yellow-700 mb-4">Coba gunakan kata kunci yang berbeda atau lebih spesifik</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-home mr-2"></i>Kembali ke Pencarian
            </a>
            <a href="{{ route('search', ['query' => 'teknologi', 'top_k' => 10]) }}"
               class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-bolt mr-2"></i>Coba "teknologi"
            </a>
        </div>
    </div>
@endif

<!-- Loading Indicator (hidden by default) -->
<div id="loading" class="hidden fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg text-center">
        <i class="fas fa-spinner fa-spin text-blue-500 text-3xl mb-3"></i>
        <p class="text-gray-700">Memproses pencarian...</p>
    </div>
</div>

<script>
// Loading indicator untuk filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.querySelector('select[name="top_k"]');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            document.getElementById('loading').classList.remove('hidden');
        });
    }
});
</script>
@endsection
