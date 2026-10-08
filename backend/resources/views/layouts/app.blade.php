<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard Admin')</title>

    {{-- Memuat Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Tema: partial ini harus ada di <head> supaya script
         anti-FOUC jalan sebelum halaman dirender. --}}
    @include('partials.theme')
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
        <aside class="motion-sidebar w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
            
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

            <nav class="motion-sidebar-nav flex-1 p-4 space-y-2">

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

        {{-- Cetak Laporan (semua data pengembalian) --}}
        <a href="{{ route('admin.laporan.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('admin.laporan.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Cetak Laporan
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

             {{-- Permintaan Edit Peminjaman --}}
        <a href="{{ route('petugas.edit-peminjaman.index') }}"
           class="block px-4 py-2 rounded-lg transition
           {{ request()->routeIs('petugas.edit-peminjaman.*')
                ? 'bg-gray-800 text-white font-medium shadow'
                : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
            Permintaan Edit Peminjaman
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
            <a href="{{ route('profile.edit') }}"
               class="motion-user-link p-4 border-t border-gray-800 text-sm text-gray-400
                      flex items-center gap-3 hover:bg-gray-800 transition group">

                @if(auth()->user()->foto_profile)
                    <img src="{{ asset(auth()->user()->foto_profile) }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-10 h-10 rounded-full object-cover border border-gray-700 flex-shrink-0">
                @else
                    <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center
                                text-white font-bold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif

                <div class="min-w-0">
                    <span class="block text-white font-semibold truncate">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="block text-xs text-gray-500 group-hover:text-gray-300 transition">
                        Lihat &amp; ubah profil
                    </span>
                </div>

            </a>

        </aside>


        <!-- MAIN CONTENT CONTAINER -->
<div class="motion-main flex-1 min-w-0 flex flex-col overflow-y-auto overflow-x-hidden">


            <!-- NAVBAR ATAS: sticky supaya notifikasi + tombol logout
                 tetap di atas saat konten di-scroll. -->
            <header class="motion-topbar sticky top-0 bg-white shadow-sm h-16
                           flex items-center justify-between px-6 z-50">

                <div class="text-lg font-semibold text-gray-800">
                    @yield('header-title', 'Dashboard')
                </div>

                <div class="flex items-center gap-4">

    {{-- Toggle Tema --}}
    @include('partials.theme-toggle')

    {{-- Notification --}}
<details class="relative">

    <summary
    id="notificationBell"
    class="list-none cursor-pointer relative w-8 h-8 flex items-center justify-center
           text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition"
    title="Notifikasi">

        {{-- Ikon lonceng statis (folder public/images di-serve langsung,
             tidak terkena compile asset). --}}
        <img src="{{ asset('images/notification.png') }}"
             alt="Notifikasi"
             class="w-6 h-6 object-contain select-none pointer-events-none">

        @if(auth()->user()->unreadNotifications->count() > 0)
            <span
    id="notificationBadge"
    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold
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
    <form action="{{ route('logout') }}" method="POST" data-confirm-logout>

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

    {{-- Stack script: halaman yang butuh JS tambahan (modal teleport, dll)
         menaruhnya lewat @push('scripts'). --}}
    @stack('scripts')

    @include('partials.motion')

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