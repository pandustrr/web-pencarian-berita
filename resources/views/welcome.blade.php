<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailwind Check</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="text-center space-y-4">
        <h1 class="text-4xl font-bold text-blue-600">
            Tailwind Berhasil Terpasang 🎉
        </h1>

        <p class="text-gray-700">
            Kalo teks ini warnanya biru & layoutnya rapi, berarti Tailwind udah jalan.
        </p>

        <button class="px-5 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
            Tombol Tailwind
        </button>
    </div>

</body>
</html>
