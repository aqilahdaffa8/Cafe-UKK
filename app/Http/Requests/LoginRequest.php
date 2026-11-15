<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void{
        $this->merge([
            'no_telp' => User::normalisasiTelepon($this->input('no_telp')) //menimpa data nomor dengan yang sudah dinormalisasi
        ]);
    }

    public function rules(): array{
        return [
            'no_telp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
