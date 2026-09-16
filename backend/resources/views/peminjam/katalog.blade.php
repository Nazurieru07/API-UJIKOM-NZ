@extends('layouts.peminjam')

@section('title', 'Katalog Alat - Peminjam')

@section('page-heading', 'Katalog Alat')
@section('page-description', 'Pilih alat yang ingin kamu pinjam dan tentukan jumlahnya.')

@section('content')

    {{-- =========================
        SEARCH & FILTER
    ========================== --}}
    <div class="mb-6">
        <div class="flex flex-col md:flex-row gap-3">

            {{-- Search --}}
            <div class="relative flex-1">

                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0
                                 a8 8 0 0 1 16 0z"/>
                    </svg>
                </div>

                <input
                    type="text"
                    id="searchAlat"
                    placeholder="Cari nama alat..."
                    class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200
                           rounded-xl shadow-sm focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:border-transparent"
                >

            </div>

            {{-- Filter kategori --}}
            <select
                id="filterKategori"
                class="md:w-56 px-4 py-3 bg-white border border-gray-200
                       rounded-xl shadow-sm focus:outline-none focus:ring-2
                       focus:ring-blue-500"
            >

                <option value="">Semua Kategori</option>

                @foreach($alats->pluck('kategori.nama_kategori')->filter()->unique()->sort() as $kategori)

                    <option value="{{ strtolower($kategori) }}">
                        {{ $kategori }}
                    </option>

                @endforeach

            </select>

        </div>
    </div>


    {{-- =========================
        FORM PEMINJAMAN
    ========================== --}}
    <form
        action="{{ route('peminjam.peminjaman.ajukan') }}"
        method="POST"
        id="formPeminjaman"
    >

        @csrf


        {{-- =========================
            KATALOG ALAT
        ========================== --}}
        <div
            id="katalogGrid"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"
        >

            @forelse($alats as $alat)

                <div
                        class="alat-card group bg-white rounded-2xl border border-gray-200
                            overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-1
                            transition-all duration-300"
                        data-nama="{{ strtolower($alat->nama_alat) }}"
                        data-kategori="{{ strtolower($alat->kategori->nama_kategori ?? '') }}"

                        data-id="{{ $alat->id }}"
                        data-nama-detail="{{ $alat->nama_alat }}"
                        data-kategori-detail="{{ $alat->kategori->nama_kategori ?? 'Tanpa Kategori' }}"
                        data-kondisi="{{ $alat->status_kondisi }}"
                        data-stok="{{ $alat->stok }}"
                        data-deskripsi="{{ $alat->deskripsi ?? 'Tidak ada deskripsi alat.' }}"
                        data-gambar="{{ $alat->gambar ? asset($alat->gambar) : '' }}"
                    >

                    {{-- =========================
                        GAMBAR
                    ========================== --}}
                    <div class="relative h-52 bg-gray-100 overflow-hidden">

                        @if($alat->gambar)

                            <img
                                src="{{ asset($alat->gambar) }}"
                                alt="{{ $alat->nama_alat }}"
                                class="w-full h-full object-cover group-hover:scale-105
                                       transition-transform duration-500"
                            >

                        @else

                            <div class="w-full h-full flex flex-col items-center justify-center
                                        text-gray-400">

                                <svg class="w-16 h-16 mb-2"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2
                                           l1.586-1.586a2 2 0 012.828 0L20 14
                                           M14 8h.01M6 20h12a2 2 0 002-2V6
                                           a2 2 0 00-2-2H6a2 2 0 00-2 2v12
                                           a2 2 0 002 2z"
                                    />

                                </svg>

                                <span class="text-sm">
                                    Belum ada gambar
                                </span>

                            </div>

                        @endif


                        {{-- Stok --}}
                        <div class="absolute top-3 right-3">

                            <span
                                class="px-3 py-1.5 rounded-full text-xs font-semibold
                                       bg-white/90 backdrop-blur-sm shadow-sm
                                       text-emerald-600"
                            >
                                Stok {{ $alat->stok }}
                            </span>

                        </div>


                        {{-- Checkbox --}}
                        <label class="absolute top-3 left-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="alat_id[]"
                                value="{{ $alat->id }}"
                                class="alat-checkbox peer sr-only"
                                data-id="{{ $alat->id }}"
                                data-nama="{{ $alat->nama_alat }}"
                            >

                            <div
                                class="w-10 h-10 rounded-xl bg-white/90 backdrop-blur-sm
                                       shadow-sm flex items-center justify-center
                                       text-gray-400 peer-checked:bg-blue-600
                                       peer-checked:text-white transition"
                            >

                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />

                                </svg>

                            </div>

                        </label>

                    </div>


                    {{-- =========================
                        INFORMASI ALAT
                    ========================== --}}
                    <div class="p-4">

                        {{-- Kategori --}}
                        <p class="text-xs font-semibold uppercase tracking-wide
                                  text-blue-600 mb-1">

                            {{ $alat->kategori->nama_kategori ?? 'Tanpa Kategori' }}

                        </p>


                        {{-- Nama --}}
                        <h3 class="font-bold text-lg text-gray-900 truncate">

                            {{ $alat->nama_alat }}

                        </h3>


                        {{-- Kondisi --}}
