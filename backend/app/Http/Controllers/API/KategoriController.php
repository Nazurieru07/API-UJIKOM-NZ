<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kategori\StoreKategoriRequest;
use App\Http\Requests\Kategori\UpdateKategoriRequest;
use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $kategori = Kategori::latest()->get();
        return response()->json([
            'message' => 'Daftar kategori berhasil diambil.',
            'data' => KategoriResource::collection($kategori)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKategoriRequest $request): JsonResponse
    {
        $kategori = Kategori::create($request->validated() );
        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => new KategoriResource($kategori)
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Kategori $kategori): JsonResponse
    {
        return response()->json([
            'data' => new KategoriResource($kategori)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKategoriRequest $request, Kategori $kategori): JsonResponse
    {
        $kategori->update($request->validated());
        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => new KategoriResource($kategori)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kategori $kategori): JsonResponse
    {
        // Kategori yang masih punya alat tidak bisa dihapus. Alatnya punya
        // riwayat peminjaman; FK alat.kategori_id RESTRICT juga menolak di DB.
        if ($kategori->alat()->exists()) {

            $jumlah = $kategori->alat()->count();

            return response()->json([
                'message' =>
                    "Kategori '{$kategori->nama_kategori}' masih memiliki {$jumlah} alat dan tidak dapat dihapus.",
            ], 422);
        }

        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.'
        ]);
    }
}
