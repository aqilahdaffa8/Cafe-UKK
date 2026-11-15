<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class RegisterRequest extends FormRequest
{
    // mengizinkan semua orang untuk mengakses request ini
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void{
        $this->merge([
            'no_telp' => User::normalisasiTelepon($this->input('no_telp')) //menimpa data nomor dengan yang sudah dinormalisasi
        ]);
    }

    //daftar aturan per kolom
    public function rules(): array{
        return [
            'no_telp' => ['required', 'regex:/^08\d{8,11}$/', 'unique:users,no_telp'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nama' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    public function messages(): array{
        return [
            'no_telp.required' => 'Nomor telepon wajib diisi.',
            'no_telp.regex' => 'Nomor telepon harus berawalan 08 atau +62 dengan panjang 10 sampai 13 digit.',
            'no_telp.unique' => 'Nomor telepon sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
        ];
    }
}
