<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        //Mengambil data pengguna yang sedang login saat itu
        $user = $request->user();

        if ($user //memastikan ada pengguna yang sedang login
            && $user->wajib_ganti_password //mengecek apakah pengguna wajib mengganti password (true)
            && ! $request->routeIs('password.*', 'logout', 'api.password.*', 'api.logout') //mencegah infinite loop,wajib ganti password diabaikan jika pengguna sedang mengakses halaman untuk ganti password atau sedang ingin logout
            ) {

            //jika permintaan berasal dari api dan apk jalankan ini
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda wajib mengganti password terlebih dahulu.',
                    'code' => 'PASSWORD_CHANGE_REQUIRED',
                ], 403);
            }

            //jika permintaan berasal dari web
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
