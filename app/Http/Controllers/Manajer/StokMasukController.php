<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\StokMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokMasukController extends Controller
{
    public function index()
    {
        $bahans = Bahan::orderBy('nama_bahan')->get();

        $riwayat = StokMasuk::with(['bahan', 'pengguna'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id_stok_masuk')
            ->paginate(10);

        return view('manajer.stok-masuk', compact('bahans', 'riwayat'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_bahan' => ['required', 'exists:bahans,id_bahan'],
            'jumlah'   => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'tanggal'  => ['required', 'date', 'before_or_equal:today'],
        ]);

        $nama = DB::transaction(function () use ($data) {
            $bahan = Bahan::lockForUpdate()->findOrFail($data['id_bahan']);

            StokMasuk::create([
                'id_bahan'    => $bahan->id_bahan,
                'id_pengguna' => auth()->user()->getKey(),
                'jumlah'      => $data['jumlah'],
                'tanggal'     => $data['tanggal'],
            ]);

            $bahan->jumlah_stok = $bahan->jumlah_stok + $data['jumlah'];
            $bahan->save();

            return $bahan->nama_bahan;
        });

        return redirect()->route('manajer.stok-masuk.index')
            ->with('sukses', "Stok masuk {$nama} berhasil dicatat.");
    }
}