@extends('layouts.app')

@section('title', 'Tambah Peminjaman - Panel Admin')
@section('header-title', 'Form Tambah Transaksi Peminjaman')

@section('content')
<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.peminjaman.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Pilih Peminjam (User)
            </label>

            <div class="relative">
            <input
                type="text"
                id="user-search"
                placeholder="Ketik minimal 3 huruf nama atau email..."
                autocomplete="off"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >

            <input
                type="hidden"
                name="user_id"
                id="user-id"
                value="{{ old('user_id') }}"
            >

            <div
                id="user-results"
                class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden"
            ></div>
        </div>

        <p
            id="selected-user"
            class="mt-2 text-sm text-green-600"
        ></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">
                    Tanggal Pinjam
                </label>

                <input
                    type="date"
                    name="tgl_pinjam"
                    value="{{ old('tgl_pinjam', date('Y-m-d')) }}"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">
                    Rencana Tanggal Kembali
                </label>

                <input
                    type="date"
                    name="tgl_kembali_plan"
                    value="{{ old('tgl_kembali_plan', date('Y-m-d', strtotime('+3 days'))) }}"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
        </div>

        <!-- Bagian Daftar Alat yang Dipinjam (Dinamis) -->
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Daftar Alat yang Dipinjam
            </label>

            <div id="alat-container" class="space-y-3">
                <div class="flex items-center gap-2 alat-row">
                    
                <div class="relative flex-1">
                <input
                    type="text"
                    placeholder="Ketik minimal 3 huruf nama alat..."
                    autocomplete="off"
                    class="alat-search w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                <input
                    type="hidden"
                    name="alat_id[]"
                    class="alat-id"
                >

                <div
                    class="alat-results absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden"
                ></div>

                <p class="selected-alat mt-1 text-xs text-green-600"></p>
            </div>

                    <input
                        type="number"
                        name="jumlah[]"
                        value="1"
                        min="1"
                        placeholder="Jumlah"
                        required
                        class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none"
                    >

                    <button
                        type="button"
                        onclick="removeRow(this)"
                        class="bg-red-500 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-600 transition"
                    >
                        X
                    </button>
                </div>
            </div>

            <button
                type="button"
                onclick="addRow()"
                class="mt-3 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold px-3 py-2 rounded-lg transition"
            >
                + Tambah Alat Lain
            </button>
        </div>

        <div class="flex justify-end space-x-2">
            <a
                href="{{ route('admin.peminjaman.index') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Batal
            </a>

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition"
            >
                Simpan Peminjaman
            </button>
        </div>
    </form>
</div>

