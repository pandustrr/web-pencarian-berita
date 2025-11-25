<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Pencarian Berita - TF-IDF + Cosine Similarity')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .search-highlight {
            background-color: #fef3c7;
            padding: 0.1rem 0.2rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }
        .news-card {
            transition: all 0.3s ease;
        }
        .news-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>

    <script>
        // Highlight search terms in results
        function highlightText(text, query) {
            if (!query || !text) return text;
            const terms = query.toLowerCase().split(' ').filter(term => term.length > 2);
            let highlighted = text;
            terms.forEach(term => {
                const regex = new RegExp(`(${term})`, 'gi');
                highlighted = highlighted.replace(regex, '<span class="search-highlight">$1</span>');
            });
            return highlighted;
        }

        // Loading state for search button
        function setLoadingState() {
            const button = document.getElementById('searchButton');
            const query = document.querySelector('input[name="query"]').value.trim();
            if (!query) return false;
            if (button) {
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Mencari...</span>';
                button.disabled = true;
            }
            return true;
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-4 md:space-y-0">
                <div class="text-center md:text-left">
                    <h1 class="text-2xl font-bold">
                        <a href="{{ route('search.index') }}" class="hover:text-blue-100 transition duration-200 flex items-center justify-center md:justify-start">
                            <i class="fas fa-newspaper mr-2"></i>
                            <span>Sistem Pencarian Berita</span>
                        </a>
                    </h1>
                    <p class="text-blue-100 text-sm mt-1">
                        TF-IDF + Cosine Similarity
                    </p>
                </div>

                <nav class="flex justify-center space-x-6">
                    <a href="{{ route('search.index') }}"
                       class="hover:text-blue-100 transition duration-200 font-medium flex items-center {{ request()->is('/') ? 'text-blue-100 border-b-2 border-blue-100 pb-1' : '' }}">
                        <i class="fas fa-search mr-1"></i>Pencarian
                    </a>
                    <a href="{{ route('debug.info') }}"
                       class="hover:text-blue-100 transition duration-200 font-medium flex items-center {{ request()->is('debug') ? 'text-blue-100 border-b-2 border-blue-100 pb-1' : '' }}">
                        <i></i>Debug
                    </a>
                </nav>
            </div>

            <!-- Search Form -->
            <form action="{{ route('search.execute') }}" method="GET" class="mt-6" onsubmit="return setLoadingState()">
                <div class="flex flex-col md:flex-row gap-3 max-w-4xl mx-auto">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input
                            type="text"
                            name="query"
                            value="{{ request('query') }}"
                            placeholder="Masukkan kata kunci pencarian berita..."
                            class="block w-full pl-12 pr-4 py-4 text-lg text-gray-900 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition duration-200 shadow-sm placeholder-gray-500 bg-white"
                            required
                            autocomplete="off"
                        >
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <select name="top_k" class="border border-gray-300 rounded-lg px-4 py-2 text-gray-900 font-medium focus:ring-2 focus:ring-blue-400 bg-white cursor-pointer hover:border-blue-400 transition">
                            <option value="10" {{ request('top_k', '10') == '10' ? 'selected' : '' }}>10 hasil</option>
                            <option value="20" {{ request('top_k', '10') == '20' ? 'selected' : '' }}>20 hasil</option>
                            <option value="30" {{ request('top_k', '10') == '30' ? 'selected' : '' }}>30 hasil</option>
                        </select>

                        <button
                            type="submit"
                            class="bg-white text-blue-700 hover:bg-blue-50 px-8 py-2 rounded-xl font-bold text-lg transition duration-200 transform hover:scale-105 focus:ring-4 focus:ring-blue-300 shadow-md flex items-center justify-center whitespace-nowrap"
                            id="searchButton"
                        >
                            <i class="fas fa-search mr-2"></i>
                            <span>Cari</span>
                        </button>
                    </div>
                </div>

                <!-- Search Tips -->
                <div class="max-w-4xl mx-auto mt-3 text-center">
                    <p class="text-blue-100 text-xs">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Contoh: "gempa", "polisi", "teknologi", "ekonomi"
                    </p>
                </div>
            </form>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-auto">
        <div class="container mx-auto px-4 py-8">
            <div class="grid md:grid-cols-3 gap-8 mb-6">
                <!-- About Section -->
                <div>
                    <h3 class="text-lg font-bold mb-3 flex items-center">
                        <i class="fas fa-newspaper mr-2 text-blue-400"></i>
                        Tentang Sistem
                    </h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Sistem pencarian berita menggunakan metode TF-IDF dan Cosine Similarity
                        untuk memberikan hasil pencarian yang relevan dan akurat.
                    </p>
                </div>

                <!-- Features -->
                <div>
                    <h3 class="text-lg font-bold mb-3">
                        <i class="fas fa-star mr-2 text-yellow-400"></i>
                        Teknologi
                    </h3>
                    <ul class="text-gray-400 text-sm space-y-2">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>TF-IDF Weighting</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Cosine Similarity</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>Text Preprocessing</li>
                    </ul>
                </div>

                <!-- Team -->
                <div>
                    <h3 class="text-lg font-bold mb-3">
                        <i class="fas fa-users mr-2 text-purple-400"></i>
                        Tim Pengembang
                    </h3>
                    <p class="text-gray-400 text-sm mb-2">Kelompok 4 - Information Retrieval</p>
                    <ul class="text-gray-400 text-sm space-y-1">
                        <li>• Vincent Antony (222410103065)</li>
                        <li>• Pandu Satria (232410103008)</li>
                        <li>• Rahmad Hidayat A (232410103023)</li>
                        <li>• Steven Kurniawan (232410103045)</li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-gray-800 pt-6 text-center">
                <p class="text-gray-500 text-sm">
                    &copy; 2024 Sistem Temu Kembali Informasi Berita. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="query"]');
            if (searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                }, 100);
            }

            // Apply highlighting to search results
            const query = "{{ request('query') }}";
            if (query) {
                document.querySelectorAll('.search-result-text').forEach(element => {
                    const originalText = element.getAttribute('data-original-text') || element.textContent;
                    element.innerHTML = highlightText(originalText, query);
                });
            }
        });

        // Keyboard shortcut: Ctrl/Cmd + K to focus search
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[name="query"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });
    </script>
</body>
</html>
