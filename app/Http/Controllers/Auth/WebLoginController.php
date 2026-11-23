<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StafLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WebLoginController extends Controller
{   
    // Tampilkan form login web untuk staf
    public function show()
    {
        return view('auth.login');
    }
    
    // Proses login staf web dan arahkan sesuai peran
    public function store(StafLoginRequest $request)
    {
        $user = User::where('email', $request->email)->first(); //mengambil data user berdasarkan email yang diisi

        //validasi apakah user ada, apakah user staf, dan apakah password cocok
        if (! $user || ! $user->isStaf() || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        Auth::login($user); //mencatat pengguna sedang aktif atau login
        $request->session()->regenerate();

        return redirect()->intended($user->isManajer() ? '/manajer' : '/kasir'); //mengarahkan pengguna ke dashboard sesuai perannya
    }

    // Logout sesi web staf
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}