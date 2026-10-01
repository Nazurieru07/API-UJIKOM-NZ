<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\DetailPinjam;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait KelolaUnitAlat
{
    /*
    |----------------------------------------------------------------------
    | CRUD unit serial
    |----------------------------------------------------------------------
    | Tiga aksi dari menu "Kelola Unit" di halaman daftar alat:
    | - storeUnit        : tambah unit baru (serial manual, atau otomatis
    |                      dari kode_alat bila dikosongkan)
    | - tandaiRusak      : unit tersedia -> rusak (unit '-- dipinjam --'
    |                      atau sudah rusak tidak bisa diotak-atak)
    | - perbaiki         : unit rusak -> tersedia (sudah diperbaiki fisik)
    |
    | Semua aksi menolak unit yang sedang dipinjam: memindahkan unit
    | 'dipinjam' ke 'rusak' atau 'tersedia' di belakang peminjam akan
    | membuat kondisi alat dan status peminjaman tidak sinkron.
    */

    /**
     * Tambah unit baru untuk sebuah alat.
     */
    public function storeUnit(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            // Kosongkan untuk auto-generate dari kode_alat.
            'serial_number' => ['nullable', 'string', 'max:50'],
        ]);

        // Serial wajib unik GLOBAL: dua alat berbeda bisa memakai prefix
        // sama, jadi cek terhadap seluruh tabel, bukan per alat.
        $serial = trim((string) $request->serial_number);

        if ($serial === '') {
            $serial = AlatUnit::serialBerikutnya($alat->id, $alat->kode_alat);
        }

        // Normalisasi huruf besar + spasi supaya 'te-01' dan 'TE-01'
        // tidak jadi dua unit berbeda.
        $serial = strtoupper(preg_replace('/\s+/', '', $serial));

        if (AlatUnit::where('serial_number', $serial)->exists()) {
            return back()->with(
                'error',
                "Serial number '{$serial}' sudah dipakai unit lain. Serial harus unik."
            );
        }

        $unit = AlatUnit::create([
            'alat_id' => $alat->id,
            'serial_number' => $serial,
            'kondisi' => 'tersedia',
        ]);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => "Menambah unit '{$unit->serial_number}' untuk alat '{$alat->nama_alat}'.",
        ]);

        return redirect()
            ->route('admin.alat.index')
            ->with('success', "Unit '{$unit->serial_number}' berhasil ditambahkan.");
    }

    /**
     * Tandai unit tersedia sebagai rusak (tidak bisa dipinjam lagi).
     */
    public function tandaiRusak(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'unit_id' => ['required', 'array', 'min:1'],
            'unit_id.*' => ['integer'],
        ]);

        $unitIds = $request->unit_id;
        $jumlahBerhasil = 0;
        $ditolak = [];

        DB::transaction(function () use ($alat, $unitIds, &$jumlahBerhasil, &$ditolak) {
            foreach ($unitIds as $unitId) {
                $unit = AlatUnit::where('alat_id', $alat->id)
                    ->lockForUpdate()
                    ->find($unitId);

                if (!$unit) {
                    $ditolak[] = "#{$unitId} (bukan unit alat ini)";
                    continue;
                }

                // Unit dipinjam tidak boleh disentuh: peminjam masih
                // memegang barangnya, status peminjaman jadi tidak sinkron.
                if ($unit->isDipinjam()) {
                    $ditolak[] = $unit->serial_number . ' (sedang dipinjam)';
                    continue;
                }

                if ($unit->isRusak()) {
                    continue;
                }

                $unit->update(['kondisi' => 'rusak']);
                $jumlahBerhasil++;
            }
        });

        if ($jumlahBerhasil > 0) {
            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => "Menandai {$jumlahBerhasil} unit alat '{$alat->nama_alat}' sebagai rusak.",
            ]);
        }

        if ($ditolak !== []) {
            return redirect()
                ->route('admin.alat.index')
                ->with(
                    'error',
                    'Unit berikut tidak bisa ditandai rusak: ' . implode(', ', $ditolak) . '.'
                );
        }

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                $jumlahBerhasil . ' unit ditandai sebagai rusak dan disembunyikan dari katalog.'
            );
    }

    /**
     * Tandai unit rusak sebagai sudah diperbaiki -> tersedia.
     */
    public function perbaiki(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'unit_id' => ['required', 'array', 'min:1'],
            'unit_id.*' => ['integer'],
        ]);

        $jumlahBerhasil = 0;

        DB::transaction(function () use ($alat, $request, &$jumlahBerhasil) {
            foreach ($request->unit_id as $unitId) {
                $unit = AlatUnit::where('alat_id', $alat->id)
                    ->lockForUpdate()
                    ->find($unitId);

                if (!$unit || !$unit->isRusak()) {
                    continue;
                }

                $unit->update(['kondisi' => 'tersedia']);
                $jumlahBerhasil++;
            }
        });

        if ($jumlahBerhasil > 0) {
            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => "Memperbaiki {$jumlahBerhasil} unit alat '{$alat->nama_alat}' sehingga kembali tersedia.",
            ]);

            return redirect()
                ->route('admin.alat.index')
                ->with(
                    'success',
                    $jumlahBerhasil . ' unit ditandai sudah diperbaiki dan kembali ke katalog.'
                );
        }

        return redirect()
            ->route('admin.alat.index')
            ->with('error', 'Tidak ada unit rusak yang bisa diperbaiki dari pilihan tersebut.');
    }
    /**
     * Hapus satu unit dari alat.
     *
     * Unit yang sedang dipinjam tidak boleh dihapus: peminjam masih
     * memegang barangnya, dan menghapus baris alat_unit sementara
     * detail_pinjam masih menunjuknya akan merusak jejak peminjaman.
     * Itupun DetailPinjam::alat_unit_id punya FK RESTRICT, jadi delete
     * akan gagal dengan Integrity constraint violation -- pesan yang
     * membingungkan. Tolak lebih awal dengan penjelasan yang jelas.
     *
     * Unit yang pernah dipinjam (sudah pernah keluar) juga tidak
     * dihapus: hapus alatnya (yang otomatis terarsip) supaya laporan
     * lama tetap bisa ditelusuri.
     */
    public function hapusUnit(Request $request, $id, $unitId)
    {
        $alat = Alat::findOrFail($id);

        $unit = AlatUnit::where('alat_id', $alat->id)->find($unitId);

        if (!$unit) {
            return back()->with('error', 'Unit tidak ditemukan pada alat ini.');
        }

        if ($unit->isDipinjam()) {
            return back()->with(
                'error',
                "Unit '{$unit->serial_number}' sedang dipinjam dan tidak bisa dihapus."
            );
        }

        $pernahDipinjam = DetailPinjam::where('alat_unit_id', $unit->id)->exists();

        if ($pernahDipinjam) {
            return back()->with(
                'error',
                "Unit '{$unit->serial_number}' pernah dipinjam dan tidak bisa dihapus. "
                . 'Hapus alatnya kalau memang tidak dipakai lagi -- data riwayat tetap tersimpan.'
            );
        }

        DB::transaction(function () use ($unit): void {
            $unit->delete();
        });

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => "Menghapus unit '{$unit->serial_number}' dari alat '{$alat->nama_alat}'.",
        ]);

        return back()->with(
            'success',
            "Unit '{$unit->serial_number}' berhasil dihapus."
        );
    }

}
