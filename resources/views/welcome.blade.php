<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linkit 360 - Portal Akses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dynamic-bg {
            background-color: #f0f4f8;
            background-image: linear-gradient(135deg, #eef2ff 0%, #f9fafb 100%);
        }
        .cta-shadow {
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4), 0 5px 10px -5px rgba(99, 102, 241, 0.2);
        }
    </style>
</head>
<body class="dynamic-bg font-sans antialiased min-h-screen flex flex-col transition-colors duration-500">

    <header class="bg-white/90 backdrop-blur-sm shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="text-3xl font-extrabold text-gray-900">
                <span class="text-indigo-600">Linkit</span> 360
            </div>
            <nav>
                <a href="/login" class="px-6 py-2.5 text-white font-semibold bg-indigo-500 rounded-full hover:bg-indigo-600 transition duration-300 ease-in-out shadow-md">
                    Masuk
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl text-center p-10 sm:p-16 bg-white rounded-3xl shadow-2xl transition-all duration-500 transform hover:shadow-3xl hover:-translate-y-1 border border-gray-100/70">

            <h1 class="text-5xl sm:text-7xl lg:text-8xl font-black text-gray-900 leading-none mb-6 tracking-tight">
                Akses Portal <span class="text-indigo-600">Anda</span>.
            </h1>

            <p class="mt-6 text-xl text-gray-600 max-w-3xl mx-auto font-light">
                Selamat datang kembali. Klik tombol di bawah untuk masuk ke dasbor Anda sekarang.
            </p>

            <div class="mt-12">
                <a href="/login" class="inline-flex items-center justify-center px-16 py-5 border border-transparent text-xl font-bold rounded-full text-white bg-indigo-600 hover:bg-indigo-700 transition duration-300 ease-in-out transform hover:scale-[1.03] cta-shadow">

                    <svg class="mr-3 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>

                    Lanjutkan ke Login
                </a>
            </div>

            <p class="mt-8 text-md text-gray-500/80 font-medium">
                Akses Khusus Pengguna Terdaftar.
            </p>

        </div>
    </main>

    <footer class="bg-gray-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center text-sm text-gray-400">
            &copy; 2025 Linkit 360. | <a href="#" class="hover:text-white transition duration-200">Privasi</a>
        </div>
    </footer>

</body>
</html>
