<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Pencarian Berita')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <h1 class="text-2xl font-bold text-center md:text-left">
                    <a href="{{ route('home') }}" class="hover:text-blue-200 flex items-center justify-center md:justify-start">
                        <i class="fas fa-search mr-2"></i>Sistem Pencarian Berita
                    </a>
                </h1>
                <nav class="flex space-x-4">
                    <a href="{{ route('home') }}" class="hover:text-blue-200 font-medium">Pencarian</a>
                    <a href="{{ route('debug') }}" class="hover:text-blue-200 font-medium">Debug</a>
                </nav>
            </div>

            <!-- Search Form -->
            <form action="{{ route('search') }}" method="GET" class="mt-4">
                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="flex-1">
                        <input
                            type="text"
                            name="query"
                            value="{{ request('query') }}"
                            placeholder="Cari berita (contoh: gempa, teknologi, ekonomi...)"
                            class="w-full px-4 py-3 rounded-lg text-gray-900 text-lg"
                            required
                        >
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <select name="top_k" class="px-4 py-3 rounded-lg text-gray-900 bg-white border-r-8 border-transparent">
                            <option value="10">10 hasil</option>
                            <option value="30">30 hasil</option>
                            <option value="50">50 hasil</option>
                            <option value="all">Semua hasil</option>
                        </select>
                        <button
                            type="submit"
                            class="bg-white text-blue-600 px-6 py-3 rounded-lg font-bold hover:bg-blue-50 transition duration-200 flex items-center justify-center"
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
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 Sistem Pencarian Berita TF-IDF</p>
        </div>
    </footer>

    <script>
        // Set selected value untuk filter results
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const topK = urlParams.get('top_k');
            if (topK) {
                const select = document.querySelector('select[name="top_k"]');
                if (select) {
                    select.value = topK;
                }
            }
        });
    </script>
</body>
</html>
