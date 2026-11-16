<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bahan extends Model
{
    protected $primaryKey = 'id_bahan';

    protected $fillable = ['kode', 'nama_bahan', 'satuan', 'jumlah_stok', 'batas_minimum', 'harga_terakhir', 'tanggal_harga',];

    protected function casts(): array{
        return [
            'jumlah_stok' => 'decimal:2',
            'batas_minimum' => 'integer',
            'harga_terakhir' => 'decimal:2',
            'tanggal_harga' => 'date',
        ];
    }

    public function reseps(){
        return $this->hasMany(Resep::class, 'id_bahan', 'id_bahan');
    }

    public function stokMasuks(){
        return $this->hasMany(StokMasuk::class, 'id_bahan', 'id_bahan');
    }
}