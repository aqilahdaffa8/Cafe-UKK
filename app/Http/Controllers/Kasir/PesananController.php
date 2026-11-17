<?php

namespace App\Http\Controllers\Kasir;

use App\Exceptions\PesananDitolak;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PesananController extends Controller
{
    public function index(){
        return view('kasir.riwayat');
    }

    public function create(){
        // Hanya menu aktif yang punya resep yang bisa dipesan
        $menus = Menu::where('aktif', true)->has('reseps')->orderBy('nama_menu')->get();

        return view('kasir.pesanan', [
            'menus'         => $menus,
            'perKategori'   => $menus->countBy('kategori'),
            'jumlahMeja'    => PesananService::JUMLAH_MEJA,
            'servicePersen' => PesananService::SERVICE_PERSEN,
            'pajakPersen'   => PesananService::PAJAK_PERSEN,
        ]);
    }

    public function store(Request $request, PesananService $service){
        $data = $request->validate([
            'nomor_meja'        => ['required', 'integer', 'between:1,' . PesananService::JUMLAH_MEJA],
            'metode_pembayaran' => ['required', Rule::in(Pesanan::METODE_PEMBAYARAN)],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.id_menu'   => ['required', 'integer'],
            'items.*.jumlah'    => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        try {
            $pesanan = $service->buat(
                $request->user(),
                (int) $data['nomor_meja'],
                $data['metode_pembayaran'],
                $data['items']
            );
        } catch (PesananDitolak $e) {
            return response()->json([
                'pesan'      => $e->getMessage(),
                'kekurangan' => $e->kekurangan,
            ], 422);
        }

        session()->flash('sukses', sprintf(
            'Pesanan %s untuk meja %d berhasil dibuat. Total Rp %s.',
            $pesanan->kode,
            $pesanan->nomor_meja,
            number_format($pesanan->total_harga, 0, ',', '.')
        ));

        return response()->json(['ok' => true, 'redirect' => route('kasir.pesanan.index')]);
    }
}