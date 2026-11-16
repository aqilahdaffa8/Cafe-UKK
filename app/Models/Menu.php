<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $primaryKey = 'id_menu';

    protected $fillable = ['kategori', 'nama_menu', 'harga', 'gambar', 'aktif'];

    protected function casts(): array{
        return [
            'harga' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function reseps(){
        return $this->hasMany(Resep::class, 'id_menu', 'id_menu');
    }

    public function bahans(){
        return $this->belongsToMany(Bahan::class, 'reseps', 'id_menu', 'id_bahan')
            ->withPivot('takaran');
    }

    public function detailPesanans(){
        return $this->hasMany(DetailPesanan::class, 'id_menu', 'id_menu');
    }

    public function punyaResep(): bool{
        return $this->reseps()->exists();
    }

    // HPP = jumlah (takaran x harga terakhir bahan)
    // Pakai with('reseps.bahan') saat mengambil menu agar tidak query berulang
    protected function hpp(): Attribute{
        return Attribute::get(fn () => $this->reseps->sum(
            fn ($r) => (float) $r->takaran * (float) ($r->bahan?->harga_terakhir ?? 0)
        ));
    }

    // Margin dalam rupiah = harga jual - HPP
    protected function margin(): Attribute{
        return Attribute::get(fn () => $this->harga - $this->hpp);
    }

    // Margin persen = margin / harga jual x 100
    protected function marginPersen(): Attribute{
        return Attribute::get(fn () => $this->harga > 0
            ? $this->margin / $this->harga * 100
            : 0);
    }
}