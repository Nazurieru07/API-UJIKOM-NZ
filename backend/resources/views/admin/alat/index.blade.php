@extends('layouts.app')

@section('title', 'Kelola Alat - Panel Admin')
@section('header-title', 'Manajemen Data Alat')

@section('content')

    @if(session('success'))
    <div class="flash-success mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="flash-error mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
        {{ session('error') }}
    </div>
@endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-wrap justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Alat Laboratorium</h3>

            <div class="flex items-center gap-3 w-full md:w-auto">

    <form
    action="{{ route('admin.alat.index') }}"
    method="GET"
    class="flex flex-wrap items-center gap-2"
>

    {{-- Search --}}
    <div class="flex">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Cari nama alat, kategori..."
            class="w-56 px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >

        <button
            type="submit"
            class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition"
        >
            Cari
        </button>
    </div>


    {{-- Filter Kategori --}}
    <select
    name="kategori"
    onchange="this.form.submit()"
    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
>
    <option value="">Semua Kategori</option>

    @foreach($kategori as $item)
        <option
            value="{{ $item->id }}"
            {{ request('kategori') == $item->id ? 'selected' : '' }}
        >
            {{ $item->nama_kategori }}
        </option>
    @endforeach
</select>


    {{-- Filter kondisi sengaja dihapus: kolom tabel sudah menampilkan jumlah
    unit Tersedia / Dipinjam / Rusak per alat, jadi filter ini redundan
    dan hanya membatasi hasil tanpa nilai tambah. --}}
    {{-- Reset --}}
    @if(request('search') || request('kategori'))
        <a
            href="{{ route('admin.alat.index') }}"
            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg transition"
        >
            Reset
        </a>
    @endif

