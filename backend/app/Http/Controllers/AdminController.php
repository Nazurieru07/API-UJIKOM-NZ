<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\DetailPinjam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // =========================================================
    // DASHBOARD
    // =========================================================

    /**
     * Menampilkan dashboard Admin dan log aktivitas.
     */
    public function index()
    {
        $logs = LogAktivitas::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('logs'));
    }


    // =========================================================
    // CRUD ALAT
    // =========================================================

    /**
     * Menampilkan daftar alat.
     */
    public function indexAlat(Request $request)
    {
        $search = $request->input('search');

        $alats = Alat::with('kategori')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_alat', 'like', "%{$search}%")
                        ->orWhere('status_kondisi', 'like', "%{$search}%")
                        ->orWhereHas('kategori', function ($kategori) use ($search) {
                            $kategori->where(
                                'nama_kategori',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.alat.index',
            compact('alats', 'search')
        );
    }


    /**
     * Menampilkan form tambah alat.
     */
    public function createAlat()
    {
        $kategoris = Kategori::all();

        return view(
            'admin.alat.create',
            compact('kategoris')
        );
    }


    /**
     * Menyimpan alat baru.
     */
    public function storeAlat(Request $request)
    {
        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        // Upload gambar
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');

            $filename = time() . '_' . $file->getClientOriginalName();

            $file->move(
                public_path('storage/alat'),
                $filename
            );

            $data['gambar'] = 'storage/alat/' . $filename;
        }

        Alat::create($data);

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                'Data alat berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit alat.
     */
    public function editAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $kategoris = Kategori::all();

        return view(
            'admin.alat.edit',
            compact('alat', 'kategoris')
        );
    }


    /**
     * Memperbarui data alat.
     */
    public function updateAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        // Upload gambar baru
        if ($request->hasFile('gambar')) {

            // Hapus gambar lama
            if (
                $alat->gambar &&
                file_exists(public_path($alat->gambar))
            ) {
                unlink(public_path($alat->gambar));
            }

            $file = $request->file('gambar');

            $filename = time() . '_' . $file->getClientOriginalName();

            $file->move(
                public_path('storage/alat'),
                $filename
            );

            $data['gambar'] = 'storage/alat/' . $filename;
        }

        $alat->update($data);

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                'Data alat berhasil diperbarui.'
            );
    }


    /**
     * Menghapus alat.
     */
    public function destroyAlat($id)
    {
        $alat = Alat::findOrFail($id);

        // Hapus gambar fisik
        if (
            $alat->gambar &&
            file_exists(public_path($alat->gambar))
        ) {
            unlink(public_path($alat->gambar));
        }

        $alat->delete();

        return redirect()
            ->route('admin.alat.index')
            ->with(
                'success',
                'Data alat berhasil dihapus.'
            );
    }


    // =========================================================
    // PENGEMBALIAN
    // =========================================================

    /**
     * Menampilkan daftar pengembalian.
     */
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');

        $pengembalians = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alat',
            'petugas'
        ])
            ->when($search, function ($query, $search) {
                $query->whereHas(
                    'peminjaman.user',
                    function ($q) use ($search) {
                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.pengembalian.index',
            compact('pengembalians', 'search')
        );
    }


    /**
     * Method lama untuk halaman create pengembalian.
     *
     * Tidak digunakan dalam alur baru.
     */
    public function createPengembalian()
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian sekarang diajukan oleh Petugas dan harus disetujui Admin.'
            );
    }

    /**
     * Method lama untuk menyimpan pengembalian langsung.
     *
     * Tidak digunakan agar Admin tidak dapat melewati
     * proses pengajuan Petugas.
     */
    public function storePengembalian(Request $request)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian harus diajukan oleh Petugas terlebih dahulu.'
            );
    }

    /**
     * Menampilkan detail pengajuan pengembalian
     * untuk diperiksa Admin.
     */
    public function editPengembalian($id)
    {
        $pengembalian = Pengembalian::with([
            'peminjaman.user',
            'peminjaman.detailPinjams.alat',
            'petugas'
        ])->findOrFail($id);

        // Hanya pengajuan yang masih menunggu
        // yang dapat diperiksa.
        if ($pengembalian->status_request !== 'menunggu') {
            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'error',
                    'Pengajuan pengembalian ini sudah diproses.'
                );
        }

        return view(
            'admin.pengembalian.edit',
            compact('pengembalian')
        );
    }


    /**
     * Menyetujui pengajuan pengembalian.
     *
     * Petugas menentukan:
     * - kondisi alat
     * - denda kerusakan
     *
     * Admin menentukan validasi akhir.
     *
     * Denda keterlambatan dihitung otomatis
     * sebesar Rp1.000 per hari.
     */
    public function setujuiPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with([
                'peminjaman.detailPinjams.alat'
            ])->findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception(
                    'Pengajuan pengembalian ini sudah diproses.'
                );
            }

            $peminjaman = $pengembalian->peminjaman;

            if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
                throw new \Exception(
                    'Peminjaman ini tidak dapat diproses sebagai pengembalian.'
                );
            }

            $tglRencana = Carbon::parse($peminjaman->tgl_kembali_plan);
            $tglKembali = Carbon::parse($pengembalian->tgl_kembali);

            if ($tglKembali->greaterThan(Carbon::today())) {
                throw new \Exception(
                    'Tanggal pengembalian tidak boleh melebihi hari ini.'
                );
            }

            $hariTerlambat = 0;

            if ($tglKembali->greaterThan($tglRencana)) {
                $hariTerlambat = $tglRencana->diffInDays($tglKembali);
            }

            // Denda keterlambatan berdasarkan tarif pada config/denda.php
                $dendaKeterlambatan =
                $hariTerlambat * config('denda.keterlambatan_per_hari');

            // Denda kerusakan sudah ditentukan oleh Petugas.
            $dendaKerusakan = (int) ($pengembalian->denda_kerusakan ?? 0);

            // Simpan masing-masing denda ke kolomnya sendiri.
            $pengembalian->update([
                'denda' => $dendaKeterlambatan,
                'status_request' => 'disetujui',
            ]);

            // Stok baru dikembalikan setelah Admin menyetujui.
            foreach ($peminjaman->detailPinjams as $detail) {
                $detail->alat->increment('stok', $detail->jumlah);
            }

            $peminjaman->update([
                'status' => 'dikembalikan',
            ]);

            DB::commit();

            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'success',
                    'Pengembalian berhasil disetujui, stok alat dikembalikan, dan total denda telah dihitung.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Pengembalian gagal disetujui: ' . $e->getMessage()
            );
        }
    }

    /**
     * Menolak pengajuan pengembalian.
     *
     * Data tidak dihapus agar Petugas dapat
     * memperbaiki dan mengajukan kembali.
     */
    public function tolakPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::findOrFail($id);

            if ($pengembalian->status_request !== 'menunggu') {
                throw new \Exception(
                    'Pengajuan pengembalian ini sudah diproses.'
                );
            }

            $pengembalian->update([
                'status_request' => 'ditolak',
                'denda' => 0,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'success',
                    'Pengajuan pengembalian ditolak dan dapat diperbaiki oleh Petugas.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Pengajuan gagal ditolak: ' . $e->getMessage()
            );
        }
    }

    /**
     * Method lama untuk update pengembalian.
     *
     * Tidak digunakan dalam alur approval baru.
     */
    public function updatePengembalian(Request $request, $id)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Data pengembalian diproses melalui pemeriksaan dan persetujuan Admin.'
            );
    }

    /**
     * Menghapus data pengembalian.
     *
     * Jika sudah disetujui, stok akan dikurangi kembali
     * karena sebelumnya sudah ditambahkan saat persetujuan.
     */
    public function destroyPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::with([
                'peminjaman.detailPinjams.alat'
            ])->findOrFail($id);

            $peminjaman = $pengembalian->peminjaman;

            // Hanya pengembalian yang sudah disetujui yang pernah
            // menambahkan stok, sehingga hanya itu yang perlu dibatalkan.
            if ($pengembalian->status_request === 'disetujui') {
                foreach ($peminjaman->detailPinjams as $detail) {
                    $alat = $detail->alat;

                    if ($alat->stok < $detail->jumlah) {
                        throw new \Exception(
                            "Stok alat '{$alat->nama_alat}' tidak mencukupi untuk menghapus data pengembalian."
                        );
                    }

                    $alat->decrement('stok', $detail->jumlah);
                }

                $peminjaman->update([
                    'status' => 'dipinjam',
                ]);
            }

            // Jika masih menunggu atau ditolak, stok tidak diubah.
            $pengembalian->delete();

            DB::commit();

            return redirect()
                ->route('admin.pengembalian.index')
                ->with(
                    'success',
                    'Data pengembalian berhasil dihapus.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /**
     * Method lama untuk memproses pengembalian langsung.
     *
     * Tidak digunakan agar pengembalian tidak dapat
     * melewati persetujuan Admin.
     */
    public function prosesPengembalian($id)
    {
        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'error',
                'Pengembalian harus melalui pengajuan Petugas dan persetujuan Admin.'
            );
    }

    // =========================================================
    // CRUD USER
    // =========================================================

    /**
     * Menampilkan daftar user.
     */
    public function indexUser(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.user.index',
            compact('users', 'search')
        );
    }


    /**
     * Menampilkan form tambah user.
     */
    public function createUser()
    {
        return view('admin.user.create');
    }


    /**
     * Menyimpan user baru.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,petugas,peminjam',
            'no_hp' => 'nullable|string|max:20',
            'foto_profile' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ];

        // Upload foto profil
        if ($request->hasFile('foto_profile')) {

            $file = $request->file('foto_profile');

            $filename =
                time() . '_' .
                $file->getClientOriginalName();

            $file->move(
                public_path('storage/profile'),
                $filename
            );

            $data['foto_profile'] =
                'storage/profile/' . $filename;
        }

        User::create($data);

        return redirect()
            ->route('admin.user.index')
            ->with(
                'success',
                'User berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit user.
     */
    public function editUser($id)
    {
        $user = User::findOrFail($id);

        return view(
            'admin.user.edit',
            compact('user')
        );
    }


    /**
     * Memperbarui data user.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' =>
                'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,petugas,peminjam',
            'no_hp' => 'nullable|string|max:20',
            'foto_profile' =>
                'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ];

        // Update password jika diisi
        if ($request->filled('password')) {
            $data['password'] =
                Hash::make($request->password);
        }

        // Upload foto baru
        if ($request->hasFile('foto_profile')) {

            if (
                $user->foto_profile &&
                file_exists(
                    public_path($user->foto_profile)
                )
            ) {
                unlink(
                    public_path($user->foto_profile)
                );
            }

            $file = $request->file('foto_profile');

            $filename =
                time() . '_' .
                $file->getClientOriginalName();

            $file->move(
                public_path('storage/profile'),
                $filename
            );

            $data['foto_profile'] =
                'storage/profile/' . $filename;
        }

        $user->update($data);

        return redirect()
            ->route('admin.user.index')
            ->with(
                'success',
                'Data user berhasil diperbarui.'
            );
    }


    /**
     * Menghapus user.
     */
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);

        // Hapus foto profil
        if (
            $user->foto_profile &&
            file_exists(
                public_path($user->foto_profile)
            )
        ) {
            unlink(
                public_path($user->foto_profile)
            );
        }

        $user->delete();

        return redirect()
            ->route('admin.user.index')
            ->with(
                'success',
                'User berhasil dihapus.'
            );
    }


    // =========================================================
    // CRUD KATEGORI
    // =========================================================

    /**
     * Menampilkan daftar kategori.
     */
    public function indexKategori(Request $request)
    {
        $search = $request->input('search');

        $kategoris = Kategori::when(
            $search,
            function ($query, $search) {
                $query->where(
                    'nama_kategori',
                    'like',
                    "%{$search}%"
                );
            }
        )
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view(
            'admin.kategori.index',
            compact('kategoris', 'search')
        );
    }


    /**
     * Menampilkan form tambah kategori.
     */
    public function createKategori()
    {
        return view('admin.kategori.create');
    }


    /**
     * Menyimpan kategori baru.
     */
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' =>
                'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil ditambahkan.'
            );
    }


    /**
     * Menampilkan form edit kategori.
     */
    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        return view(
            'admin.kategori.edit',
            compact('kategori')
        );
    }


    /**
     * Memperbarui kategori.
     */
    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' =>
                'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil diperbarui.'
            );
    }


    /**
     * Menghapus kategori.
     */
    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        $kategori->delete();

        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'success',
                'Kategori berhasil dihapus.'
            );
    }


    // =========================================================
    // CRUD PEMINJAMAN
    // =========================================================

    /**
     * Menampilkan daftar peminjaman.
     */
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');

        $peminjamans = Peminjaman::with([
            'user',
            'detailPinjams.alat'
        ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'status',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhereHas(
                            'user',
                            function ($user) use ($search) {
                                $user->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.peminjaman.index',
            compact('peminjamans', 'search')
        );
    }


    /**
     * Menampilkan form tambah peminjaman.
     */
    public function createPeminjaman()
    {
        $users = User::where(
            'role',
            'peminjam'
        )->get();

        $alats = Alat::where(
            'stok',
            '>',
            0
        )->get();

        return view(
            'admin.peminjaman.create',
            compact('users', 'alats')
        );
    }


    /**
     * Menyimpan peminjaman baru.
     */
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id' =>
                'required|exists:users,id',

            'tgl_pinjam' =>
                'required|date',

            'tgl_kembali_plan' =>
                'required|date|after_or_equal:tgl_pinjam',

            'alat_id' =>
                'required|array',

            'alat_id.*' =>
                'exists:alat,id',

            'jumlah' =>
                'required|array',

            'jumlah.*' =>
                'integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $peminjaman = Peminjaman::create([
                'user_id' => $request->user_id,
                'tgl_pinjam' => $request->tgl_pinjam,
                'tgl_kembali_plan' =>
                    $request->tgl_kembali_plan,
                'status' => 'diajukan',
            ]);


            foreach (
                $request->alat_id
                as $index => $alatId
            ) {

                $jumlahPinjam =
                    $request->jumlah[$index];

                $alat = Alat::findOrFail($alatId);

                // Validasi stok
                if (
                    $alat->stok <
                    $jumlahPinjam
                ) {
                    throw new \Exception(
                        "Stok alat '{$alat->nama_alat}' tidak mencukupi."
                    );
                }

                DetailPinjam::create([
                    'peminjaman_id' =>
                        $peminjaman->id,

                    'alat_id' =>
                        $alatId,

                    'jumlah' =>
                        $jumlahPinjam,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.peminjaman.index')
                ->with(
                    'success',
                    'Data peminjaman berhasil diajukan.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }


    /**
     * Mengubah status peminjaman.
     *
     * Pengembalian TIDAK dapat dilakukan langsung
     * dari sini. Pengembalian harus melalui:
     *
     * Petugas → Pengajuan → Admin → Persetujuan.
     */
    public function updateStatusPeminjaman(
        Request $request,
        $id
    ) {
        $peminjaman = Peminjaman::with([
            'detailPinjams.alat',
            'pengembalian'
        ])->findOrFail($id);

        $request->validate([
            'status' =>
                'required|in:diajukan,dipinjam,telat,dikembalikan',
        ]);

        DB::beginTransaction();

        try {

            $statusLama = $peminjaman->status;
            $statusBaru = $request->status;


            // =====================================================
            // DIAJUKAN → DIPINJAM
            // =====================================================

            if (
                $statusLama === 'diajukan' &&
                $statusBaru === 'dipinjam'
            ) {

                foreach (
                    $peminjaman->detailPinjams
                    as $detail
                ) {

                    $alat = $detail->alat;

                    if (
                        $alat->stok <
                        $detail->jumlah
                    ) {
                        throw new \Exception(
                            "Stok alat '{$alat->nama_alat}' tidak mencukupi untuk dipinjam."
                        );
                    }

                    $alat->decrement(
                        'stok',
                        $detail->jumlah
                    );
                }
            }


            // =====================================================
            // DIPINJAM / TELAT → DIKEMBALIKAN
            // =====================================================

            if (
                $statusBaru === 'dikembalikan' &&
                $statusLama !== 'dikembalikan'
            ) {
                throw new \Exception(
                    'Status dikembalikan hanya dapat diberikan setelah pengajuan pengembalian disetujui Admin.'
                );
            }


            // Update status biasa
            $peminjaman->update([
                'status' => $statusBaru
            ]);

            DB::commit();

            return redirect()
                ->route('admin.peminjaman.index')
                ->with(
                    'success',
                    'Status peminjaman berhasil diperbarui.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }


    /**
     * Menghapus data peminjaman.
     */
    public function destroyPeminjaman($id)
    {
        $peminjaman = Peminjaman::with([
            'detailPinjams.alat'
        ])->findOrFail($id);

        // Jika sedang dipinjam, kembalikan stok
        if (in_array($peminjaman->status, ['dipinjam', 'telat'])) {

            foreach (
                $peminjaman->detailPinjams
                as $detail
            ) {

                $detail->alat->increment(
                    'stok',
                    $detail->jumlah
                );
            }
        }

        $peminjaman->delete();

        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'success',
                'Data peminjaman berhasil dihapus.'
            );
    }


    // =========================================================
    // SEARCH USER
    // =========================================================

    /**
     * Pencarian user untuk form peminjaman.
     */
    public function searchUser(Request $request)
    {
        $keyword = $request->input('search');

        if (strlen($keyword) < 3) {
            return response()->json([]);
        }

        $users = User::where(
            'role',
            'peminjam'
        )
            ->where(function ($query) use ($keyword) {
                $query->where(
                    'name',
                    'like',
                    "%{$keyword}%"
                )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$keyword}%"
                    );
            })
            ->limit(10)
            ->get([
                'id',
                'name',
                'email'
            ]);

        return response()->json($users);
    }


    // =========================================================
    // SEARCH ALAT
    // =========================================================

    /**
     * Pencarian alat untuk form peminjaman.
     */
    public function searchAlat(Request $request)
    {
        $keyword = $request->input('search');

        if (strlen($keyword) < 3) {
            return response()->json([]);
        }

        $alats = Alat::where(
            'stok',
            '>',
            0
        )
            ->where(
                'nama_alat',
                'like',
                "%{$keyword}%"
            )
            ->limit(10)
            ->get([
                'id',
                'nama_alat',
                'stok'
            ]);

        return response()->json($alats);
    }
}
