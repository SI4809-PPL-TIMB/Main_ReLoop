<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 8.1.2 · Batasi halaman sesuai peran akun.
 * Pemakaian: ->middleware('peran:admin') atau 'peran:admin,pengepul'.
 * Dipakai ulang oleh story lain (8.2, 8.3, 9.1, 11.1, 11.3).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        abort_unless($user->hasRole(...$peran), 403, 'Halaman ini tidak tersedia untuk akunmu.');

        return $next($request);
    }
}
