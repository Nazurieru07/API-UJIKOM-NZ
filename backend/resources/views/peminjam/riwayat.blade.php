@extends('layouts.peminjam')

@section('title', 'Riwayat Peminjaman - Peminjam')

@section('page-heading', 'Riwayat Peminjaman')

@section('page-description')
    Pantau pengajuan dan status peminjaman alat kamu.
@endsection

@section('content')

    {{-- Flash pesan dari server: gagal validasi, penolakan, dll. --}}
    @if(session('error'))
        <div class="mb-5 px-4 py-3 rounded-xl
                    bg-red-50 border border-red-200 text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-xl
                    bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

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
            {{-- TOMBOL EDIT (hanya untuk status dipinjam/telat) --}}
            {{-- ================================================= --}}

            @if(in_array($peminjaman->status, ['dipinjam', 'telat']))

                <div class="px-5 sm:px-6 pb-5">

                    <a
                        href="{{ route('peminjam.edit.form', $peminjaman->id) }}"
                        class="inline-flex items-center gap-2
                               px-4 py-2.5 rounded-xl
                               bg-amber-50 text-amber-700
                               border border-amber-200
                               font-semibold text-sm
                               hover:bg-amber-100
                               transition"
                    >

                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>

                        Ajukan Edit Peminjaman

                    </a>

                </div>

            @endif


            {{-- ================================================= --}}
            {{-- PENGEMBALIAN: status + tombol ajukan --}}
            {{-- ================================================= --}}
            @if(in_array($peminjaman->status, ['dipinjam', 'telat']))

                <div class="px-5 sm:px-6 pb-5 flex flex-wrap items-center gap-3">

                    @php
                        $pg = $peminjaman->pengembalian;

                        // Kalau validasi gagal, form harus langsung
                        // terbuka supaya peminjam melihat isi formnya
                        // beserta pesan error -- bukan halaman yang
                        // kembali ke tombol "Ajukan Lagi" tanpa
                        // penjelasan kenapa gagal.
                        $formTerbuka = $errors->has('catatan')
                            || $errors->has('diproses_oleh');
                    @endphp

                    @if($pg && $pg->status_request === 'menunggu')

                        {{-- Pengajuan sudah dikirim, belum diperiksa --}}
                        <span class="inline-flex items-center gap-2
                                     px-3 py-1.5 rounded-full text-xs font-semibold
                                     bg-amber-50 text-amber-700 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Pengembalian Diajukan
                        </span>

                        <span class="text-xs text-gray-500">
                            Menunggu pemeriksaan Admin.
                        </span>

                    @elseif($pg && $pg->status_request === 'ditolak')

                        {{-- Ditolak: peminjam bisa mengajukan lagi --}}
                        <span class="inline-flex items-center gap-2
                                     px-3 py-1.5 rounded-full text-xs font-semibold
                                     bg-red-50 text-red-700 border border-red-200">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            Pengembalian Ditolak
                        </span>

                        {{--
                            Tombol ini hanya membuka form di bawah, bukan submit.
                            Submit langsung tanpa catatan & pilihan diproses_oleh
                            akan gagal validasi dan redirect kemari lagi --
                            kelihatan seperti loop dari sisi peminjam.
                        --}}
                        <button type="button"
                                onclick="document.getElementById('form-pengembalian-{{ $peminjaman->id }}').classList.toggle('hidden')"
                                class="inline-flex items-center gap-2
                                       px-4 py-2 rounded-xl text-sm font-semibold
                                       bg-red-600 text-white
                                       hover:bg-red-700 transition">
                            Ajukan Lagi
                        </button>

                    @elseif($pg && $pg->status_request === 'disetujui')

                        <span class="inline-flex items-center gap-2
                                     px-3 py-1.5 rounded-full text-xs font-semibold
                                     bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Pengembalian Disetujui
                        </span>

                    @else

                        {{-- Belum pernah diajukan: tampilkan tombol --}}
                        <button type="button"
                                onclick="document.getElementById('form-pengembalian-{{ $peminjaman->id }}').classList.toggle('hidden')"
                                class="inline-flex items-center gap-2
                                       px-4 py-2.5 rounded-xl text-sm font-semibold
                                       bg-emerald-600 text-white
                                       hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 10h18M3 10a2 2 0 01.701-1.526l7-5.5a2 2 0 012.598 0l7 5.5A2 2 0 0121 10v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8z" />
                            </svg>
                            Kembalikan Alat
                        </button>

                    @endif

                </div>

                {{-- Form pengembalian (default tersembunyi) --}}
                @if(!$pg || $pg->status_request !== 'menunggu')

                    <div id="form-pengembalian-{{ $peminjaman->id }}"
                         class="{{ $formTerbuka ? '' : 'hidden' }} px-5 sm:px-6 pb-5">

                        <form action="{{ route('peminjam.pengembalian.ajukan', $peminjaman->id) }}"
                              method="POST"
                              class="space-y-3 p-4 rounded-xl
                                     border border-emerald-200
                                     bg-emerald-50/50">

                            @csrf

                            {{-- Pilih siapa yang memproses --}}
                            <div>
                                <label for="diproses_oleh-{{ $peminjaman->id }}"
                                       class="block text-sm font-semibold text-gray-800 mb-1.5">
                                    Diproses Oleh
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="diproses_oleh-{{ $peminjaman->id }}"
                                    name="diproses_oleh"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg
                                           focus:outline-none focus:ring-2 focus:ring-emerald-500
                                           text-sm bg-white">
                                    <option value="admin">Admin</option>
                                    <option value="petugas">Petugas</option>
                                </select>

                                @error('diproses_oleh')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror

                                <p class="mt-1.5 text-xs text-gray-500">
                                    Pengajuan ini masuk ke antrean yang Anda pilih,
                                    dan masuk ke laporan milik orang yang menyetujuinya.
                                </p>
                            </div>

                            <div>
                                <label for="catatan-{{ $peminjaman->id }}"
                                       class="block text-sm font-semibold text-gray-800 mb-1.5">
                                    Catatan Pengembalian
                                    <span class="text-red-500">*</span>
                                </label>

                                <textarea
                                    id="catatan-{{ $peminjaman->id }}"
                                    name="catatan"
                                    rows="3"
                                    required
                                    minlength="3"
                                    maxlength="500"
                                    placeholder="Mis. sudah saya titip di pos satpam / saya bawa langsung ke kantor"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg
                                           focus:outline-none focus:ring-2 focus:ring-emerald-500
                                           text-sm resize-none"
                                >{{ old('catatan') }}</textarea>

                                @error('catatan')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror

                                <p class="mt-1.5 text-xs text-gray-500">
                                    Kondisi barang dan denda kerusakan akan diperiksa
                                    oleh petugas/admin. Pastikan barang sudah diserahkan.
                                </p>
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center gap-2
                                           px-4 py-2.5 rounded-xl text-sm font-semibold
                                           bg-emerald-600 text-white
                                           hover:bg-emerald-700 transition">
                                Kirim Pengajuan Pengembalian
                            </button>

                        </form>

                    </div>

                @endif

            @endif


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

                                @if($detail->alatUnit?->alat?->gambar)

                                    <img
                                        src="{{ asset($detail->alatUnit->alat->gambar) }}"
                                        alt="{{ $detail->alatUnit->alat->nama_alat }}"
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

                                    {{ $detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus' }}

                                </h5>

                                <p class="text-xs text-gray-500 mt-1">

                                    {{ $detail->alatUnit?->alat->kategori->nama_kategori ?? 'Tanpa kategori' }}

                                </p>

                            </div>


                            {{-- SERIAL UNIT --}}

                            <div class="text-right flex-shrink-0">

                                <p class="text-xs text-gray-500">
                                    Serial
                                </p>

                                <p class="font-bold text-gray-800 font-mono">

                                    {{ $detail->alatUnit?->serial_number ?? '-' }}

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