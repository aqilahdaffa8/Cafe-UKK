<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    // batas margin (%) => label, diperiksa dari yang tertinggi
    private const AMBANG_MARGIN = [70 => 'Sangat Tinggi', 60 => 'Optimal', 55 => 'Baik'];

    public function index(Request $request)
    {
        [$dari, $sampai, $periode] = $this->rentang($request);

        $menuHpp = Menu::with('reseps.bahan')->get()->keyBy('id_menu');

        // 1. Penjualan per menu (hanya pesanan selesai)
        $queryPenjualan = DB::table('detail_pesanans')
            ->join('pesanans', 'pesanans.id_pesanan', '=', 'detail_pesanans.id_pesanan')
            ->join('menus', 'menus.id_menu', '=', 'detail_pesanans.id_menu')
            ->where('pesanans.status', 'selesai')
            ->whereBetween('pesanans.created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->groupBy('menus.id_menu', 'menus.nama_menu', 'menus.kategori')
            ->selectRaw('menus.id_menu, menus.nama_menu, menus.kategori,
                SUM(detail_pesanans.jumlah_menu) as porsi,
                SUM(detail_pesanans.jumlah_menu * detail_pesanans.harga_satuan) as pendapatan')
            ->orderByDesc('porsi')
            ->orderBy('menus.nama_menu');

        // Semua baris: untuk total dan HPP terpakai (bukan untuk ditampilkan)
        $semuaPenjualan = (clone $queryPenjualan)->get();

        $totalPorsi = (int) $semuaPenjualan->sum('porsi');
        $totalOmset = (int) $semuaPenjualan->sum('pendapatan');

        $hppTerpakai = $semuaPenjualan->sum(fn ($p) => $p->porsi * ($menuHpp[$p->id_menu]->hpp ?? 0));
        $rataMargin  = $totalOmset > 0 ? ($totalOmset - $hppTerpakai) / $totalOmset * 100 : 0;
        $persenHpp   = $totalOmset > 0 ? $hppTerpakai / $totalOmset * 100 : 0;

        // Baris yang ditampilkan: 10 per halaman
        $penjualan = $queryPenjualan->paginate(10, ['*'], 'hal_penjualan')->withQueryString();

        foreach ($penjualan as $p) {
            $p->harga_rata = $p->porsi > 0 ? $p->pendapatan / $p->porsi : 0;
            $p->kontribusi = $totalOmset > 0 ? $p->pendapatan / $totalOmset * 100 : 0;
        }

        // 2. Bahan menipis
        $menipis = Bahan::whereColumn('jumlah_stok', '<=', 'batas_minimum')
            ->orderByRaw('(jumlah_stok - batas_minimum)')
            ->paginate(10, ['*'], 'hal_menipis')
            ->withQueryString();

        // 3. Analisis margin: diurutkan dari margin % tertinggi (HPP dihitung di SQL hanya untuk urutan)
        $hppSql = DB::table('reseps')
            ->join('bahans', 'bahans.id_bahan', '=', 'reseps.id_bahan')
            ->whereColumn('reseps.id_menu', 'menus.id_menu')
            ->selectRaw('COALESCE(SUM(reseps.takaran * bahans.harga_terakhir), 0)');

        $margin = Menu::query()
            ->has('reseps')
            ->with('reseps.bahan')
            ->select('menus.*')
            ->selectSub($hppSql, 'hpp_sql')
            ->orderByRaw('(menus.harga - hpp_sql) / NULLIF(menus.harga, 0) DESC')
            ->orderBy('menus.nama_menu')
            ->paginate(10, ['*'], 'hal_margin')
            ->withQueryString();

        $labelMargin = fn (float $persen) => collect(self::AMBANG_MARGIN)
            ->first(fn ($label, $batas) => $persen >= $batas, 'Standar');

        return view('manajer.laporan', compact(
            'dari', 'sampai', 'periode',
            'penjualan', 'totalPorsi', 'totalOmset',
            'hppTerpakai', 'rataMargin', 'persenHpp',
            'menipis', 'margin', 'labelMargin'
        ));
    }

    private function rentang(Request $request): array
    {
        $periode = $request->query('periode', 'bulan_ini');
        $hariIni = now()->startOfDay();

        return match ($periode) {
            'hari_ini'   => [$hariIni, $hariIni->copy(), 'hari_ini'],
            '7_hari'     => [$hariIni->copy()->subDays(6), $hariIni->copy(), '7_hari'],
            'bulan_lalu' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth()->startOfDay(),
                'bulan_lalu',
            ],
            'custom'     => $this->rentangKustom($request),
            default      => [now()->startOfMonth(), $hariIni, 'bulan_ini'],
        };
    }

    private function rentangKustom(Request $request): array
    {
        try {
            $dari   = Carbon::createFromFormat('Y-m-d', $request->query('dari'))->startOfDay();
            $sampai = Carbon::createFromFormat('Y-m-d', $request->query('sampai'))->startOfDay();
        } catch (\Throwable) {
            return [now()->startOfMonth(), now()->startOfDay(), 'bulan_ini'];
        }

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai, 'custom'];
    }
}