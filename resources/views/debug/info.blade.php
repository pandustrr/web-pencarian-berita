@extends('layouts.app')

@section('title', 'Debug Information')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">🔧 Debug Information</h1>

    <!-- System Status -->
    <div class="grid grid-cols-1 gap-6 mb-8">
        <!-- CSV Status -->
        <div class="bg-white rounded-lg shadow p-6 {{ $csvExists ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' }}">
            <h2 class="text-xl font-semibold mb-4">📊 CSV File Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $csvExists ? 'text-green-600' : 'text-red-600' }} mb-1">
                        {{ $csvExists ? '✅' : '❌' }}
                    </div>
                    <p class="font-medium text-gray-700">Status</p>
                    <p class="text-sm text-gray-600">{{ $csvExists ? 'Available' : 'Not Found' }}</p>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 mb-1">{{ $csvCount }}</div>
                    <p class="font-medium text-gray-700">Records</p>
                    <p class="text-sm text-gray-600">Total loaded</p>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 mb-1">5,000</div>
                    <p class="font-medium text-gray-700">TF-IDF Limit</p>
                    <p class="text-sm text-gray-600">For performance</p>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600 mb-1">{{ number_format($csvSize / 1024 / 1024, 1) }}</div>
                    <p class="font-medium text-gray-700">File Size</p>
                    <p class="text-sm text-gray-600">MB</p>
                </div>
            </div>
            <div class="mt-4 p-3 bg-gray-50 rounded border">
                <p class="text-sm font-medium text-gray-700 mb-1">File Path:</p>
                <code class="text-xs text-gray-600 break-all">{{ $fullPath }}</code>
            </div>
        </div>
    </div>

    <!-- TF-IDF Configuration -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-blue-900 mb-4">🎯 TF-IDF + Cosine Similarity Configuration</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div class="bg-white p-4 rounded-lg border border-blue-100 text-center">
                <div class="text-2xl font-bold text-blue-600 mb-1">{{ $csvCount }}</div>
                <p class="font-semibold text-blue-800">Dataset Size</p>
                <p class="text-xs text-blue-600 mt-1">5,000 TF-IDF limit</p>
            </div>
            <div class="bg-white p-4 rounded-lg border border-green-100 text-center">
                <div class="text-2xl font-bold text-green-600 mb-1">TF-IDF</div>
                <p class="font-semibold text-green-800">Algorithm</p>
                <p class="text-xs text-green-600 mt-1">Full implementation</p>
            </div>
            <div class="bg-white p-4 rounded-lg border border-purple-100 text-center">
                <div class="text-2xl font-bold text-purple-600 mb-1">Cosine</div>
                <p class="font-semibold text-purple-800">Similarity</p>
                <p class="text-xs text-purple-600 mt-1">Vector comparison</p>
            </div>
            <div class="bg-white p-4 rounded-lg border border-yellow-100 text-center">
                <div class="text-2xl font-bold text-yellow-600 mb-1">5K</div>
                <p class="font-semibold text-yellow-800">Performance</p>
                <p class="text-xs text-yellow-600 mt-1">Optimized limit</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-6 mb-8">
        <div class="flex items-center mb-4">
            <i class="fas fa-bolt text-yellow-500 text-xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-yellow-800 text-lg">Quick Actions</h3>
                <p class="text-yellow-700 text-sm">Test the TF-IDF system quickly</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="/force-reload" class="inline-flex items-center bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded transition text-sm">
                <i class="fas fa-sync-alt mr-2"></i>Force Reload CSV
            </a>
            <a href="/test-simple" class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition text-sm">
                <i class="fas fa-chart-bar mr-2"></i>Test Data ({{ $csvCount }})
            </a>
            <a href="/search?query=gempa" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition text-sm">
                <i class="fas fa-search mr-2"></i>Test TF-IDF Search
            </a>
        </div>
    </div>

    <!-- Test Search Results -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">🧪 TF-IDF Search Test Results</h2>
        <p class="text-gray-600 text-sm mb-4">Testing search with various queries using TF-IDF + Cosine Similarity:</p>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            @foreach($testResults as $query => $result)
                <div class="border rounded-lg p-4 text-center transition-all duration-200 {{ $result['count'] > 0 ? 'bg-green-50 border-green-200 hover:bg-green-100' : 'bg-red-50 border-red-200 hover:bg-red-100' }}">
                    <div class="font-semibold text-gray-800 mb-2">"{{ $query }}"</div>
                    <div class="text-2xl font-bold {{ $result['count'] > 0 ? 'text-green-600' : 'text-red-600' }} mb-1">
                        {{ $result['count'] }}
                    </div>
                    <div class="text-xs text-gray-600 mb-2">matches</div>
                    @if(!empty($result['scores']))
                    <div class="text-xs text-gray-500">
                        @foreach(array_slice($result['scores'], 0, 1) as $score)
                            Score: {{ number_format($score, 3) }}
                        @endforeach
                    </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <strong>Note:</strong> Using <strong>TF-IDF + Cosine Similarity</strong> algorithm on
                <strong>{{ $csvCount }} records</strong> (5,000 record limit for optimal performance)
            </p>
        </div>
    </div>

    <!-- Sample Data -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold">📋 Sample CSV Records</h2>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                First 5 of {{ $csvCount }}
            </span>
        </div>

        @if(count($sampleRecords) > 0)
        <div class="space-y-4">
            @foreach($sampleRecords as $index => $record)
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 hover:bg-gray-100 transition duration-200">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-semibold text-gray-800">Record #{{ $index + 1 }}</h3>
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                            ID: {{ $index }}
                        </span>
                    </div>
                    <div class="text-sm space-y-2">
                        <p><strong class="text-gray-700">Text:</strong>
                            <span class="text-gray-600">{{ Str::limit($record['text'] ?? 'N/A', 100) }}</span>
                        </p>
                        <p><strong class="text-gray-700">Processed:</strong>
                            <span class="text-gray-600">{{ Str::limit($record['processed'] ?? 'N/A', 80) }}</span>
                        </p>
                        <div class="flex gap-2 mt-3">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                                {{ $record['category'] ?? 'General' }}
                            </span>
                            <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">
                                {{ $record['source'] ?? 'Berita Online' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-exclamation-triangle text-3xl mb-3"></i>
            <p>No CSV records found</p>
        </div>
        @endif
    </div>

    <!-- TF-IDF Technical Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-6">⚙️ TF-IDF Technical Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="font-semibold text-gray-900 mb-4 text-lg">Algorithm Details</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <i class="fas fa-calculator mt-1 mr-3 text-blue-500"></i>
                        <div>
                            <p class="font-medium text-gray-800">TF Formula</p>
                            <p class="text-sm text-gray-600">TF(t,d) = f<sub>t,d</sub> / Σf<sub>i,d</sub></p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-chart-line mt-1 mr-3 text-green-500"></i>
                        <div>
                            <p class="font-medium text-gray-800">IDF Formula</p>
                            <p class="text-sm text-gray-600">IDF(t) = log(N / df<sub>t</sub>) + 1</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-ruler-combined mt-1 mr-3 text-purple-500"></i>
                        <div>
                            <p class="font-medium text-gray-800">Cosine Similarity</p>
                            <p class="text-sm text-gray-600">cos(θ) = A·B / (||A|| × ||B||)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 mb-4 text-lg">System Configuration</h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <i class="fas fa-database mr-3 text-yellow-500"></i>
                        <div>
                            <p class="font-medium text-gray-800">Dataset Size</p>
                            <p class="text-sm text-gray-600">{{ $csvCount }} records (5,000 TF-IDF limit)</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-microchip mr-3 text-red-500"></i>
                        <div>
                            <p class="font-medium text-gray-800">Processing</p>
                            <p class="text-sm text-gray-600">TF-IDF Matrix + Vector Comparison</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-bolt mr-3 text-green-500"></i>
                        <div>
                            <p class="font-medium text-gray-800">Performance</p>
                            <p class="text-sm text-gray-600">Optimized for 5,000 records</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-refresh debug info every 30 seconds
    setTimeout(() => {
        window.location.reload();
    }, 30000);
</script>
@endsection
