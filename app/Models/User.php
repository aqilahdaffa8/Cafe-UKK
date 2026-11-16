<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens ,HasFactory, Notifiable;
    
    protected $primaryKey = 'id_pengguna';

    protected $fillable = ['nama', 'no_telp', 'email', 'password', 'peran', 'wajib_ganti_password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array{
        return [
            'password' => 'hashed',
            'wajib_ganti_password' => 'boolean'
        ];
    }


    public function pesanans (){
        return $this->hasMany(Pesanan::class, 'id_pengguna', 'id_pengguna');  //Artinya satu user bisa memiliki banyak pesanan
    }

    public function stokMasuks(){
        return $this->hasMany(StokMasuk::class, 'id_pengguna', 'id_pengguna'); //artinya satu user bisa mencatat banyak stok masuk
    }


    //sistem shortcut untuk ngecek hak akses pengguna
    public function isPelanggan():bool {
        return $this->peran === 'pelanggan';
    }

    public function isKasir():bool {
        return $this->peran === 'kasir';
    }

    public function isManajer():bool {
        return $this->peran === 'manajer';
    }

    public function isStaf(): bool {
        return in_array($this->peran, ['manajer', 'kasir'], true);
    }


    public static function normalisasiTelepon(?string $nomor): ?string{
        //Kalo tidak ada yang perlu dinormalisasi langsung menegembalikan null.
        if ($nomor === null){
            return null;
        }

        $nomor = preg_replace('/[^\d+]/', '', $nomor); //hapus semua karakter kecuali angka dan +

        // mengganti awalan +62 dan 62 dengan 0
        if (str_starts_with($nomor, '+62')){
            $nomor = '0' . substr($nomor, 3); 
        }
        elseif (str_starts_with($nomor, '62')){
            $nomor = '0' . substr($nomor, 2);
        }

        return preg_replace('/\D/', '', $nomor);
    }
}