<div class="mt-2 flex items-center gap-2">

    @if ($alat->status_kondisi === 'Baik')
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>

    @elseif ($alat->status_kondisi === 'Rusak')
        <span class="w-2 h-2 rounded-full bg-orange-500"></span>

    @elseif ($alat->status_kondisi === 'Rusak Parah')
        <span class="w-2 h-2 rounded-full bg-red-500"></span>

    @else
        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
    @endif

    <span class="text-sm text-gray-500">
        Kondisi: {{ $alat->status_kondisi }}
    </span>

</div>


                        {{-- Deskripsi sementara --}}
                        @if($alat->deskripsi)

                            <p class="mt-3 text-sm text-gray-500 line-clamp-2">

                                {{ $alat->deskripsi }}

                            </p>

                        @else

                            <p class="mt-3 text-sm text-gray-400 italic">

                                Tidak ada deskripsi alat.

                            </p>

                        @endif

                        {{-- Tombol lihat detail --}}
                        <button
    type="button"
    class="btn-detail-alat mt-4 w-full py-2.5 rounded-xl
           border border-gray-200 text-gray-600
           hover:bg-gray-50 hover:text-gray-900
           font-semibold text-sm transition"
    data-id="{{ $alat->id }}"
>
    Lihat Detail
    <span class="ml-1">→</span>
