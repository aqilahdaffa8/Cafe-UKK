@php
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $rp    = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp
<x-layouts.manajer title="Kelola Resep" subtitle="Atur takaran bahan baku per menu. HPP dan margin dihitung otomatis.">

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- KIRI: daftar menu (tinggi mengikuti kolom kanan) --}}
        <div class="relative col-span-1 min-h-[24rem]">
            <div class="absolute inset-0 flex flex-col rounded-lg border border-gray-200 bg-white">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="text-sm font-bold">Pilih Menu</h3>
                    <span class="rounded-full border border-gray-300 px-2 py-0.5 text-xs">{{ $menus->count() }} Menu</span>
                </div>
                <div class="shrink-0 border-b border-gray-200 p-3">
                    <input type="text" id="cariMenu" placeholder="Cari nama menu..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto p-3" id="daftarMenu">
                    @foreach ($menus as $m)
                        @php $aktif = $menuDipilih && $menuDipilih->id_menu === $m->id_menu; @endphp
                        <a href="{{ route('manajer.resep.index', ['menu' => $m->id_menu]) }}"
                        @if ($aktif) id="menuAktif" @endif
                        data-nama="{{ strtolower($m->nama_menu) }}"
                        class="item-menu block rounded-md border px-3 py-2 text-sm {{ $aktif ? 'border-2 border-black' : 'border-gray-200 hover:bg-gray-50' }}">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-semibold">{{ $m->nama_menu }}</p>
                                @if ($m->reseps_count > 0)
                                    <span class="shrink-0 rounded bg-black px-2 py-0.5 text-[11px] text-white">Ada Resep</span>
                                @else
                                    <span class="shrink-0 rounded border border-gray-300 px-2 py-0.5 text-[11px] text-gray-500">Belum ada</span>
                                @endif
                            </div>
                            <p class="text-xs capitalize text-gray-500">{{ $m->kategori }}</p>
                            <p class="mt-1 text-xs text-gray-600">Harga jual {{ $rp($m->harga) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- KANAN: rincian resep --}}
        <div class="col-span-2 space-y-6">
            @if (! $menuDipilih)
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">
                    Belum ada menu.
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <h3 class="text-lg font-bold">{{ $menuDipilih->nama_menu }}</h3>
                    <p class="text-sm capitalize text-gray-500">
                        Kategori: {{ $menuDipilih->kategori }} &middot; Harga jual: {{ $rp($menuDipilih->harga) }}
                    </p>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Bahan</th>
                            <th class="px-4 py-3">Takaran per porsi</th>
                            <th class="px-4 py-3 text-right">Harga satuan</th>
                            <th class="px-4 py-3 text-right">Subtotal HPP</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @forelse ($menuDipilih->reseps as $r)
                            <tr>
                                <td class="px-4 py-3 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ $r->bahan->nama_bahan }}</p>
                                    <p class="text-xs text-gray-500">{{ $r->bahan->kode }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('manajer.resep.update', $r->id_resep) }}"
                                          class="flex items-center gap-2">
                                        @csrf @method('PUT')
                                        <input type="number" step="0.01" min="0.01" name="takaran"
                                               value="{{ $r->takaran }}" required
                                               class="w-24 rounded-md border border-gray-300 px-2 py-1">
                                        <span class="text-gray-500">{{ $r->bahan->satuan }}</span>
                                        <button class="rounded-md border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50">Simpan</button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    {{ $rp($r->bahan->harga_terakhir) }} / {{ $r->bahan->satuan }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold">
                                    {{ $rp($r->takaran * $r->bahan->harga_terakhir) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('manajer.resep.destroy', $r->id_resep) }}"
                                          onsubmit="return confirm('Hapus {{ $r->bahan->nama_bahan }} dari resep ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Menu ini belum punya resep.</td></tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{-- Form tambah bahan --}}
                    <form method="POST" action="{{ route('manajer.resep.store') }}"
                        class="flex flex-wrap items-end gap-3 border-t border-gray-200 bg-gray-50 px-4 py-4">
                        @csrf
                        <input type="hidden" name="id_menu" value="{{ $menuDipilih->id_menu }}">

                        <label class="flex min-w-56 flex-1 flex-col gap-1 text-sm">
                            <span>Tambah bahan</span>
                            <select name="id_bahan" required
                                    class="h-10 w-full rounded-md border border-gray-300 bg-white px-3">
                                <option value="">Pilih bahan...</option>
                                @foreach ($bahanTersedia as $b)
                                    <option value="{{ $b->id_bahan }}" @selected(old('id_bahan') == $b->id_bahan)>
                                        {{ $b->nama_bahan }} ({{ $b->satuan }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex flex-col gap-1 text-sm">
                            <span>Takaran</span>
                            <input type="number" step="0.01" min="0.01" name="takaran" value="{{ old('takaran') }}" required
                                class="h-10 w-28 rounded-md border border-gray-300 bg-white px-3">
                        </label>

                        <div class="flex flex-col gap-1">
                            <span class="invisible text-sm" aria-hidden="true">Aksi</span>
                            <button type="submit"
                                    class="h-10 whitespace-nowrap rounded-md bg-black px-4 text-sm font-medium text-white hover:bg-gray-800">
                                + Tambah
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Ringkasan HPP & margin --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <h3 class="mb-4 text-sm font-bold">Ringkasan HPP & Margin</h3>
                    @if ($menuDipilih->reseps->isEmpty())
                        <p class="text-sm text-gray-500">Belum ada resep, jadi HPP dan margin belum bisa dihitung.</p>
                    @else
                        <div class="grid grid-cols-4 gap-4">
                            <div class="rounded-md border border-gray-200 p-4">
                                <p class="text-xs text-gray-500">Total HPP</p>
                                <p class="mt-1 text-lg font-bold">{{ $rp($menuDipilih->hpp) }}</p>
                                <p class="text-xs text-gray-500">{{ $menuDipilih->reseps->count() }} bahan</p>
                            </div>
                            <div class="rounded-md border border-gray-200 p-4">
                                <p class="text-xs text-gray-500">Harga jual</p>
                                <p class="mt-1 text-lg font-bold">{{ $rp($menuDipilih->harga) }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 p-4">
                                <p class="text-xs text-gray-500">Margin (Rp)</p>
                                <p class="mt-1 text-lg font-bold">{{ $rp($menuDipilih->margin) }}</p>
                                <p class="text-xs text-gray-500">Laba kotor per porsi</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-100 p-4">
                                <p class="text-xs text-gray-500">Margin (%)</p>
                                <p class="mt-1 text-lg font-bold">{{ number_format($menuDipilih->margin_persen, 1, ',', '.') }}%</p>
                            </div>
                        </div>
                        <p class="mt-4 text-xs text-gray-500">
                            Margin (Rp) = Harga jual - HPP. Margin (%) = Margin / Harga jual x 100.
                            HPP memakai harga terakhir tiap bahan.
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <script>
        // Cari menu di sisi browser, tanpa reload
        document.getElementById('cariMenu').addEventListener('input', function () {
            const kata = this.value.toLowerCase().trim();
            document.querySelectorAll('.item-menu').forEach(function (el) {
                el.style.display = el.dataset.nama.includes(kata) ? '' : 'none';
            });
        });
    </script>
</x-layouts.manajer>