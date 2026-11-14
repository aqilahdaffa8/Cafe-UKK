<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPesanan extends Model
{
    protected $primaryKey = 'id_detail_pesanan';

    protected $fillable = ['id_pesanan', 'id_menu', 'jumlah_menu', 'harga_satuan'];

    protected function casts(): array{
        return [
            'jumlah_menu' => 'integer',
            'harga_satuan' => 'integer',
        ];
    }

    public function pesanan(){
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan');
    }

    public function menu(){
        return $this->belongsTo(Menu::class, 'id_menu', 'id_menu');
    }
}
