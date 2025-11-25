@extends('layouts.app')

@section('title', 'Debug Information')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">🔧 Debug Information</h1>

    <!-- System Status -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- CSV Status -->
        <div class="bg-white rounded-lg shadow p-6 {{ $csvExists ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' }}">
            <h2 class="text-xl font-semibold mb-4">CSV File Status</h2>
            <div class="space-y-2">
                <p><strong>Exists:</strong>
                    <span class="{{ $csvExists ? 'text-green-600 font-bold' : 'text-red-600 font-bold' }}">
                        {{ $csvExists ? '✅ YES' : '❌ NO' }}
                    </span>
                </p>
                <p><strong>Path:</strong> <code class="bg-gray-100 p-1 rounded text-xs">{{ $csvPath }}</code></p>
                <p><strong>Full Path:</strong> <code class="bg-gray-100 p-1 rounded text-xs break-all">{{ $fullPath }}</code></p>
                <p><strong>Size:</strong> {{ number_format($csvSize) }} bytes ({{ number_format($csvSize / 1024 / 1024, 2) }} MB)</p>
                <p><strong>Records:</strong> <span class="font-bold text-blue-600">{{ $csvCount }}</span></p>
            </div>
        </div>

        <!-- Database Status -->
        <div class="bg-white rounded-lg shadow p-6 {{ $dbCount > 0 ? 'border-l-4 border-green-500' : 'border-l-4 border-yellow-500' }}">
            <h2 class="text-xl font-semibold mb-4">Database Status</h2>
            <div class="space-y-2">
                <p><strong>Records:</strong>
                    <span class="{{ $dbCount > 0 ? 'text-green-600 font-bold' : 'text-yellow-600 font-bold' }}">
                        {{ $dbCount }}
                    </span>
                </p>
                <p><strong>Status:</strong>
                    <span class="{{ $dbCount > 0 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $dbCount > 0 ? 'Active' : 'Empty' }}
                    </span>
                </p>
                <p><strong>Total Available:</strong>
                    <span class="font-bold text-purple-600">{{ $dbCount + $csvCount }}</span> records
                </p>
            </div>
        </div>
    </div>

    <!-- Test Search Results -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">🧪 Test Search Results</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach($testResults as $query => $result)
                <div class="border rounded p-4 text-center {{ $result['count'] > 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <div class="text-lg font-bold mb-2">"{{ $query }}"</div>
                    <div class="text-2xl font-bold {{ $result['count'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $result['count'] }}
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        @if(!empty($result['scores']))
                            @foreach(array_slice($result['scores'], 0, 2) as $score)
                                {{ number_format($score, 3) }}@if(!$loop->last), @endif
                            @endforeach
                        @else
                            No matches
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Sample Data -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Sample CSV Records -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">📋 Sample CSV Records (First 5)</h2>
            <div class="space-y-4">
                @foreach($sampleRecords as $index => $record)
                    <div class="border rounded p-4 bg-gray-50">
                        <h3 class="font-semibold text-lg mb-2">Record #{{ $index }}</h3>
                        <div class="text-sm">
                            <p><strong>Text:</strong> {{ Str::limit($record['text'] ?? 'N/A', 100) }}</p>
                            <p><strong>Category:</strong> {{ $record['category'] ?? 'N/A' }}</p>
                            <p><strong>Source:</strong> {{ $record['source'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Sample DB Records -->
        @if($sampleDbRecords->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">💾 Sample Database Records (First 5)</h2>
            <div class="space-y-4">
                @foreach($sampleDbRecords as $news)
                    <div class="border rounded p-4 bg-blue-50">
                        <h3 class="font-semibold text-lg mb-2">ID: {{ $news->id }}</h3>
                        <div class="text-sm">
                            <p><strong>Title:</strong> {{ $news->title }}</p>
                            <p><strong>Text:</strong> {{ Str::limit($news->original_text, 100) }}</p>
                            <p><strong>Category:</strong> {{ $news->category ?? 'N/A' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="bg-white rounded-lg shadow p-6 mt-8">
        <h2 class="text-xl font-semibold mb-4">⚡ Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('csv.import') }}"
               class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                <i class="fas fa-download mr-2"></i>Import CSV to Database
            </a>
            <a href="{{ route('system.info') }}" target="_blank"
               class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                <i class="fas fa-info-circle mr-2"></i>System Info (JSON)
            </a>
            <a href="{{ route('test.search', 'gempa') }}" target="_blank"
               class="inline-flex items-center bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                <i class="fas fa-search mr-2"></i>Test Search "gempa"
            </a>
            <a href="{{ route('search.index') }}"
               class="inline-flex items-center bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-home mr-2"></i>Back to Search
            </a>
        </div>
    </div>
</div>
@endsection
