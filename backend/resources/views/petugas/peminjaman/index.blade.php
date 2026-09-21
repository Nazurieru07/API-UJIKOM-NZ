@extends('layouts.app')

@section('title', 'Persetujuan Peminjaman - Dashboard Petugas')
@section('header-title', 'Daftar Pengajuan Peminjaman Alat')

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
            <h3 class="text-lg font-bold text-gray-800">Menunggu Verifikasi Persetujuan</h3>

            <form action="{{ route('petugas.peminjaman.index') }}" method="GET"
    class="flex flex-col md:flex-row gap-2 w-full">

    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Cari nama peminjam..."
        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <select
        name="jenis_kelamin"
        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

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

    <input
    type="date"
    name="tanggal_dari"
    value="{{ request('tanggal_dari') }}"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <input
    type="date"
    name="tanggal_sampai"
    value="{{ request('tanggal_sampai') }}"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <select
    name="alat_id"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <option value="">Semua Alat</option>

    @foreach($daftarAlat as $alat)
        <option
            value="{{ $alat->id }}"
            {{ request('alat_id') == $alat->id ? 'selected' : '' }}>
            {{ $alat->nama_alat }}
        </option>
    @endforeach

</select>

<select
    name="status"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">

    <option value="">Semua Status</option>

    <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>
        Diajukan
    </option>

    <option value="dipinjam" {{ request('status') == 'dipinjam' ? 'selected' : '' }}>
        Dipinjam
    </option>

    <option value="dikembalikan" {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>
        Dikembalikan
    </option>

    <option value="telat" {{ request('status') == 'telat' ? 'selected' : '' }}>
        Telat
    </option>

</select>

    <button
        type="submit"
        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition">
        Cari
    </button>

    @if(request()->hasAny([
    'search',
    'jenis_kelamin',
    'tanggal_dari',
    'tanggal_sampai',
    'alat_id',
    'status'
]))
        <a href="{{ route('petugas.peminjaman.index') }}"
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
                    <th class="py-3 px-4 border-b">Jenis Kelamin</th>
                    <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                    <th class="py-3 px-4 border-b">Rencana Kembali</th>
                    <th class="py-3 px-4 border-b">Detail Alat</th>
                    <th class="py-3 px-4 border-b text-center">Status Peminjaman</th>
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
    @if(optional($item->user)->jenis_kelamin == 'Laki-laki')
        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
            Laki-laki
        </span>

    @elseif(optional($item->user)->jenis_kelamin == 'Perempuan')
        <span class="px-2 py-0.5 text-xs rounded-full bg-pink-100 text-pink-700">
            Perempuan
        </span>

    @else
        <span class="text-gray-400 text-xs">-</span>
    @endif
</td>

                            <td class="py-3 px-4 border-b">
                                {{ $item->tgl_pinjam }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $item->tgl_kembali_plan }}
                            </td>

                            <td class="py-3 px-4 border-b">
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    @foreach($item->detailPinjams as $detail)
                                        <li>
                                            <button
                                            type="button"
                                            class="font-semibold text-blue-600 hover:text-blue-800 hover:underline transition"
                                            onclick='openAlatModal({
                                                nama: @json($detail->alat->nama_alat),
                                                kategori: @json(optional($detail->alat->kategori)->nama_kategori ?? "-"),
                                                stok: {{ $detail->alat->stok }},
                                                stok_baik: {{ $detail->alat->stok_baik }},
                                                stok_rusak: {{ $detail->alat->stok_rusak }},
                                                stok_rusak_parah: {{ $detail->alat->stok_rusak_parah }},
                                                deskripsi: @json($detail->alat->deskripsi ?? "Tidak ada deskripsi.")
                                            })'>

                                            {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}

                                        </button>
                                            <br>
                                            (Jumlah: {{ $detail->jumlah }})
                                        </li>
                                    @endforeach
                                </ul>
                            </td>

                            <td class="py-3 px-4 border-b text-center">
    @if($item->status == 'diajukan')
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
            Diajukan
        </span>

    @elseif($item->status == 'dipinjam')
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
            Dipinjam
        </span>

    @elseif($item->status == 'dikembalikan')
        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
            Dikembalikan
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

    @if($item->status == 'diajukan')

        <div class="flex justify-center items-center space-x-2">

            {{-- Tombol Setujui --}}
            <form action="{{ route('petugas.peminjaman.setujui', $item->id) }}" method="POST">
                @csrf

                <button type="submit"
                    onclick="return confirm('Setujui peminjaman alat ini?')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm">
                    Setujui
                </button>
            </form>


            {{-- Tombol Tolak --}}
            <form action="{{ route('petugas.peminjaman.tolak', $item->id) }}" method="POST">
                @csrf

                <button type="submit"
                    onclick="return confirm('Yakin ingin menolak pengajuan peminjaman ini?')"
                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition shadow-sm">
                    Tolak
                </button>
            </form>

        </div>

   @else
    <span class="text-gray-400 text-sm">-</span>
@endif

</td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500">
                                Tidak ada pengajuan peminjaman baru.
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
                    <p class="text-sm text-gray-500">Total Stok</p>
                    <p id="modalStok" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Kondisi</p>

                    <div class="text-sm mt-1 space-y-1">
                        <div>Baik: <span id="modalBaik"></span></div>
                        <div>Rusak: <span id="modalRusak"></span></div>
                        <div>Rusak Parah: <span id="modalParah"></span></div>
                    </div>

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
    </div>


<script>
function openAlatModal(alat) {

    document.getElementById('modalNama').textContent = alat.nama;
    document.getElementById('modalKategori').textContent = alat.kategori;
    document.getElementById('modalStok').textContent = alat.stok + ' pcs';
    document.getElementById('modalBaik').textContent = alat.stok_baik;
    document.getElementById('modalRusak').textContent = alat.stok_rusak;
    document.getElementById('modalParah').textContent = alat.stok_rusak_parah;
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
</script>

@endsection