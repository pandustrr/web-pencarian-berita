@extends('layouts.app')

@section('title', 'Detail Berita')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium mb-3">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $news->title ?? 'Berita #' . $news->id }}</h1>
        @if(!empty($news->category))
        <p class="text-gray-600">
            Kategori:
            <span class="font-semibold bg-blue-100 text-blue-700 px-3 py-1 rounded inline-block text-sm">
                {{ $news->category }}
            </span>
        </p>
        @endif
        @if(!empty($news->source))
        <p class="text-gray-500 text-sm mt-2">
            <i class="fas fa-globe mr-2"></i>Sumber: <span class="font-medium">{{ $news->source }}</span>
        </p>
        @endif
    </div>

    <!-- Content -->
    <div class="bg-white rounded-lg shadow border p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Isi Berita</h2>
        @if(!empty($news->original_text))
        <p class="text-gray-700 leading-relaxed whitespace-pre-wrap text-justify">
            {{ $news->original_text }}
        </p>
        @else
        <p class="text-gray-500 italic">Tidak ada konten berita tersedia</p>
        @endif
    </div>

    <!-- Info Berita -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-lg border p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Informasi</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID Berita:</span>
                    <span class="font-medium text-blue-600">#{{ $news->id ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Kategori:</span>
                    <span class="font-medium">
                        @if(isset($news->category) && $news->category && $news->category !== '-')
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">{{ $news->category }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Sumber:</span>
                    <span class="font-medium">{{ $news->source ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Statistik Konten</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Jumlah Kata:</span>
                    <span class="font-medium">{{ number_format(str_word_count($news->original_text ?? '')) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Jumlah Karakter:</span>
                    <span class="font-medium">{{ number_format(strlen($news->original_text ?? '')) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Rata-rata Panjang Kata:</span>
                    <span class="font-medium">
                        @php
                            $wordCount = str_word_count($news->original_text ?? '');
                            $charCount = strlen($news->original_text ?? '');
                            $avgWordLength = $wordCount > 0 ? round($charCount / $wordCount, 1) : 0;
                        @endphp
                        {{ $avgWordLength }} karakter
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ url()->previous() }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium text-center transition">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <a href="{{ route('home') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium text-center transition">
            <i class="fas fa-home mr-2"></i> Beranda
        </a>
    </div>
</div>
@endsection
