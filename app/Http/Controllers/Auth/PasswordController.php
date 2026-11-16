<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function edit(){
        return view('auth.ganti_password');
    }

    public function update(Request $request){
        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:password_lama'],
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        //Mengambil data pengguna yang sedang aktif saat itu
        $user = $request->user();

        //Memeriksa apakah password lama yang diketik pengguna cocok dengan password terenkripsi yang ada di database
        if (! Hash::check($data['password_lama'], $user->password)) {
            throw ValidationException::withMessages([
                'password_lama' => 'Password lama salah.',
            ]);
        }

        //Mengubah password pengguna di database dengan password baru
        $user->update([
            'password' => $data['password'],
            'wajib_ganti_password' => false,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password berhasil diganti.']);
        }

        return redirect('/')->with('status', 'Password berhasil diganti.');
    }
}
