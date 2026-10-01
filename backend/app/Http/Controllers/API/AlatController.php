<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alat\StoreAlatRequest;
use App\Http\Requests\Alat\UpdateAlatRequest;
use App\Http\Resources\AlatResource;
use App\Models\Alat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AlatController extends Controller
{
    public function index(): JsonResponse
    {
        // Tetap menggunakan eager loading untuk mencegah N+1 Query
        $alat = Alat::with('kategori')->latest()->get();

        return response()->json([
            'message' => 'Daftar alat berhasil diambil.',
            'data' => AlatResource::collection($alat)
        ]);
    }

    public function store(StoreAlatRequest $request): JsonResponse
    {
        $data = $request->validated();

        $alat = DB::transaction(function () use ($request, $data) {
            if ($request->hasFile('gambar')) {
                $data['gambar'] = $request->file('gambar')->store('alat', 'public');
            }

            return Alat::create($data);
        });

        return response()->json([
            'message' => 'Alat berhasil ditambahkan.',
            'data' => new AlatResource($alat->load('kategori'))
        ], 201);
    }

    public function show(Alat $alat): JsonResponse
    {
        // Menggunakan Route Model Binding ($alat) dikombinasikan dengan load()
        return response()->json([
            'data' => new AlatResource($alat->load('kategori'))
        ]);
    }

    public function update(UpdateAlatRequest $request, Alat $alat): JsonResponse
    {
        $data = $request->validated();
        $oldGambar = $alat->gambar; // Simpan path gambar lama lebih dahulu

        DB::transaction(function () use ($request, &$data, $alat, $oldGambar) {
            if ($request->hasFile('gambar')) {
                $data['gambar'] = $request->file('gambar')->store('alat', 'public');

                // Hapus gambar lama dari server HANYA setelah gambar baru sukses masuk database
                if ($oldGambar) {
                    Storage::disk('public')->delete($oldGambar);
                }
            }

            $alat->update($data);
        });

        return response()->json([
            'message' => 'Alat berhasil diperbarui.',
            'data' => new AlatResource($alat->load('kategori'))
        ]);
    }

    public function destroy(Alat $alat): JsonResponse
    {
        // Alat yang sudah dipakai peminjaman tidak bisa dihapus permanen --
        // riwayatnya harus tetap ada. Lihat AdminController::destroyAlat.
        if ($alat->detailPinjam()->exists()) {

            $alat->update(['is_arsip' => true]);

            return response()->json([
                'message' =>
                    "Alat '{$alat->nama_alat}' diarsipkan karena memiliki riwayat peminjaman. Data tetap ada untuk laporan.",
            ], 200);
        }

        DB::transaction(function () use ($alat) {
            if ($alat->gambar) {
                Storage::disk('public')->delete($alat->gambar);
            }

            $alat->delete();
        });

        return response()->json([
            'message' => 'Alat berhasil dihapus.'
        ]);
    }

    public function katalog(): JsonResponse
    {
        // Hanya alat yang masih punya unit 'tersedia' yang muncul.
        // whereHas + withCount pakai closure yang sama supaya filter dan
        // angka 'jumlah_tersedia' selalu konsisten.
        $alat = Alat::with('kategori', 'alatUnit')
            ->tidakTerarsip()
            ->whereHas('alatUnit', fn ($q) => $q->where('kondisi', 'tersedia'))
            ->withCount([
                'alatUnit as jumlah_tersedia' => fn ($q) => $q->where('kondisi', 'tersedia'),
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Katalog alat tersedia.',
            'data' => AlatResource::collection($alat)
        ]);
    }
}