<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengembalian\StorePengembalianRequest;
use App\Http\Requests\Pengembalian\UpdatePengembalianRequest;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class PengembalianController extends Controller
{
    public function index() : JsonResponse
    {
        $user = auth()->user();
        $query = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjams.alatUnit.alat', 'petugas']);
        if ($user->role === 'peminjam') {
            $query->whereHas('peminjaman', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $pengembalian = $query->latest()->get();
        return response()->json([
            'message'   => 'Riwayat pengembalian berhasil diambil.',
            'data'      => $pengembalian
        ]);
    }

    /**
     * Kondisi kembali dikunci dua nilai: 'Baik' atau 'Rusak'.
     *
     * Sama seperti AdminController::setujuiPengembalian di web. Nilai
     * lain tidak pernah muncul karena StorePengembalianRequest sudah
     * memblokirnya di validasi.
     */
    private function kondisiUnit(int|string|null $kondisiKembali): string
    {
        return mb_strtolower(trim((string) $kondisiKembali)) === 'baik'
            ? 'tersedia'
            : 'rusak';
    }

    /**
     * MengAJUKAN pengembalian (petugas/admin) -- tidak langsung memproses.
     *
     * Alur mengikuti web: pengajuan ini butuh persetujuan Admin sebelum
     * unit benar-benar kembali. Versi API sebelumnya menyelesaikan
     * semuanya dalam satu request: unit langsung dibebaskan, status
     * peminjaman langsung 'dikembalikan', dan denda keterlambatan tidak
     * pernah dihitung -- jadi alur pemeriksaan Admin dilewati sepenuhnya.
     *
     * Yang dilakukan method ini (dan TIDAK melakukan):
     * - membuat/mereset baris pengembalian dengan status_request='menunggu'
     * - TIDAK mengubah alat_unit.kondisi
     * - TIDAK mengubah status peminjaman
     * - TIDAK menghitung denda keterlambatan (itu tâche approve()).
     */
    public function store(StorePengembalianRequest $request) : JsonResponse
    {
        try {
            $pengembalian = DB::transaction(function () use ($request) {
                $peminjaman = Peminjaman::with('pengembalian')
                    ->lockForUpdate()
                    ->findOrFail($request->peminjaman_id);

                if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                    throw new Exception(
                        "Data ditolak. Peminjaman ini berstatus '{$peminjaman->status}', bukan 'dipinjam' atau 'telat'."
                    );
                }

                $existing = $peminjaman->pengembalian;

                if ($existing && $existing->status_request === 'menunggu') {
                    throw new Exception('Pengajuan pengembalian ini masih menunggu persetujuan Admin.');
                }

                if ($existing && $existing->status_request === 'disetujui') {
                    throw new Exception('Pengembalian untuk peminjaman ini sudah disetujui Admin.');
                }

                // Pengembalian dan peminjaman relasinya 1:1 (kolom
                // peminjaman_id unik), jadi pengajuan yang DITOLAK
                // sebelumnya diperbarui di tempat -- bukan dibuat baru,
                // yang akan melanggar constraint unik.
                $data = [
                    'peminjaman_id'   => $peminjaman->id,
                    'tgl_kembali'     => now()->toDateString(),
                    'kondisi_kembali' => $request->kondisi_kembali,
                    'denda'           => 0,
                    'denda_kerusakan' => $request->denda_kerusakan,
                    'petugas_id'      => auth()->id(),
                    'status_request'  => 'menunggu',
                ];

                if ($existing) {
                    $existing->update($data);

                    return $existing->fresh(['peminjaman.user', 'petugas']);
                }

                return Pengembalian::create($data)
                    ->load(['peminjaman.user', 'petugas']);
            });

            return response()->json([
                'message' => 'Pengajuan pengembalian berhasil dikirim ke Admin untuk diperiksa.',
                'data'    => $pengembalian,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Admin MENYETUJUI pengembalian: titik di mana unit benar-benar
     * kembali dan denda keterlambatan dihitung.
     *
     * Cerminan alur web (PetugasController::ajukanPengembalian ->
     * AdminController::setujuiPengembalian).
     */
    public function approve(Pengembalian $pengembalian) : JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($pengembalian) {
                $pengembalian = Pengembalian::with([
                    'peminjaman.user',
                    'peminjaman.detailPinjams.alatUnit.alat',
                ])->lockForUpdate()->findOrFail($pengembalian->id);

                if ($pengembalian->status_request !== 'menunggu') {
                    throw new Exception('Pengajuan pengembalian ini sudah diproses.');
                }

                $peminjaman = $pengembalian->peminjaman;

                if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                    throw new Exception('Peminjaman ini tidak dapat diproses sebagai pengembalian.');
                }

                $tglKembali = Carbon::parse($pengembalian->tgl_kembali);

                if ($tglKembali->greaterThan(Carbon::today())) {
                    throw new Exception('Tanggal pengembalian tidak boleh melebihi hari ini.');
                }

                // Denda keterlambatan dihitung sistem dari selisih
                // tanggal rencana vs aktual, bukan dari input petugas --
                // jadi tidak bisa dimanipulasi. Tarif di config/denda.php.
                $tglRencana = Carbon::parse($peminjaman->tgl_kembali_plan);
                $hariTerlambat = $tglKembali->greaterThan($tglRencana)
                    ? $tglRencana->diffInDays($tglKembali)
                    : 0;

                $dendaKeterlambatan = $hariTerlambat * config('denda.keterlambatan_per_hari');

                $pengembalian->update([
                    'denda'           => $dendaKeterlambatan,
                    'status_request'  => 'disetujui',
                ]);

                $kondisiUnit = $this->kondisiUnit($pengembalian->kondisi_kembali);

                foreach ($peminjaman->detailPinjams as $detail) {
                    $unit = $detail->alatUnit;

                    if (!$unit) {
                        throw new Exception(
                            "Persetujuan gagal. Unit pada item #{$detail->id} tidak ditemukan."
                        );
                    }

                    // Update per instance (bukan mass update) supaya
                    // AlatUnitObserver mencatat jejak audit tiap unit.
                    $unit->update(['kondisi' => $kondisiUnit]);
                }

                $peminjaman->update(['status' => 'dikembalikan']);

                auth()->user()->logAktivitas()->create([
                    'aktivitas' => "Menyetujui pengembalian peminjaman #{$peminjaman->id} "
                        . "dengan denda keterlambatan Rp" . number_format($dendaKeterlambatan, 0, ',', '.') . '.',
                ]);

                return $pengembalian->fresh(['peminjaman.user', 'petugas']);
            });

            return response()->json([
                'message' => 'Pengembalian disetujui. Unit alat dikembalikan dan denda keterlambatan telah dihitung.',
                'data'    => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Admin MENOLAK pengembalian: kembali ke 'ditolak' supaya petugas
     * bisa mengajukan ulang. Unit dan status peminjaman tidak
     * disentuh -- memang belum ada yang berubah.
     */
    public function reject(Pengembalian $pengembalian) : JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($pengembalian) {
                $pengembalian = Pengembalian::lockForUpdate()->findOrFail($pengembalian->id);

                if ($pengembalian->status_request !== 'menunggu') {
                    throw new Exception('Pengajuan pengembalian ini sudah diproses.');
                }

                $pengembalian->update([
                    'status_request' => 'ditolak',
                    'denda'          => 0,
                ]);

                auth()->user()->logAktivitas()->create([
                    'aktivitas' => "Menolak pengembalian ID #{$pengembalian->id}.",
                ]);

                return $pengembalian->fresh(['peminjaman.user', 'petugas']);
            });

            return response()->json([
                'message' => 'Pengajuan pengembalian ditolak. Petugas dapat mengajukan ulang.',
                'data'    => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Pengembalian $pengembalian) : JsonResponse
    {
        $user = auth()->user();

        $pengembalian->load(['peminjaman.user', 'peminjaman.detailPinjams.alatUnit.alat', 'petugas']);

        if ($user->role === 'peminjam' && $pengembalian->peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        return response()->json([
            'message'   => 'Detail pengembalian berhasil diambil.',
            'data'      => $pengembalian
        ]);
    }

    /**
     * Admin mengoreksi data pengembalian (kondisi/denda).
     *
     * Hanya data pengembalian yang berubah. Status peminjaman dan
     * kondisi unit TIDAK disentuh: kalau pengembalian sudah disetujui,
     * perubahan kondisi di sini tidak akan mengubah unit yang sudah
     * kembali -- supaya tidak ada dua sumber kebenaran soal kondisi unit.
     *
     * Kalau memang perlu memperbaiki kondisi unit, alurnya lewat
     * 'Kelola Unit' admin (tandai rusak / perbaiki), bukan lewat edit
     * pengembalian.
     */
    public function update(UpdatePengembalianRequest $request, Pengembalian $pengembalian) : JsonResponse
    {
        $pengembalian->update($request->validated());

        return response()->json([
            'message'   => 'Data pengembalian berhasil diperbarui.',
            'data'      => $pengembalian->load(['peminjaman.user', 'petugas'])
        ]);
    }

    public function destroy(Pengembalian $pengembalian) : JsonResponse
    {
        try {
            DB::transaction(function () use ($pengembalian) {
                $pengembalian = Pengembalian::with('peminjaman.detailPinjams.alatUnit')
                    ->lockForUpdate()->findOrFail($pengembalian->id);

                $peminjaman = $pengembalian->peminjaman;

                // Batalkan efek pengembalian yang sudah disetujui:
                // unit balik lagi ke 'dipinjam' (barang fisik belum
                // benar-benar masuk gudang), status peminjaman ke 'dipinjam'.
                // Pengembalian yang masih menunggu/ditolak belum pernah
                // mengubah unit, jadi tidak perlu rollback efek apa pun.
                if ($pengembalian->status_request === 'disetujui') {
                    foreach ($peminjaman->detailPinjams as $detail) {
                        $unit = $detail->alatUnit;

                        if (!$unit) {
                            continue;
                        }

                        $unit->update(['kondisi' => 'dipinjam']);
                    }

                    $peminjaman->update(['status' => 'dipinjam']);
                }

                $pengembalian->delete();

                auth()->user()->logAktivitas()->create([
                    'aktivitas' => "Menghapus pengembalian ID #{$pengembalian->id}.",
                ]);
            });

            return response()->json([
                'message' => 'Data pengembalian berhasil dihapus. Unit alat dan status peminjaman telah dikembalikan ke kondisi semula.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}