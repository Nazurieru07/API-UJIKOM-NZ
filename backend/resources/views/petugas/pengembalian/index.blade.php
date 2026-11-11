@extends('layouts.app')

@section('title', 'Pemantauan Pengembalian - Petugas')
@section('header-title', 'Pemantauan Pengembalian')

@section('content')

<div class="bg-white rounded-lg shadow-sm border border-gray-200">

    {{-- Header --}}
    <div class="p-4 border-b border-gray-200">

        <h2 class="text-lg font-semibold text-gray-800">
            Daftar Peminjaman yang Belum Dikembalikan
        </h2>

        <p class="text-sm text-gray-500 mt-1">
            Data peminjaman yang saat ini masih dalam status dipinjam.
        </p>

        {{-- Filter --}}
        <form
            action="{{ route('petugas.pengembalian.index') }}"
            method="GET"
            class="mt-4 flex flex-col md:flex-row flex-wrap gap-2 w-full"
        >

            {{-- Pencarian --}}
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama peminjam..."
                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            {{-- Kategori --}}
            <select
                name="kategori_id"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Semua Kategori</option>

                @foreach($kategoris as $kategori)
                    <option
                        value="{{ $kategori->id }}"
                        {{ (string) request('kategori_id') === (string) $kategori->id ? 'selected' : '' }}
                    >
                        {{ $kategori->nama_kategori }}
                    </option>
                @endforeach
            </select>

            {{-- Tanggal Dari --}}
            <input
                type="date"
                name="tanggal_dari"
                value="{{ request('tanggal_dari') }}"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            {{-- Tanggal Sampai --}}
            <input
                type="date"
                name="tanggal_sampai"
                value="{{ request('tanggal_sampai') }}"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            {{-- Tombol Cari --}}
            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition"
            >
                Cari
            </button>

            {{-- Reset --}}
            @if(request()->hasAny([
                'search',
                'kategori_id',
                'tanggal_dari',
                'tanggal_sampai'
            ]))
                <a
                    href="{{ route('petugas.pengembalian.index') }}"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm flex items-center justify-center transition"
                >
                    Reset
                </a>
            @endif

        </form>

    </div>


    {{-- Alert Success --}}
    @if(session('success'))
        <div class="m-4 bg-green-50 border border-green-200 text-green-800 p-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif


    {{-- Alert Error --}}
    @if(session('error'))
        <div class="m-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif


    {{-- Table --}}
    <div class="overflow-x-auto">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">

                <tr>

                    <th class="px-4 py-3">
                        Peminjam
                    </th>

                    <th class="px-4 py-3">
                        Alat
                    </th>

                    <th class="px-4 py-3">
                        Tanggal Pinjam
                    </th>

                    <th class="px-4 py-3">
                        Rencana Kembali
                    </th>

                    <th class="px-4 py-3 text-center">
                        Status
                    </th>

                    <th class="px-4 py-3 text-center">
                        Aksi
                    </th>

                </tr>

            </thead>

            <tbody class="divide-y divide-gray-200">

                @forelse($peminjamans as $peminjaman)

                    <tr class="hover:bg-gray-50">

                        {{-- Peminjam --}}
                        <td class="px-4 py-4 font-medium text-gray-800">

                            {{ $peminjaman->user->name ?? 'User Dihapus' }}

                        </td>

                        {{-- Alat --}}
                        <td class="px-4 py-4">

                            <ul class="list-disc list-inside space-y-1">

                                @foreach($peminjaman->detailPinjams as $detail)

                                    <li>

                                        {{ $detail->alatUnit->alat->nama_alat ?? 'Alat Dihapus' }}

                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                            {{ $detail->alatUnit?->serial_number ?? '-' }}
                                        </span>

                                        @if($detail->alatUnit && $detail->alatUnit->alat && $detail->alatUnit->alat->kategori)
                                            <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">
                                                {{ $detail->alatUnit->alat->kategori->nama_kategori }}
                                            </span>
                                        @endif

                                    </li>

                                @endforeach

                            </ul>

                        </td>

                        {{-- Tanggal Pinjam --}}
                        <td class="px-4 py-4 text-gray-600">

                            {{ $peminjaman->tgl_pinjam->format('d-m-Y') }}

                        </td>

                        {{-- Rencana Kembali --}}
                        <td class="px-4 py-4 text-gray-600">

                            {{ $peminjaman->tgl_kembali_plan->format('d-m-Y') }}

                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-4 text-center">

                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium">
                                {{ ucfirst($peminjaman->status) }}
                            </span>

                        </td>

                        {{-- Aksi --}}
