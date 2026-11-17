<?php

namespace App\Exceptions;

use Exception;

class PesananDitolak extends Exception
{
    // $kekurangan diisi kalau penolakan karena stok bahan tidak cukup
    public function __construct(string $pesan, public readonly array $kekurangan = []){
        parent::__construct($pesan);
    }
}
