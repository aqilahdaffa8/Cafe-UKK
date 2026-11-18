<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PesananDitolak;
use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    protected PesananService $pesananService;

    //menghubungkan controller dengan PesananService
    public function __construct(PesananService $pesananService){
        $this->pesananService = $pesananService;
    }

    //mengambil riwayat pesanan pelanggan yang sedang login
    public function index(Request $request){
        $user = $request->user();

        //mengurutkan dari yang terbaru
        $pesanans = Pesanan::with('detailPesanans.menu')
            ->where('id_pengguna', $user->id_pengguna)
            ->latest('id_pesanan')
            ->get();

        return response()->json([
            'sukses' => true,
            'data'   => $pesanans,
        ]);
    }

    // Membuat pesanan baru
    public function store(Request $request)
    {
        // memvalidasi input
        $validated = $request->validate([
            'nomor_meja'        => ['required', 'integer', 'min:1', 'max:' . PesananService::JUMLAH_MEJA],
            'metode_pembayaran' => ['required', 'in:tunai,qris'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.id_menu'   => ['required', 'integer', 'exists:menus,id_menu'],
            'items.*.jumlah'    => ['required', 'integer', 'min:1'],
        ]);

        try {
            //buat pesanan dan potong stok bahan lewat service
            $pesanan = $this->pesananService->buat(
                $request->user(),
                $validated['nomor_meja'],
                $validated['metode_pembayaran'],
                $validated['items']
            );

            return response()->json([
                'sukses' => true,
                'pesan'  => 'Pesanan berhasil dibuat.',
                'data'   => $pesanan,
            ], 201);

        } catch (PesananDitolak $e) {
            // Jika stok kurang atau aturan dilanggar, kirim pesan error
            return response()->json([
                'sukses' => false,
                'pesan'  => $e->getMessage(),
                'detail' => $e->kekurangan ?? null,
            ], 422);
        }
    }

    //melihat detail 1 pesanan (untuk struk digital)
    public function show(Request $request, $id)
    {
        //cari pesanan milik user ini
        $pesanan = Pesanan::with('detailPesanans.menu')
            ->where('id_pengguna', $request->user()->id_pengguna)
            ->find($id);

        if (! $pesanan) {
            return response()->json([
                'sukses' => false,
                'pesan'  => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'sukses' => true,
            'data'   => $pesanan,
        ]);
    }

    //membatalkan pesanan (hanya jika statusnya baru)
    public function batal(Request $request, $id)
    {
        // Cari pesanan milik user ini
        $pesanan = Pesanan::where('id_pengguna', $request->user()->id_pengguna)->find($id);

        if (! $pesanan) {
            return response()->json([
                'sukses' => false,
                'pesan'  => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        try {
            //batalkan pesanan dan kembalikan stok bahan lewat service
            $pesananBatal = $this->pesananService->batalkan($pesanan);

            return response()->json([
                'sukses' => true,
                'pesan'  => 'Pesanan berhasil dibatalkan dan stok telah dikembalikan.',
                'data'   => $pesananBatal,
            ]);

        } catch (PesananDitolak $e) {
            // Jika pesanan sudah diproses, tolak pembatalan
            return response()->json([
                'sukses' => false,
                'pesan'  => $e->getMessage(),
            ], 422);
        }
    }
}