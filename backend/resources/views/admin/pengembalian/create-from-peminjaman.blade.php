<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Buat Pengembalian</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <div class="max-w-4xl mx-auto px-4 py-8">

        <div class="bg-white rounded-2xl shadow p-6">

            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                Buat Pengembalian
            </h1>

            <p class="text-gray-500 mb-6">
                Form pengembalian alat oleh Admin.
            </p>

            <div class="space-y-3 mb-6">

                <p>
                    <strong>Peminjam:</strong>
                    {{ $peminjaman->user->name ?? '-' }}
                </p>

                <p>
                    <strong>Status:</strong>
                    {{ $peminjaman->status }}
                </p>

                <p>
                    <strong>Tanggal Pinjam:</strong>
                    {{ $peminjaman->tgl_pinjam }}
                </p>

                <p>
                    <strong>Rencana Kembali:</strong>
                    {{ $peminjaman->tgl_kembali_plan }}
                </p>

            </div>

            <h2 class="font-semibold text-gray-800 mb-3">
                Alat yang Dipinjam
            </h2>

            <div class="border rounded-lg mb-6">

                @foreach($peminjaman->detailPinjams as $detail)

                    <div class="px-4 py-3 border-b last:border-b-0">
                        {{ $detail->alat->nama_alat ?? '-' }}
                        — {{ $detail->jumlah }}
                    </div>

                @endforeach

            </div>

            <form
                action="{{ route('admin.peminjaman.pengembalian.ajukan', $peminjaman->id) }}"
                method="POST"
            >

                @csrf

                <div class="mb-4">

                    <label class="block font-semibold mb-2">
                        Kondisi Alat
                    </label>

                    <select
                        name="kondisi_kembali"
                        required
                        class="w-full border rounded-lg px-4 py-3"
                    >

                        <option value="">
                            Pilih Kondisi
                        </option>

                        <option value="Baik">
                            Baik
                        </option>

                        <option value="Rusak Ringan">
                            Rusak Ringan
                        </option>

                        <option value="Rusak Berat">
                            Rusak Berat
                        </option>

                    </select>

                </div>

                <div class="mb-6">

                    <label class="block font-semibold mb-2">
                        Denda Kerusakan
                    </label>

                    <input
                        type="number"
                        name="denda_kerusakan"
                        min="0"
                        value="0"
                        required
                        class="w-full border rounded-lg px-4 py-3"
                    >

                    <p class="text-sm text-gray-500 mt-1">
                        Denda keterlambatan dihitung otomatis oleh sistem.
                    </p>

                </div>

                <div class="flex gap-3">

                    <a
                        href="{{ route('admin.peminjaman.index') }}"
                        class="px-5 py-3 bg-gray-200 rounded-lg"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="px-5 py-3 bg-blue-600 text-white rounded-lg"
                    >
                        Ajukan Pengembalian
                    </button>

                </div>

            </form>

        </div>

    </div>

</body>
</html>