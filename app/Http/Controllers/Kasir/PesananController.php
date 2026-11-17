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
    private const STATUS = [
        Pesanan::STATUS_BARU,
        Pesanan::STATUS_DIPROSES,
        Pesanan::STATUS_SELESAI,
        Pesanan::STATUS_DIBATALKAN,
    ];

    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q'));
        $status = $request->query('status', 'semua');
        $meja   = (string) $request->query('meja', 'semua');

        $pesanans = Pesanan::query()
            ->with(['pembuat', 'detailPesanans.menu'])
            ->when(in_array($status, self::STATUS, true), fn ($w) => $w->where('status', $status))
            ->when(ctype_digit($meja), fn ($w) => $w->where('nomor_meja', (int) $meja))
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    // Angka di ujung teks dianggap nomor pesanan atau nomor meja: "#ORD-2026-089" atau "89"
                    if (preg_match('/(\d+)\s*$/', $q, $m)) {
                        $x->orWhere('id_pesanan', (int) $m[1])->orWhere('nomor_meja', (int) $m[1]);
                    }
                    $x->orWhereHas('detailPesanans.menu', fn ($d) => $d->where('nama_menu', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id_pesanan')
            ->paginate(10)
            ->withQueryString();

        $hitung      = Pesanan::selectRaw('status, count(*) as jml')->groupBy('status')->pluck('jml', 'status');
        $jumlahSemua = (int) $hitung->sum();

        $hariIni         = Pesanan::where('status', Pesanan::STATUS_SELESAI)->whereDate('created_at', today());
        $selesaiHariIni  = (clone $hariIni)->count();
        $diterimaHariIni = (int) (clone $hariIni)->sum('total_harga');

        return view('kasir.riwayat', [
            'pesanans'        => $pesanans,
            'hitung'          => $hitung,
            'jumlahSemua'     => $jumlahSemua,
            'status'          => $status,
            'q'               => $q,
            'meja'            => $meja,
            'jumlahMeja'      => PesananService::JUMLAH_MEJA,
            'selesaiHariIni'  => $selesaiHariIni,
            'diterimaHariIni' => $diterimaHariIni,
        ]);
    }

    public function create()
    {
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

    public function store(Request $request, PesananService $service)
    {
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

    public function proses(Pesanan $pesanan, PesananService $service)
    {
        return $this->jalankan(
            fn () => $service->ubahStatus($pesanan, Pesanan::STATUS_DIPROSES),
            "Pesanan {$pesanan->kode} sedang diproses."
        );
    }

    public function selesai(Pesanan $pesanan, PesananService $service)
    {
        return $this->jalankan(
            fn () => $service->ubahStatus($pesanan, Pesanan::STATUS_SELESAI),
            "Pesanan {$pesanan->kode} selesai."
        );
    }

    public function batal(Pesanan $pesanan, PesananService $service)
    {
        return $this->jalankan(
            fn () => $service->batalkan($pesanan),
            "Pesanan {$pesanan->kode} dibatalkan dan stok bahan dikembalikan."
        );
    }

    public function struk(Pesanan $pesanan)
    {
        // Sesuai UI: struk hanya untuk pesanan yang sudah selesai
        abort_unless($pesanan->status === Pesanan::STATUS_SELESAI, 404);

        $pesanan->load('pembuat', 'detailPesanans.menu');

        return view('kasir.struk', [
            'pesanan'       => $pesanan,
            'servicePersen' => PesananService::SERVICE_PERSEN,
            'pajakPersen'   => PesananService::PAJAK_PERSEN,
        ]);
    }

    // Jalankan aksi status; kalau ditolak service, kembali dengan pesan galat
    private function jalankan(callable $aksi, string $sukses)
    {
        try {
            $aksi();
        } catch (PesananDitolak $e) {
            return back()->withErrors(['aksi' => $e->getMessage()]);
        }

        return back()->with('sukses', $sukses);
    }
}