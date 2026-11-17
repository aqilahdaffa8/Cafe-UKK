<?php

use App\Http\Controllers\Auth\WebLoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Manajer\BahanController;
use App\Http\Controllers\Manajer\StokMasukController;
use App\Http\Controllers\Manajer\MenuController;
use App\Http\Controllers\Manajer\ResepController;
use App\Http\Controllers\Manajer\LaporanController;
use App\Http\Controllers\Kasir\PesananController;


// Halaman awal: arahkan sesuai kondisi login
Route::get('/', function () {
    $user = Auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect($user->isManajer() ? '/manajer' : '/kasir');
});

// Hanya untuk yang BELUM login
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebLoginController::class, 'show'])->name('login');
    Route::post('/login', [WebLoginController::class, 'store'])->middleware('throttle:5,1');
});

// Hanya untuk yang SUDAH login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebLoginController::class, 'destroy'])->name('logout');
    Route::get('/ganti-password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/ganti-password', [PasswordController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'password.changed', 'role:manajer'])
    ->prefix('manajer')
    ->name('manajer.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('manajer.menu.index'))->name('beranda');
        Route::get('/bahan', [BahanController::class, 'index'])->name('bahan.index');
        Route::post('/bahan', [BahanController::class, 'store'])->name('bahan.store');
        Route::patch('/bahan/{bahan}/batas', [BahanController::class, 'updateBatas'])->name('bahan.batas');

        Route::get('/stok-masuk', [StokMasukController::class, 'index'])->name('stok-masuk.index');
        Route::post('/stok-masuk', [StokMasukController::class, 'store'])->name('stok-masuk.store');

        Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
        Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
        Route::put('/menu/{menu}', [MenuController::class, 'update'])->name('menu.update');
        Route::patch('/menu/{menu}/status', [MenuController::class, 'toggleStatus'])->name('menu.status');

        Route::get('/resep', [ResepController::class, 'index'])->name('resep.index');
        Route::post('/resep', [ResepController::class, 'store'])->name('resep.store');
        Route::put('/resep/{resep}', [ResepController::class, 'update'])->name('resep.update');
        Route::delete('/resep/{resep}', [ResepController::class, 'destroy'])->name('resep.destroy');

        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    });

Route::middleware(['auth', 'password.changed', 'role:kasir'])
    ->prefix('kasir')
    ->name('kasir.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('kasir.pesanan.create'))->name('beranda');
        Route::get('/pesanan/baru', [PesananController::class, 'create'])->name('pesanan.create');
        Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
        Route::post('/pesanan', [PesananController::class, 'store'])->name('pesanan.store');
    });