<td class="px-4 py-4 text-center">

    @php
        // Dua jalur berbeda:
        // - pengajuan sudah ada dan dialokasikan ke petugas
        //   (peminjam yang mengajukan sendiri) -> petugas MEMERIKSA
        //   lalu menyetujui, tanpa mengisi ulang kondisi di sini.
        // - belum ada pengajuan -> petugas yang MengAJUKAN dengan
        //   mengisi kondisi + denda (form lama).
        $sudahDiajukan = $peminjaman->pengembalian
            && $peminjaman->pengembalian->status_request === 'menunggu';

        // PERUBAHAN: Kolom diproses_oleh sudah dihapus
        // Sekarang semua pengajuan pengembalian dari peminjam langsung ke petugas
        $dariPeminjam = $sudahDiajukan
            && $peminjaman->pengembalian->user_id !== auth()->id();
    @endphp

    @if($dariPeminjam)

        <div class="flex flex-col gap-2 items-center">

            <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-medium">
                Dari Peminjam
            </span>

            @if($peminjaman->pengembalian->catatan_peminjam)
                <p class="text-xs text-gray-500 max-w-[220px] italic">
                    &ldquo;{{ $peminjaman->pengembalian->catatan_peminjam }}&rdquo;
                </p>
            @endif

            <a
                href="{{ route('petugas.pengembalian.periksa', $peminjaman->pengembalian->id) }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
            >
                Periksa
            </a>

        </div>

    @else

    <div class="flex flex-col gap-3">
        {{-- Tombol untuk pengembalian parsial --}}
        <a href="{{ route('petugas.pengembalian.parsial.form', $peminjaman->id) }}"
           class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-xs font-semibold transition shadow-sm text-center">
            Kembalikan Sebagian
        </a>
        
        {{-- Tombol untuk pengembalian lengkap --}}
        <button type="button"
                onclick="openPengembalianModal({{ $peminjaman->id }})"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition shadow-sm">
            Kembalikan Semua
        </button>
    </div>

    @endif

</td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-gray-500"
                        >
                            Tidak ada peminjaman yang sedang dipinjam.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="mt-4">
        {{ $peminjamans->links() }}
    </div>

</div>

{{-- Modal untuk pengembalian lengkap --}}
<div id="pengembalianModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center p-5 border-b">
            <h3 class="text-lg font-bold">Pengembalian Alat</h3>
            <button type="button" onclick="closePengembalianModal()" class="text-gray-500 hover:text-red-500 text-2xl">
                ×
            </button>
        </div>
        
        <form id="formPengembalian" action="" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" id="peminjamanId" name="peminjaman_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kondisi Kembali
                </label>
                <select name="kondisi_kembali" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" required>
                    <option value="Baik">Baik</option>
                    <option value="Rusak">Rusak</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Denda Kerusakan (Rp)
                </label>
                <input type="number" name="denda_kerusakan" value="0" min="0" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" required>
                <p class="text-xs text-gray-500 mt-1">Isi 0 jika tidak ada kerusakan</p>
            </div>
            
            <div class="border-t pt-4 flex justify-end space-x-3">
                <button type="button" onclick="closePengembalianModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Proses Pengembalian
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openPengembalianModal(peminjamanId) {
    document.getElementById('peminjamanId').value = peminjamanId;
    const form = document.getElementById('formPengembalian');
    form.action = `/petugas/pengembalian/${peminjamanId}/ajukan`;
    const modal = document.getElementById('pengembalianModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePengembalianModal() {
    const modal = document.getElementById('pengembalianModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

// Handle form submission untuk pengembalian lengkap
document.getElementById('formPengembalian').addEventListener('submit', function(e) {
    if (!confirm('Apakah alat sudah benar-benar dikembalikan dan ingin mengajukan pengembalian?')) {
        e.preventDefault();
        return false;
    }
});
</script>
@endpush
