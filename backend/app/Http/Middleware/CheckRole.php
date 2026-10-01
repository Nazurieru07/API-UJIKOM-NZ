<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        /*
        | Otorisasi server-side. Mencegah user biasa mengakses halaman
        | admin hanya dengan mengetik URL-nya secara langsung.
        | $roles diisi dari definisi route, misal: ->middleware('role:admin').
        */
        if (!auth()->check() || !in_array(auth()->user()->role, $roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
