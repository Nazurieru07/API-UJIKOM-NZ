<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CekUserAktif
{
    /*
    |----------------------------------------------------------------------
    | User nonaktif: putus aksesnya di setiap request
    |----------------------------------------------------------------------
    | Cek is_aktif hanya di proses login menutup pintu masuk baru. User
    | yang SUDAH login saat dinonaktifkan masih memegang session/token
    | valid dan bisa beraktivitas sampai kadaluarsa.
    |
    | Dipasang di grup web (routes/web.php) DAN api (routes/api.php).
    | Tanpa yang API, token Sanctum milik user nonaktif tetap bisa dipakai
    | tanpa pernah menyentuh halaman web.
    |
    | Pakai $request->user(), bukan Auth::user(). Auth::user() selalu
    | memakai default guard (web); request API di-resolve guard sanctum,
    | sehingga Auth::user() di sini null dan pengecekan diam-diam
    | dilewati.
    */

    public function handle(Request $request, Closure $next): Response
    {
        // null = guest. Route yang memakai 'auth'/'auth:sanctum' yang
        // menolak guest, bukan middleware ini.
        $user = $request->user();

        if ($user && ! $user->isAktif()) {
            // Request API stateless (Bearer token): tidak ada session untuk
            // diinvalidate, cukup tolak dengan 403.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda telah dinonaktifkan. Hubungi admin.',
                ], 403);
            }

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi admin.');
        }

        return $next($request);
    }
}
