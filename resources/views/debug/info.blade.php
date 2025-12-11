@extends('layouts.app')

@section('title', 'System Debug')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">System Debug</h1>

    <!-- Quick Status -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-sm text-gray-600 mb-1">Python Engine</div>
            <div class="text-lg font-bold {{ $stats['python_connected'] ? 'text-green-600' : 'text-red-600' }}">
                {{ $stats['python_connected'] ? '✅ Online' : '❌ Offline' }}
            </div>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-sm text-gray-600 mb-1">Total Berita</div>
            <div class="text-lg font-bold text-blue-600">{{ number_format($stats['total_documents']) }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-sm text-gray-600 mb-1">Kata Unik</div>
            <div class="text-lg font-bold text-blue-600">{{ number_format($stats['vocabulary_size']) }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-sm text-gray-600 mb-1">Duplikat Filter</div>
            <div class="text-lg font-bold text-green-600">✅ Always On</div>
        </div>
    </div>


    <!-- System Information -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">System Information</h2>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600">Python API Status</div>
                    <div class="text-base font-semibold text-gray-900">
                        {{ $stats['python_connected'] ? '✅ Connected' : '❌ Disconnected' }}
                    </div>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600">Database Status</div>
                    <div class="text-base font-semibold text-gray-900">✅ Connected</div>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600">CSV File</div>
                    <div class="text-base font-semibold {{ $stats['csv_exists'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $stats['csv_exists'] ? '✅ Found' : '❌ Missing' }}
                    </div>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600">CSV Filename</div>
                    <div class="text-base font-semibold text-gray-900">
                        {{ basename($debugInfo['csv_absolute_path'] ?? 'N/A') }}
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600">Duplikat Filter</div>
                    <div class="text-base font-semibold text-green-600">✅ Always Active</div>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600">Duplikat Threshold</div>
                    <div class="text-base font-semibold text-gray-900">> 90% similarity</div>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600">Auto Threshold</div>
                    <div class="text-base font-semibold text-gray-900">Based on Keywords</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Quick Test</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('search', ['query' => 'gempa', 'top_k' => 10]) }}"
               class="px-4 py-2 bg-blue-100 text-blue-700 rounded border border-blue-300 hover:bg-blue-200 text-sm font-medium">
                Search: gempa
            </a>
            <a href="{{ route('search', ['query' => 'teknologi', 'top_k' => 10]) }}"
               class="px-4 py-2 bg-green-100 text-green-700 rounded border border-green-300 hover:bg-green-200 text-sm font-medium">
                Search: teknologi
            </a>
            <a href="{{ route('search', ['query' => 'politik', 'top_k' => 10]) }}"
               class="px-4 py-2 bg-purple-100 text-purple-700 rounded border border-purple-300 hover:bg-purple-200 text-sm font-medium">
                Search: politik
            </a>
        </div>
    </div>
    <!-- Footer -->
    <div class="text-center text-sm text-gray-500 mt-8">
        <p>System Debug Page • Last Updated: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</div>
@endsection
