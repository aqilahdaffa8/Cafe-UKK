<?php

namespace App\Models;

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
}
