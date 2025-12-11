@extends('layouts.app')

@section('title', 'Pencarian Berita')

@section('content')
<div class="max-w-5xl mx-auto py-8">
    <!-- Search Form with Filters (Like Google) -->
    <form action="{{ route('search') }}" method="GET" class="space-y-4 mb-8">
        <!-- Filter Section -->
        <div class="bg-white p-4 rounded-lg shadow border">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- Jumlah Hasil -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-list-ol mr-1"></i> Jumlah Hasil
                    </label>
                    <select name="top_k" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="10">10 hasil</option>
                        <option value="20">20 hasil</option>
                        <option value="30">30 hasil</option>
                        <option value="50">50 hasil</option>
                        <option value="all">Lihat Semua</option>
                    </select>
                </div>
            </div>

            <!-- Search Button -->
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
                <i class="fas fa-search mr-2"></i> Cari Berita
            </button>
        </div>

        <!-- Contoh Pencarian -->
        <div class="text-center">
            <p class="text-sm text-gray-600 mb-3">Coba cari:</p>
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ route('search', ['query' => 'gempa', 'top_k' => 10]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm transition">Gempa</a>
                <a href="{{ route('search', ['query' => 'teknologi', 'top_k' => 10]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm transition">Teknologi</a>
                <a href="{{ route('search', ['query' => 'ekonomi', 'top_k' => 10]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm transition">Ekonomi</a>
                <a href="{{ route('search', ['query' => 'kesehatan', 'top_k' => 10]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm transition">Kesehatan</a>
            </div>
        </div>
    </form>

    <!-- Stats Sebelum Pencarian -->
    <div class="text-center py-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">NewsSearch</h1>
        <p class="text-lg text-gray-600 mb-8">
            Cari di antara <strong class="text-blue-600">{{ number_format($stats['total_documents']) }}</strong> berita
            dengan <strong class="text-blue-600">{{ number_format($stats['vocabulary_size']) }}</strong> kata unik
        </p>

        <!-- Engine Status -->
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg">
            <i class="fas fa-server {{ $stats['python_connected'] ? 'text-green-600' : 'text-red-600' }}"></i>
            <span class="text-sm text-gray-600">
                Python Engine:
                <strong>{{ $stats['python_connected'] ? 'Terhubung ✓' : 'Offline ✗' }}</strong>
            </span>
        </div>
    </div>
</div>
@endsection
