<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catat halaman-halaman web yang dibuka user ke session.
 *
 * Ini pendukung tombol "Kembali" di halaman profil. Laravel bawaan
 * cuma menyimpan SATU url sebelumnya (`session._previous.url`), yang
 * tertimpa setiap request GET -- termasuk request ke halaman profil
 * sendiri. Kalau halaman profil direfresh, url "sebelumnya" berubah
 * jadi /profile dan tombol Kembali menunjuk ke halaman yang sedang
 * dibuka. Middleware ini menyimpan DAFTAR halaman sehingga selalu bisa
 * diambil halaman terakhir yang benar-benar berbeda.
 */
class SimpanRiwayatHalaman
{
    public function handle(Request $request, Closure $next): Response
    {
        // Catat SEBELUM $next(). Middleware ini global, jadi posisinya
        // di luar grup 'web': StartSession (yang menyimpan session ke
        // database) berjalan di dalam $next() dan sudah men-flush
        // session sebelum kode kita selesai. Menulis session setelah
        // $next() berarti data hilang diam-diam -- tidak error, hanya
        // tidak tersimpan.
        if ($request->isMethod('GET')
            && ! $request->ajax()
            && ! $request->prefetch()
            && ! $request->isPrecognitive()
            // Jangan catat halaman login: tombol "Kembali" yang
            // mengarah ke /login akan terlihat seperti logout, dan
            // middleware ini global jadi tidak tahu soal grup 'guest'.
            && $request->route()?->getName() !== 'login') {
            $this->catat($request->fullUrl());
        }

        return $next($request);
    }

    /**
     * Tambah satu url ke daftar riwayat, buang duplikat terakhir, dan
     * batasi 10 entri supaya session tidak membengkak.
     */
    private function catat(string $url): void
    {
        $riwayat = session()->get('riwayat_halaman', []);

        // Duplikat berturut-turut (reload) tidak menambah info baru.
        if (end($riwayat) === $url) {
            return;
        }

        $riwayat[] = $url;

        if (count($riwayat) > 10) {
            array_shift($riwayat);
        }

        session()->put('riwayat_halaman', $riwayat);
    }

    /**
     * Ambil url halaman terakhir yang berbeda dari url yang diberikan.
     *
     * @param string $urlSekarang url halaman yang sedang dibuka
     * @return string|null null kalau tidak ada riwayat yang cocok
     */
    public static function urlSebelumnya(string $urlSekarang): ?string
    {
        $riwayat = session()->get('riwayat_halaman', []);

        for ($i = count($riwayat) - 1; $i >= 0; $i--) {
            if ($riwayat[$i] !== $urlSekarang) {
                return $riwayat[$i];
            }
        }

        return null;
    }
}
