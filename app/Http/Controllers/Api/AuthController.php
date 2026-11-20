<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //regitrasi pelanggan baru
    public function register(RegisterRequest $request)
    {
        //mengambil data yang sudah tervalidasi
        $validated = $request->validated();

        //membuat user baru dengan peran pelanggan
        $user = User::create([
            'nama'      => $validated['nama'] ?? null,
            'no_telp'   => $validated['no_telp'],
            'email'     => $validated['email'] ?? null,
            'password'  => Hash::make($validated['password']),
            'peran'     => 'pelanggan',
        ]);

        //terbitkan token Sanctum
        $token = $user->createToken('flutter-apk')->plainTextToken;

        //mengembalikan respons JSON
        return response()->json([
            'sukses'  => true,
            'pesan'   => 'Registrasi berhasil.',
            'data'    => [
                'user'  => [
                    'id_pengguna' => $user->id_pengguna,
                    'nama'        => $user->nama,
                    'no_telp'     => $user->no_telp,
                    'email'       => $user->email,
                    'peran'       => $user->peran,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    //login pelanggan
    public function login(LoginRequest $request)
    {
        //mencari user berdasarkan nomer yang sudah dinormalisasi
        $user = User::where('no_telp', $request->no_telp)->first();

        //periksa apakah user ada, password benar, dan berstatus pelanggan
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'sukses' => false,
                'pesan'  => 'Nomor telepon atau password salah.',
            ], 401);
        }

        //memastikan akun bukan kasir/manajer yang login lewat aplikasi pelanggan
        if ($user->peran !== 'pelanggan') {
            return response()->json([
                'sukses' => false,
                'pesan'  => 'Aplikasi ini khusus untuk pelanggan kafe.',
            ], 403);
        }

        //mengapus token lama perangkat ini jika ada, lalu buat token baru
        $token = $user->createToken('flutter-apk')->plainTextToken;

        //mengembalikan data profil dan token
        return response()->json([
            'sukses'  => true,
            'pesan'   => 'Login berhasil.',
            'data'    => [
                'user'  => [
                    'id_pengguna'          => $user->id_pengguna,
                    'nama'                 => $user->nama,
                    'no_telp'              => $user->no_telp,
                    'email'                => $user->email,
                    'peran'                => $user->peran,
                    'wajib_ganti_password' => (bool) $user->wajib_ganti_password,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    //mengambil profil pengguna yang sedang login
    public function profil(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'sukses' => true,
            'data'   => [
                'id_pengguna'          => $user->id_pengguna,
                'nama'                 => $user->nama,
                'no_telp'              => $user->no_telp,
                'email'                => $user->email,
                'peran'                => $user->peran,
                'wajib_ganti_password' => (bool) $user->wajib_ganti_password,
            ],
        ]);
    }

    //mengubah password pengguna yang sedang login
    public function gantiPassword(Request $request)
    {
        $validated = $request->validate([
            'password_lama' => ['required', 'string'],
            'password'      => ['required', 'string', 'min:8', 'confirmed', 'different:password_lama'],
        ], [
            'password_lama.required' => 'Password lama / sementara wajib diisi.',
            'password.required'      => 'Password baru wajib diisi.',
            'password.min'           => 'Password baru minimal 8 karakter.',
            'password.confirmed'     => 'Konfirmasi password baru tidak cocok.',
            'password.different'     => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['password_lama'], $user->password)) {
            return response()->json([
                'sukses' => false,
                'pesan'  => 'Password lama / sementara salah.',
                'errors' => ['password_lama' => ['Password lama / sementara salah.']],
            ], 422);
        }

        $user->update([
            'password'             => $validated['password'],
            'wajib_ganti_password' => false,
        ]);

        return response()->json([
            'sukses' => true,
            'pesan'  => 'Password berhasil diperbarui.',
        ]);
    }

    //logout pengguna yang sedang login
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan untuk request ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'sukses' => true,
            'pesan'  => 'Berhasil logout. Token telah dihapus.',
        ]);
    }
}