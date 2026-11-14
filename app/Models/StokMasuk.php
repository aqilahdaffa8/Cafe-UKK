<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokMasuk extends Model
{
    protected $primaryKey = 'id_stok_masuk';

    protected $fillable = ['id_bahan', 'id_pengguna', 'tanggal', 'jumlah'];

    protected function casts(): array{
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
        ];
    }

    public function bahan(){
        return $this->belongsTo(Bahan::class, 'id_bahan', 'id_bahan');
    }

    public function pencatat(){
        return $this->belongsTo(User::class, 'id_pengguna', 'id_pengguna');
    }
}
