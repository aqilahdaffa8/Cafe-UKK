<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resep extends Model
{
    protected $primaryKey = 'id_resep';

    protected $fillable = ['id_menu', 'id_bahan', 'takaran'];

    protected function casts(): array{
        return ['takaran' => 'decimal:2'];
    }

    public function menu(){
        return $this->belongsTo(Menu::class, 'id_menu', 'id_menu');
    }

    public function bahan(){
        return $this->belongsTo(Bahan::class, 'id_bahan', 'id_bahan');
    }
}
