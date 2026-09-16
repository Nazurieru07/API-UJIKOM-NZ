@extends('layouts.app')

@section('title', 'Tambah Pengembalian - Panel Admin')
@section('header-title', 'Catat Pengembalian Alat')

@section('content')

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.pengembalian.store') }}" method="POST">
        @csrf

        {{-- Peminjaman --}}
        <div class="mb-6">

            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Peminjaman
            </label>

            <select
                name="peminjaman_id"
                id="peminjaman_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

                <option value="">
                    -- Pilih Peminjaman --
                </option>

                @foreach($peminjamans as $peminjaman)

                    <option
                        value="{{ $peminjaman->id }}"
                        data-tanggal-kembali="{{ $peminjaman->tgl_kembali_plan->format('Y-m-d') }}"
                        {{ old('peminjaman_id') == $peminjaman->id ? 'selected' : '' }}
                    >

                        {{ $peminjaman->user->name }}
                        - Peminjaman #{{ $peminjaman->id }}

                    </option>

                @endforeach

            </select>

            @error('peminjaman_id')
                <span class="text-red-500 text-xs">
                    {{ $message }}
                </span>
            @enderror

        </div>


        {{-- Detail Alat --}}
        <div id="detail-alat" class="mb-6 hidden">

            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Alat yang Dipinjam
            </label>

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">

                @foreach($peminjamans as $peminjaman)

                    <div
                        class="detail-peminjaman hidden"
                        data-peminjaman="{{ $peminjaman->id }}"
                    >

                        <p class="text-sm font-semibold text-gray-700 mb-2">
                            Daftar Alat:
                        </p>

                        <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">

                            @foreach($peminjaman->detailPinjams as $detail)

                                <li>
                                    {{ $detail->alat->nama_alat }}

                                    <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded">
                                        {{ $detail->jumlah }} pcs
                                    </span>
                                </li>

                            @endforeach

                        </ul>

                    </div>

                @endforeach

            </div>

        </div>


        {{-- Tanggal Kembali --}}
        <div class="mb-4">

            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Tanggal Kembali
            </label>

            <input
                type="date"
                name="tgl_kembali"
                id="tgl_kembali"
                value="{{ old('tgl_kembali', date('Y-m-d')) }}"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            @error('tgl_kembali')
                <span class="text-red-500 text-xs">
                    {{ $message }}
                </span>
            @enderror

        </div>


        {{-- Informasi Keterlambatan --}}
        <div id="info-keterlambatan" class="mb-6 hidden">

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">

                <div class="flex justify-between items-center text-sm mb-2">

                    <span class="text-gray-600">
                        Hari keterlambatan
                    </span>

                    <span
                        id="hari-terlambat"
                        class="font-semibold text-blue-700"
                    >
                        0 hari
                    </span>

                </div>

                <div class="flex justify-between items-center text-sm">

                    <span class="text-gray-600">
                        Denda keterlambatan
                    </span>

                    <span
                        id="denda-keterlambatan"
                        class="font-semibold text-red-600"
                    >
                        Rp 0
                    </span>

                </div>

                <p class="text-xs text-gray-500 mt-2">
                    Denda keterlambatan adalah Rp1.000 per hari.
                </p>

            </div>

        </div>


        {{-- Kondisi --}}
        <div class="mb-4">

            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Kondisi Alat Saat Dikembalikan
            </label>

            <select
                name="kondisi_kembali"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

                <option value="">
                    -- Pilih Kondisi --
                </option>

                <option
                    value="Baik"
                    {{ old('kondisi_kembali') == 'Baik' ? 'selected' : '' }}
                >
                    Baik
                </option>

                <option
                    value="Rusak Ringan"
                    {{ old('kondisi_kembali') == 'Rusak Ringan' ? 'selected' : '' }}
                >
                    Rusak Ringan
                </option>

                <option
                    value="Rusak Berat"
                    {{ old('kondisi_kembali') == 'Rusak Berat' ? 'selected' : '' }}
                >
                    Rusak Berat
                </option>

            </select>

            @error('kondisi_kembali')
                <span class="text-red-500 text-xs">
                    {{ $message }}
                </span>
            @enderror

        </div>


        {{-- Denda Kerusakan --}}
        <div class="mb-6">

            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Denda Kerusakan
                <span class="text-xs text-gray-400 font-normal">
                    (Opsional)
                </span>
            </label>

            <div class="relative">

                <span class="absolute left-3 top-2.5 text-gray-500 text-sm">
                    Rp
                </span>

                <input
                    type="number"
                    name="denda_kerusakan"
                    id="denda_kerusakan"
                    value="{{ old('denda_kerusakan', 0) }}"
                    min="0"
                    placeholder="0"
                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>

            @error('denda_kerusakan')
                <span class="text-red-500 text-xs">
                    {{ $message }}
                </span>
            @enderror

            <p class="text-xs text-gray-500 mt-1">
                Nominal denda kerusakan dapat ditentukan oleh petugas/admin.
            </p>

        </div>


        {{-- Total Denda --}}
        <div class="mb-6">

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">

                <div class="flex justify-between items-center">

                    <span class="font-semibold text-gray-700">
                        Total Denda
                    </span>

                    <span
                        id="total-denda"
                        class="text-xl font-bold text-red-600"
                    >
                        Rp 0
                    </span>

                </div>

            </div>

        </div>


        {{-- Tombol --}}
        <div class="flex justify-end space-x-2">

            <a
                href="{{ route('admin.pengembalian.index') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Batal
            </a>

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Simpan Pengembalian
            </button>

        </div>

    </form>

