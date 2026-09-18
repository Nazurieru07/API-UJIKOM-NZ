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

            <div class="flex items-center gap-3 w-full md:w-auto">

                <!-- Form Search dan Filter -->
<form
    action="{{ route('admin.peminjaman.index') }}"
    method="GET"
    class="flex flex-wrap items-center gap-2 w-full md:w-auto"
>

    <!-- Search -->
    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Cari nama peminjam / status..."
        class="w-full md:w-64 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
    >

    <!-- Filter Status -->
    <select
        name="status"
        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
    >

        <option value="">
            Semua Status
        </option>

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

    <!-- Filter Jenis Kelamin -->
<select
    name="jenis_kelamin"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
>
    <option value="">Semua Jenis Kelamin</option>
    <option value="Laki-laki" {{ request('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>
        Laki-laki
    </option>
    <option value="Perempuan" {{ request('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>
        Perempuan
    </option>
</select>

<!-- Filter Tanggal Pinjam -->
<input
    type="date"
    name="tanggal_dari"
    value="{{ request('tanggal_dari') }}"
    title="Tanggal Pinjam Dari"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
>

<input
    type="date"
    name="tanggal_sampai"
    value="{{ request('tanggal_sampai') }}"
    title="Tanggal Pinjam Sampai"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
>

   @if(
            request('search') ||
            request('status') ||
            request('jenis_kelamin') ||
            request('tanggal_dari') ||
            request('tanggal_sampai')
        )
    <a
        href="{{ route('admin.peminjaman.index') }}"
        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition whitespace-nowrap"
        title="Reset Pencarian dan Filter"
    >
        Reset
    </a>
@endif

    <!-- Tombol Cari -->
    <button
        type="submit"
        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-lg transition"
    >
        Cari
    </button>

</form>

     

                <!-- Tombol Tambah -->
                <a
                    href="{{ route('admin.peminjaman.create') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap"
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