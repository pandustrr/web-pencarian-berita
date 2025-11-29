@extends('layouts.app')

@section('title', 'Beranda - Pencarian Berita TF-IDF')

@section('content')
<div class="text-center mb-8">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Sistem Pencarian Berita</h1>
    <p class="text-xl text-gray-600">TF-IDF + Cosine Similarity dengan dataset {{ number_format($stats['total_documents']) }} berita</p>
</div>

<!-- System Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-4 rounded-lg shadow border text-center">
        <div class="text-2xl font-bold text-blue-600 mb-1">{{ number_format($stats['total_documents']) }}</div>
        <div class="text-sm text-gray-600">Total Berita</div>
        <div class="text-xs text-blue-500 mt-1">
            <i class="fas fa-database"></i> Dataset
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow border text-center">
        <div class="text-2xl font-bold text-green-600 mb-1">{{ number_format($stats['vocabulary_size']) }}</div>
        <div class="text-sm text-gray-600">Kata Unik</div>
        <div class="text-xs text-green-500 mt-1">
            <i class="fas fa-font"></i> Vocabulary
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow border text-center">
        <div class="text-2xl font-bold text-purple-600 mb-1">TF-IDF</div>
        <div class="text-sm text-gray-600">Algoritma</div>
        <div class="text-xs text-purple-500 mt-1">
            <i class="fas fa-calculator"></i> Weighting
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow border text-center {{ $stats['python_connected'] ? 'border-green-500' : 'border-red-500' }}">
        <div class="text-2xl font-bold {{ $stats['python_connected'] ? 'text-green-600' : 'text-red-600' }} mb-1">
            {{ $stats['python_connected'] ? '✅' : '❌' }}
        </div>
        <div class="text-sm text-gray-600">Python Engine</div>
        <div class="text-xs {{ $stats['python_connected'] ? 'text-green-500' : 'text-red-500' }} mt-1">
            {{ $stats['python_connected'] ? 'Connected' : 'Disconnected' }}
        </div>
    </div>
</div>

<!-- Quick Search -->
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-200 mb-8">
    <h2 class="text-xl font-semibold text-center mb-4 text-gray-800">Coba Pencarian Cepat</h2>
    <div class="flex flex-wrap justify-center gap-3">
        @foreach(['gempa', 'polisi', 'teknologi', 'ekonomi', 'kesehatan', 'pendidikan'] as $example)
            <a href="{{ route('search', ['query' => $example]) }}"
               class="bg-white text-blue-700 hover:bg-blue-100 px-4 py-2 rounded-lg font-medium transition duration-200 border border-blue-300 hover:border-blue-400 shadow-sm">
                <i class="fas fa-search mr-2"></i>{{ $example }}
            </a>
        @endforeach
    </div>
</div>

<!-- Algorithm Info -->
<div class="bg-white p-6 rounded-lg shadow border">
    <h2 class="text-xl font-semibold mb-4 text-center">Teknologi yang Digunakan</h2>
    <div class="grid md:grid-cols-3 gap-6">
        <div class="text-center">
            <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-database text-blue-600 text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">Preprocessing</h3>
            <p class="text-sm text-gray-600">Case folding, tokenizing, stopword removal</p>
        </div>
        <div class="text-center">
            <div class="bg-green-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-calculator text-green-600 text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">TF-IDF</h3>
            <p class="text-sm text-gray-600">Term Frequency - Inverse Document Frequency</p>
        </div>
        <div class="text-center">
            <div class="bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
            </div>
            <h3 class="font-semibold mb-2">Cosine Similarity</h3>
            <p class="text-sm text-gray-600">Mengukur kemiripan vektor query-dokumen</p>
        </div>
    </div>
</div>

<!-- System Info -->
<div class="mt-6 text-center">
    <div class="inline-flex items-center bg-gray-100 text-gray-700 px-4 py-2 rounded-full text-sm">
        <i class="fas fa-info-circle mr-2"></i>
        Dataset: <strong>{{ number_format($stats['total_documents']) }} berita</strong> •
        Vocabulary: <strong>{{ number_format($stats['vocabulary_size']) }} kata</strong> •
        <a href="{{ route('debug') }}" class="underline ml-1">Detail Sistem</a>
    </div>
</div>
@endsection