</button>

                        {{-- =========================
                            JUMLAH
                        ========================== --}}
                        <div class="mt-4 flex items-center justify-between">

                            <span class="text-sm font-medium text-gray-700">
                                Jumlah
                            </span>


                            <div
                                class="flex items-center border border-gray-200
                                       rounded-xl overflow-hidden"
                            >

                                {{-- Minus --}}
                                <button
                                    type="button"
                                    class="btn-minus w-9 h-9 flex items-center
                                           justify-center text-gray-500
                                           hover:bg-gray-100 transition"
                                    data-id="{{ $alat->id }}"
                                >
                                    −
                                </button>


                                {{-- Input jumlah --}}
                                <input
                                    type="number"
                                    name="jumlah[{{ $alat->id }}]"
                                    value="1"
                                    min="1"
                                    max="{{ $alat->stok }}"
                                    class="jumlah-input w-12 h-9 text-center
                                           border-x border-gray-200
                                           focus:outline-none text-sm
                                           font-semibold"
                                    data-id="{{ $alat->id }}"
                                >


                                {{-- Plus --}}
                                <button
                                    type="button"
                                    class="btn-plus w-9 h-9 flex items-center
                                           justify-center text-gray-500
                                           hover:bg-gray-100 transition"
                                    data-id="{{ $alat->id }}"
                                    data-max="{{ $alat->stok }}"
                                >
                                    +
                                </button>

                            </div>

                        </div>


                        {{-- =========================
                            TOMBOL PILIH
                        ========================== --}}

                        <button
                            type="button"
                            class="btn-pilih mt-4 w-full py-2.5 rounded-xl
                                   border border-blue-200 text-blue-600
                                   hover:bg-blue-50 font-semibold text-sm transition"
                            data-id="{{ $alat->id }}"
                        >

                            Pilih Alat

                        </button>

                    </div>

                </div>

            @empty

                {{-- Empty state --}}
                <div class="col-span-full">

                    <div
                        class="bg-white rounded-2xl border border-gray-200
                               p-10 text-center"
                    >

                        <div class="text-5xl mb-4">
                            📦
                        </div>

                        <h3 class="font-bold text-lg text-gray-800">
                            Belum ada alat tersedia
                        </h3>

                        <p class="text-gray-500 mt-1">
                            Saat ini belum ada alat yang dapat dipinjam.
                        </p>

                    </div>

                </div>

            @endforelse

        </div>


        {{-- =========================
            TIDAK DITEMUKAN
        ========================== --}}
        <div
            id="tidakDitemukan"
            class="hidden mt-8 bg-white border border-gray-200
                   rounded-2xl p-10 text-center"
        >

            <div class="text-5xl mb-4">
                🔎
            </div>

            <h3 class="font-bold text-lg text-gray-800">
                Alat tidak ditemukan
            </h3>

            <p class="text-gray-500 mt-1">
                Coba gunakan kata kunci pencarian yang berbeda.
            </p>

        </div>


        {{-- =========================
    STICKY BAR PEMINJAMAN
========================== --}}
<div
    class="fixed bottom-0 left-0 right-0 z-50
           bg-white/95 backdrop-blur-md
           border-t border-gray-200
           shadow-[0_-4px_20px_rgba(0,0,0,0.08)]"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div
            class="py-3 sm:py-4
                   flex flex-col sm:flex-row
                   gap-3 sm:items-center
                   sm:justify-between"
        >

            {{-- Informasi alat yang dipilih --}}
            <div class="flex items-center gap-3">

                <div
                    class="w-11 h-11 sm:w-12 sm:h-12
                           rounded-xl bg-blue-50
                           flex items-center justify-center
                           text-blue-600 flex-shrink-0"
                >

                    <svg
                        class="w-5 h-5 sm:w-6 sm:h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4
                               M7 13L5.4 5M7 13l-2 2.5
                               A1 1 0 006 17h11m-1 4a1 1 0 100-2
                               1 1 0 000 2zm-9 0a1 1 0 100-2
                               1 1 0 000 2z"
                        />

                    </svg>

                </div>


                <div>

                    <p class="text-xs sm:text-sm text-gray-500">
                        Alat Dipilih
                    </p>

                    <div class="flex items-center gap-2">

                        <span
                            id="jumlahDipilih"
                            class="text-xl sm:text-2xl font-bold
                                   text-gray-900"
                        >
                            0
                        </span>

                        <span class="text-sm text-gray-500">
                            alat
                        </span>

                    </div>

                </div>

            </div>


            {{-- Tombol lanjut --}}
            <button
                type="button"
                id="btnLanjut"
                disabled
                class="w-full sm:w-auto
                       px-6 py-3 sm:py-3.5
                       rounded-xl
                       bg-blue-600 text-white
                       font-semibold
                       hover:bg-blue-700
                       active:scale-[0.98]
                       transition
                       disabled:bg-gray-200
                       disabled:text-gray-400
                       disabled:cursor-not-allowed
                       disabled:active:scale-100"
            >

                Lanjutkan Pengajuan

                <span class="ml-1">
                    →
                </span>

            </button>

        </div>

    </div>
</div>

    {{-- ==================================================
    MODAL / POPUP DETAIL ALAT
=================================================== --}}
<div
    id="modalDetailAlat"
    class="fixed inset-0 z-[90] hidden"
