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

    // Tampilkan daftar antrean dan riwayat pesanan dengan filter status dan meja
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

    // Tampilkan form kasir membuat pesanan baru (hanya menu aktif yang memiliki resep)
    public function create()
    {
        $menus = Menu::where('aktif', true)->has('reseps')->orderBy('nama_menu')->get();

        return view('kasir.pesanan', [
            'menus'         => $menus,
            'perKategori'   => $menus->countBy('kategori'),
            'jumlahMeja'    => PesananService::JUMLAH_MEJA,
            'servicePersen' => PesananService::SERVICE_PERSEN,
            'pajakPersen'   => PesananService::PAJAK_PERSEN,
        ]);
    }

    // Validasi dan buat pesanan baru dari kasir, potong stok bahan secara atomik
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

    // Ubah status pesanan menjadi Diproses
    public function proses(Pesanan $pesanan, PesananService $service)
    {
        return $this->jalankan(
            fn () => $service->ubahStatus($pesanan, Pesanan::STATUS_DIPROSES),
            "Pesanan {$pesanan->kode} sedang diproses."
        );
    }

    // Ubah status pesanan menjadi Selesai
    public function selesai(Pesanan $pesanan, PesananService $service)
    {
        return $this->jalankan(
            fn () => $service->ubahStatus($pesanan, Pesanan::STATUS_SELESAI),
            "Pesanan {$pesanan->kode} selesai."
        );
    }

    // Batalkan pesanan status Baru dan kembalikan stok bahan sesuai resep
    public function batal(Pesanan $pesanan, PesananService $service)
    {
        return $this->jalankan(
            fn () => $service->batalkan($pesanan),
            "Pesanan {$pesanan->kode} dibatalkan dan stok bahan dikembalikan."
        );
    }

    // Tampilkan struk cetak kasir (hanya untuk pesanan selesai)
    public function struk(Pesanan $pesanan)
    {
        abort_unless($pesanan->status === Pesanan::STATUS_SELESAI, 404);

        $pesanan->load('pembuat', 'detailPesanans.menu');

        return view('kasir.struk', [
            'pesanan'       => $pesanan,
            'servicePersen' => PesananService::SERVICE_PERSEN,
            'pajakPersen'   => PesananService::PAJAK_PERSEN,
        ]);
    }

    // Helper eksekusi aksi status pesanan dengan penanganan error PesananDitolak
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