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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Cek apakah user sudah login
        // (Meskipun biasanya middleware 'auth' sudah menjaga ini, double check lebih aman)
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Cek apakah role user yang sedang login ada di dalam daftar role yang diizinkan?
        // $roles akan berisi array, misal: ['admin'] atau ['admin', 'operator']
        if (in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
