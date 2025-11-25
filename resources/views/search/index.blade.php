@extends('layouts.app')

@section('title', 'Beranda - Sistem Pencarian Berita')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="text-center mb-12 py-8">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Temukan Berita yang Relevan
        </h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
            Sistem pencarian berita canggih menggunakan metode <span class="font-semibold text-blue-600">TF-IDF</span> dan
            <span class="font-semibold text-blue-600">Cosine Similarity</span> untuk memberikan hasil terbaik.
        </p>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white rounded-xl shadow-md p-6 text-center border border-gray-200">
            <div class="text-3xl font-bold text-blue-600 mb-2">{{ $totalNews ?? '0' }}</div>
            <div class="text-gray-600 font-medium">Total Berita Tersedia</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 text-center border border-gray-200">
            <div class="text-3xl font-bold text-green-600 mb-2">TF-IDF</div>
            <div class="text-gray-600 font-medium">Pembobotan Kata</div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 text-center border border-gray-200">
            <div class="text-3xl font-bold text-purple-600 mb-2">Cosine</div>
            <div class="text-gray-600 font-medium">Pengukuran Kemiripan</div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Bagaimana Sistem Bekerja</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-database text-blue-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-lg mb-2">1. Kumpulan Data</h3>
                <p class="text-gray-600 text-sm">Data berita dari berbagai sumber diproses dan disimpan</p>
            </div>
            <div class="text-center">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calculator text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-lg mb-2">2. TF-IDF</h3>
                <p class="text-gray-600 text-sm">Menghitung bobot pentingnya kata dalam dokumen</p>
            </div>
            <div class="text-center">
                <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-lg mb-2">3. Cosine Similarity</h3>
                <p class="text-gray-600 text-sm">Mengukur kemiripan antara query dan dokumen</p>
            </div>
        </div>
    </div>

    <!-- Quick Search Examples -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-8 border border-blue-200">
        <h2 class="text-xl font-bold text-gray-900 mb-4 text-center">Coba Pencarian Cepat</h2>
        <div class="flex flex-wrap justify-center gap-3">
            @php
                $examples = ['gempa', 'polisi', 'teknologi', 'ekonomi', 'kesehatan', 'pendidikan'];
            @endphp
            @foreach($examples as $example)
                <a href="{{ route('search.execute', ['query' => $example, 'top_k' => 10]) }}"
                   class="bg-white text-blue-700 hover:bg-blue-100 px-4 py-2 rounded-lg font-medium transition duration-200 border border-blue-300 hover:border-blue-400 shadow-sm">
                    {{ $example }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