</form>

                {{-- Tombol Tambah --}}
                <a
                    href="{{ route('admin.alat.create') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap"
                >
                    + Tambah Alat
                </a>

            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Gambar</th>
                        <th class="py-3 px-4 border-b">Nama Alat</th>
                        <th class="py-3 px-4 border-b">Kategori</th>
                        <th class="py-3 px-4 border-b">Unit Tersedia</th>
                        <th class="py-3 px-4 border-b">Unit Dipinjam</th>
                        <th class="py-3 px-4 border-b">Unit Rusak</th>
                        <th class="py-3 px-4 border-b">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-sm">
                    @forelse($alats as $alat)
                        <tr class="hover:bg-gray-50 transition">

                            <td class="py-3 px-4 border-b">
                                @if($alat->gambar)
                                    <img
                                        src="{{ asset($alat->gambar) }}"
                                        alt="{{ $alat->nama_alat }}"
                                        class="w-12 h-12 object-cover rounded-lg border"
                                    >
                                @else
                                    <span class="text-xs text-gray-400 italic">Tidak ada</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $alat->nama_alat }}

                                @if($alat->is_arsip)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                                        Arsip
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 border-b">
                                {{ $alat->kategori->nama_kategori ?? '-' }}
                            </td>

                            {{-- ponytail: jumlah_tersedia/dipinjam/rusak di-set controller lewat
                                withCount bertarget. Kalau butuh filter/pagination server-side
                                per kondisi, tambahkan scope di Alat + query terpisah. --}}
                            <td class="py-3 px-4 border-b font-semibold text-emerald-700">
                                {{ $alat->jumlah_tersedia ?? 0 }}
                            </td>

                            <td class="py-3 px-4 border-b font-semibold text-blue-700">
                                {{ $alat->jumlah_dipinjam ?? 0 }}
                            </td>

                            <td class="py-3 px-4 border-b font-semibold text-red-700">
                                {{ $alat->jumlah_rusak ?? 0 }}
                            </td>

                            <td class="py-3 px-4 border-b">
    <div class="flex flex-wrap items-center gap-2">

        {{-- Edit --}}
        <a
            href="{{ route('admin.alat.edit', $alat->id) }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
        >
            Edit
        </a>

        {{-- Kelola Unit: daftar serial + aksi per unit --}}
        <details class="relative" data-teleport-modal data-modal-id="unit-{{ $alat->id }}">
            <summary
                class="list-none cursor-pointer bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
            >
                Kelola Unit
            </summary>

            <!--
                ponytail: modal harus fixed terhadap VIEWPORT, bukan
                ancestor. CSS motion memberi transform ke div di dalam
                details[open] (animasi dropdown), dan transform ancestor
                membatalkan position:fixed -- akibatnya modal ikut
                ter-scroll halaman dan tombol Tambah Unit di bawah keluar
                viewport. Modal ini dipindahkan ke <body> saat dibuka
                (lihat script teleport di bawah halaman), jadi lepas dari
                transform ancestor.
            -->
            <div class="fixed inset-0 z-40 bg-black/30" onclick="this.parentElement.removeAttribute('open')"></div>
            <div class="modal-fixed fixed z-50 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[28rem] max-h-[85vh] overflow-auto bg-white border border-gray-200 rounded-lg shadow-2xl p-4">

                <div class="flex items-center justify-between mb-3 gap-2">
                    <p class="text-sm font-semibold text-gray-800 min-w-0 truncate">
                        Kelola Unit — {{ $alat->nama_alat }}
                    </p>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs text-gray-500">
                            {{ $alat->alat_unit_count ?? 0 }} unit
                        </span>

                        <button
                            type="button"
                            data-close-modal="unit-{{ $alat->id }}"
                            title="Tutup"
                            aria-label="Tutup"
                            class="w-6 h-6 flex items-center justify-center rounded-full
                                   text-gray-500 hover:bg-gray-100 hover:text-gray-800
                                   text-lg leading-none transition"
                        >
                            &times;
                        </button>
                    </div>
                </div>

                @forelse($alat->alatUnit as $unit)
                    <div class="flex items-center justify-between gap-2 py-2 border-t border-gray-100 first:border-t-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xs font-mono text-gray-800 truncate">
                                {{ $unit->serial_number }}
                            </span>

                            @if($unit->isTersedia())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                    Tersedia
                                </span>
                            @elseif($unit->isDipinjam())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                                    Dipinjam
                                </span>
                            @elseif($unit->isRusak())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">
                                    Rusak
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            {{-- ponytail: route admin.alat.unit.* belum ada selama migrasi
                                serial berjalan. Guard supaya view tetap render; tombol
                                aktif otomatis begitu route ditambahkan. --}}
                            @if(Route::has('admin.alat.unit.tandaiRusak') && $unit->isTersedia())
                                <form
                                    action="{{ route('admin.alat.unit.tandaiRusak', $alat->id) }}"
                                    method="POST"
                                >
                                    @csrf
                                    <input type="hidden" name="unit_id[]" value="{{ $unit->id }}">

                                    <button
                                        type="submit"
                                        onclick="return confirm('Tandai unit {{ $unit->serial_number }} sebagai rusak? Unit tidak akan tampil di katalog peminjaman.')"
                                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold transition"
                                    >
                                        Tandai Rusak
                                    </button>
                                </form>
                            @elseif(Route::has('admin.alat.unit.perbaiki') && $unit->isRusak())
                                <form
                                    action="{{ route('admin.alat.unit.perbaiki', $alat->id) }}"
                                    method="POST"
                                >
                                    @csrf
                                    <input type="hidden" name="unit_id[]" value="{{ $unit->id }}">

                                    <button
                                        type="submit"
                                        onclick="return confirm('Perbaiki unit {{ $unit->serial_number }}? Kondisi unit akan dikembalikan menjadi tersedia.')"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 rounded text-xs font-semibold transition"
                                    >
                                        Perbaiki
                                    </button>
                                </form>
                            @endif

                            {{--
                                Hapus unit. Hanya untuk unit yang belum pernah
                                dipinjam -- controller menolak unit dipinjam dan
                                unit yang punya riwayat, jadi pesan errornya
                                jauh lebih jelas daripada FK error dari database.
                            --}}
                            @if(Route::has('admin.alat.unit.hapus'))
                                <form
                                    action="{{ route('admin.alat.unit.hapus', [$alat->id, $unit->id]) }}"
                                    method="POST"
                                    class="inline"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        title="Hapus unit ini"
                                        onclick="return confirm('Hapus unit {{ $unit->serial_number }}? Tindakan ini tidak bisa dibatalkan.')"
                                        class="w-6 h-6 flex items-center justify-center
                                               text-gray-400 hover:bg-red-50 hover:text-red-600
                                               rounded transition"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 italic py-4 text-center">
                        Belum ada unit terdaftar untuk alat ini. Gunakan "Tambah Unit" di bawah.
                    </p>
                @endforelse

                {{-- Tambah Unit: serial manual, kosongkan untuk auto --}}
                @if(Route::has('admin.alat.unit.store'))
                <form
                    action="{{ route('admin.alat.unit.store', $alat->id) }}"
                    method="POST"
                    class="mt-4 pt-4 border-t border-gray-200"
                >
                    @csrf

                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Tambah Unit (Serial Number)
                    </label>

                    <input
                        type="text"
                        name="serial_number"
                        placeholder="Kosongkan untuk serial otomatis"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg font-mono focus:outline-none focus:ring-2 focus:ring-amber-500"
                    >

                    <p class="text-xs text-gray-400 mt-1 mb-3">
                        Format serial: prefix "kode_alat" + nomor urut. Biarkan kosong
                        untuk mendapatkan nomor urut berikutnya secara otomatis.
                    </p>

                    <button
                        type="submit"
                        class="w-full bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
                    >
                        Tambah Unit
                    </button>
                </form>
                @else
                    <p class="text-xs text-gray-400 italic mt-4 pt-4 border-t border-gray-200">
                        Tambah unit tersedia setelah rute serial siap.
                    </p>
                @endif
            </div>
        </details>

        {{-- Perbaiki Massal: pilih unit rusak via checkbox --}}
        @if(Route::has('admin.alat.unit.perbaiki') && ($alat->jumlah_rusak ?? 0) > 0)
            <details class="relative" data-teleport-modal data-modal-id="perbaiki-{{ $alat->id }}">
                <summary
                    class="list-none cursor-pointer bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
                >
                    Perbaiki
                </summary>

                <div class="fixed inset-0 z-40 bg-black/30" onclick="this.parentElement.removeAttribute('open')"></div>
                <div class="modal-fixed fixed z-50 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[28rem] max-h-[85vh] overflow-auto bg-white border border-gray-200 rounded-lg shadow-2xl p-4">

                    <div class="flex items-center justify-between mb-3 gap-2">
                        <p class="text-sm font-semibold text-gray-800">
                            Perbaiki Unit Rusak
                        </p>

                        <button
                            type="button"
                            data-close-modal="perbaiki-{{ $alat->id }}"
                            title="Tutup"
                            aria-label="Tutup"
                            class="w-6 h-6 flex items-center justify-center rounded-full
                                   text-gray-500 hover:bg-gray-100 hover:text-gray-800
                                   text-lg leading-none transition shrink-0"
                        >
                            &times;
                        </button>
                    </div>

                    <form
                        action="{{ route('admin.alat.unit.perbaiki', $alat->id) }}"
                        method="POST"
                    >
                        @csrf

                        <div class="space-y-2 mb-4">
                            @foreach($alat->alatUnit as $unit)
                                @if($unit->isRusak())
                                    <label class="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            name="unit_id[]"
                                            value="{{ $unit->id }}"
                                            checked
                                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                        >

                                        <span class="font-mono text-xs">{{ $unit->serial_number }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>

                        <button
                            type="submit"
                            onclick="return confirm('Yakin unit-unit yang dipilih sudah diperbaiki? Kondisi akan dikembalikan menjadi tersedia.')"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-xs font-semibold transition"
                        >
                            Tandai Perbaikan
                        </button>
                    </form>
                </div>
            </details>
        @endif

        {{-- Hapus --}}
        @if($alat->is_arsip)
            {{--
                | Alat sudah diarsipkan. Riwayat peminjamannya masih ada
                | (FK detail_pinjam RESTRICT), jadi tidak boleh dihapus
                | permanen. Bisa dibatalkan admin lain waktu.
            --}}
            <form
                action="{{ route('admin.alat.restore', $alat->id) }}"
                method="POST"
            >
                @csrf
                @method('POST')

                <button
                    type="submit"
                    onclick="return confirm('Kembalikan alat ini ke katalog?')"
                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
                >
                    Buka Arsip
                </button>
            </form>
        @else
            <form
                action="{{ route('admin.alat.destroy', $alat->id) }}"
                method="POST"
                onsubmit="return confirm('Yakin ingin menghapus alat ini?')"
            >
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition"
                >
                    Hapus
                </button>
            </form>
        @endif

    </div>
</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">
                                Belum ada data alat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $alats->links() }}
        </div>

    </div>

