<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Pesanan extends Model
{
    protected $primaryKey = 'id_pesanan';

    public const STATUS_BARU = 'baru';
    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const METODE_PEMBAYARAN = ['tunai', 'qris'];

    protected $fillable = [
        'id_pengguna', 'nomor_meja', 'metode_pembayaran', 'status',
        'subtotal', 'service', 'pajak', 'total_harga',
    ];

    protected function casts(): array{
        return [
            'nomor_meja' => 'integer',
            'subtotal' => 'integer',
            'service' => 'integer',
            'pajak' => 'integer',
            'total_harga' => 'integer',
        ];
    }

    protected function kode(): Attribute{
        return Attribute::get(fn () => sprintf(
            '#ORD-%s-%03d',
            ($this->created_at ?? now())->format('Y'),
            $this->id_pesanan
        ));
    }

    public function pembuat(){
        return $this->belongsTo(User::class, 'id_pengguna', 'id_pengguna');
    }

    public function detailPesanans(){
        return $this->hasMany(DetailPesanan::class, 'id_pesanan', 'id_pesanan');
    }
}
