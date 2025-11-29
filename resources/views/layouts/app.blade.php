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
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">
                    <a href="{{ route('home') }}" class="hover:text-blue-200">
                        <i class="fas fa-search mr-2"></i>Sistem Pencarian Berita
                    </a>
                </h1>
                <nav class="flex space-x-4">
                    <a href="{{ route('home') }}" class="hover:text-blue-200">Pencarian</a>
                    <a href="{{ route('debug') }}" class="hover:text-blue-200">Debug</a>
                </nav>
            </div>

            <!-- Search Form -->
            <form action="{{ route('search') }}" method="GET" class="mt-4">
                <div class="flex gap-2">
                    <input
                        type="text"
                        name="query"
                        value="{{ request('query') }}"
                        placeholder="Cari berita..."
                        class="flex-1 px-4 py-2 rounded-lg text-gray-900"
                        required
                    >
                    <button
                        type="submit"
                        class="bg-white text-blue-600 px-6 py-2 rounded-lg font-bold hover:bg-blue-50"
                    >
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
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
</body>
</html>
