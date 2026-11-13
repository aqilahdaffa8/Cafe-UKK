<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bahans', function (Blueprint $table) {
            $table->id('id_bahan');
            $table->string('nama_bahan')->unique();
            $table->enum('satuan', ['g', 'ml', 'pcs']);
            $table->integer('jumlah_stok');
            $table->integer('batas_minimum');
            $table->integer('harga_terakhir');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahans');
    }
};
