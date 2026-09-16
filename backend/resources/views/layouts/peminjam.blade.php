<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Peminjam - Sistem Peminjaman Alat')</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Konfigurasi Tailwind --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 antialiased">

    {{-- ========================================================= --}}
    {{-- NAVBAR PEMINJAM --}}
    {{-- ========================================================= --}}

    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="h-16 flex items-center justify-between">

                {{-- BRAND --}}
                <a href="{{ route('peminjam.katalog') }}"
                   class="flex items-center gap-3">

                    <div class="w-10 h-10 bg-blue-600 rounded-xl
                                flex items-center justify-center
                                text-white font-bold text-lg">
                        PA
                    </div>

                    <div class="hidden sm:block">
                        <h1 class="font-bold text-gray-900 leading-tight">
                            PinjamAlat
                        </h1>

                        <p class="text-xs text-gray-500">
                            Sistem Peminjaman Alat
                        </p>
                    </div>

                </a>


                {{-- NAVIGASI --}}
                <nav class="hidden md:flex items-center gap-2">

                    {{-- Katalog --}}
                    <a href="{{ route('peminjam.katalog') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                       {{ request()->routeIs('peminjam.katalog')
                            ? 'bg-blue-50 text-blue-600'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">

                        Katalog Alat

                    </a>


                    {{-- Riwayat --}}
                    <a href="{{ route('peminjam.riwayat') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                       {{ request()->routeIs('peminjam.riwayat')
                            ? 'bg-blue-50 text-blue-600'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">

                        Riwayat Peminjaman

                    </a>

                </nav>


                {{-- USER --}}
                <div class="flex items-center gap-3">

                    {{-- Informasi user --}}
                    <div class="hidden sm:block text-right">

                        <p class="text-sm font-semibold text-gray-800">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="text-xs text-gray-500">
                            Peminjam
                        </p>

                    </div>


                    {{-- Avatar --}}
                    <div class="w-10 h-10 rounded-full bg-blue-100
                                flex items-center justify-center
                                text-blue-600 font-bold">

                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}

                    </div>


                    {{-- Logout --}}
                    <form action="{{ route('logout') }}" method="POST">

                        @csrf

                        <button type="submit"
                            class="hidden sm:block
                                   px-3 py-2
                                   text-sm font-medium
                                   text-red-600
                                   hover:bg-red-50
                                   rounded-lg
                                   transition">

                            Logout

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </header>



    {{-- ========================================================= --}}
    {{-- MOBILE NAVIGATION --}}
    {{-- ========================================================= --}}

    <div class="md:hidden bg-white border-b border-gray-200">

        <div class="max-w-7xl mx-auto px-4 py-2">

            <div class="flex gap-2 overflow-x-auto">

                <a href="{{ route('peminjam.katalog') }}"
                   class="whitespace-nowrap px-4 py-2 rounded-lg text-sm font-medium
                   {{ request()->routeIs('peminjam.katalog')
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-600' }}">

                    Katalog Alat

                </a>


                <a href="{{ route('peminjam.riwayat') }}"
                   class="whitespace-nowrap px-4 py-2 rounded-lg text-sm font-medium
                   {{ request()->routeIs('peminjam.riwayat')
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-600' }}">

                    Riwayat Peminjaman

                </a>


                <form action="{{ route('logout') }}" method="POST"
                      class="inline">

                    @csrf

                    <button type="submit"
                        class="whitespace-nowrap px-4 py-2 rounded-lg
                               text-sm font-medium
                               bg-red-50 text-red-600">

                        Logout

                    </button>

                </form>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================================================= --}}

    <main class="min-h-[calc(100vh-4rem)]">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            {{-- Pesan sukses --}}
            @if(session('success'))

                <div class="mb-6 flex items-start gap-3
                            bg-emerald-50
                            border border-emerald-200
                            text-emerald-800
                            px-4 py-3
                            rounded-xl">

                    <div class="mt-0.5">
                        ✓
                    </div>

                    <p class="text-sm">
                        {{ session('success') }}
                    </p>

                </div>

            @endif


            {{-- Pesan error --}}
            @if(session('error'))

                <div class="mb-6 flex items-start gap-3
                            bg-red-50
                            border border-red-200
                            text-red-800
                            px-4 py-3
                            rounded-xl">

                    <div class="mt-0.5">
                        !
                    </div>

                    <p class="text-sm">
                        {{ session('error') }}
                    </p>

                </div>

            @endif


            {{-- Judul halaman --}}
            @hasSection('page-heading')

                <div class="mb-6">

                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        @yield('page-heading')
                    </h2>

                    @hasSection('page-description')

                        <p class="mt-1 text-gray-500">
                            @yield('page-description')
                        </p>

                    @endif

                </div>

            @endif


            {{-- CONTENT DARI HALAMAN --}}
            @yield('content')

        </div>

    </main>



    {{-- ========================================================= --}}
    {{-- FOOTER --}}
    {{-- ========================================================= --}}

    <footer class="border-t border-gray-200 bg-white">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="py-5 text-center text-sm text-gray-500">

                Sistem Peminjaman Alat

                <span class="mx-1">•</span>

                Panel Peminjam

            </div>

        </div>

    </footer>

</body>

</html>
```
