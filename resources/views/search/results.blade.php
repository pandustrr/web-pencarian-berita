@extends('layouts.app')

@section('title', 'Hasil Pencarian - ' . request('query'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Search Results Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="mb-4 md:mb-0">
                <h1 class="text-2xl font-bold text-gray-900">Hasil Pencarian</h1>
                <div class="flex items-center mt-2 space-x-4">
                    <p class="text-gray-600">
                        <span class="font-semibold">Kata kunci:</span>
                        <span class="text-blue-600 bg-blue-50 px-2 py-1 rounded-md">"{{ $query }}"</span>
                    </p>
                    <span class="text-gray-400">•</span>
                    <p class="text-gray-600">{{ $totalFound }} hasil ditemukan</p>
                    @if(isset($algorithm))
                    <span class="text-gray-400">•</span>
                    <p class="text-gray-600">
                        <span class="font-semibold">Algoritma:</span> {{ $algorithm }}
                    </p>
                    @endif
                </div>
            </div>

            <a href="{{ route('search.index') }}"
               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Pencarian
            </a>
        </div>

        <!-- Search Info -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 mb-6">
            <div class="flex items-start">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-calculator text-blue-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-blue-900 mb-2">Metode TF-IDF + Cosine Similarity</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-800">
                        <div>
                            <p class="font-semibold">📊 TF (Term Frequency)</p>
                            <p class="text-xs">TF(t,d) = f<sub>t,d</sub> / Σf<sub>i,d</sub></p>
                            <p class="text-xs mt-1">Frekuensi kata dalam dokumen</p>
                        </div>
                        <div>
                            <p class="font-semibold">📈 IDF (Inverse Document Frequency)</p>
                            <p class="text-xs">IDF(t) = log(N / df<sub>t</sub>) + 1</p>
                            <p class="text-xs mt-1">Kepadatan kata dalam korpus</p>
                        </div>
                        <div>
                            <p class="font-semibold">🎯 Cosine Similarity</p>
                            <p class="text-xs">cos(θ) = A·B / (||A|| × ||B||)</p>
                            <p class="text-xs mt-1">Kemiripan vektor query-dokumen</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Algorithm Performance -->
        @if($results->count() > 0)
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-rocket text-green-500 mr-3"></i>
                <div>
                    <p class="text-green-800 text-sm font-semibold">
                        ✅ Pencarian berhasil dengan {{ $algorithm ?? 'TF-IDF + Cosine Similarity' }}
                    </p>
                    <p class="text-green-700 text-xs mt-1">
                        Hasil diurutkan berdasarkan nilai kemiripan tertinggi ({{ number_format($results->max('score') * 100, 1) }}% match tertinggi)
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Results -->
    @if($results->count() > 0)
        <div class="space-y-6">
            @foreach($results as $result)
                <div class="news-card bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300">
                    <div class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <!-- Content -->
                            <div class="flex-1">
                                <!-- Score Badge dengan detail TF-IDF -->
                                <div class="flex flex-wrap items-center gap-3 mb-4">
                                    <div class="inline-flex items-center
                                        @if($result['score'] > 0.7) bg-gradient-to-r from-green-500 to-emerald-600
                                        @elseif($result['score'] > 0.3) bg-gradient-to-r from-yellow-500 to-orange-500
                                        @else bg-gradient-to-r from-blue-500 to-blue-600
                                        @endif text-white px-4 py-2 rounded-full text-sm font-semibold">
                                        <i class="fas fa-chart-line mr-2"></i>
                                        {{ number_format($result['score'] * 100, 1) }}% Match
                                    </div>

                                    <!-- Confidence Level -->
                                    <div class="flex items-center text-xs text-gray-600">
                                        <span class="font-semibold">Tingkat Relevansi:</span>
                                        <span class="ml-2 px-2 py-1 rounded
                                            @if($result['score'] > 0.7) bg-green-100 text-green-800
                                            @elseif($result['score'] > 0.3) bg-yellow-100 text-yellow-800
                                            @else bg-blue-100 text-blue-800
                                            @endif">
                                            @if($result['score'] > 0.7) Sangat Tinggi
                                            @elseif($result['score'] > 0.3) Tinggi
                                            @else Sedang
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <!-- News Content -->
                                <div class="mb-4">
                                    <h3 class="font-semibold text-lg text-gray-900 mb-2 flex items-center">
                                        <i class="fas fa-newspaper text-blue-500 mr-2"></i>
                                        Ringkasan Berita
                                    </h3>
                                    <p class="text-gray-700 leading-relaxed search-result-text bg-gray-50 p-4 rounded-lg"
                                       data-original-text="{{ $result['news']->original_text }}">
                                        {{ Str::limit($result['news']->original_text, 400) }}
                                    </p>
                                </div>

                                <!-- Metadata -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    @if($result['news']->category)
                                        <span class="inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-md font-medium">
                                            <i class="fas fa-tag mr-2 text-xs"></i>
                                            {{ $result['news']->category }}
                                        </span>
                                    @endif
                                    @if($result['news']->source)
                                        <span class="inline-flex items-center bg-purple-100 text-purple-800 px-3 py-1 rounded-md">
                                            <i class="fas fa-source mr-2"></i>
                                            {{ $result['news']->source }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center bg-gray-100 text-gray-700 px-3 py-1 rounded-md">
                                        <i class="fas fa-calendar mr-2"></i>
                                        {{ $result['news']->created_at->format('d M Y') }}
                                    </span>
                                    <span class="inline-flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-md">
                                        <i class="fas fa-hashtag mr-2"></i>
                                        ID: {{ $result['news']->id }}
                                    </span>
                                </div>

                                <!-- TF-IDF Info -->
                                <div class="mt-4 p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                                    <p class="text-xs text-indigo-800">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <strong>Analisis TF-IDF:</strong> Dokumen ini memiliki kemiripan
                                        <strong>{{ number_format($result['score'] * 100, 1) }}%</strong> dengan query Anda
                                        berdasarkan perhitungan Cosine Similarity antara vektor TF-IDF.
                                    </p>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="lg:pl-4 flex-shrink-0">
                                <div class="space-y-3">
                                    <a href="{{ route('search.show', $result['news']->id) }}"
                                       class="inline-flex items-center justify-center w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition duration-200 transform hover:scale-105 shadow-md">
                                        <i class="fas fa-eye mr-2"></i>
                                        Lihat Detail Lengkap
                                    </a>
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            Analisis TF-IDF & Cosine Similarity
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Results Summary -->
        <div class="mt-8 bg-white rounded-lg shadow p-6 border border-gray-200">
            <h3 class="font-semibold text-lg text-gray-900 mb-4 flex items-center">
                <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                Ringkasan Hasil Pencarian
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $totalFound }}</div>
                    <div class="text-blue-800">Total Hasil</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($results->max('score') * 100, 1) }}%</div>
                    <div class="text-green-800">Match Tertinggi</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">{{ number_format($results->avg('score') * 100, 1) }}%</div>
                    <div class="text-yellow-800">Rata-rata Match</div>
                </div>
                <div class="text-center p-3 bg-purple-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ $algorithm ?? 'TF-IDF' }}</div>
                    <div class="text-purple-800">Algoritma</div>
                </div>
            </div>
        </div>

    @else
        <!-- No Results dengan penjelasan TF-IDF -->
        <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
            <div class="text-6xl mb-4 text-gray-300">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="text-2xl font-semibold text-gray-900 mb-4">Tidak ada hasil ditemukan untuk "{{ $query }}"</h3>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6 max-w-2xl mx-auto">
                <h4 class="font-semibold text-yellow-800 mb-3 flex items-center justify-center">
                    <i class="fas fa-microscope mr-2"></i>
                    Analisis TF-IDF
                </h4>
                <p class="text-yellow-700 text-sm mb-3">
                    Sistem menggunakan TF-IDF dan Cosine Similarity untuk menghitung kemiripan.
                    Tidak ada dokumen yang memiliki kemiripan signifikan dengan query Anda.
                </p>
                <div class="text-xs text-yellow-600 grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>• TF-IDF membandingkan bobot kata kunci</div>
                    <div>• Cosine Similarity mengukur sudut kemiripan</div>
                    <div>• Threshold similarity: > 0.001</div>
                    <div>• Vocabulary size: {{ $totalFound ?? 'N/A' }} terms</div>
                </div>
            </div>

            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                Coba gunakan strategi pencarian berikut:
            </p>

            <div class="max-w-lg mx-auto text-left mb-6">
                <ul class="text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <div>
                            <span class="font-semibold">Gunakan kata kunci spesifik</span>
                            <p class="text-xs text-gray-500">TF-IDF bekerja lebih baik dengan kata kunci yang unik</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <div>
                            <span class="font-semibold">Coba sinonim atau kata terkait</span>
                            <p class="text-xs text-gray-500">Vocabulary TF-IDF terbatas pada kata dalam dataset</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <div>
                            <span class="font-semibold">Periksa ejaan kata kunci</span>
                            <p class="text-xs text-gray-500">Preprocessing membersihkan teks sebelum TF-IDF</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="space-y-3">
                <a href="{{ route('search.index') }}"
                   class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition duration-200">
                    <i class="fas fa-home mr-2"></i>Kembali ke Pencarian
                </a>
                <br>
                <a href="{{ route('debug.info') }}"
                   class="inline-flex items-center bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition duration-200 text-sm">
                    <i class="fas fa-bug mr-2"></i>Debug Information
                </a>
            </div>
        </div>
    @endif
</div>

<script>
    // Apply highlighting when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const query = "{{ $query }}";
        if (query) {
            document.querySelectorAll('.search-result-text').forEach(element => {
                const originalText = element.getAttribute('data-original-text') || element.textContent;
                element.innerHTML = highlightText(originalText, query);
            });
        }
    });

    // Add animation to result cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.news-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate-fade-in-up');
        });
    });
</script>

<style>
    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.5s ease-out forwards;
    }
</style>
@endsection
