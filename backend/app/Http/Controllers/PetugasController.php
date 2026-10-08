<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\AlatUnit;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Notifications\PeminjamanDisetujuiNotification;
use App\Models\User;
use App\Notifications\PengembalianDiajukanNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\PermintaanEditPeminjaman;
use App\Models\DetailPermintaanEdit;
use App\Models\DetailPinjam;
use App\Notifications\PermintaanEditDiprosesNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Notifications\PengembalianSelesaiNotification;

class PetugasController extends Controller
{
    // =========================================================
    // PEMINJAMAN
    // =========================================================

    /**
     * Menampilkan daftar pengajuan peminjaman.
     *
     * Daftar difilter berdasarkan: search (nama peminjam),
     * jenis_kelamin, dan rentang tanggal tgl_pinjam.
     * Filter pakai when() supaya query tanpa filter tetap berjalan.
     */
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');
        $jenisKelamin = $request->input('jenis_kelamin');
        $tanggalDari = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');
        $alatId = $request->input('alat_id');
        $status = $request->input('status');

            $peminjamans = Peminjaman::with([
                'user',
                'detailPinjams.alatUnit.alat.kategori'
            ])
            ->when($search, function ($query, $search) {
    $query->whereHas('user', function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%");
            });
        })
        ->when($jenisKelamin, function ($query, $jenisKelamin) {
            $query->whereHas('user', function ($q) use ($jenisKelamin) {
                $q->where('jenis_kelamin', $jenisKelamin);
            });
        })
        ->when($tanggalDari, function ($query, $tanggalDari) {
            $query->whereDate('tgl_pinjam', '>=', $tanggalDari);
        })
        ->when($tanggalSampai, function ($query, $tanggalSampai) {
            $query->whereDate('tgl_pinjam', '<=', $tanggalSampai);
        })
            ->when($alatId, function ($query, $alatId) {
        $query->whereHas('detailPinjams.alatUnit', function ($q) use ($alatId) {
            $q->where('alat_id', $alatId);
        });
    })
    ->when($status, function ($query, $status) {
    $query->where('status', $status);
})


           ->orderByRaw("
    CASE status
        WHEN 'diajukan' THEN 1
        WHEN 'telat' THEN 2
        WHEN 'dipinjam' THEN 3
        WHEN 'dikembalikan' THEN 4
        ELSE 5
    END
")
->latest('created_at')
->paginate(10)
->withQueryString();

            $daftarAlat = Alat::select('alat.id', 'alat.nama_alat')
            ->join('alat_unit', 'alat.id', '=', 'alat_unit.alat_id')
            ->join('detail_pinjam', 'detail_pinjam.alat_unit_id', '=', 'alat_unit.id')
            ->distinct()
            ->orderBy('nama_alat')
            ->get();

        return view(
    'petugas.peminjaman.index',
    compact(
    'peminjamans',
    'search',
    'jenisKelamin',
    'tanggalDari',
    'tanggalSampai',
    'alatId',
    'status',
    'daftarAlat'
    )
    );
    }


    /**
     * Menyetujui peminjaman dan menandai unit serial sebagai dipinjam.
     *
     * Titik kritis alur: kondisi unit baru di-flip ke 'dipinjam' di
     * method ini, BUKAN saat peminjaman diajukan. Kalau pengajuan
     * ditolak, unit tetap 'tersedia' tanpa perlu dikembalikan.
     *
     * UPDATE bersyarat (WHERE kondisi = 'tersedia') + cek rowCount():
     * cegah 2 petugas approve serial yang sama bersamaan.
     */
    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('detailPinjams.alatUnit.alat')
                ->lockForUpdate()
                ->findOrFail($id);

            // Pastikan pengajuan memang belum diproses
            if ($peminjaman->status !== 'diajukan') {
                throw new \Exception(
                    'Pengajuan peminjaman ini sudah diproses.'
                );
            }

            $peminjaman->update([
                'status' => 'dipinjam'
            ]);

            // Tandai tiap unit serial sebagai dipinjam (atomic).
