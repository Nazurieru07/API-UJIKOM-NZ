@extends('layouts.app')

@section('title', 'Periksa Pengembalian')
@section('header-title', 'Periksa Pengembalian')

@section('content')

    @if(session('success'))
        <div class="flash-success fixed top-20 right-4 z-50 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-lg text-sm max-w-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flash-error fixed top-20 right-4 z-50 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-lg text-sm max-w-sm">
            {{ session('error') }}
        </div>
    @endif

    @php
        $peminjam = $pengembalian->peminjaman->user;
        $kondisiAda = !empty($pengembalian->kondisi_kembali);
    @endphp

    {{-- Kembali --}}
    <a href="{{ route('petugas.pengembalian.index') }}"
       class="inline-flex items-center gap-2 mb-5
              px-4 py-2.5 rounded-xl
              bg-gray-200 hover:bg-gray-300
              text-gray-800 text-sm font-semibold transition">

        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>

        Kembali

    </a>


    {{-- ================================================= --}}
    {{-- INFORMASI --}}
    {{-- ================================================= --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

        <div class="p-5 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800">Informasi Pengembalian</h3>
        </div>

        <div class="p-5 space-y-3">

            <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm">

                <div>
                    <span class="text-gray-500">Peminjam:</span>
                    <span class="font-semibold text-gray-800 ml-1">{{ $peminjam?->name ?? '-' }}</span>
                </div>

                <div>
                    <span class="text-gray-500">ID Peminjaman:</span>
                    <span class="font-semibold text-gray-800 ml-1">#{{ $pengembalian->peminjaman_id }}</span>
                </div>

                <div>
                    <span class="text-gray-500">Tanggal Kembali:</span>
                    <span class="font-semibold text-gray-800 ml-1">
                        {{ $pengembalian->tgl_kembali->format('d-m-Y') }}
                    </span>
                </div>

            </div>


            {{-- Catatan peminjam --}}
            @if($pengembalian->catatan_peminjam)

                <div class="mt-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                    <p class="text-xs font-semibold text-emerald-800 mb-1">
                        Catatan Peminjam
                    </p>
                    <p class="text-sm text-emerald-900">
                        {{ $pengembalian->catatan_peminjam }}
                    </p>
                </div>

            @endif


            {{-- Daftar unit --}}
            <div class="mt-4">
                <p class="text-xs font-semibold text-gray-600 mb-2">Unit yang Dikembalikan</p>

                <ul class="space-y-1.5">
                    @foreach($pengembalian->peminjaman->detailPinjams as $detail)
                        <li class="flex items-center gap-2 text-sm text-gray-700">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">
                                {{ $detail->alatUnit?->serial_number ?? '-' }}
                            </span>
                            <span>{{ $detail->alatUnit?->alat?->nama_alat ?? '-' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>

    </div>


    {{-- ================================================= --}}
    {{-- HASIL PEMERIKSAAN --}}
    {{-- ================================================= --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">

        <div class="p-5 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800">Hasil Pemeriksaan Barang</h3>
            <p class="text-xs text-gray-500 mt-1">
                Peminjam tidak mengisi kondisi dan denda. Anda yang menentukannya
                setelah memeriksa barang secara langsung.
            </p>
        </div>

        <form action="{{ route('petugas.pengembalian.pemeriksaan', $pengembalian->id) }}"
              method="POST"
              class="p-5 space-y-4">

            @csrf
            @method('PUT')

            {{-- Kondisi --}}
            <div>
                <label for="kondisi_kembali" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Kondisi Barang
                    <span class="text-red-500">*</span>
                </label>

                <select id="kondisi_kembali"
                        name="kondisi_kembali"
                        required
                        class="w-full md:w-64 px-3 py-2.5 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">

                    <option value="">-- Pilih kondisi --</option>

                    <option value="Baik"
                        @selected(old('kondisi_kembali', $pengembalian->kondisi_kembali) === 'Baik')>
                        Baik
                    </option>

                    <option value="Rusak"
                        @selected(old('kondisi_kembali', $pengembalian->kondisi_kembali) === 'Rusak')>
                        Rusak
                    </option>

                </select>

                @error('kondisi_kembali')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Denda kerusakan --}}
            <div>
                <label for="denda_kerusakan" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Denda Kerusakan (Rp)
                    <span class="text-red-500">*</span>
                </label>

                <input type="number"
                       id="denda_kerusakan"
                       name="denda_kerusakan"
                       value="{{ old('denda_kerusakan', $pengembalian->denda_kerusakan ?? 0) }}"
                       min="0"
                       step="1000"
                       required
                       class="w-full md:w-64 px-3 py-2.5 border border-gray-300 rounded-lg
                              focus:outline-none focus:ring-2 focus:ring-blue-500">

                <p class="text-xs text-gray-500 mt-1">
                    Isi 0 jika barang kembali tanpa kerusakan. Denda keterlambatan
                    dihitung sistem, tidak di sini.
                </p>

                @error('denda_kerusakan')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5
                           rounded-lg text-sm font-semibold transition">
                Simpan Hasil Pemeriksaan
            </button>

        </form>

    </div>


    {{-- ================================================= --}}
    {{-- AKSI --}}
    {{-- ================================================= --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">

        <div class="p-5 flex flex-col sm:flex-row items-center justify-between gap-3">

            <a href="{{ route('petugas.pengembalian.index') }}"
               class="w-full sm:w-auto text-center bg-gray-200 hover:bg-gray-300
                      text-gray-800 px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                Kembali
            </a>

            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">

                <form action="{{ route('petugas.pengembalian.tolak', $pengembalian->id) }}"
                      method="POST"
                      class="w-full sm:w-auto">

                    @csrf

                    <button type="submit"
                            class="w-full bg-red-500 hover:bg-red-600 text-white
                                   px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                        Tolak Pengajuan
                    </button>

                </form>

                <form action="{{ route('petugas.pengembalian.setujui', $pengembalian->id) }}"
                      method="POST"
                      class="w-full sm:w-auto">

                    @csrf

                    <button type="submit"
                            @disabled(!$kondisiAda)
                            title="{{ $kondisiAda ? 'Setujui pengembalian ini' : 'Isi kondisi barang terlebih dahulu' }}"
                            class="w-full px-5 py-2.5 rounded-lg text-sm font-semibold transition
                                   {{ $kondisiAda
                                       ? 'bg-green-600 hover:bg-green-700 text-white'
                                       : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                        Setujui Pengembalian
                    </button>

                </form>

            </div>

        </div>

    </div>

@endsection
