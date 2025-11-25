@extends('layouts.app')

@section('title', 'Detail Berita')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="gradient-bg text-white p-6">
            <div class="flex justify-between items-start">
                <div>
                    <a href="{{ url()->previous() }}" class="inline-flex items-center text-blue-100 hover:text-white mb-4">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <h1 class="text-2xl font-bold">{{ $news->title ?? 'Detail Berita' }}</h1>
                </div>
                <div class="text-right">
                    @if($news->category)
                        <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm">
                            {{ $news->category }}
                        </span>
                    @endif
                    @if($news->source)
                        <p class="text-blue-100 text-sm mt-2">Sumber: {{ $news->source }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <div class="prose max-w-none">
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Isi Berita:</h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-line">
                        {{ $news->original_text }}
                    </p>
                </div>

                @if($news->translated_text && $news->translated_text != $news->original_text)
                    <div class="bg-blue-50 rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold text-blue-900 mb-3">Terjemahan:</h3>
                        <p class="text-blue-800 leading-relaxed whitespace-pre-line">
                            {{ $news->translated_text }}
                        </p>
                    </div>
                @endif

                @if(property_exists($news, 'processed_text') && $news->processed_text)
                    <div class="bg-green-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-green-900 mb-3">Teks yang Diproses (TF-IDF):</h3>
                        <p class="text-green-800 leading-relaxed text-sm">
                            {{ $news->processed_text }}
                        </p>
                    </div>
                @endif
            </div>

            <!-- Metadata -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Informasi Dokumen</h4>
                        <p><strong>ID:</strong> {{ $news->id }}</p>
                        <p><strong>Sumber:</strong> {{ $news->source ?? 'Tidak diketahui' }}</p>
                        <p><strong>Kategori:</strong> {{ $news->category ?? 'Tidak ada' }}</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Statistik</h4>
                        <p><strong>Panjang teks asli:</strong> {{ strlen($news->original_text) }} karakter</p>
                        @if(property_exists($news, 'processed_text') && $news->processed_text)
                            <p><strong>Panjang teks diproses:</strong> {{ strlen($news->processed_text) }} karakter</p>
                        @endif
                        <p><strong>Tanggal:</strong> {{ $news->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