@endsection

@push('scripts')
<script>
    /*
    | Teleport modal "Kelola Unit" / "Perbaiki" ke <body> saat dibuka.
    |
    | CSS motion di app memberi animasi (yang ujungnya transform) pada
    | details[open] > div. Modal ada di dalam details itu, jadi ia dapat
    | transform. Sebuah ancestor dengan transform membatalkan
    | position:fixed: modal jadi "fixed terhadap ancestor" -- ikut
    | ter-scroll halaman, dan bagian bawahnya (tombol Tambah Unit) keluar
    | viewport saat list unit panjang.
    |
    | Pindahkan node modal + overlay ke <body> saat details terbuka,
    | kembalikan saat ditutup. Tanpa transform di ancestor, fixed-nya
    | merujuk viewport seperti mestinya.
    |
    | Toggle: toggleAttribute('open') pada details, jadi overlay tidak
    | perlu JS click handler -- klik summary dan klik overlay sama-sama
    | melempar event yang ditangkap di sini.
    */
    document.addEventListener('DOMContentLoaded', function () {
        var teleporters = document.querySelectorAll('[data-teleport-modal]');

        teleporters.forEach(function (details) {
            var placeholder = document.createElement('template');
            var overlay = details.querySelector('.fixed.inset-0');
            var modal = details.querySelector('.fixed.z-50');

            // Pindahkan modal + overlay ke body, sisipkan placeholder di
            // posisi aslinya supaya bisa dikembalikan.
            function teleportOut() {
                if (!modal || !overlay) return;
                if (modal.dataset.teleported === '1') return;
                modal.dataset.teleported = '1';
                document.body.appendChild(overlay);
                document.body.appendChild(modal);
            }

            function teleportIn() {
                if (!modal || !overlay) return;
                if (modal.dataset.teleported !== '1') return;
                delete modal.dataset.teleported;
                details.appendChild(overlay);
                details.appendChild(modal);
            }

            // Details diawasi: atribut open berubah = buka/tutup.
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    if (m.attributeName !== 'open') return;
                    if (details.hasAttribute('open')) {
                        teleportOut();
                    } else {
                        teleportIn();
                    }
                });
            });

            observer.observe(details, { attributes: true });
        });
    });
</script>
@endpush
