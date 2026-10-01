<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Menampilkan Form Login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Memproses Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        /*
        | Anti brute-force: maksimal 5 percobaan gagal per 60 detik.
        | Key kombinasi email+IP agar pembatasan tidak berlaku
        | ke user lain di jaringan yang sama (IP publik bersama).
        */
        $key = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $seconds . ' detik.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            $user = Auth::user();

            /*
            | Akun nonaktif tidak boleh masuk. Auth::attempt sudah
            | memvalidasi password, jadi pengecekan is_aktif di sini
            | adalah satu-satunya pintu: tanpa ini email+password yang
            | benar tetap membuka akun yang sudah dibekukan admin.
            |
            | Pesan langsung menyebut "dinonaktifkan": user sekolah
            | bukan target serangan enumeration, tapi perlu tahu kenapa
            | mereka tidak bisa masuk supaya mencari admin, bukan
            | mengulang-ulang password.
            */
            if (! $user->isAktif()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                RateLimiter::hit($key, 60);

                return back()->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan. Hubungi admin untuk mengaktifkan kembali.',
                ])->onlyInput('email');
            }

            // Landing page berbeda per role, sesuai matriks akses.
            // Role yang tidak terdaftar langsung logout (jangan biarkan
            // user dengan role aneh masuk ke menu manapun).
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role === 'petugas') {
                return redirect()->route('petugas.peminjaman.index');
            } elseif ($user->role === 'peminjam') {
                return redirect()->route('peminjam.katalog');
            }

            Auth::logout();

            return redirect()
                ->route('login')
                ->with('error', 'Role tidak dikenali.');
        }

        // Catat percobaan gagal, expired dalam 60 detik.
        // Hit hanya dinaikkan saat login GAGAL, tidak saat berhasil.
        RateLimiter::hit($key, 60);

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // Proses Logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}