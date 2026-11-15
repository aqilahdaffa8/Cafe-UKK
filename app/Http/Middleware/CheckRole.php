<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function  handle(Request $request, Closure $next, string ...$roles): Response{

        // Pengecekan Status Login
        $user = $request->user();

        if (! $user) { // Mengecek apakah user sudah login
            abort(401, 'Belum login');
        }

        if (! in_array($user->peran, $roles, true)) { //Mengecek apakah peran user ada
            abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}