</div>


<script>

const peminjamanSelect = document.getElementById('peminjaman_id');
const tanggalKembali = document.getElementById('tgl_kembali');
const dendaKerusakan = document.getElementById('denda_kerusakan');

const detailAlat = document.getElementById('detail-alat');
const detailPeminjaman = document.querySelectorAll('.detail-peminjaman');

const infoKeterlambatan = document.getElementById('info-keterlambatan');
const hariTerlambat = document.getElementById('hari-terlambat');
const dendaKeterlambatan = document.getElementById('denda-keterlambatan');
const totalDenda = document.getElementById('total-denda');


function formatRupiah(angka)
{
    return new Intl.NumberFormat('id-ID').format(angka);
}


function updateDetailAlat()
{
    detailPeminjaman.forEach(function (item) {
        item.classList.add('hidden');
    });

    if (!peminjamanSelect.value) {

        detailAlat.classList.add('hidden');

        return;
    }

    const id = peminjamanSelect.value;

    const detail = document.querySelector(
        `.detail-peminjaman[data-peminjaman="${id}"]`
    );

    if (detail) {

        detail.classList.remove('hidden');
        detailAlat.classList.remove('hidden');

    }
}


function hitungDenda()
{
    if (!peminjamanSelect.value || !tanggalKembali.value) {

        infoKeterlambatan.classList.add('hidden');

        totalDenda.textContent = 'Rp 0';

        return;
    }

    const selectedOption =
        peminjamanSelect.options[peminjamanSelect.selectedIndex];

    const tanggalRencana =
        selectedOption.dataset.tanggalKembali;

    if (!tanggalRencana) {
        return;
    }

    const rencana =
        new Date(tanggalRencana + 'T00:00:00');

    const kembali =
        new Date(tanggalKembali.value + 'T00:00:00');

    let hari = 0;

    if (kembali > rencana) {

        const selisih =
            kembali.getTime() - rencana.getTime();

        hari = Math.ceil(
            selisih / (1000 * 60 * 60 * 24)
        );

    }

    const dendaTelat = hari * 1000;

    const dendaRusak =
        parseInt(dendaKerusakan.value) || 0;

    const total =
        dendaTelat + dendaRusak;

    hariTerlambat.textContent =
        hari + ' hari';

    dendaKeterlambatan.textContent =
        'Rp ' + formatRupiah(dendaTelat);

    totalDenda.textContent =
        'Rp ' + formatRupiah(total);

    infoKeterlambatan.classList.remove('hidden');
}


peminjamanSelect.addEventListener(
    'change',
    function () {

        updateDetailAlat();
        hitungDenda();

    }
);


tanggalKembali.addEventListener(
    'change',
    hitungDenda
);


dendaKerusakan.addEventListener(
    'input',
    hitungDenda
);


updateDetailAlat();
hitungDenda();

</script>

@endsection