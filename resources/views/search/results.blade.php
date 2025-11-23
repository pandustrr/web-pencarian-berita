@extends('layouts.app')

@section('title', 'Hasil Pencarian: ' . ($query ?? ''))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Results Header -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Hasil Pencarian untuk "<span class="text-blue-600">{{ $query ?? '' }}</span>"
                    </h1>
                    @if(isset($processed_query) && $processed_query !== $query)
                    <p class="text-gray-600 text-sm">
                        <i class="fas fa-cog mr-1"></i>Query terproses: "<span class="font-mono">{{ $processed_query }}</span>"
                    </p>
                    @endif
                    <p class="text-gray-600">
                        <i class="fas fa-chart-bar mr-1"></i>Ditemukan
                        <span class="font-semibold text-blue-600">{{ $total_results ?? 0 }}</span> hasil
                    </p>
                </div>

                <div class="flex space-x-3">
                    <a
                        href="{{ route('search.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition duration-200"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>Pencarian Baru
                    </a>
                    <button
                        onclick="window.location.reload()"
                        class="inline-flex items-center px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition duration-200"
                    >
                        <i class="fas fa-redo mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Results List -->
        @if(isset($success) && $success && isset($results) && $results->count() > 0)
            <div class="space-y-6">
                @foreach($results as $result)
                <article class="bg-white rounded-xl shadow-md hover:shadow-lg transition duration-300 transform hover:-translate-y-1 news-card">
                    <div class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-semibold text-gray-900 mb-2 leading-tight">
                                    {{ $result['title'] }}
                                </h3>

                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                                        <i class="fas fa-percentage mr-1"></i>
                                        {{ number_format($result['similarity_percent'], 1) }}% Relevan
                                    </span>
                                    @if(isset($result['category']))
                                    <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                                        <i class="fas fa-tag mr-1"></i>
                                        {{ $result['category'] }}
                                    </span>
                                    @endif
                                    <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                                        <i class="fas fa-database mr-1"></i>
                                        {{ $result['source'] ?? 'IR System' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-700 leading-relaxed mb-4">
                            {{ Str::limit($result['content'], 300) }}
                        </p>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-chart-line mr-1"></i>
                                Skor kemiripan: <span class="font-semibold">{{ number_format($result['similarity_score'], 4) }}</span>
                            </div>
                            <button
                                onclick="showFullContent({{ $result['id'] }}, `{{ addslashes($result['content']) }}`)"
                                class="text-blue-600 hover:text-blue-800 font-medium text-sm transition duration-200 flex items-center"
                            >
                                Baca Selengkapnya
                                <i class="fas fa-chevron-right ml-1 text-xs"></i>
                            </button>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>

        @elseif(isset($success) && !$success)
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
                <div class="text-red-600 text-6xl mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-xl font-semibold text-red-800 mb-2">Terjadi Kesalahan</h3>
                <p class="text-red-600 mb-4">{{ $error ?? 'Sistem pencarian sedang tidak tersedia' }}</p>
                <div class="space-x-4">
                    <a
                        href="{{ route('search.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200"
                    >
                        <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                    </a>
                    <button
                        onclick="window.location.reload()"
                        class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-100 text-red-700 border border-red-300 rounded-lg transition duration-200"
                    >
                        <i class="fas fa-redo mr-2"></i>Coba Lagi
                    </button>
                </div>
            </div>

        @else
            <!-- No Results -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
                <div class="text-yellow-600 text-6xl mb-4">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="text-xl font-semibold text-yellow-800 mb-2">Tidak ada hasil ditemukan</h3>
                <p class="text-yellow-600 mb-6">Coba gunakan kata kunci yang berbeda atau periksa ejaan.</p>

                <div class="max-w-md mx-auto text-left bg-white rounded-lg p-4 mb-6">
                    <p class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Tips pencarian:
                    </p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            Gunakan kata kunci yang lebih spesifik
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            Coba sinonim dari kata kunci Anda
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            Kurangi jumlah kata kunci
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            Periksa ejaan kata kunci
                        </li>
                    </ul>
                </div>

                <a
                    href="{{ route('search.index') }}"
                    class="inline-flex items-center px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition duration-200 font-semibold"
                >
                    <i class="fas fa-arrow-left mr-2"></i>Cari dengan Kata Kunci Lain
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Full Content Modal -->
<div id="contentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold flex items-center">
                <i class="fas fa-newspaper text-blue-600 mr-2"></i>Detail Berita
            </h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition duration-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
            <div id="modalContent" class="prose max-w-none">
                <!-- Content will be inserted here -->
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition duration-200">
                <i class="fas fa-times mr-2"></i>Tutup
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showFullContent(newsId, content) {
    const modalContent = document.getElementById('modalContent');
    const highlightedContent = highlightText(content, '{{ $query ?? "" }}');

    modalContent.innerHTML = `
        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">Informasi Dokumen:</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium">ID Dokumen:</span> ${newsId}
                </div>
                <div>
                    <span class="font-medium">Query:</span> {{ $query ?? '' }}
                </div>
            </div>
        </div>
        <div class="whitespace-pre-wrap leading-relaxed text-gray-700 bg-white p-4 rounded-lg border">
            ${highlightedContent}
        </div>
    `;

    document.getElementById('contentModal').classList.remove('hidden');
    document.getElementById('contentModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('contentModal').classList.add('hidden');
    document.getElementById('contentModal').classList.remove('flex');
}

// Highlight search terms in content
function highlightText(text, query) {
    if (!query || !text) return text;

    const terms = query.toLowerCase().split(' ').filter(term => term.length > 2);
    let highlighted = text;

    terms.forEach(term => {
        const regex = new RegExp(`(${term})`, 'gi');
        highlighted = highlighted.replace(regex, '<mark class="search-highlight">$1</mark>');
    });

    return highlighted;
}

// Close modal with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endpush
