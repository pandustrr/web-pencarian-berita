@extends('layouts.app')

@section('title', 'Detail Berita')

@section('content')
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="bg-blue-600 text-white p-6">
        <a href="{{ url()->previous() }}" class="inline-flex items-center text-blue-200 hover:text-white mb-4">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <h1 class="text-2xl font-bold">{{ $news->title ?? 'Detail Berita' }}</h1>
        <div class="flex gap-4 mt-2 text-blue-200">
            <span>Kategori: {{ $news->category ?? 'General' }}</span>
            <span>Sumber: {{ $news->source ?? 'System' }}</span>
        </div>
    </div>

    <div class="p-6">
        <div class="prose max-w-none">
            <p class="text-gray-700 leading-relaxed whitespace-pre-line">
                {{ $news->original_text ?? 'Tidak ada konten' }}
            </p>
        </div>
    </div>
</div>
@endsection
