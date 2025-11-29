@extends('layouts.app')

@section('title', 'System Debug Information')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">System Debug Information</h1>

    <!-- System Status -->
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 {{ $stats['python_connected'] ? 'border-green-500' : 'border-red-500' }}">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fab fa-python mr-2"></i>
                Python TF-IDF Service
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="font-medium">Status:</span>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $stats['python_connected'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $stats['python_connected'] ? 'TERHUBUNG' : 'TERPUTUS' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium">Vocabulary Size:</span>
                    <span class="font-bold text-blue-600">{{ number_format($stats['vocabulary_size']) }} kata</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium">Total Documents:</span>
                    <span class="font-bold text-green-600">{{ number_format($stats['total_documents']) }} berita</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 {{ $stats['csv_exists'] ? 'border-green-500' : 'border-red-500' }}">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-database mr-2"></i>
                Dataset Information
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="font-medium">Total Documents:</span>
                    <span class="font-bold text-blue-600">{{ number_format($stats['total_documents']) }} berita</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium">CSV File:</span>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $stats['csv_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $stats['csv_exists'] ? 'ADA' : 'TIDAK ADA' }}
                    </span>
                </div>
                @if($stats['csv_exists'])
                <div class="flex justify-between items-center">
                    <span class="font-medium">File Size:</span>
                    <span class="font-bold text-purple-600">{{ number_format($debugInfo['csv_file_size'] / 1024 / 1024, 2) }} MB</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Debug Information -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Debug Details</h2>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium">CSV Path:</span>
                    <span class="text-gray-600 text-xs">{{ $debugInfo['csv_absolute_path'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">CSV Exists:</span>
                    <span class="text-gray-600">{{ $debugInfo['csv_file_exists'] ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Python URL:</span>
                    <span class="text-gray-600">{{ $debugInfo['python_url'] }}</span>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium">File Size:</span>
                    <span class="text-gray-600">{{ number_format($debugInfo['csv_file_size']) }} bytes</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Python Connected:</span>
                    <span class="text-gray-600">{{ $stats['python_connected'] ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Last Check:</span>
                    <span class="text-gray-600">{{ $debugInfo['current_time'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- File Check -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">File System Check</h2>
        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 {{ $stats['csv_exists'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }} rounded">
                <div class="flex items-center">
                    <i class="fas fa-file-csv mr-3 {{ $stats['csv_exists'] ? 'text-green-500' : 'text-red-500' }}"></i>
                    <div>
                        <div class="font-medium">preprocessed_news.csv</div>
                        <div class="text-sm text-gray-600">{{ $debugInfo['csv_absolute_path'] }}</div>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $stats['csv_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $stats['csv_exists'] ? 'FOUND' : 'MISSING' }}
                </span>
            </div>

            @if($stats['csv_exists'])
            <div class="bg-blue-50 p-3 rounded border border-blue-200">
                <div class="text-sm">
                    <strong>File Details:</strong>
                    <div>Size: {{ number_format($debugInfo['csv_file_size'] / 1024 / 1024, 2) }} MB</div>
                    <div>Total Records: {{ number_format($stats['total_documents']) }} berita</div>
                    <div>Path: {{ $debugInfo['csv_absolute_path'] }}</div>
                </div>
            </div>
            @else
            <div class="bg-red-50 p-3 rounded border border-red-200">
                <div class="text-sm text-red-700">
                    <strong>File tidak ditemukan!</strong>
                    <div>Pastikan file CSV berada di: python_app/preprocessed_news.csv</div>
                    <div>Current path: {{ $debugInfo['csv_absolute_path'] }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
