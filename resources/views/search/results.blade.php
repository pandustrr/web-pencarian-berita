@extends('layouts.app')

@section('title', 'Hasil Pencarian: ' . $query)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Hasil Pencarian</h1>
    <div class="flex flex-wrap items-center gap-4 text-gray-600 mb-4">
        <div class="flex items-center">
            <i class="fas fa-search mr-2"></i>
            Kata kunci: <strong class="ml-1 text-blue-600">"{{ $query }}"</strong>
        </div>
        <div class="hidden sm:block">•</div>
        <div class="flex items-center">
            <i class="fas fa-calculator mr-2"></i>
            Algoritma: <strong class="ml-1">{{ $algorithm }}</strong>
        </div>
        <div class="hidden sm:block">•</div>
        <div class="flex items-center">
            <i class="fas fa-list mr-2"></i>
            Hasil: <strong class="ml-1">{{ count($results) }} ditemukan</strong>
        </div>
    </div>

    <!-- Engine Status -->
    @if($engine === 'python')
    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
        <div class="flex items-center">
            <i class="fab fa-python text-green-500 mr-2"></i>
            <span class="text-green-800 font-medium">✅ Powered by Python TF-IDF Engine</span>
            <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                {{ number_format($stats['vocabulary_size']) }} vocabulary
            </span>
        </div>
    </div>
    @else
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
        <div class="flex items-center">
            <i class="fab fa-php text-blue-500 mr-2"></i>
            <span class="text-blue-800 font-medium">🔄 Using PHP Fallback Engine</span>
        </div>
    </div>
    @endif
</div>

@if(count($results) > 0)
    <div class="space-y-4">
        @foreach($results as $result)
        <div class="bg-white p-6 rounded-lg shadow border hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-3">
                <h3 class="font-semibold text-lg text-gray-900 flex-1 pr-4">
                    @if(isset($result['news']))
                        {{ Str::limit($result['news']->original_text, 100) }}
                    @else
                        {{ Str::limit($result['original_text'], 100) }}
                    @endif
                </h3>
                <div class="flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                        <i class="fas fa-chart-line mr-1"></i>
                        {{ number_format(($result['score'] ?? 0) * 100, 1) }}%
                    </span>
                </div>
            </div>

            <p class="text-gray-700 mb-4 leading-relaxed">
                @if(isset($result['news']))
                    {{ Str::limit($result['news']->original_text, 300) }}
                @else
                    {{ Str::limit($result['original_text'], 300) }}
                @endif
            </p>

            <div class="flex flex-wrap gap-2 text-sm">
                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded flex items-center">
                    <i class="fas fa-tag mr-1"></i>
                    {{ $result['category'] ?? 'General' }}
                </span>
                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded flex items-center">
                    <i class="fas fa-source mr-1"></i>
                    {{ $result['source'] ?? 'CSV' }}
                </span>
                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded flex items-center">
                    <i class="fas fa-hashtag mr-1"></i>
                    ID: {{ $result['index'] ?? 0 }}
                </span>
            </div>

            <div class="mt-4">
                <a href="{{ route('news.show', $result['index'] ?? 0) }}"
                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">
                    <i class="fas fa-eye mr-2"></i>
                    Baca Detail Lengkap
                </a>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="bg-yellow-50 p-8 rounded-lg border border-yellow-200 text-center">
        <i class="fas fa-search text-4xl text-yellow-500 mb-4"></i>
        <h3 class="text-xl font-semibold text-yellow-800 mb-2">Tidak ada hasil ditemukan</h3>
        <p class="text-yellow-700 mb-4">Coba gunakan kata kunci yang berbeda atau lebih spesifik</p>
        <a href="{{ route('home') }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg font-medium">
            <i class="fas fa-home mr-2"></i>Kembali ke Pencarian
        </a>
    </div>
@endif

<!-- Results Summary -->
@if(count($results) > 0)
<div class="mt-8 bg-gray-50 p-4 rounded-lg border">
    <h3 class="font-semibold text-gray-900 mb-2">Ringkasan Pencarian</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div class="text-center">
            <div class="text-lg font-bold text-blue-600">{{ count($results) }}</div>
            <div class="text-gray-600">Total Hasil</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-bold text-green-600">{{ $algorithm }}</div>
            <div class="text-gray-600">Algoritma</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-bold text-purple-600">{{ $engine }}</div>
            <div class="text-gray-600">Engine</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-bold text-orange-600">{{ number_format($stats['total_documents']) }}</div>
            <div class="text-gray-600">Total Dataset</div>
        </div>
    </div>
</div>
@endif
@endsection
