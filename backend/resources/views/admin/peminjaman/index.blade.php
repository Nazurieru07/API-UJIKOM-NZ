@extends('layouts.app')

@section('title', 'Kelola Peminjaman - Panel Admin')
@section('header-title', 'Manajemen Transaksi Peminjaman')

@section('content')

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">

        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">

    <h3 class="text-lg font-bold text-gray-800">
        Daftar Transaksi Peminjaman
    </h3>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">

        {{-- ========================================= --}}
        {{-- SEARCH MOBILE --}}
        {{-- ========================================= --}}
        <form
            action="{{ route('admin.peminjaman.index') }}"
            method="GET"
            class="flex md:hidden w-full"
        >
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama peminjam / status..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            {{-- Pertahankan filter aktif --}}
            @if(request('status'))
                <input
                    type="hidden"
                    name="status"
                    value="{{ request('status') }}"
                >
            @endif

            @if(request('jenis_kelamin'))
                <input
                    type="hidden"
                    name="jenis_kelamin"
                    value="{{ request('jenis_kelamin') }}"
                >
            @endif

            @if(request('tanggal_dari'))
                <input
                    type="hidden"
                    name="tanggal_dari"
                    value="{{ request('tanggal_dari') }}"
                >
            @endif

            @if(request('tanggal_sampai'))
                <input
                    type="hidden"
                    name="tanggal_sampai"
                    value="{{ request('tanggal_sampai') }}"
                >
            @endif
        </form>


        {{-- ========================================= --}}
        {{-- FILTER DESKTOP --}}
        {{-- ========================================= --}}
        <form
            action="{{ route('admin.peminjaman.index') }}"
            method="GET"
            class="hidden md:flex flex-wrap items-center gap-2"
        >

            {{-- Search --}}
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama peminjam / status..."
                class="w-64 px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            {{-- Status --}}
            <select
                name="status"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Semua Status</option>

                <option value="diajukan"
                    {{ request('status') == 'diajukan' ? 'selected' : '' }}>
                    Diajukan
                </option>

                <option value="dipinjam"
                    {{ request('status') == 'dipinjam' ? 'selected' : '' }}>
                    Dipinjam
                </option>

                <option value="dikembalikan"
                    {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>
                    Dikembalikan
                </option>

                <option value="telat"
                    {{ request('status') == 'telat' ? 'selected' : '' }}>
                    Telat
                </option>
            </select>


            {{-- Jenis Kelamin --}}
            <select
                name="jenis_kelamin"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Semua Jenis Kelamin</option>

                <option value="Laki-laki"
                    {{ request('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>
                    Laki-laki
                </option>

                <option value="Perempuan"
                    {{ request('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>
                    Perempuan
                </option>
            </select>


            {{-- Tanggal Dari --}}
            <input
                type="date"
                name="tanggal_dari"
                value="{{ request('tanggal_dari') }}"
                title="Tanggal Pinjam Dari"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >


            {{-- Tanggal Sampai --}}
            <input
                type="date"
                name="tanggal_sampai"
                value="{{ request('tanggal_sampai') }}"
                title="Tanggal Pinjam Sampai"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            >


            {{-- Reset --}}
            @if(
                request('search') ||
                request('status') ||
                request('jenis_kelamin') ||
                request('tanggal_dari') ||
                request('tanggal_sampai')
            )
                <a
                    href="{{ route('admin.peminjaman.index') }}"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700
                           px-3 py-2 text-sm rounded-lg flex items-center
                           transition whitespace-nowrap"
                >
                    Reset
                </a>
            @endif


            {{-- Cari --}}
            <button
                type="submit"
                class="bg-gray-800 hover:bg-gray-900 text-white
                       px-4 py-2 text-sm font-semibold rounded-lg transition"
            >
                Cari
            </button>

        </form>


        {{-- ========================================= --}}
        {{-- FILTER MOBILE --}}
        {{-- ========================================= --}}
        <details class="relative md:hidden w-full">

            <summary
                class="list-none cursor-pointer w-full
                       bg-gray-800 hover:bg-gray-900
                       text-white px-4 py-2 rounded-lg
                       text-sm font-semibold text-center
                       transition"
            >
                ☰ Filter
            </summary>


            {{-- Panel Filter --}}
            <div
                class="absolute left-0 right-0 top-12
                       max-h-[70vh] overflow-y-auto
                       bg-white border border-gray-200
                       rounded-xl shadow-lg z-50 p-4"
            >

                <h4 class="font-semibold text-gray-800 mb-4">
                    Filter Peminjaman
                </h4>


                <form
                    action="{{ route('admin.peminjaman.index') }}"
                    method="GET"
                    class="space-y-3"
                >

                    {{-- Search --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Cari Peminjam / Status
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama peminjam / status..."
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    {{-- Status --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Semua Status</option>

                            <option value="diajukan"
                                {{ request('status') == 'diajukan' ? 'selected' : '' }}>
                                Diajukan
                            </option>

                            <option value="dipinjam"
                                {{ request('status') == 'dipinjam' ? 'selected' : '' }}>
                                Dipinjam
                            </option>

                            <option value="dikembalikan"
                                {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>
                                Dikembalikan
                            </option>

                            <option value="telat"
                                {{ request('status') == 'telat' ? 'selected' : '' }}>
                                Telat
                            </option>
                        </select>
                    </div>


                    {{-- Jenis Kelamin --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Jenis Kelamin
                        </label>

                        <select
                            name="jenis_kelamin"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Semua Jenis Kelamin</option>

                            <option value="Laki-laki"
                                {{ request('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>
                                Laki-laki
                            </option>

                            <option value="Perempuan"
                                {{ request('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>
                                Perempuan
                            </option>
                        </select>
                    </div>


                    {{-- Tanggal Dari --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Pinjam Dari
                        </label>

                        <input
                            type="date"
                            name="tanggal_dari"
                            value="{{ request('tanggal_dari') }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    {{-- Tanggal Sampai --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Pinjam Sampai
                        </label>

                        <input
                            type="date"
                            name="tanggal_sampai"
                            value="{{ request('tanggal_sampai') }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300
                                   rounded-lg focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >
                    </div>


                    {{-- Tombol --}}
                    <div class="flex flex-col gap-2 pt-2">

                        {{-- Cari --}}
                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700
                                   text-white px-4 py-2 rounded-lg
                                   text-sm font-semibold transition"
                        >
                            Cari
                        </button>


                        {{-- Reset --}}
<a
    href="{{ route('admin.peminjaman.index') }}"
    class="w-full bg-gray-300 hover:bg-gray-400
           text-gray-700 px-4 py-2 rounded-lg
           text-sm font-semibold text-center
           transition"
>
    Reset
</a>

                    </div>

                </form>

            </div>

        </details>


        {{-- ========================================= --}}
        {{-- TOMBOL TAMBAH --}}
        {{-- ========================================= --}}
        <a
            href="{{ route('admin.peminjaman.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white
                   text-sm font-semibold px-4 py-2 rounded-lg
                   transition whitespace-nowrap text-center"
        >
            + Tambah Peminjaman
        </a>

    </div>

</div>

        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Jenis Kelamin</th>
                        <th class="py-3 px-4 border-b">Alat yang Dipinjam</th>
                        <th class="py-3 px-4 border-b">Tgl Pinjam / Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">Status</th>
                        <th class="py-3 px-4 border-b">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">

                    @forelse($peminjamans as $peminjaman)

                        <tr class="hover:bg-gray-50 transition align-top">

                            <!-- Peminjam -->
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $peminjaman->user->name ?? 'User Dihapus' }}
                            </td>

                            <!-- Jenis Kelamin -->
<td class="py-3 px-4 border-b">
    @if($peminjaman->user?->jenis_kelamin === 'Laki-laki')
        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
            Laki-laki
        </span>
    @elseif($peminjaman->user?->jenis_kelamin === 'Perempuan')
        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-700">
            Perempuan
        </span>
    @else
        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
            Belum diisi
        </span>
    @endif
</td>

                            <!-- Alat yang Dipinjam -->
                            <td class="py-3 px-4 border-b">

                                <ul class="list-disc list-inside space-y-1">

                                    @foreach($peminjaman->detailPinjams as $detail)

                                        <li>
                                            <span class="font-semibold">
                                                {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}
                                            </span>

                                            <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded">
                                                ({{ $detail->jumlah }} pcs)
                                            </span>
                                        </li>

                                    @endforeach

                                </ul>

                            </td>

                            <!-- Tanggal -->
                            <td class="py-3 px-4 border-b text-xs text-gray-600">

                                <span class="block">
                                    Pinjam: {{ $peminjaman->tgl_pinjam }}
                                </span>

                                <span class="block font-semibold">
                                    Rencana: {{ $peminjaman->tgl_kembali_plan }}
                                </span>

                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 border-b">

                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full
                                    @if($peminjaman->status == 'diajukan')
                                        bg-yellow-100 text-yellow-800
                                    @elseif($peminjaman->status == 'dipinjam')
                                        bg-blue-100 text-blue-800
                                    @elseif($peminjaman->status == 'dikembalikan')
                                        bg-emerald-100 text-emerald-800
                                    @else
                                        bg-red-100 text-red-800
                                    @endif"
                                >
                                    {{ ucfirst($peminjaman->status) }}
                                </span>

                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 border-b">

                                <div class="flex flex-col space-y-2">

                                    <!-- Form Ubah Status -->
                                    <form
                                        action="{{ route('admin.peminjaman.updateStatus', $peminjaman->id) }}"
                                        method="POST"
                                        class="flex items-center space-x-1"
                                    >

                                        @csrf
                                        @method('PUT')

                                        <select
                                            name="status"
                                            onchange="this.form.submit()"
                                            class="text-xs font-semibold border-0 rounded-lg px-3 py-2
                                                focus:outline-none focus:ring-2 focus:ring-blue-500
                                                cursor-pointer appearance-auto
                                                @if($peminjaman->status == 'diajukan')
                                                    bg-yellow-100 text-yellow-800
                                                @elseif($peminjaman->status == 'dipinjam')
                                                    bg-blue-100 text-blue-800
                                                @elseif($peminjaman->status == 'dikembalikan')
                                                    bg-emerald-100 text-emerald-800
                                                @else
                                                    bg-red-100 text-red-800
                                                @endif"
                                        >

                                            <option value="diajukan"
                                                {{ $peminjaman->status == 'diajukan' ? 'selected' : '' }}>
                                                Diajukan
                                            </option>

                                            <option value="dipinjam"
                                                {{ $peminjaman->status == 'dipinjam' ? 'selected' : '' }}>
                                                Dipinjam
                                            </option>

                                            <option value="dikembalikan"
                                                {{ $peminjaman->status == 'dikembalikan' ? 'selected' : '' }}>
                                                dikembalikan
                                            </option>

                                            <option value="telat"
                                                {{ $peminjaman->status == 'telat' ? 'selected' : '' }}>
                                                Telat
                                            </option>

                                        </select>

                                    </form>

                                    <!-- Tombol Hapus -->
                                    <form
                                        action="{{ route('admin.peminjaman.destroy', $peminjaman->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus data peminjaman ini?')"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold transition w-full"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-500">
                                Belum ada data peminjaman.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $peminjamans->links() }}
        </div>

    </div>

@endsection