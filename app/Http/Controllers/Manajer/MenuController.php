<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    private const KATEGORI = ['minuman', 'makanan', 'lainnya'];

    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q'));
        $status   = $request->query('status', 'semua');
        $kategori = $request->query('kategori', 'semua');

        $menus = Menu::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                ->where('nama_menu', 'like', "%{$q}%")
                ->orWhere('kategori', 'like', "%{$q}%")))
            ->when($status === 'aktif', fn ($w) => $w->where('aktif', true))
            ->when($status === 'nonaktif', fn ($w) => $w->where('aktif', false))
            ->when(in_array($kategori, self::KATEGORI, true), fn ($w) => $w->where('kategori', $kategori))
            ->orderBy('nama_menu')
            ->paginate(8)
            ->withQueryString();

        $total       = Menu::count();
        $aktif       = Menu::where('aktif', true)->count();
        $perKategori = Menu::selectRaw('kategori, count(*) as jml')->groupBy('kategori')->pluck('jml', 'kategori');
        $kategoriAktif = Menu::where('aktif', true)->distinct()->pluck('kategori');

        return view('manajer.menu', compact(
            'menus', 'total', 'aktif', 'perKategori', 'kategoriAktif', 'q', 'status', 'kategori'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan() + [
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        Menu::create([
            'kategori'  => $data['kategori'],
            'nama_menu' => $data['nama_menu'],
            'harga'     => $data['harga'],
            'aktif'     => $data['status'] === 'aktif',
            'gambar'    => $request->hasFile('gambar')
                ? $request->file('gambar')->store('menu', 'public')
                : null,
        ]);

        return redirect()->route('manajer.menu.index')->with('sukses', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate($this->aturan($menu));

        if ($request->hasFile('gambar')) {
            if ($menu->gambar) {
                Storage::disk('public')->delete($menu->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('menu', 'public');
        } else {
            unset($data['gambar']);
        }

        $menu->update($data);

        return back()->with('sukses', "Menu {$menu->nama_menu} diperbarui.");
    }

    public function toggleStatus(Menu $menu)
    {
        $menu->update(['aktif' => ! $menu->aktif]);

        return back()->with('sukses', $menu->aktif
            ? "Menu {$menu->nama_menu} diaktifkan."
            : "Menu {$menu->nama_menu} dinonaktifkan.");
    }

    private function aturan(?Menu $menu = null): array
    {
        return [
            'nama_menu' => ['required', 'string', 'max:255',
                Rule::unique('menus', 'nama_menu')->ignore($menu?->id_menu, 'id_menu')],
            'kategori'  => ['required', Rule::in(self::KATEGORI)],
            'harga'     => ['required', 'integer', 'min:0'],
            'gambar'    => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }
}