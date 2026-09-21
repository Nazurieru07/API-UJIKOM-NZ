<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard Admin')</title>

    <!-- Memuat Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    @keyframes flashSuccess {
        0% {
            opacity: 0;
            transform: translateY(-30px);
        }

        15% {
            opacity: 1;
            transform: translateY(0);
        }

        80% {
            opacity: 1;
            transform: translateY(0);
        }

        100% {
            opacity: 0;
            transform: translateY(-30px);
        }
    }

    .flash-success {
        animation: flashSuccess 4.5s ease-in-out forwards;
    }

        @keyframes flashError {
        0% {
            opacity: 0;
            transform: translateY(-30px);
        }

        15% {
            opacity: 1;
            transform: translateY(0);
        }

        80% {
            opacity: 1;
            transform: translateY(0);
        }

        100% {
            opacity: 0;
            transform: translateY(-30px);
        }
    }

    .flash-error {
    animation: flashError 4.5s ease-in-out forwards;
}

</style>    

</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
            
        @if(auth()->user()->role === 'admin')
            <div class="p-5 text-xl font-bold tracking-wider border-b border-gray-800">
                PANEL ADMIN
            </div>
            @endif

             @if(auth()->user()->role === 'petugas')
            <div class="p-5 text-xl font-bold tracking-wider border-b border-gray-800">
                PANEL PETUGAS
            </div>
            @endif

            <nav class="flex-1 p-4 space-y-2">

    {{-- ========================================= --}}
    {{-- MENU KHUSUS ADMIN --}}
    {{-- ========================================= --}}
    @if(auth()->user()->role == 'admin')

        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('admin.dashboard')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Dashboard
        </a>

        {{-- Kelola User --}}
        <a href="{{ route('admin.user.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('admin.user.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Kelola User
        </a>

        {{-- Kelola Kategori --}}
        <a href="{{ route('admin.kategori.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('admin.kategori.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Kelola Kategori
        </a>

        {{-- Kelola Alat --}}
        <a href="{{ route('admin.alat.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('admin.alat.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Kelola Alat
        </a>

        {{-- Kelola Peminjam --}}
        <a href="{{ route('admin.peminjaman.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('admin.peminjaman.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Kelola Peminjam
        </a>

        {{-- Kelola Pengembalian --}}
        <a href="{{ route('admin.pengembalian.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('admin.pengembalian.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Kelola Pengembalian
        </a>

                {{-- Log Aktivitas --}}
        <a href="{{ route('admin.log_aktivitas.index') }}"
        class="block px-4 py-2 rounded-lg transition
        {{ request()->routeIs('admin.log_aktivitas.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Log Aktivitas
        </a>

    @endif


    {{-- ========================================= --}}
    {{-- MENU KHUSUS PETUGAS --}}
    {{-- ========================================= --}}
    @if(auth()->user()->role == 'petugas')

        {{-- Persetujuan Peminjaman --}}
        <a href="{{ route('petugas.peminjaman.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('petugas.peminjaman.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Persetujuan Peminjaman
        </a>

        <a href="{{ route('petugas.pengembalian.index') }}"
            class="block px-4 py-2 rounded-lg transition
            {{ request()->routeIs('petugas.pengembalian.*')
                    ? 'bg-gray-800 text-white font-medium shadow'
                    : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                Pemantauan Pengembalian
            </a>

             {{-- Cetak Laporan --}}
        <a href="{{ route('petugas.laporan.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('petugas.laporan.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Cetak Laporan
        </a>

    @endif

</nav>


            <!-- INFORMASI USER -->
            <div class="p-4 border-t border-gray-800 text-sm text-gray-400">

                Logged in as:

                <span class="text-white font-semibold">
                    {{ auth()->user()->name }}
                </span>

            </div>

        </aside>


        <!-- MAIN CONTENT CONTAINER -->
<div class="flex-1 min-w-0 flex flex-col overflow-y-auto overflow-x-hidden">


            <!-- NAVBAR ATAS -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 z-10">

                <div class="text-lg font-semibold text-gray-800">
                    @yield('header-title', 'Dashboard')
                </div>

                <div class="flex items-center gap-4">

    {{-- Notification --}}
<details class="relative">

    <summary
    id="notificationBell"
    class="list-none cursor-pointer relative text-gray-600 hover:text-gray-800 transition text-xl"
    title="Notifikasi">

        🔔

        @if(auth()->user()->unreadNotifications->count() > 0)
            <span
    id="notificationBadge"
    class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold
           min-w-[20px] h-5 px-1 rounded-full flex items-center justify-center">
    {{ auth()->user()->unreadNotifications->count() }}
</span>
        @endif

    </summary>

    {{-- Dropdown Notifikasi --}}
    <div
        class="absolute right-0 mt-3 w-96 bg-white border border-gray-200
               rounded-xl shadow-lg z-50 overflow-hidden">

        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">
                Notifikasi
            </h3>
        </div>

        <div class="max-h-96 overflow-y-auto">

            @forelse(auth()->user()->notifications()->latest()->take(5)->get() as $notification)

                <div
                    class="px-4 py-3 border-b border-gray-100
                           hover:bg-gray-50 transition
                           {{ is_null($notification->read_at) ? 'bg-blue-50' : '' }}">

                    <div class="flex items-start gap-3">

                        <div class="text-lg">
                            🔔
                        </div>

                        <div class="flex-1">

                            <p class="text-sm font-semibold text-gray-800">
                                {{ $notification->data['judul'] ?? 'Notifikasi' }}
                            </p>

                            <p class="text-xs text-gray-600 mt-1">
                                {{ $notification->data['pesan'] ?? '' }}
                            </p>

                            <p class="text-[11px] text-gray-400 mt-2">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>

                        </div>

                    </div>

                </div>

            @empty

                <div class="px-4 py-8 text-center text-sm text-gray-500">
                    Belum ada notifikasi.
                </div>

            @endforelse

        </div>

    </div>

</details>

    {{-- Logout --}}
    <form action="{{ route('logout') }}" method="POST">

        @csrf

        <button type="submit"
            class="bg-red-500 hover:bg-red-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            Logout
        </button>

    </form>

</div>

            </header>


            <!-- KONTEN UTAMA HALAMAN -->
            <main class="flex-1 p-6">

                @yield('content')

            </main>

        </div>

    </div>

</body>

</html>

<script>
    const notificationBell = document.getElementById('notificationBell');
    const notificationBadge = document.getElementById('notificationBadge');

    if (notificationBell) {
        notificationBell.addEventListener('click', function () {

            fetch('{{ route('notifications.readAll') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && notificationBadge) {
                    notificationBadge.remove();
                }
            });
        });
    }
</script>