>

    {{-- Overlay --}}
    <div
        id="detailOverlay"
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
    ></div>


    {{-- Container --}}
    <div
        class="relative min-h-screen flex items-center
               justify-center p-4"
    >

        <div
            class="relative w-full max-w-2xl
                   max-h-[90vh]
                   bg-white rounded-2xl
                   shadow-2xl overflow-hidden"
        >

            {{-- =========================
                HEADER
            ========================== --}}
            <div
                class="px-5 sm:px-6 py-4
                       border-b border-gray-200
                       flex items-center justify-between"
            >

                <div>

                    <h3 class="text-xl font-bold text-gray-900">
                        Detail Alat
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        Informasi lengkap mengenai alat.
                    </p>

                </div>


                {{-- Tombol close --}}
                <button
                    type="button"
                    id="btnCloseDetail"
                    class="w-10 h-10 rounded-xl
                           flex items-center justify-center
                           text-gray-400
                           hover:text-gray-700
                           hover:bg-gray-100
                           transition"
                >

                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />

                    </svg>

                </button>

            </div>


            {{-- =========================
                ISI DETAIL
            ========================== --}}
            <div
                class="p-5 sm:p-6
                       overflow-y-auto
                       max-h-[calc(90vh-150px)]"
            >

                {{-- Gambar --}}
                <div
                    id="detailGambar"
                    class="w-full h-64 sm:h-80
                           bg-gray-100
                           rounded-2xl
                           overflow-hidden
                           mb-6"
                ></div>


                {{-- Kategori --}}
                <p
                    id="detailKategori"
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-blue-600 mb-1"
                ></p>


                {{-- Nama --}}
                <h4
                    id="detailNama"
                    class="text-2xl sm:text-3xl
                           font-bold text-gray-900"
                ></h4>


                {{-- Informasi singkat --}}
                <div class="grid grid-cols-2 gap-3 mt-5">

                    {{-- Kondisi --}}
                    <div
                        class="bg-gray-50
                               border border-gray-200
                               rounded-xl p-4"
                    >

                        <p class="text-xs text-gray-500 mb-1">
                            Kondisi
                        </p>

                        <p
                            id="detailKondisi"
                            class="font-semibold text-gray-800"
                        ></p>

                    </div>


                    {{-- Stok --}}
                    <div
                        class="bg-gray-50
                               border border-gray-200
                               rounded-xl p-4"
                    >

                        <p class="text-xs text-gray-500 mb-1">
                            Stok Tersedia
                        </p>

                        <p
                            id="detailStok"
                            class="font-semibold text-gray-800"
                        ></p>

                    </div>

                </div>


                {{-- Deskripsi --}}
                <div class="mt-6">

                    <h5
                        class="text-sm font-bold
                               text-gray-900 mb-2"
                    >
                        Deskripsi
                    </h5>

                    <div
                        id="detailDeskripsi"
                        class="text-sm leading-6
                               text-gray-600
                               whitespace-pre-line"
                    ></div>

                </div>

            </div>


            {{-- =========================
                FOOTER
            ========================== --}}
            <div
                class="px-5 sm:px-6 py-4
                       bg-gray-50
                       border-t border-gray-200
                       flex flex-col-reverse
                       sm:flex-row
                       gap-3 sm:justify-end"
            >

                <button
                    type="button"
                    id="btnTutupDetail"
                    class="w-full sm:w-auto
                           px-5 py-3 rounded-xl
                           border border-gray-200
                           text-gray-700
                           font-semibold
                           hover:bg-white
                           transition"
                >
                    Tutup
                </button>


                <button
                    type="button"
                    id="btnPilihDariDetail"
                    data-id=""
                    class="w-full sm:w-auto
                           px-5 py-3 rounded-xl
                           bg-blue-600
                           text-white
                           font-semibold
                           hover:bg-blue-700
                           transition"
                >
                    Pilih Alat
                </button>

            </div>

        </div>

    </div>

