<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Pencarian Berita dengan Filter Relevansi')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .similarity-indicator {
            background: linear-gradient(90deg, #f56565, #ed8936, #ecc94b, #48bb78, #38b2ac);
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            cursor: pointer;
        }

        input[type="range"]::-webkit-slider-track {
            background: #e5e7eb;
            height: 8px;
            border-radius: 4px;
        }

        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            height: 20px;
            width: 20px;
            background: #3b82f6;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        input[type="range"]::-moz-range-track {
            background: #e5e7eb;
            height: 8px;
            border-radius: 4px;
        }

        input[type="range"]::-moz-range-thumb {
            height: 20px;
            width: 20px;
            background: #3b82f6;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-header text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0 mb-4">
                <!-- Logo and Title -->
                <div class="flex items-center">
                    <div class="bg-white p-2 rounded-lg mr-3">
                        <i class="fas fa-search text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">
                            <a href="{{ route('home') }}" class="hover:text-blue-200">
                                NewsSearch
                            </a>
                        </h1>
                        <p class="text-blue-200 text-sm">TF-IDF dengan Filter Relevansi</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex space-x-4">
                    <a href="{{ route('home') }}"
                       class="hover:text-blue-200 font-medium px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-white/20' : '' }}">
                        <i class="fas fa-home mr-2"></i>Beranda
                    </a>
                    <a href="{{ route('debug') }}"
                       class="hover:text-blue-200 font-medium px-3 py-2 rounded-lg {{ request()->routeIs('debug') ? 'bg-white/20' : '' }}">
                        <i class="fas fa-cogs mr-2"></i>Debug
                    </a>
                </nav>
            </div>

            <!-- Main Search Form -->
            <form action="{{ route('search') }}" method="GET" class="space-y-3">
                <!-- Search Input Row -->
                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="flex-1">
                        <div class="relative">
                            <input
                                type="text"
                                name="query"
                                value="{{ request('query') }}"
                                placeholder="Cari berita (contoh: gempa, teknologi, ekonomi...)"
                                class="w-full px-4 py-3 rounded-lg text-gray-900 text-lg pl-12 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                <i class="fas fa-search text-gray-400 text-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Search Button -->
                    <div>
                        <button
                            type="submit"
                            class="bg-white text-blue-600 px-6 py-3 rounded-lg font-bold hover:bg-blue-50 transition duration-200 flex items-center justify-center w-full sm:w-auto"
                            onclick="showLoading()"
                        >
                            <i class="fas fa-search mr-2"></i>
                            <span class="hidden sm:inline">Cari</span>
                        </button>
                    </div>
                </div>


            </form>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- About -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">NewsSearch</h3>
                    <p class="text-gray-300 text-sm">
                        Sistem pencarian berita menggunakan algoritma TF-IDF dan Cosine Similarity dengan filter relevansi .
                    </p>
                </div>

                <!-- Features -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Fitur</h3>
                    <ul class="text-gray-300 text-sm space-y-1">
                        <li><i class="fas fa-check-circle text-green-400 mr-2"></i> Filter relevansi dengan threshold</li>
                        <li><i class="fas fa-check-circle text-green-400 mr-2"></i> Scoring relevansi 0-100%</li>
                        <li><i class="fas fa-check-circle text-green-400 mr-2"></i> Fallback PHP jika Python down</li>
                    </ul>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Tautan Cepat</h3>
                    <ul class="text-gray-300 text-sm space-y-1">
                        <li><a href="{{ route('home') }}" class="hover:text-blue-300"><i class="fas fa-home mr-2"></i>Beranda</a></li>
                        <li><a href="{{ route('debug') }}" class="hover:text-blue-300"><i class="fas fa-cogs mr-2"></i>Debug System</a></li>
                        <li><a href="{{ route('search', ['query' => 'teknologi', 'top_k' => 20]) }}" class="hover:text-blue-300"><i class="fas fa-search mr-2"></i>Coba: Teknologi</a></li>
                        <li><a href="{{ route('search', ['query' => 'ekonomi', 'top_k' => 30]) }}" class="hover:text-blue-300"><i class="fas fa-search mr-2"></i>Coba: Ekonomi</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-6 pt-6 text-center text-gray-400 text-sm">
                <p>&copy; 2024 NewsSearch - TF-IDF Search Engine. All rights reserved.</p>
                <p class="mt-1">Dataset: {{ number_format($stats['total_documents'] ?? 0) }} berita • Vocabulary: {{ number_format($stats['vocabulary_size'] ?? 0) }} kata</p>
            </div>
        </div>
    </footer>

    <script>
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Set form values from URL parameters (jika ada)
        const urlParams = new URLSearchParams(window.location.search);

        const topK = urlParams.get('top_k');
        if (topK) {
            const select = document.querySelector('select[name="top_k"]');
            if (select) {
                select.value = topK;
            }
        }

        const minSimilarity = urlParams.get('min_similarity');
        if (minSimilarity) {
            const slider = document.querySelector('input[name="min_similarity"]');
            if (slider) {
                slider.value = minSimilarity;
            }
        }
    });

    function showLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.remove('hidden');
        }
    }

    // Handle form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (form.method.toLowerCase() === 'get') {
            form.addEventListener('submit', showLoading);
        }
    });
    </script>
</body>
</html>
