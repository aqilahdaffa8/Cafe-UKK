<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\Menu;
use App\Models\Resep;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResepController extends Controller
{
    // Tampilkan resep menu terpilih dan saring bahan yang belum digunakan untuk dropdown
    public function index(Request $request)
    {
        $menus = Menu::withCount('reseps')->orderBy('nama_menu')->get();

        // Menu yang dipilih lewat ?menu=ID, kalau kosong ambil menu pertama
        $menuDipilih = Menu::with('reseps.bahan')->find($request->query('menu'))
            ?? Menu::with('reseps.bahan')->orderBy('nama_menu')->first();

        // Ambil bahan yang belum dipakai di resep menu ini
        $bahanTersedia = $menuDipilih
            ? Bahan::whereNotIn('id_bahan', $menuDipilih->reseps->pluck('id_bahan'))
                ->orderBy('nama_bahan')->get()
            : collect();

        return view('manajer.resep', compact('menus', 'menuDipilih', 'bahanTersedia'));
    }

    // Tambahkan bahan ke resep menu (mencegah duplikasi bahan yang sama pada satu menu)
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_menu'  => ['required', 'exists:menus,id_menu'],
            'id_bahan' => [
                'required',
                'exists:bahans,id_bahan',
                // Cegah bahan ganda pada resep yang sama
                Rule::unique('reseps', 'id_bahan')->where('id_menu', $request->input('id_menu')),
            ],
            'takaran'  => ['required', 'numeric', 'gt:0', 'max:999999.99'],
        ], [
            'id_bahan.unique' => 'Bahan ini sudah ada di resep menu tersebut.',
        ]);

        Resep::create($data);

        return redirect()->route('manajer.resep.index', ['menu' => $data['id_menu']])
            ->with('sukses', 'Bahan ditambahkan ke resep.');
    }

    // Perbarui angka takaran bahan pada resep
    public function update(Request $request, Resep $resep)
    {
        $data = $request->validate([
            'takaran' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
        ]);

        $resep->update($data);

        return redirect()->route('manajer.resep.index', ['menu' => $resep->id_menu])
            ->with('sukses', 'Takaran diperbarui.');
    }

    // Hapus bahan dari komposisi resep menu
    public function destroy(Resep $resep)
    {
        $idMenu = $resep->id_menu;
        $resep->delete();

        return redirect()->route('manajer.resep.index', ['menu' => $idMenu])
            ->with('sukses', 'Bahan dihapus dari resep.');
    }
}