</div>

    {{-- ==================================================
        MODAL / POPUP PENGAJUAN PEMINJAMAN
    =================================================== --}}
    <div
        id="modalPeminjaman"
        class="fixed inset-0 z-[100] hidden"
    >

        {{-- Overlay --}}
        <div
            id="modalOverlay"
            class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        ></div>


        {{-- Container modal --}}
        <div
            class="relative min-h-screen flex items-center
                   justify-center p-4"
        >

            <div
                class="relative w-full max-w-lg bg-white
                       rounded-2xl shadow-2xl overflow-hidden"
            >

                {{-- =========================
                    HEADER MODAL
                ========================== --}}
                <div
                    class="px-5 sm:px-6 py-5 border-b border-gray-200
                           flex items-center justify-between"
                >

                    <div>

                        <h3 class="text-xl font-bold text-gray-900">
                            Ajukan Peminjaman
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Tentukan tanggal rencana pengembalian alat.
                        </p>

                    </div>


                    {{-- Tombol close --}}
                    <button
                        type="button"
                        id="btnCloseModal"
                        class="w-10 h-10 rounded-xl
                               flex items-center justify-center
                               text-gray-400 hover:text-gray-700
                               hover:bg-gray-100 transition"
                    >

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />

                        </svg>

                    </button>

                </div>


                {{-- =========================
                    ISI MODAL
                ========================== --}}
                <div class="p-5 sm:p-6">

                    {{-- Ringkasan --}}
                    <div
                        class="bg-blue-50 border border-blue-100
                               rounded-xl p-4 mb-5"
                    >

                        <div class="flex items-center justify-between">

                            <div>

                                <p class="text-sm text-blue-600 font-medium">
                                    Alat yang dipilih
                                </p>

                                <p
                                    id="modalJumlahAlat"
                                    class="text-2xl font-bold text-blue-700"
                                >
                                    0
                                </p>

                            </div>


                            <div
                                class="w-11 h-11 rounded-xl bg-blue-100
                                       flex items-center justify-center
                                       text-blue-600"
                            >

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
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4
                                           M7 13L5.4 5M7 13l-2 2.5
                                           A1 1 0 006 17h11m-1 4a1 1 0 100-2
                                           1 1 0 000 2zm-9 0a1 1 0 100-2
                                           1 1 0 000 2z"
                                    />

                                </svg>

                            </div>

                        </div>

                    </div>


                    {{-- Daftar alat --}}
                    <div class="mb-5">

                        <p class="text-sm font-semibold text-gray-800 mb-3">
                            Daftar Alat
                        </p>

                        <div
                            id="modalDaftarAlat"
                            class="max-h-40 overflow-y-auto
                                   space-y-2 pr-1"
                        >
                            {{-- Diisi oleh JavaScript --}}
                        </div>

                    </div>


                    {{-- Tanggal kembali --}}
                    <div>

                        <label
                            for="tgl_kembali_plan"
                            class="block text-sm font-semibold
                                   text-gray-800 mb-2"
                        >

                            Rencana Tanggal Kembali

                        </label>


                        <input
    type="date"
    id="tgl_kembali_plan"
    name="tgl_kembali_plan"
    form="formPeminjaman"
    min="{{ now()->addDay()->format('Y-m-d') }}"
    required
    class="w-full px-4 py-3 rounded-xl
           border border-gray-200
           focus:outline-none
           focus:ring-2 focus:ring-blue-500"
>


                        <p class="mt-2 text-xs text-gray-500">
                            Tanggal kembali harus setelah hari ini.
                        </p>

                    </div>

                </div>


                {{-- =========================
                    FOOTER MODAL
                ========================== --}}
                <div
                    class="px-5 sm:px-6 py-4 bg-gray-50
                           border-t border-gray-200
                           flex flex-col-reverse sm:flex-row
                           gap-3 sm:justify-end"
                >

                    <button
                        type="button"
                        id="btnBatalModal"
                        class="w-full sm:w-auto px-5 py-3 rounded-xl
                               border border-gray-200
                               text-gray-700 font-semibold
                               hover:bg-white transition"
                    >

                        Batal

                    </button>


                    <button
    type="submit"
    id="btnSubmit"
    form="formPeminjaman"
    class="w-full sm:w-auto px-5 py-3 rounded-xl
           bg-blue-600 text-white font-semibold
           hover:bg-blue-700 transition"
>
    Ajukan Peminjaman