// Cek rowCount() === 1: kalau bukan 1, unit sudah dipakai pengajuan
// lain di antara waktu peminjam memilih dan petugas menyetujui.
foreach ($peminjaman->detailPinjams as $detail) {
    $unit = $detail->alatUnit;

    $updated = AlatUnit::where('id', $unit->id)
        ->where('kondisi', 'tersedia')
        ->update(['kondisi' => 'dipinjam']);

    if ($updated !== 1) {
        throw new \Exception(
            "Serial {$unit->serial_number} ({$unit->alat->nama_alat}) sudah dipakai peminjaman lain."
        );
    }
}

           DB::commit();

// Notifikasi masuk antrian (ShouldQueue). Jika queue worker tidak
// jalan, notifikasi numpuk di tabel jobs dan tidak pernah dikirim.
$peminjaman->user->notify(
    new PeminjamanDisetujuiNotification($peminjaman)
);

return redirect()
    ->back()
    ->with(
        'success',
        'Peminjaman disetujui, unit ditandai sebagai dipinjam.'
    );

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Terjadi kesalahan: ' . $e->getMessage()
                );
        }
    }


    /**
     * Menolak peminjaman.
     *
     * Pengajuan yang masih berstatus "diajukan" dihapus total,
     * karena alat belum pernah dikeluarkan. Menghapus memungkinkan
     * peminjam mengajukan ulang dengan benar.
     *
     * Pengajuan yang sudah pernah disetujui (statusnya sudah berubah
     * ke 'dipinjam' tapi kemudian dibatalkan lewat sini) meninggalkan
     * unit berkondisi 'dipinjam', jadi semua serial dikembalikan ke
     * 'tersedia' dulu sebelum data dihapus.
     *
     * Catatan: delete() memicu PeminjamanObserver::deleted, jadi
     * penolakan tetap tercatat di log aktivitas.
     */
    public function tolakPeminjaman($id)
    {
        try {
            $peminjaman = Peminjaman::findOrFail($id);

            // Pastikan status masih diajukan
            if ($peminjaman->status !== 'diajukan') {
                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'Status peminjaman sudah berubah.'
                    );
            }

            // Kembalikan semua unit yang sudah ditandai dipinjam
            $peminjaman->load('detailPinjams.alatUnit');
            foreach ($peminjaman->detailPinjams as $detail) {
                if ($detail->alatUnit && $detail->alatUnit->isDipinjam()) {
                    $detail->alatUnit->update(['kondisi' => 'tersedia']);
                }
            }

            $peminjaman->delete();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Pengajuan peminjaman berhasil ditolak.'
                );

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Terjadi kesalahan: ' . $e->getMessage()
                );
        }
    }


    // =========================================================
    // PENGEMBALIAN
    // =========================================================

    /**
     * Menampilkan daftar peminjaman yang dapat diajukan
     * sebagai pengembalian.
     *
     * Hanya peminjaman berstatus dipinjam/telat. Ditampilkan juga
     * pengembalian yang status_request-nya ditolak, supaya petugas
     * bisa mengajukan ulang peminjaman yang gagal sebelumnya.
     */
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');
        $kategoriId = $request->input('kategori_id');
        $tanggalDari = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');

        $peminjamans = Peminjaman::with([
            'user',
            'detailPinjams.alatUnit.alat.kategori',
            'pengembalian'
        ])
            ->whereIn('status', ['dipinjam', 'telat'])
            // Tiga sumber pengembalian di antrean petugas: belum diajukan,
            // sebelumnya ditolak (ajukan ulang), atau pengajuan peminjam
            // yang dialokasikan ke petugas. Tanpa ketiga-ketiganya,
            // pengajuan peminjam tidak pernah diproses.
            ->where(function ($query) {
                $query->whereDoesntHave('pengembalian')
                    ->orWhereHas('pengembalian', function ($q) {
                        $q->where('status_request', 'ditolak');
                    })
                    ->orWhereHas('pengembalian', function ($q) {
                        $q->where('status_request', 'menunggu')
                            ->where('diproses_oleh', 'petugas');
                    });
            })
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($kategoriId, function ($query, $kategoriId) {
                $query->whereHas('detailPinjams.alatUnit.alat', function ($q) use ($kategoriId) {
                    $q->where('kategori_id', $kategoriId);
                });
            })
            ->when($tanggalDari, function ($query, $tanggalDari) {
                $query->whereDate('tgl_pinjam', '>=', $tanggalDari);
            })
            ->when($tanggalSampai, function ($query, $tanggalSampai) {
                $query->whereDate('tgl_pinjam', '<=', $tanggalSampai);
            })
            ->latest('tgl_pinjam')
            ->paginate(10)
            ->withQueryString();

        // Daftar kategori untuk dropdown filter
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view(
            'petugas.pengembalian.index',
            compact('peminjamans', 'search', 'kategoriId', 'tanggalDari', 'tanggalSampai', 'kategoris')
        );
    }


    /**
     * Mengajukan pengembalian kepada Admin.
     *
     * Petugas menentukan:
     * - kondisi alat (Baik / Rusak Ringan / Rusak Berat)
     * - denda kerusakan (angka bebas, bisa Rp0)
     *
     * Kenapa denda KETERLAMBATAN tidak diisi petugas:
     * denda ini dihitung sistem dari selisih tgl_kembali_plan vs
     * tgl_kembali aktual, agar tidak bisa dimanipulasi. Dihitung saat
     * Admin menyetujui (AdminController::setujuiPengembalian), bukan sini.
     *
     * Relasi peminjaman -> pengembalian itu 1:1 (unique), jadi jika
     * pengembalian sebelumnya DITOLAK, data lama dipakai ulang (update)
     * bukan dibuat baru, agar tidak melanggar constraint unik.
     */
    public function ajukanPengembalian(Request $request, $peminjamanId)
    {
        $request->validate([
            'kondisi_kembali' => 'required|in:Baik,Rusak',
            'denda_kerusakan' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('pengembalian')
                ->findOrFail($peminjamanId);

            // Hanya peminjaman yang masih berjalan yang bisa
            // diajukan pengembaliannya. Yang sudah dikembalikan ditolak.
            if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                throw new \Exception(
                    'Peminjaman ini tidak dapat diajukan sebagai pengembalian.'
                );
            }

            $pengembalian = $peminjaman->pengembalian;

            // Pengajuan masih menunggu Admin
            if (
                $pengembalian &&
                $pengembalian->status_request === 'menunggu'
            ) {
                throw new \Exception(
                    'Pengajuan pengembalian ini masih menunggu persetujuan Admin.'
                );
            }

            // Pengembalian sudah disetujui
            if (
                $pengembalian &&
                $pengembalian->status_request === 'disetujui'
            ) {
                throw new \Exception(
                    'Pengembalian untuk peminjaman ini sudah disetujui Admin.'
                );
            }

            /*
             * Jika pengajuan sebelumnya ditolak,
             * gunakan kembali data pengembalian tersebut.
             */
            if ($pengembalian) {

                $pengembalian->update([
                    'tgl_kembali' => now()->toDateString(),
                    'kondisi_kembali' => $request->kondisi_kembali,
                    'denda' => 0,
                    'denda_kerusakan' => $request->denda_kerusakan,
                    'petugas_id' => auth()->id(),
                    'status_request' => 'menunggu',
                ]);

            } else {

                // Jika belum pernah ada pengajuan
                $pengembalian = Pengembalian::create([
                    'peminjaman_id' => $peminjaman->id,
                    'tgl_kembali' => now()->toDateString(),
                    'kondisi_kembali' => $request->kondisi_kembali,
                    'denda' => 0,
                    'denda_kerusakan' => $request->denda_kerusakan,
                    'petugas_id' => auth()->id(),
                    'status_request' => 'menunggu',
                ]);
            }

            DB::commit();

// Kirim notifikasi ke semua Admin
User::where('role', 'admin')
    ->get()
    ->each(function ($admin) use ($pengembalian) {
        $admin->notify(
            new PengembalianDiajukanNotification($pengembalian)
        );
    });

return redirect()
    ->back()
                ->with(
                    'success',
                    'Pengajuan pengembalian berhasil dikirim ke Admin untuk diperiksa.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Terjadi kesalahan: ' . $e->getMessage()
                );
        }
    }


    // =========================================================
    // LAPORAN
    // =========================================================

    /**
     * Menampilkan laporan pengembalian.
     *
     * Hanya pengembalian status_request=disetujui yang masuk ke laporan
     * resmi. Yang masih menunggu/ditolak TIDAK masuk, karena transaksinya
     * belum final (alat belum resmi kembali).
     *
     * petugas_id NULL -> pengembalian ditangani Admin langsung, tampil
     * sebagai "Admin" di laporan (bukan kosong/error).
     */
    public function indexLaporan(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas'
        ])
            ->where('status_request', 'disetujui')
            // Petugas hanya melihat pengembalian yang diprosesnya sendiri.
            // Admin (menu admin/laporan) melihat semuanya.
            ->where('petugas_id', auth()->id());

        // Filter tanggal mulai
        if (!empty($tanggalMulai)) {
            $query->whereDate(
                'tgl_kembali',
                '>=',
                $tanggalMulai
            );
        }

        // Filter tanggal selesai
        if (!empty($tanggalSelesai)) {
            $query->whereDate(
                'tgl_kembali',
                '<=',
                $tanggalSelesai
            );
        }

        $pengembalians = $query
            ->orderBy('tgl_kembali', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('petugas.laporan.index', [
            'pengembalians' => $pengembalians,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);
    }


    /**
     * Mencetak laporan pengembalian dalam bentuk PDF.
     *
     * Hanya pengembalian yang sudah disetujui Admin yang dicetak.
     *
     * Kenapa query diulang (tidak pakai method terpisah): laporan web
     * dan PDF butuh format data berbeda (paginate untuk web, semua
     * baris untuk PDF), jadi tidak bisa dibagikan langsung.
     * after_or_equal:tanggal_mulai mencegah rentang tanggal terbalik.
     */
    public function cetakLaporan(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas'
        ])
            ->where('status_request', 'disetujui')
            // Petugas hanya melihat pengembalian yang diprosesnya sendiri.
            ->where('petugas_id', auth()->id());

        // Filter tanggal mulai
        if ($tanggalMulai) {
            $query->whereDate(
                'tgl_kembali',
                '>=',
                $tanggalMulai
            );
        }

        // Filter tanggal selesai
        if ($tanggalSelesai) {
            $query->whereDate(
                'tgl_kembali',
                '<=',
                $tanggalSelesai
            );
        }

        $pengembalians = $query
            ->orderBy('tgl_kembali', 'desc')
            ->get();

        $pdf = Pdf::loadView('petugas.laporan.pdf', [
            'pengembalians' => $pengembalians,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream(
            'laporan-pengembalian-alat.pdf'
        );
    }

    /**
     * Mengekspor laporan pengembalian ke Excel (.xlsx).
     * Petugas hanya melihat data yang diprosesnya sendiri.
     */
    public function cetakLaporanExcel(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas'
        ])
            ->where('status_request', 'disetujui')
            ->where('petugas_id', auth()->id());

        if ($tanggalMulai) {
            $query->whereDate('tgl_kembali', '>=', $tanggalMulai);
        }

        if ($tanggalSelesai) {
            $query->whereDate('tgl_kembali', '<=', $tanggalSelesai);
        }

        $pengembalians = $query->orderBy('tgl_kembali', 'desc')->get();

        $namaFile = 'laporan-pengembalian-alat';
        if ($tanggalMulai || $tanggalSelesai) {
            $namaFile .= '-' . ($tanggalMulai ?? 'awal') . '-sd-' . ($tanggalSelesai ?? 'akhir');
        }

        return (new \App\Exports\LaporanPengembalianExport(
            $pengembalians,
            $tanggalMulai,
            $tanggalSelesai,
            auth()->user()->name
        ))->download($namaFile . '.xlsx');
    }

    /**
     * Daftar permintaan edit peminjaman (status menunggu).
     * Filter: peminjam, search (nama/email/alasan), rentang tanggal pengajuan.
     */
    public function indexEditPeminjaman(Request $request)
    {
        $search = $request->input('search');
        $peminjamId = $request->input('peminjam_id');
        $tanggalDari = $request->input('tanggal_dari');
        $tanggalSampai = $request->input('tanggal_sampai');

        $daftarPeminjam = PermintaanEditPeminjaman::where('status', 'menunggu')
            ->join('users', 'users.id', '=', 'permintaan_edit_peminjaman.user_id')
            ->distinct()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email']);

        $permintaanEdits = PermintaanEditPeminjaman::with(['peminjaman.user', 'user', 'detailEdits.alatUnit.alat'])
            ->where('status', 'menunggu')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhere('alasan', 'like', "%{$search}%");
                });
            })
            ->when($peminjamId, function ($query, $peminjamId) {
                $query->where('user_id', $peminjamId);
            })
            ->when($tanggalDari, function ($query, $tanggalDari) {
                $query->whereDate('created_at', '>=', $tanggalDari);
            })
            ->when($tanggalSampai, function ($query, $tanggalSampai) {
                $query->whereDate('created_at', '<=', $tanggalSampai);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('petugas.edit-peminjaman.index', compact('permintaanEdits', 'daftarPeminjam'));
    }

    /**
     * Setujui permintaan edit: apply perubahan per unit serial.
     *
     * aksi 'tambah' = claim unit (kondisi tersedia -> dipinjam) lalu
     * buat detail_pinjam baru. aksi 'hapus' = hapus detail_pinjam dan
     * kembalikan unit ke 'tersedia'.
     */
    public function setujuiEditPeminjaman($id)
    {
        $permintaanEdit = PermintaanEditPeminjaman::with([
            'peminjaman.detailPinjams.alatUnit.alat',
            'detailEdits.alatUnit.alat',
        ])
            ->lockForUpdate()
            ->findOrFail($id);

        if ($permintaanEdit->status !== 'menunggu') {
            return redirect()->back()->with('error', 'Permintaan ini sudah diproses.');
        }

        DB::beginTransaction();

        try {
            $peminjaman = $permintaanEdit->peminjaman;

            foreach ($permintaanEdit->detailEdits as $detail) {
                $unit = $detail->alatUnit;

                if ($detail->aksi === 'tambah') {
                    // Claim unit secara atomic: kalau bukan 1 baris,
                    // unit sudah tidak tersedia lagi.
                    $updated = AlatUnit::where('id', $unit->id)
                        ->where('kondisi', 'tersedia')
                        ->update(['kondisi' => 'dipinjam']);

                    if ($updated !== 1) {
                        throw new \Exception(
                            "Serial {$unit->serial_number} ({$unit->alat->nama_alat}) sudah tidak tersedia."
                        );
                    }

                    DetailPinjam::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_unit_id' => $unit->id,
                    ]);
                } elseif ($detail->aksi === 'hapus') {
                    // Lepaskan unit dari peminjaman ini
                    $dp = DetailPinjam::where('peminjaman_id', $peminjaman->id)
                        ->where('alat_unit_id', $unit->id)
                        ->first();
                    if (!$dp) {
                        throw new \Exception(
                            "Serial {$unit->serial_number} tidak ada di peminjaman ini."
                        );
                    }
                    $dp->delete();

                    // Unit bebas dipinjam lagi
                    AlatUnit::where('id', $unit->id)
                        ->where('kondisi', 'dipinjam')
                        ->update(['kondisi' => 'tersedia']);
                }
            }

            if ($permintaanEdit->tgl_kembali_plan_baru) {
                $peminjaman->tgl_kembali_plan = $permintaanEdit->tgl_kembali_plan_baru;
                $peminjaman->save();
            }

            $permintaanEdit->update([
                'status' => 'disetujui',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            DB::commit();

            $permintaanEdit->user->notify(new PermintaanEditDiprosesNotification($permintaanEdit));

            return redirect()->route('petugas.edit-peminjaman.index')->with('success', 'Permintaan edit disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Tolak permintaan edit.
     *
     * Permintaan edit aksi 'tambah' belum pernah mengubah kondisi unit
     * (claim baru terjadi di setujuiEditPeminjaman), jadi tidak ada
     * serial yang harus dikembalikan disini.
     */
    public function tolakEditPeminjaman(Request $request, $id)
    {
        $request->validate(['catatan_penolakan' => 'nullable|string|max:500']);
        $permintaanEdit = PermintaanEditPeminjaman::findOrFail($id);

        if ($permintaanEdit->status !== 'menunggu') {
            return redirect()->back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $permintaanEdit->update([
            'status' => 'ditolak',
            'catatan_penolakan' => $request->catatan_penolakan,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $permintaanEdit->user->notify(new PermintaanEditDiprosesNotification($permintaanEdit));

        return redirect()->route('petugas.edit-peminjaman.index')->with('success', 'Permintaan edit ditolak.');
    }

    /*
    |--------------------------------------------------------------------------
    | Pemeriksaan & persetujuan pengembalian oleh Petugas
    |--------------------------------------------------------------------------
    | Peminjam memilih "Diproses Oleh: Petugas" saat mengajukan
    | pengembalian, jadi antrean ini milik petugas -- sama seperti
    | pengembalian yang diajukan petugas sendiri lewat menu lama.
    |
    | Petugas mencatat hasil pemeriksaan (kondisi + denda kerusakan),
    | lalu menyetujui. Efeknya sama dengan AdminController::setujui-
    | Pengembalian: unit kembali sesuai kondisi, status peminjaman
    | jadi 'dikembalikan'. Bedanya petugas_id diisi petugas yang
    | menyetujui, sehingga pengembalian ini masuk ke LAPORAN PETUGAS
    | itu (menu petugas/laporan), bukan laporan admin saja.
    */

    /**
     * Halaman pemeriksaan satu pengembalian.
     */
    public function periksaPengembalian($id)
    {
        $pengembalian = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alatUnit.alat',
            'petugas',
        ])->findOrFail($id);

        // Hanya pengajuan yang menunggu yang bisa diperiksa, dan hanya
        // kalau memang dialokasikan ke petugas.
        if ($pengembalian->status_request !== 'menunggu') {
            return redirect()
                ->route('petugas.pengembalian.index')
                ->with('error', 'Pengajuan pengembalian ini sudah diproses.');
        }

        return view(
            'petugas.pengembalian.periksa',
            compact('pengembalian')
        );
    }

    /**
     * Menyimpan hasil pemeriksaan: kondisi barang + denda kerusakan.
     *
     * Terpisah dari persetujuan supaya petugas bisa menutupi data
     * lalu mengecek ulang sebelum menekan Setujui.
     */
    public function simpanPemeriksaan(Request $request, $id)
    {
        $validated = $request->validate([
            'kondisi_kembali' => ['required', 'string', Rule::in(['Baik', 'Rusak'])],
            'denda_kerusakan' => ['required', 'integer', 'min:0'],
        ]);

        $pengembalian = Pengembalian::findOrFail($id);

        if ($pengembalian->status_request !== 'menunggu') {
            return redirect()
                ->route('petugas.pengembalian.index')
                ->with('error', 'Pengajuan pengembalian ini sudah diproses.');
        }

        $pengembalian->update([
            'kondisi_kembali' => $validated['kondisi_kembali'],
            'denda_kerusakan' => $validated['denda_kerusakan'],
        ]);

        return redirect()
            ->route('petugas.pengembalian.periksa', $pengembalian->id)
            ->with('success', 'Hasil pemeriksaan tersimpan. Periksa kembali lalu setujui.');
    }

    /**
     * Petugas menyetujui pengembalian.
     *
     * Dikunci dengan konsekuensi yang sama seperti approve Admin:
     * unit balik ke katalog (atau ditandai rusak), status peminjaman
     * 'dikembalikan', denda keterlambatan dihitung sistem. Yang
     * membedakan: petugas_id diisi petugas ini, jadi pengembalian
     * masuk ke laporan petugas yang sama.
     */
    public function setujuiPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with([
                'peminjaman.user',
                'peminjaman.detailPinjams.alatUnit',
            ])->lockForUpdate()->findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception('Pengajuan pengembalian ini sudah diproses.');
            }

            // Peminjam tidak mengisi kondisi; tanpa guard ini null
            // akan terbaca 'rusak' dan semua unit ikut ditandai rusak.
            if (empty($pengembalian->kondisi_kembali)) {
                throw new \Exception(
                    'Kondisi barang belum diperiksa. Isi hasil pemeriksaan sebelum menyetujui.'
                );
            }

            $peminjaman = $pengembalian->peminjaman;

            if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                throw new \Exception(
                    'Peminjaman ini tidak dapat diproses sebagai pengembalian.'
                );
            }

            // Bandingkan per tanggal, bukan per detik: tgl_kembali
            // bisa punya jam (diisi default now()), sedangkan hari ini
            // mulai jam 00:00 -- tanpa startOfDay, pengembalian hari
            // ini yang sah selalu terbaca "di masa depan".
            $tglRencana = \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->startOfDay();
            $tglKembali = \Carbon\Carbon::parse($pengembalian->tgl_kembali)->startOfDay();

            if ($tglKembali->greaterThan(\Carbon\Carbon::today())) {
                throw new \Exception('Tanggal pengembalian tidak boleh melebihi hari ini.');
            }

            $hariTerlambat = $tglKembali->greaterThan($tglRencana)
                ? $tglRencana->diffInDays($tglKembali)
                : 0;

            $dendaKeterlambatan = $hariTerlambat * config('denda.keterlambatan_per_hari');

            $pengembalian->update([
                'denda' => $dendaKeterlambatan,
                'status_request' => 'disetujui',
                // Penentu laporan: pengembalian ini masuk ke laporan
                // petugas ini, bukan laporan admin saja.
                'petugas_id' => auth()->id(),
            ]);

            // 'Baik' -> kembali ke katalog, selain itu ->rusak dan
            // disembunyikan sampai diperbaiki dari menu Kelola Unit.
            $kondisiUnit = strcasecmp($pengembalian->kondisi_kembali, 'Baik') === 0
                ? 'tersedia'
                : 'rusak';

            foreach ($peminjaman->detailPinjams as $detail) {
                $unit = $detail->alatUnit;

                if (!$unit) {
                    continue;
                }

                // Update per instance supaya observer mencatat audit.
                $unit->update(['kondisi' => $kondisiUnit]);
            }

            $peminjaman->update(['status' => 'dikembalikan']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
        }

        auth()->user()->logAktivitas()->create([
            'aktivitas' => 'Menyetujui pengembalian peminjaman #'
                . $pengembalian->peminjaman_id
                . ' untuk peminjam '
                . ($pengembalian->peminjaman->user?->name ?? '-')
                . ', denda keterlambatan Rp'
                . number_format($dendaKeterlambatan, 0, ',', '.')
                . '.',
        ]);

        if ($pengembalian->peminjaman->user) {
            $pengembalian->peminjaman->user->notify(
                new PengembalianSelesaiNotification($pengembalian)
            );
        }

        return redirect()
            ->route('petugas.pengembalian.index')
            ->with(
                'success',
                'Pengembalian disetujui. Unit sudah kembali dan pengembalian ini masuk ke laporan Anda.'
            );
    }

    /**
     * Petugas menolak pengembalian. Unit dan status peminjaman tidak
     * disentuh -- memang belum ada yang berubah.
     */
    public function tolakPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with('peminjaman.user')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception('Pengajuan pengembalian ini sudah diproses.');
            }

            $pengembalian->update([
                'status_request' => 'ditolak',
                'denda' => 0,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menolak: ' . $e->getMessage());
        }

        auth()->user()->logAktivitas()->create([
            'aktivitas' => 'Menolak pengajuan pengembalian peminjaman #'
                . $pengembalian->peminjaman_id
                . ' dari peminjam '
                . ($pengembalian->peminjaman->user?->name ?? '-')
                . '.',
        ]);

        return redirect()
            ->route('petugas.pengembalian.index')
            ->with('success', 'Pengajuan pengembalian ditolak. Peminjam bisa mengajukan ulang.');
    }
}
