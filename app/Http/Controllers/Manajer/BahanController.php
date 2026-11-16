<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use Illuminate\Http\Request;

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
}