</button>

                </div>

            </div>

        </div>

    </div>


    {{-- ==================================================
        JAVASCRIPT
    =================================================== --}}
    <script>

        document.addEventListener('DOMContentLoaded', function () {

            /* ==========================================
               ELEMENT
            ========================================== */

            const checkboxes =
                document.querySelectorAll('.alat-checkbox');

            const jumlahInputs =
                document.querySelectorAll('.jumlah-input');

            const jumlahDipilih =
                document.getElementById('jumlahDipilih');

            const btnLanjut =
                document.getElementById('btnLanjut');

            const searchInput =
                document.getElementById('searchAlat');

            const filterKategori =
                document.getElementById('filterKategori');

            const tidakDitemukan =
                document.getElementById('tidakDitemukan');

            const modal =
                document.getElementById('modalPeminjaman');

            const modalOverlay =
                document.getElementById('modalOverlay');

            const btnCloseModal =
                document.getElementById('btnCloseModal');

            const btnBatalModal =
                document.getElementById('btnBatalModal');

            const modalJumlahAlat =
                document.getElementById('modalJumlahAlat');

            const modalDaftarAlat =
                document.getElementById('modalDaftarAlat');

                /* ==========================================
                ELEMENT MODAL DETAIL ALAT
                ========================================== */

                const modalDetail =
                    document.getElementById('modalDetailAlat');

                const detailOverlay =
                    document.getElementById('detailOverlay');

                const btnCloseDetail =
                    document.getElementById('btnCloseDetail');

                const btnTutupDetail =
                    document.getElementById('btnTutupDetail');

                const btnPilihDariDetail =
                    document.getElementById('btnPilihDariDetail');

                const detailGambar =
                    document.getElementById('detailGambar');

                const detailKategori =
                    document.getElementById('detailKategori');

                const detailNama =
                    document.getElementById('detailNama');

                const detailKondisi =
                    document.getElementById('detailKondisi');

                const detailStok =
                    document.getElementById('detailStok');

                const detailDeskripsi =
                    document.getElementById('detailDeskripsi');


            /* ==========================================
               UPDATE JUMLAH ALAT DIPILIH
            ========================================== */

            function updateSelectedCount() {

                const selected =
                    document.querySelectorAll(
                        '.alat-checkbox:checked'
                    );

                jumlahDipilih.textContent =
                    selected.length;

                btnLanjut.disabled =
                    selected.length === 0;

            }


            /* ==========================================
               CHECKBOX
            ========================================== */

            checkboxes.forEach(function (checkbox) {

                checkbox.addEventListener('change', function () {

                    const card =
                        this.closest('.alat-card');

                    const button =
                        card.querySelector('.btn-pilih');


                    if (this.checked) {

                        card.classList.add(
                            'ring-2',
                            'ring-blue-500',
                            'border-blue-500'
                        );


                        button.textContent =
                            '✓ Alat Dipilih';


                        button.classList.remove(
                            'border-blue-200',
                            'text-blue-600',
                            'hover:bg-blue-50'
                        );


                        button.classList.add(
                            'bg-blue-600',
                            'text-white',
                            'border-blue-600'
                        );

                    } else {

                        card.classList.remove(
                            'ring-2',
                            'ring-blue-500',
                            'border-blue-500'
                        );


                        button.textContent =
                            'Pilih Alat';


                        button.classList.remove(
                            'bg-blue-600',
                            'text-white',
                            'border-blue-600'
                        );


                        button.classList.add(
                            'border-blue-200',
                            'text-blue-600',
                            'hover:bg-blue-50'
                        );

                    }


                    updateSelectedCount();

                });

            });

            /* ==========================================
   MODAL DETAIL ALAT
========================================== */

function bukaDetailAlat(card) {

    const nama =
        card.dataset.namaDetail;

    const kategori =
        card.dataset.kategoriDetail;

    const kondisi =
        card.dataset.kondisi;

    const stok =
        card.dataset.stok;

    const deskripsi =
        card.dataset.deskripsi;

    const gambar =
        card.dataset.gambar;


    /* =========================
       ISI DATA
    ========================== */

    detailNama.textContent =
        nama;

    detailKategori.textContent =
        kategori;

    detailKondisi.textContent =
        kondisi;

    detailStok.textContent =
        stok + ' unit';

    detailDeskripsi.textContent =
        deskripsi;


    /* =========================
       GAMBAR
    ========================== */

    if (gambar) {

        detailGambar.innerHTML = `

            <img
                src="${gambar}"
                alt="${nama}"
                class="w-full h-full object-cover"
            >

        `;

    } else {

        detailGambar.innerHTML = `

            <div
                class="w-full h-full
                       flex flex-col
                       items-center
                       justify-center
                       text-gray-400"
            >

                <svg
                    class="w-20 h-20 mb-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2
                           l1.586-1.586a2 2 0 012.828 0L20 14
                           M14 8h.01M6 20h12a2 2 0 002-2V6
                           a2 2 0 00-2-2H6a2 2 0 00-2 2v12
                           a2 2 0 002 2z"
                    />

                </svg>

                <span class="text-sm">
                    Belum ada gambar
                </span>

            </div>

        `;

    }


    /* =========================
       SIMPAN ID ALAT
    ========================== */

    btnPilihDariDetail.dataset.id =
        card.dataset.id;


    /* =========================
       BUKA MODAL
    ========================== */

    modalDetail.classList.remove('hidden');

    document.body.classList.add('overflow-hidden');

}


/* ==========================================
   TOMBOL LIHAT DETAIL
========================================== */

document.querySelectorAll('.btn-detail-alat')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            const card =
                this.closest('.alat-card');

            bukaDetailAlat(card);

        });

    });

        /* ==========================================
            TUTUP MODAL DETAIL
            ========================================== */

            function tutupDetailAlat() {

                modalDetail.classList.add('hidden');

                document.body.classList.remove('overflow-hidden');

            }


            btnCloseDetail.addEventListener(
                'click',
                tutupDetailAlat
            );


            btnTutupDetail.addEventListener(
                'click',
                tutupDetailAlat
            );


            detailOverlay.addEventListener(
                'click',
                tutupDetailAlat
            );

            /* ==========================================
            PILIH ALAT DARI MODAL DETAIL
            ========================================== */

            btnPilihDariDetail.addEventListener(
                'click',
                function () {

                    const id =
                        this.dataset.id;


                    const checkbox =
                        document.querySelector(
                            '.alat-checkbox[data-id="' +
                            id +
                            '"]'
                        );


                    if (!checkbox) {
                        return;
                    }


                    /* Kalau belum dipilih */
                    if (!checkbox.checked) {

                        checkbox.checked = true;

                        checkbox.dispatchEvent(
                            new Event('change')
                        );

                    }


                    /* Tutup modal */
                    tutupDetailAlat();

                }
            );


            /* ==========================================
               TOMBOL PILIH ALAT
            ========================================== */

            document.querySelectorAll('.btn-pilih')
                .forEach(function (button) {

                    button.addEventListener('click', function () {

                        const id =
                            this.dataset.id;


                        const checkbox =
                            document.querySelector(
                                '.alat-checkbox[data-id="' +
                                id +
                                '"]'
                            );


                        checkbox.checked =
                            !checkbox.checked;


                        checkbox.dispatchEvent(
                            new Event('change')
                        );

                    });

                });


            /* ==========================================
               TOMBOL PLUS
            ========================================== */

            document.querySelectorAll('.btn-plus')
                .forEach(function (button) {

                    button.addEventListener('click', function () {

                        const id =
                            this.dataset.id;


                        const input =
                            document.querySelector(
                                '.jumlah-input[data-id="' +
                                id +
                                '"]'
                            );


                        const max =
                            parseInt(this.dataset.max);


                        let value =
                            parseInt(input.value) || 1;


                        if (value < max) {

                            input.value =
                                value + 1;

                        }

                    });

                });


            /* ==========================================
               TOMBOL MINUS
            ========================================== */

            document.querySelectorAll('.btn-minus')
                .forEach(function (button) {

                    button.addEventListener('click', function () {

                        const id =
                            this.dataset.id;


                        const input =
                            document.querySelector(
                                '.jumlah-input[data-id="' +
                                id +
                                '"]'
                            );


                        let value =
                            parseInt(input.value) || 1;


                        if (value > 1) {

                            input.value =
                                value - 1;

                        }

                    });

                });


            /* ==========================================
               VALIDASI INPUT JUMLAH
            ========================================== */

            jumlahInputs.forEach(function (input) {

                input.addEventListener('change', function () {

                    let value =
                        parseInt(this.value) || 1;


                    const max =
                        parseInt(this.max);


                    if (value < 1) {
                        value = 1;
                    }


                    if (value > max) {
                        value = max;
                    }


                    this.value =
                        value;

                });

            });


            /* ==========================================
               SEARCH & FILTER
            ========================================== */

            function filterAlat() {

                const keyword =
                    searchInput.value
                    .toLowerCase()
                    .trim();


                const kategori =
                    filterKategori.value;


                const cards =
                    document.querySelectorAll('.alat-card');


                let visibleCount =
                    0;


                cards.forEach(function (card) {

                    const nama =
                        card.dataset.nama;


                    const kategoriAlat =
                        card.dataset.kategori;


                    const cocokNama =
                        nama.includes(keyword);


                    const cocokKategori =
                        !kategori ||
                        kategoriAlat === kategori;


                    if (
                        cocokNama &&
                        cocokKategori
                    ) {

                        card.classList.remove('hidden');

                        visibleCount++;

                    } else {

                        card.classList.add('hidden');

                    }

                });


                if (visibleCount === 0) {

                    tidakDitemukan.classList.remove('hidden');

                } else {

                    tidakDitemukan.classList.add('hidden');

                }

            }


            searchInput.addEventListener(
                'input',
                filterAlat
            );


            filterKategori.addEventListener(
                'change',
                filterAlat
            );


            /* ==========================================
               ISI DAFTAR ALAT DI MODAL
            ========================================== */

            function updateModal() {

                const selected =
                    document.querySelectorAll(
                        '.alat-checkbox:checked'
                    );


                modalJumlahAlat.textContent =
                    selected.length;


                modalDaftarAlat.innerHTML =
                    '';


                selected.forEach(function (checkbox) {

                    const id =
                        checkbox.dataset.id;


                    const nama =
                        checkbox.dataset.nama;


                    const input =
                        document.querySelector(
                            '.jumlah-input[data-id="' +
                            id +
                            '"]'
                        );


                    const jumlah =
                        input
                            ? input.value
                            : 1;


                    const item =
                        document.createElement('div');


                    item.className =
                        'flex items-center justify-between ' +
                        'gap-3 bg-gray-50 border ' +
                        'border-gray-200 rounded-xl px-3 py-2.5';


                    item.innerHTML = `

                        <div class="flex items-center gap-3 min-w-0">

                            <div
                                class="w-9 h-9 rounded-lg bg-blue-100
                                       flex items-center justify-center
                                       text-blue-600 flex-shrink-0"
                            >
                                📦
                            </div>

                            <p
                                class="text-sm font-medium text-gray-800
                                       truncate"
                            >
                                ${nama}
                            </p>

                        </div>

                        <span
                            class="text-sm font-semibold text-gray-600
                                   whitespace-nowrap"
                        >
                            ${jumlah} pcs
                        </span>

                    `;


                    modalDaftarAlat.appendChild(item);

                });

            }


            /* ==========================================
               BUKA MODAL
            ========================================== */

            function bukaModal() {

                updateModal();

                modal.classList.remove('hidden');

                document.body.classList.add('overflow-hidden');

            }


            /* ==========================================
               TUTUP MODAL
            ========================================== */

            function tutupModal() {

                modal.classList.add('hidden');

                document.body.classList.remove('overflow-hidden');

            }


            /* ==========================================
               TOMBOL LANJUT
            ========================================== */

            btnLanjut.addEventListener(
                'click',
                function () {

                    const selected =
                        document.querySelectorAll(
                            '.alat-checkbox:checked'
                        );


                    if (selected.length === 0) {

                        return;

                    }


                    bukaModal();

                }
            );


            /* ==========================================
               TOMBOL CLOSE
            ========================================== */

            btnCloseModal.addEventListener(
                'click',
                tutupModal
            );


            btnBatalModal.addEventListener(
                'click',
                tutupModal
            );


            modalOverlay.addEventListener(
                'click',
                tutupModal
            );


            /* ==========================================
            ESC UNTUK MENUTUP MODAL
            ========================================== */

            document.addEventListener(
                'keydown',
                function (event) {

                    if (event.key !== 'Escape') {
                        return;
                    }


                    /* Jika modal detail terbuka */
                    if (
                        !modalDetail.classList.contains('hidden')
                    ) {

                        tutupDetailAlat();

                        return;

                    }


                    /* Jika modal pengajuan terbuka */
                    if (
                        !modal.classList.contains('hidden')
                    ) {

                        tutupModal();

                    }

                }
            );


            /* ==========================================
               UPDATE MODAL SAAT JUMLAH BERUBAH
            ========================================== */

            jumlahInputs.forEach(function (input) {

                input.addEventListener(
                    'change',
                    function () {

                        if (
                            !modal.classList.contains('hidden')
                        ) {

                            updateModal();

                        }

                    }
                );

            });


            /* ==========================================
               INITIAL STATE
            ========================================== */

            updateSelectedCount();

        });

    </script>

@endsection