<!-- Script Sederhana untuk Tambah/Hapus Baris Alat -->
<script>
    const userSearch = document.getElementById('user-search');
    const userResults = document.getElementById('user-results');
    const userId = document.getElementById('user-id');
    const selectedUser = document.getElementById('selected-user');

    /*
    |--------------------------------------------------------------------------
    | SEARCH USER
    |--------------------------------------------------------------------------
    */

    userSearch.addEventListener('input', function () {
        const keyword = this.value.trim();

        userId.value = '';
        selectedUser.textContent = '';

        if (keyword.length < 3) {
            userResults.innerHTML = '';
            userResults.classList.add('hidden');
            return;
        }

        fetch(`{{ route('admin.search.users') }}?search=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(users => {

                userResults.innerHTML = '';

                if (users.length === 0) {
                    userResults.innerHTML = `
                        <div class="px-4 py-3 text-sm text-gray-500">
                            User tidak ditemukan.
                        </div>
                    `;

                    userResults.classList.remove('hidden');
                    return;
                }

                users.forEach(user => {

                    const item = document.createElement('button');

                    item.type = 'button';

                    item.className =
                        'w-full text-left px-4 py-3 hover:bg-blue-50 border-b last:border-b-0';

                    item.innerHTML = `
                        <div class="font-semibold text-gray-800">
                            ${user.name}
                        </div>

                        <div class="text-xs text-gray-500">
                            ${user.email}
                        </div>
                    `;

                    item.addEventListener('click', function () {

                        userSearch.value =
                            `${user.name} (${user.email})`;

                        userId.value = user.id;

                        selectedUser.textContent =
                            `✓ Peminjam dipilih: ${user.name}`;

                        userResults.classList.add('hidden');

                    });

                    userResults.appendChild(item);

                });

                userResults.classList.remove('hidden');

            });
    });


    /*
    |--------------------------------------------------------------------------
    | SEARCH ALAT
    |--------------------------------------------------------------------------
    */

    function setupAlatSearch(row) {

        const searchInput =
            row.querySelector('.alat-search');

        const alatId =
            row.querySelector('.alat-id');

        const results =
            row.querySelector('.alat-results');

        const selectedAlat =
            row.querySelector('.selected-alat');


        searchInput.addEventListener('input', function () {

            const keyword = this.value.trim();

            alatId.value = '';
            selectedAlat.textContent = '';

            if (keyword.length < 3) {

                results.innerHTML = '';
                results.classList.add('hidden');

                return;
            }

            fetch(`{{ route('admin.search.alats') }}?search=${encodeURIComponent(keyword)}`)

                .then(response => response.json())

                .then(alats => {

                    results.innerHTML = '';

                    if (alats.length === 0) {

                        results.innerHTML = `
                            <div class="px-4 py-3 text-sm text-gray-500">
                                Alat tidak ditemukan.
                            </div>
                        `;

                        results.classList.remove('hidden');

                        return;
                    }


                    alats.forEach(alat => {

                        const item =
                            document.createElement('button');

                        item.type = 'button';

                        item.className =
                            'w-full text-left px-4 py-3 hover:bg-blue-50 border-b last:border-b-0';

                        item.innerHTML = `
                            <div class="font-semibold text-gray-800">
                                ${alat.nama_alat}
                            </div>

                            <div class="text-xs text-gray-500">
                                Stok tersedia: ${alat.stok_baik}
                            </div>
                        `;


                        item.addEventListener('click', function () {

                            searchInput.value =
                                alat.nama_alat;

                            alatId.value =
                                alat.id;

                            selectedAlat.textContent =
                                `✓ Dipilih: ${alat.nama_alat} | Stok: ${alat.stok_baik}`;

                            results.classList.add('hidden');

                        });


                        results.appendChild(item);

                    });


                    results.classList.remove('hidden');

                });

        });

    }


    /*
    |--------------------------------------------------------------------------
    | TAMBAH BARIS ALAT
    |--------------------------------------------------------------------------
    */

    function addRow() {

        const container =
            document.getElementById('alat-container');

        const firstRow =
            container.querySelector('.alat-row');

        const newRow =
            firstRow.cloneNode(true);


        // Reset input pencarian
        newRow.querySelector('.alat-search').value = '';

        // Reset ID alat
        newRow.querySelector('.alat-id').value = '';

        // Reset jumlah
        newRow.querySelector('input[name="jumlah[]"]').value = 1;

        // Reset hasil pencarian
        newRow.querySelector('.alat-results').innerHTML = '';

        newRow.querySelector('.alat-results')
            .classList.add('hidden');

        // Reset alat yang dipilih
        newRow.querySelector('.selected-alat').textContent = '';


        container.appendChild(newRow);

        // Aktifkan search pada baris baru
        setupAlatSearch(newRow);

    }


    /*
    |--------------------------------------------------------------------------
    | HAPUS BARIS ALAT
    |--------------------------------------------------------------------------
    */

    function removeRow(button) {

        const rows =
            document.querySelectorAll('.alat-row');

        if (rows.length > 1) {

            button.closest('.alat-row').remove();

        } else {

            alert('Minimal harus ada 1 alat yang dipilih.');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | AKTIFKAN SEARCH PADA BARIS PERTAMA
    |--------------------------------------------------------------------------
    */

    document.addEventListener('DOMContentLoaded', function () {

        const firstRow =
            document.querySelector('.alat-row');

        setupAlatSearch(firstRow);

    });
</script>
@endsection