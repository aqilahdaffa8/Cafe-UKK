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
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id('id_pesanan');
            $table->foreignId('id_pengguna')->constrained('users', 'id_pengguna');
            $table->integer('nomor_meja');
            $table->enum('metode_pembayaran', ['tunai', 'qris']);
            $table->enum('status', ['baru', 'diproses', 'selesai', 'dibatalkan'])->default('baru');
            $table->integer('subtotal');
            $table->integer('service');
            $table->integer('pajak');
            $table->integer('total_harga');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};
