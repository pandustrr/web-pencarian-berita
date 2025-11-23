@extends('layouts.app')

@section('title', 'Pencarian Berita - Sistem TF-IDF')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="text-center mb-16">
            <div class="mb-8">
                <div class="w-24 h-24 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <i class="fas fa-newspaper text-white text-4xl"></i>
                </div>
                <h1 class="text-5xl font-bold text-gray-900 mb-4">
                    Sistem Temu Kembali Informasi Berita
                </h1>
                <p class="text-xl text-gray-700 mb-6 max-w-3xl mx-auto leading-relaxed">
                    Implementasi metode <span class="font-semibold text-blue-700">TF-IDF</span> dan
                    <span class="font-semibold text-green-700">Cosine Similarity</span> untuk pencarian
                    berita online yang akurat dan relevan
                </p>

                <!-- Status Indicators -->
                <div class="flex flex-wrap justify-center gap-4 mb-8">
                    <div class="flex items-center bg-white rounded-lg px-4 py-2 shadow-sm">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        <span class="text-sm text-gray-800 font-medium">Laravel Ready</span>
                    </div>
                    <div id="pythonStatus" class="flex items-center bg-white rounded-lg px-4 py-2 shadow-sm">
                        <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                        <span class="text-sm text-gray-800 font-medium">Checking Python Service...</span>
                    </div>
                    <div class="flex items-center bg-white rounded-lg px-4 py-2 shadow-sm">
                        <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                        <span class="text-sm text-gray-800 font-medium">Bahasa Indonesia</span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 max-w-4xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center transform hover:scale-105 transition duration-300">
                    <div class="text-3xl font-bold text-blue-600 mb-2" id="documentCount">-</div>
                    <div class="text-gray-700 font-medium">Dokumen Berita</div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center transform hover:scale-105 transition duration-300">
                    <div class="text-3xl font-bold text-green-600 mb-2">TF-IDF</div>
                    <div class="text-gray-700 font-medium">Pembobotan Kata</div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center transform hover:scale-105 transition duration-300">
                    <div class="text-3xl font-bold text-purple-600 mb-2">Cosine</div>
                    <div class="text-gray-700 font-medium">Pengukuran Kemiripan</div>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid md:grid-cols-3 gap-8 mb-16">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center hover:shadow-2xl transition duration-300">
                <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-weight-hanging text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">TF-IDF Weighting</h3>
                <p class="text-gray-700 leading-relaxed">
                    Pembobotan kata berdasarkan frekuensi kemunculan dalam dokumen dan kelangkaan dalam koleksi
                </p>

            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 text-center hover:shadow-2xl transition duration-300">
                <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-calculator text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Cosine Similarity</h3>
                <p class="text-gray-700 leading-relaxed">
                    Pengukuran kemiripan antara vektor query dan dokumen menggunakan perhitungan cosine
                </p>
                <div class="mt-4 text-sm text-green-700 font-semibold">
                    Similarity
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 text-center hover:shadow-2xl transition duration-300">
                <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-language text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Indonesian NLP</h3>
                <p class="text-gray-700 leading-relaxed">
                    Preprocessing teks bahasa Indonesia dengan stemming, stopword removal, dan case folding
                </p>

            </div>
        </div>

        <!-- Quick Search Examples -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">Coba Pencarian Cepat</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <button onclick="setSearchExample('politik Indonesia')"
                        class="bg-blue-50 hover:bg-blue-100 text-blue-800 py-3 px-4 rounded-lg transition duration-200 text-sm font-semibold">
                    🏛️ Politik
                </button>
                <button onclick="setSearchExample('ekonomi bisnis')"
                        class="bg-green-50 hover:bg-green-100 text-green-800 py-3 px-4 rounded-lg transition duration-200 text-sm font-semibold">
                    💼 Ekonomi
                </button>
                <button onclick="setSearchExample('teknologi digital')"
                        class="bg-purple-50 hover:bg-purple-100 text-purple-800 py-3 px-4 rounded-lg transition duration-200 text-sm font-semibold">
                    💻 Teknologi
                </button>
                <button onclick="setSearchExample('olahraga sepak bola')"
                        class="bg-red-50 hover:bg-red-100 text-red-800 py-3 px-4 rounded-lg transition duration-200 text-sm font-semibold">
                    ⚽ Olahraga
                </button>
            </div>
        </div>

        <!-- How It Works -->
        <div class="mt-16 bg-white rounded-2xl shadow-lg p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Cara Kerja Sistem</h3>
            <div class="grid md:grid-cols-9 gap-4 items-center">
                <div class="text-center md:col-span-2">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-blue-700 font-bold text-lg">1</span>
                    </div>
                    <p class="text-sm text-gray-800 font-medium">Input Query</p>
                </div>
                <div class="text-center text-gray-400 md:col-span-1">
                    <i class="fas fa-arrow-right text-xl"></i>
                </div>
                <div class="text-center md:col-span-2">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-green-700 font-bold text-lg">2</span>
                    </div>
                    <p class="text-sm text-gray-800 font-medium">Preprocessing</p>
                </div>
                <div class="text-center text-gray-400 md:col-span-1">
                    <i class="fas fa-arrow-right text-xl"></i>
                </div>
                <div class="text-center md:col-span-2">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-purple-700 font-bold text-lg">3</span>
                    </div>
                    <p class="text-sm text-gray-800 font-medium">TF-IDF + Cosine</p>
                </div>
                <div class="text-center text-gray-400 md:col-span-1 hidden md:block">
                    <i class="fas fa-arrow-right text-xl"></i>
                </div>
                <div class="text-center md:col-span-2 hidden md:block">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-orange-700 font-bold text-lg">4</span>
                    </div>
                    <p class="text-sm text-gray-800 font-medium">Hasil Relevan</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Check Python service status
async function checkPythonStatus() {
    try {
        const response = await fetch('http://localhost:5000/health');
        const data = await response.json();

        const statusElement = document.getElementById('pythonStatus');
        if (data.status === 'healthy') {
            statusElement.innerHTML = `
                <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                <span class="text-sm text-gray-800 font-medium">Python Service Ready</span>
            `;

            // Get document count if available
            if (data.document_count) {
                document.getElementById('documentCount').textContent = data.document_count.toLocaleString();
            }
        }
    } catch (error) {
        const statusElement = document.getElementById('pythonStatus');
        statusElement.innerHTML = `
            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
            <span class="text-sm text-gray-800 font-medium">Python Service Offline</span>
        `;
    }
}

// Set search example
function setSearchExample(query) {
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        searchInput.value = query;
        searchInput.focus();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    checkPythonStatus();
    // Check every 10 seconds
    setInterval(checkPythonStatus, 10000);
});
</script>
@endpush
