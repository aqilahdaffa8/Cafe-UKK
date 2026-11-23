<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    // Cari data pelanggan berdasarkan nomor telepon
    public function cari(Request $request)
    {
        $request->validate(['no_telp' => ['required', 'string', 'max:20']]);

        // 0812..., +62812..., dan 62812... dianggap nomor yang sama
        $nomor = User::normalisasiTelepon($request->input('no_telp'));

        // Hanya akun pelanggan: akun staf tidak bisa direset lewat jalur ini
        $pelanggan = $nomor
            ? User::where('peran', 'pelanggan')->where('no_telp', $nomor)->first()
            : null;

        if (! $pelanggan) {
            return response()->json(['pesan' => 'Pelanggan dengan nomor itu tidak ditemukan.'], 404);
        }

        return response()->json([
            'nama'    => $pelanggan->nama,
            'no_telp' => $pelanggan->no_telp,
            'url'     => route('kasir.pelanggan.reset', $pelanggan),
        ]);
    }

    // Reset password pelanggan ke sementara dan cabut token login
    public function reset(Request $request, User $pelanggan)
    {
        abort_unless($pelanggan->isPelanggan(), 403);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ]);

        // Cast 'hashed' di model yang melakukan hashing, jadi jangan di-hash dua kali
        $pelanggan->update([
            'password'             => $data['password'],
            'wajib_ganti_password' => true,
        ]);

        // Token lama (aplikasi pelanggan yang masih login) dicabut
        $pelanggan->tokens()->delete();

        return response()->json(['ok' => true]);
    }
}