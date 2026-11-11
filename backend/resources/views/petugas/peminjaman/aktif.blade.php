@extends('layouts.app')

@section('title', 'Peminjaman Aktif - Dashboard Petugas')
@section('header-title', 'Daftar Peminjaman Aktif')

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
        <div class="p-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Peminjaman Aktif</h3>
            <p class="text-sm text-gray-500 mt-1">
                Menampilkan semua peminjaman yang masih aktif (status dipinjam/telat), termasuk yang sudah ada pengembalian parsial.
            </p>

            <form action="{{ route('petugas.peminjaman.aktif') }}" method="GET"
    class="flex flex-col md:flex-row gap-2 w-full mt-4">

    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Cari nama peminjam..."
        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <select
        name="kategori_id"
        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Semua Kategori</option>
        @foreach($kategoris as $kategori)
            <option value="{{ $kategori->id }}" {{ request('kategori_id') == $kategori->id ? 'selected' : '' }}>
                {{ $kategori->nama_kategori }}
            </option>
        @endforeach
    </select>

    <input
    type="date"
    name="tanggal_dari"
    value="{{ request('tanggal_dari') }}"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <input
    type="date"
    name="tanggal_sampai"
    value="{{ request('tanggal_sampai') }}"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

    <button
        type="submit"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
        Cari
    </button>

    @if(request()->hasAny(['search', 'kategori_id', 'tanggal_dari', 'tanggal_sampai']))
        <a href="{{ route('petugas.peminjaman.aktif') }}"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center justify-center transition">
            Reset
        </a>
    @endif

</form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                    <th class="py-3 px-4 border-b">Peminjam</th>
                    <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                    <th class="py-3 px-4 border-b">Rencana Kembali</th>
                    <th class="py-3 px-4 border-b">Detail Alat</th>
                    <th class="py-3 px-4 border-b text-center">Status Peminjaman</th>
                    <th class="py-3 px-4 border-b text-center">Status Pengembalian</th>
                    <th class="py-3 px-4 border-b text-center">Aksi</th>
                </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $item)
                        <tr class="hover:bg-gray-50 transition align-top">

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $item->user->name ?? 'User Dihapus' }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $item->tgl_pinjam->format('d F Y') }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $item->tgl_kembali_plan->format('d F Y') }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    @foreach($item->detailPinjams as $detail)
                                        @php
                                            $dikembalikan = false;
                                            $kondisi = null;
                                            $denda = 0;
                                            
                                            if ($item->pengembalian && $item->pengembalian->detailPengembalians) {
                                                $detailPengembalian = $item->pengembalian->detailPengembalians
                                                    ->where('detail_pinjam_id', $detail->id)
                                                    ->first();
                                                
                                                if ($detailPengembalian) {
                                                    $dikembalikan = true;
                                                    $kondisi = $detailPengembalian->kondisi_kembali;
                                                    $denda = $detailPengembalian->denda_kerusakan;
                                                }
                                            }
                                        @endphp
                                        
                                        <li class="{{ $dikembalikan ? 'text-green-600' : 'text-gray-700' }}">
                                            {{ $detail->alatUnit?->alat->nama_alat ?? 'Alat Dihapus' }}
                                            ({{ $detail->alatUnit?->serial_number ?? '-' }})
                                            @if($dikembalikan)
                                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                                    ✓ Dikembalikan ({{ $kondisi }}, Denda: Rp{{ number_format($denda, 0, ',', '.') }})
                                                </span>
                                            @else
                                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded">
                                                    ● Masih dipinjam
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>

                            <td class="py-3 px-4 border-b text-center">
    @if($item->status == 'dipinjam')
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
            Dipinjam
        </span>
    @elseif($item->status == 'telat')
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
            Telat
        </span>
    @else
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
            {{ ucfirst($item->status) }}
        </span>
    @endif
</td>

<td class="py-3 px-4 border-b text-center">
    @if($item->pengembalian)
        @if($item->pengembalian->is_parsial)
            <div class="flex flex-col items-center gap-1">
                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                    Parsial
                </span>
                <span class="text-xs text-gray-600">
                    {{ $item->pengembalian->jumlah_unit_dikembalikan }} / {{ $item->detailPinjams->count() }} unit
                </span>
            </div>
        @else
            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                Lengkap
            </span>
        @endif
    @else
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
            Belum ada
        </span>
    @endif
</td>

<td class="py-3 px-4 border-b text-center">
    <div class="flex flex-col gap-2 items-center">
        @if($item->pengembalian && $item->pengembalian->is_parsial)
            {{-- Jika sudah ada pengembalian parsial, tampilkan tombol untuk tambah pengembalian --}}
            <a href="{{ route('petugas.pengembalian.parsial.form', $item->id) }}"
               class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm w-full text-center">
                Tambah Pengembalian
            </a>
        @else
            {{-- Jika belum ada pengembalian, tampilkan kedua opsi --}}
            <a href="{{ route('petugas.pengembalian.parsial.form', $item->id) }}"
               class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm w-full text-center">
                Kembalikan Sebagian
            </a>
            
            <button type="button"
                    onclick="openPengembalianModal({{ $item->id }})"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm w-full">
                Kembalikan Semua
            </button>
        @endif
    </div>
</td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500">
                                Tidak ada peminjaman aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div id="alatModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">

        <div class="flex justify-between items-center p-5 border-b">
            <h3 class="text-lg font-bold">Detail Alat</h3>

            <button
                type="button"
                onclick="closeAlatModal()"
                class="text-gray-500 hover:text-red-500 text-2xl">

                ×

            </button>
        </div>

        <div class="p-5 space-y-4">

            <div>
                <p class="text-sm text-gray-500">Nama Alat</p>
                <p id="modalNama" class="font-semibold"></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Kategori</p>
                <p id="modalKategori"></p>
            </div>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <p class="text-sm text-gray-500">Tersedia</p>
                    <p id="modalTersedia" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Sedang Dipinjam</p>
                    <p id="modalDipinjam" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Rusak</p>
                    <p id="modalRusak" class="font-semibold"></p>
                </div>

            </div>

            <div>
                <p class="text-sm text-gray-500">Deskripsi</p>
                <p id="modalDeskripsi" class="text-sm"></p>
            </div>

        </div>

        <div class="border-t p-4 flex justify-end">

            <button
                type="button"
                onclick="closeAlatModal()"
                class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg">

                Tutup

            </button>

        </div>

    </div>

</div>

        </div>
        
        <div class="p-4 border-t border-gray-200">
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

<script>
function openAlatModal(alat) {
    document.getElementById('modalNama').textContent = alat.nama;
    document.getElementById('modalKategori').textContent = alat.kategori;
    document.getElementById('modalTersedia').textContent = alat.jumlah_tersedia + ' unit';
    document.getElementById('modalDipinjam').textContent = alat.jumlah_dipinjam + ' unit';
    document.getElementById('modalRusak').textContent = alat.jumlah_rusak + ' unit';
    document.getElementById('modalDeskripsi').textContent = alat.deskripsi;

    const modal = document.getElementById('alatModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAlatModal() {
    const modal = document.getElementById('alatModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

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
    if (!confirm('Apakah semua alat sudah benar-benar dikembalikan?')) {
        e.preventDefault();
        return false;
    }
});
</script>

@endsection