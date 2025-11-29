@extends('layouts.app')

@section('title', 'Beranda - Pencarian Berita TF-IDF')

@section('content')
<div class="text-center mb-8">
    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Sistem Pencarian Berita</h1>
    <p class="text-lg sm:text-xl text-gray-600">TF-IDF + Cosine Similarity dengan dataset {{ number_format($stats['total_documents']) }} berita</p>
</div>

<!-- System Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8">
    <div class="bg-white p-3 sm:p-4 rounded-lg shadow border text-center">
        <div class="text-xl sm:text-2xl font-bold text-blue-600 mb-1">{{ number_format($stats['total_documents']) }}</div>
        <div class="text-xs sm:text-sm text-gray-600">Total Berita</div>
        <div class="text-xs text-blue-500 mt-1">
            <i class="fas fa-database"></i> Dataset
        </div>
    </div>

    <div class="bg-white p-3 sm:p-4 rounded-lg shadow border text-center">
        <div class="text-xl sm:text-2xl font-bold text-green-600 mb-1">{{ number_format($stats['vocabulary_size']) }}</div>
        <div class="text-xs sm:text-sm text-gray-600">Kata Unik</div>
        <div class="text-xs text-green-500 mt-1">
            <i class="fas fa-font"></i> Vocabulary
        </div>
    </div>

    <div class="bg-white p-3 sm:p-4 rounded-lg shadow border text-center">
        <div class="text-xl sm:text-2xl font-bold text-purple-600 mb-1">TF-IDF</div>
        <div class="text-xs sm:text-sm text-gray-600">Algoritma</div>
        <div class="text-xs text-purple-500 mt-1">
            <i class="fas fa-calculator"></i> Weighting
        </div>
    </div>

    <div class="bg-white p-3 sm:p-4 rounded-lg shadow border text-center {{ $stats['python_connected'] ? 'border-green-500' : 'border-red-500' }}">
        <div class="text-xl sm:text-2xl font-bold {{ $stats['python_connected'] ? 'text-green-600' : 'text-red-600' }} mb-1">
            {{ $stats['python_connected'] ? '✅' : '❌' }}
        </div>
        <div class="text-xs sm:text-sm text-gray-600">Python Engine</div>
        <div class="text-xs {{ $stats['python_connected'] ? 'text-green-500' : 'text-red-500' }} mt-1">
            {{ $stats['python_connected'] ? 'Connected' : 'Disconnected' }}
        </div>
    </div>
</div>

<!-- Quick Search dengan Filter -->
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 sm:p-6 rounded-lg border border-blue-200 mb-8">
    <h2 class="text-lg sm:text-xl font-semibold text-center mb-4 text-gray-800">Pencarian Cepat dengan Filter</h2>

    <!-- Filter Options -->
    <div class="flex flex-wrap justify-center gap-2 mb-4">
        @foreach(['10' => '10 hasil', '30' => '30 hasil', '50' => '50 hasil', 'all' => 'Semua hasil'] as $value => $label)
            <div class="bg-white rounded-lg border border-blue-200 p-2 text-center min-w-[80px]">
                <div class="text-xs text-gray-600 mb-1">Tampilkan</div>
                <div class="font-semibold text-blue-600">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    <!-- Quick Search Buttons -->
    <div class="flex flex-wrap justify-center gap-2 sm:gap-3">
        @foreach(['gempa', 'polisi', 'teknologi', 'ekonomi', 'kesehatan', 'pendidikan'] as $example)
            <div class="flex flex-col items-center">
                <a href="{{ route('search', ['query' => $example, 'top_k' => 10]) }}"
                   class="bg-white text-blue-700 hover:bg-blue-100 px-3 sm:px-4 py-2 rounded-lg font-medium transition duration-200 border border-blue-300 hover:border-blue-400 shadow-sm text-sm sm:text-base mb-1">
                    <i class="fas fa-search mr-1 sm:mr-2"></i>{{ $example }}
                </a>
                <div class="flex gap-1">
                    @foreach(['10', '30', '50'] as $count)
                        <a href="{{ route('search', ['query' => $example, 'top_k' => $count]) }}"
                           class="text-xs text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-1 rounded">
                            {{ $count }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Algorithm Info -->
<div class="bg-white p-4 sm:p-6 rounded-lg shadow border">
    <h2 class="text-lg sm:text-xl font-semibold mb-4 text-center">Teknologi yang Digunakan</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        <div class="text-center">
            <div class="bg-blue-100 w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-database text-blue-600 text-lg sm:text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2 text-sm sm:text-base">Preprocessing</h3>
            <p class="text-xs sm:text-sm text-gray-600">Case folding, tokenizing, stopword removal</p>
        </div>
        <div class="text-center">
            <div class="bg-green-100 w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-calculator text-green-600 text-lg sm:text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2 text-sm sm:text-base">TF-IDF</h3>
            <p class="text-xs sm:text-sm text-gray-600">Term Frequency - Inverse Document Frequency</p>
        </div>
        <div class="text-center">
            <div class="bg-purple-100 w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-chart-line text-purple-600 text-lg sm:text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2 text-sm sm:text-base">Cosine Similarity</h3>
            <p class="text-xs sm:text-sm text-gray-600">Mengukur kemiripan vektor query-dokumen</p>
        </div>
    </div>
</div>

<!-- System Info -->
<div class="mt-6 text-center">
    <div class="inline-flex flex-col sm:flex-row items-center bg-gray-100 text-gray-700 px-4 py-2 rounded-full text-xs sm:text-sm space-y-1 sm:space-y-0 sm:space-x-2">
        <div class="flex items-center">
            <i class="fas fa-database mr-1"></i>
            <strong>{{ number_format($stats['total_documents']) }} berita</strong>
        </div>
        <div class="hidden sm:block">•</div>
        <div class="flex items-center">
            <i class="fas fa-font mr-1"></i>
            <strong>{{ number_format($stats['vocabulary_size']) }} kata</strong>
        </div>
        <div class="hidden sm:block">•</div>
        <a href="{{ route('debug') }}" class="underline">Detail Sistem</a>
    </div>
</div>
@endsection
