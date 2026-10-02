<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /*
    |----------------------------------------------------------------------
    | Halaman profil milik user yang sedang login
    |----------------------------------------------------------------------
    | Setiap user (admin, petugas, peminjam) bisa mengubah data dan foto
    | profilnya sendiri tanpa harus minta admin. Perubahan di sini
    | menulis ke record `users` yang sama persis, jadi menu "Kelola
    | User" milik admin langsung memperlihatkan data terbaru tanpa
    | perlu sinkronisasi tambahan.
    |
    | Field role, is_aktif, dan password admin sengaja tidak bisa diubah
    | dari sini: itu urusan admin, bukan user sendiri.
    */

    /**
     * Tampilkan halaman detail + form profil user yang login.
     */
    public function edit()
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Simpan perubahan profil (data dasar + foto + password opsional).
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->profileData();

        // Upload foto profil baru, ganti yang lama kalau ada.
        if ($request->hasFile('foto_profile')) {
            $data['foto_profile'] = $this->simpanFoto($request, $user);
        }

        // Password baru hanya kalau user mengisinya; kalau kosong,
        // password lama dipertahankan apa adanya.
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        LogAktivitas::create([
            'user_id' => $user->id,
            'aktivitas' => "User {$user->name} ({$user->email}) memperbarui profilnya sendiri.",
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Simpan foto baru ke public/storage/profile dan hapus foto lama.
     */
    private function simpanFoto(Request $request, User $user): string
    {
        if ($user->foto_profile && file_exists(public_path($user->foto_profile))) {
            unlink(public_path($user->foto_profile));
        }

        $file = $request->file('foto_profile');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('storage/profile'), $filename);

        return 'storage/profile/' . $filename;
    }
}
