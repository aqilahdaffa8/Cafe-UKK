<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use Illuminate\Http\Request;
use App\Services\SupplierService;


class BahanController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q'));
        $status = $request->query('status', 'semua');

        $bahans = Bahan::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                ->where('nama_bahan', 'like', "%{$q}%")
                ->orWhere('kode', 'like', "%{$q}%")))
            ->when($status === 'menipis', fn ($w) => $w->whereColumn('jumlah_stok', '<=', 'batas_minimum'))
            ->when($status === 'aman', fn ($w) => $w->whereColumn('jumlah_stok', '>', 'batas_minimum'))
            ->orderBy('nama_bahan')
            ->paginate(10)
            ->withQueryString();

        $total   = Bahan::count();
        $menipis = Bahan::whereColumn('jumlah_stok', '<=', 'batas_minimum')->count();

        return view('manajer.bahan', compact('bahans', 'total', 'menipis', 'q', 'status'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_bahan'    => ['required', 'string', 'max:255', 'unique:bahans,nama_bahan'],
            'satuan'        => ['required', 'in:g,ml,pcs'],
            'jumlah_stok'   => ['required', 'numeric', 'min:0'],
            'batas_minimum' => ['required', 'numeric', 'min:0'],
            'harga'         => ['required', 'numeric', 'min:0'],
        ]);

        // Kode otomatis: BHN-001, BHN-002, dan seterusnya
        $terakhir = Bahan::where('kode', 'like', 'BHN-%')->pluck('kode')
            ->map(fn ($k) => (int) substr($k, 4))
            ->max() ?? 0;

        Bahan::create([
            'kode'          => sprintf('BHN-%03d', $terakhir + 1),
            'nama_bahan'    => $data['nama_bahan'],
            'satuan'        => $data['satuan'],
            'jumlah_stok'   => $data['jumlah_stok'],
            'batas_minimum' => $data['batas_minimum'],
            'harga_terakhir' => $data['harga'],
            'tanggal_harga' => now()->toDateString(),
        ]);

        return redirect()->route('manajer.bahan.index')->with('sukses', 'Bahan berhasil ditambahkan.');
    }

    public function updateBatas(Request $request, Bahan $bahan)
    {
        $data = $request->validate([
            'batas_minimum' => ['required', 'numeric', 'min:0'],
        ]);

        $bahan->update($data);

        return back()->with('sukses', "Batas minimum {$bahan->nama_bahan} diperbarui.");
    }

    public function destroy(Bahan $bahan)
    {
        // 1. Cek apakah bahan masih digunakan dalam resep menu
        if ($bahan->reseps()->exists()) {
            $namaMenu = $bahan->reseps()
                ->with('menu')
                ->get()
                ->pluck('menu.nama_menu')
                ->filter()
                ->unique()
                ->implode(', ');

            return back()->with('error', "Bahan '{$bahan->nama_bahan}' tidak dapat dihapus karena masih digunakan dalam resep menu: {$namaMenu}. Hapus atau ubah resep menu tersebut terlebih dahulu.");
        }

        // 2. Cek apakah bahan memiliki riwayat pencatatan stok masuk
        if ($bahan->stokMasuks()->exists()) {
            return back()->with('error', "Bahan '{$bahan->nama_bahan}' tidak dapat dihapus karena memiliki riwayat pencatatan transaksi stok masuk.");
        }

        // 3. Jika aman (belum pernah dipakai di resep & belum ada stok masuk), hapus bahan
        $nama = $bahan->nama_bahan;
        $bahan->delete();

        return back()->with('sukses', "Bahan '{$nama}' berhasil dihapus.");
    }

    public function syncHargaSupplier(SupplierService $supplierService)
    {
        $hasil = $supplierService->ambilHargaBahan();
        // JIKA LAYANAN GAGAL / TIMEOUT / API KEY SALAH (PERILAKU FALLBACK)
        if (! $hasil['sukses']) {
            return back()->with('peringatan', $hasil['pesan']);
        }
        
        // JIKA BERHASIL: Update harga bahan yang cocok berdasarkan kode
        $tanggalPembaruan = $hasil['diperbarui'];
        $jumlahDiupdate   = 0;

        foreach ($hasil['data'] as $item) {
            $kode  = $item['kode'] ?? null;
            $harga = $item['harga_per_satuan'] ?? null;
            if ($kode && $harga !== null) {
                $bahan = Bahan::where('kode', $kode)->first();
                if ($bahan) {
                    $bahan->update([
                        'harga_terakhir' => $harga,
                        'tanggal_harga'  => $tanggalPembaruan,
                    ]);
                    $jumlahDiupdate++;
                }
            }
        }
        return back()->with('sukses', "Berhasil memperbarui {$jumlahDiupdate} harga bahan dari supplier (Per tanggal: {$tanggalPembaruan}).");
    }
}