@extends('layouts.peminjam')

@section('title', 'Riwayat Peminjaman - Peminjam')

@section('page-heading', 'Riwayat Peminjaman')

@section('page-description')
    Pantau pengajuan dan status peminjaman alat kamu.
@endsection

@section('content')

    {{-- ========================================================= --}}
    {{-- HEADER RINGKASAN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

        {{-- Total Peminjaman --}}

        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500">
                        Total Peminjaman
                    </p>

                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{ $peminjamans->count() }}
                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-blue-50
                            flex items-center justify-center text-blue-600">

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12
                               a2 2 0 002 2h10a2 2 0 002-2V7
                               a2 2 0 00-2-2h-2
                               M9 5a3 3 0 006 0
                               M9 5h6"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- Menunggu --}}

        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500">
                        Menunggu Persetujuan
                    </p>

                    <p class="mt-1 text-2xl font-bold text-yellow-600">

                        {{ $peminjamans->where('status', 'diajukan')->count() }}

                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-yellow-50
                            flex items-center justify-center text-yellow-600">

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 2m6-2a9 9 0 11-18 0
                               9 9 0 0118 0z"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- Sedang Dipinjam --}}

        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500">
                        Sedang Dipinjam
                    </p>

                    <p class="mt-1 text-2xl font-bold text-blue-600">

                        {{ $peminjamans->whereIn('status', ['dipinjam', 'telat'])->count() }}

                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-blue-50
                            flex items-center justify-center text-blue-600">

                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6v6l4 2"
                        />
                    </svg>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- DAFTAR RIWAYAT --}}
    {{-- ========================================================= --}}

    @forelse($peminjamans as $peminjaman)

        <div
            class="bg-white border border-gray-200 rounded-2xl
                   shadow-sm hover:shadow-md transition mb-5 overflow-hidden"
        >

            {{-- ================================================= --}}
            {{-- HEADER CARD --}}
            {{-- ================================================= --}}

            <div class="p-5 sm:p-6 border-b border-gray-100">

                <div class="flex flex-col sm:flex-row
                            sm:items-center sm:justify-between gap-3">

                    <div>

                        <p class="text-xs font-semibold uppercase
                                  tracking-wide text-blue-600">

                            Peminjaman #{{ $peminjaman->id }}

                        </p>

                        <h3 class="mt-1 text-lg font-bold text-gray-900">

                            Pengajuan Peminjaman Alat

                        </h3>

                    </div>


                    {{-- STATUS --}}

                    @if($peminjaman->status === 'diajukan')

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-yellow-50 text-yellow-700
                                   border border-yellow-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-yellow-500"></span>

                            Menunggu Persetujuan

                        </span>

                    @elseif($peminjaman->status === 'dipinjam')

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-blue-50 text-blue-700
                                   border border-blue-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>

                            Sedang Dipinjam

                        </span>

                    @elseif($peminjaman->status === 'telat')

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-red-50 text-red-700
                                   border border-red-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-red-500"></span>

                            Terlambat

                        </span>

                    @elseif($peminjaman->status === 'dikembalikan')

                        <span
                            class="inline-flex items-center gap-2
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-emerald-50 text-emerald-700
                                   border border-emerald-200"
                        >

                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>

                            Sudah Dikembalikan

                        </span>

                    @else

                        <span
                            class="inline-flex items-center
                                   px-3 py-1.5 rounded-full
                                   text-xs font-semibold
                                   bg-gray-100 text-gray-600"
                        >

                            {{ ucfirst($peminjaman->status) }}

                        </span>

                    @endif

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- INFORMASI TANGGAL --}}
            {{-- ================================================= --}}

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 sm:p-6
                        bg-gray-50/70">

                <div class="flex items-start gap-3">

                    <div class="w-10 h-10 rounded-xl bg-white
                                border border-gray-200
                                flex items-center justify-center">

                        📅

                    </div>

                    <div>

                        <p class="text-xs text-gray-500">
                            Tanggal Pinjam
                        </p>

                        <p class="mt-1 font-semibold text-gray-800">

                            {{ $peminjaman->tgl_pinjam?->format('d F Y') ?? '-' }}

                        </p>

                    </div>

                </div>


                <div class="flex items-start gap-3">

                    <div class="w-10 h-10 rounded-xl bg-white
                                border border-gray-200
                                flex items-center justify-center">

                        🔄

                    </div>

                    <div>

                        <p class="text-xs text-gray-500">
                            Rencana Dikembalikan
                        </p>

                        <p class="mt-1 font-semibold text-gray-800">

                            {{ $peminjaman->tgl_kembali_plan?->format('d F Y') ?? '-' }}

                        </p>

                    </div>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- DAFTAR ALAT --}}
            {{-- ================================================= --}}

            <div class="p-5 sm:p-6">

                <div class="flex items-center justify-between mb-4">

                    <div>

                        <h4 class="font-semibold text-gray-900">
                            Alat yang Dipinjam
                        </h4>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ $peminjaman->detailPinjams->count() }} jenis alat
                        </p>

                    </div>

                </div>


                <div class="space-y-3">

                    @foreach($peminjaman->detailPinjams as $detail)

                        <div
                            class="flex items-center gap-4 p-3
                                   rounded-xl border border-gray-100
                                   bg-gray-50"
                        >

                            {{-- GAMBAR ALAT --}}

                            <div
                                class="w-16 h-16 flex-shrink-0
                                       rounded-xl overflow-hidden
                                       bg-white border border-gray-200"
                            >

                                @if($detail->alat && $detail->alat->gambar)

                                    <img
                                        src="{{ asset($detail->alat->gambar) }}"
                                        alt="{{ $detail->alat->nama_alat }}"
                                        class="w-full h-full object-cover"
                                    >

                                @else

                                    <div class="w-full h-full
                                                flex items-center justify-center
                                                text-gray-400 text-xl">

                                        📦

                                    </div>

                                @endif

                            </div>


                            {{-- INFORMASI ALAT --}}

                            <div class="min-w-0 flex-1">

                                <h5 class="font-semibold text-gray-800 truncate">

                                    {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}

                                </h5>

                                <p class="text-xs text-gray-500 mt-1">

                                    {{ $detail->alat->kategori->nama_kategori ?? 'Tanpa kategori' }}

                                </p>

                            </div>


                            {{-- JUMLAH --}}

                            <div class="text-right flex-shrink-0">

                                <p class="text-xs text-gray-500">
                                    Jumlah
                                </p>

                                <p class="font-bold text-gray-800">

                                    {{ $detail->jumlah }} pcs

                                </p>

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        </div>

    @empty

        {{-- ===================================================== --}}
        {{-- KOSONG --}}
        {{-- ===================================================== --}}

        <div
            class="bg-white border border-gray-200 rounded-2xl
                   shadow-sm p-10 text-center"
        >

            <div
                class="w-20 h-20 mx-auto rounded-2xl
                       bg-blue-50 flex items-center justify-center
                       text-4xl"
            >
                📋
            </div>

            <h3 class="mt-5 text-xl font-bold text-gray-900">

                Belum Ada Riwayat

            </h3>

            <p class="mt-2 max-w-md mx-auto text-gray-500">

                Kamu belum memiliki pengajuan peminjaman.
                Silakan pilih alat yang ingin kamu pinjam dari katalog.

            </p>

            <a
                href="{{ route('peminjam.katalog') }}"
                class="inline-flex items-center gap-2
                       mt-6 px-5 py-3
                       rounded-xl bg-blue-600 text-white
                       font-semibold hover:bg-blue-700
                       transition"
            >

                + Ajukan Peminjaman

            </a>

        </div>

    @endforelse


    {{-- ========================================================= --}}
    {{-- TOMBOL TAMBAH PEMINJAMAN --}}
    {{-- ========================================================= --}}

    @if($peminjamans->count() > 0)

        <div class="flex justify-center mt-8">

            <a
                href="{{ route('peminjam.katalog') }}"
                class="inline-flex items-center gap-2
                       px-6 py-3 rounded-xl
                       bg-blue-600 text-white
                       font-semibold hover:bg-blue-700
                       shadow-sm hover:shadow-md transition"
            >

                <span class="text-lg">
                    +
                </span>

                Ajukan Peminjaman Baru

            </a>

        </div>

    @endif

@endsection