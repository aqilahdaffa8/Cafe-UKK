<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // Tampilkan daftar menu aktif beserta status ketersediaannya
    public function index(Request $request)
    {
        $kategori = $request->query('kategori');

        // Ambil menu yang aktif dan memiliki resep
        $menus = Menu::with(['reseps.bahan'])
            ->where('aktif', true)
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderBy('nama_menu')
            ->get()
            ->map(function ($m) {
                // Cek apakah stok bahan cukup untuk minimal 1 porsi
                $stokCukup = true;
                foreach ($m->reseps as $resep) {
                    if (! $resep->bahan || $resep->bahan->jumlah_stok < $resep->takaran) {
                        $stokCukup = false;
                        break;
                    }
                }

                return [
                    'id_menu'      => $m->id_menu,
                    'kode'         => $m->kode,
                    'nama_menu'    => $m->nama_menu,
                    'kategori'     => $m->kategori,
                    'harga'        => $m->harga,
                    'gambar_url'   => $m->gambar ? asset('storage/' . $m->gambar) : null,
                    'tersedia'     => $stokCukup,
                ];
            });

        return response()->json([
            'sukses' => true,
            'data'   => $menus,
        ]);
    }
}