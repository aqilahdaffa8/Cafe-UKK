@php
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $label = ['minuman' => 'Minuman', 'makanan' => 'Makanan', 'lainnya' => 'Lainnya'];
    $tabs = ['semua' => 'Semua (' . $total . ')'];
    foreach ($label as $k => $v) { $tabs[$k] = $v . ' (' . ($perKategori[$k] ?? 0) . ')'; }
@endphp
<x-layouts.manajer title="Kelola Menu" subtitle="Atur katalog produk, harga jual, dan status ketersediaan pesanan menu.">

    <div class="mb-4 flex justify-end">
        <button type="button" onclick="document.getElementById('dlg-tambah').showModal()"
                class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">+ Tambah Menu Baru</button>
    </div>

    <div class="mb-6 grid grid-cols-4 gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total menu</p>
            <p class="mt-2 text-3xl font-bold">{{ $total }} <span class="text-sm font-normal text-gray-500">Item terdaftar</span></p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Menu aktif</p>
            <p class="mt-2 text-3xl font-bold">{{ $aktif }} <span class="text-sm font-normal text-gray-500">Bisa dipesan</span></p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Menu dinonaktifkan</p>
            <p class="mt-2 text-3xl font-bold">{{ $total - $aktif }} <span class="text-sm font-normal text-gray-500">Disembunyikan</span></p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori aktif</p>
            <p class="mt-2 text-3xl font-bold">{{ $kategoriAktif->count() }}
                <span class="text-sm font-normal text-gray-500">{{ $kategoriAktif->map(fn ($k) => $label[$k] ?? $k)->join(', ') ?: '-' }}</span>
            </p>
        </div>
    </div>

    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="kategori" value="{{ $kategori }}">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama menu atau kategori..."
                   class="flex-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
            <select name="status" onchange="this.form.submit()" class="rounded-md border border-gray-200 px-3 py-2 text-sm">
                <option value="semua" @selected($status === 'semua')>Status: Semua</option>
                <option value="aktif" @selected($status === 'aktif')>Hanya aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Hanya nonaktif</option>
            </select>
            <a href="{{ route('manajer.menu.index') }}" class="rounded-md border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">Reset</a>
        </form>
        <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
            @foreach ($tabs as $k => $teks)
                <a href="{{ route('manajer.menu.index', array_merge(request()->except(['page', 'kategori']), ['kategori' => $k])) }}"
                   class="rounded-full border px-4 py-1.5 text-sm {{ $kategori === $k ? 'border-black bg-black font-semibold text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                    {{ $teks }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">Gambar</th>
                <th class="px-4 py-3">Nama menu</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3 text-right">Harga jual</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($menus as $m)
                <tr class="{{ $m->aktif ? '' : 'bg-gray-50 text-gray-400' }}">
                    <td class="px-4 py-3">
                        @if ($m->gambar)
                            <img src="{{ asset('storage/' . $m->gambar) }}" alt="{{ $m->nama_menu }}"
                                 class="h-12 w-12 rounded-md border border-gray-200 object-cover">
                        @else
                            <span class="flex h-12 w-12 items-center justify-center rounded-md border border-gray-200 bg-gray-100 text-sm font-bold text-gray-500">
                                {{ strtoupper(mb_substr($m->nama_menu, 0, 1)) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-semibold {{ $m->aktif ? '' : 'line-through' }}">{{ $m->nama_menu }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded border border-gray-300 px-2 py-0.5 text-xs">{{ $label[$m->kategori] ?? $m->kategori }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium">{{ $rp($m->harga) }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded px-2 py-0.5 text-[10px] font-bold {{ $m->aktif ? 'bg-black text-white' : 'border border-dashed border-gray-400 text-gray-500' }}">
                            {{ $m->aktif ? 'AKTIF' : 'NONAKTIF' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="editMenu(this)"
                                    data-action="{{ route('manajer.menu.update', $m) }}"
                                    data-nama="{{ $m->nama_menu }}"
                                    data-kategori="{{ $m->kategori }}"
                                    data-harga="{{ $m->harga }}"
                                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs text-gray-900 hover:bg-gray-50">Edit</button>
                            <form method="POST" action="{{ route('manajer.menu.status', $m) }}">
                                @csrf @method('PATCH')
                                <button class="rounded-md border px-3 py-1.5 text-xs {{ $m->aktif ? 'border-gray-300 text-gray-600 hover:bg-gray-50' : 'border-black bg-white font-semibold text-gray-900 hover:bg-gray-100' }}">
                                    {{ $m->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada menu yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            <p>Menampilkan <b>{{ $menus->firstItem() ?? 0 }}-{{ $menus->lastItem() ?? 0 }}</b> dari <b>{{ $menus->total() }}</b> menu</p>
        </div>
    </div>
    <div class="mt-4">{{ $menus->links() }}</div>

    {{-- Modal tambah --}}
    <dialog id="dlg-tambah" class="m-auto w-full max-w-lg rounded-xl p-0 backdrop:bg-black/40">
        <form method="POST" action="{{ route('manajer.menu.store') }}" enctype="multipart/form-data" class="space-y-4 p-6">
            @csrf
            <input type="hidden" name="_form" value="tambah">
            <div>
                <h3 class="text-lg font-bold">Tambah menu baru</h3>
                <p class="text-sm text-gray-500">Tambahkan item sajian baru ke daftar menu kafe.</p>
            </div>
            @if ($errors->any() && old('_form') === 'tambah')
                <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
                </div>
            @endif
            <label class="block text-sm">Foto menu (opsional)
                <input type="file" name="gambar" accept="image/png,image/jpeg"
                       class="mt-1 w-full rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-4 text-sm">
                <span class="text-xs text-gray-500">Format PNG/JPG, maks 2MB</span>
            </label>
            <label class="block text-sm">Nama menu *
                <input type="text" name="nama_menu" value="{{ old('_form') === 'tambah' ? old('nama_menu') : '' }}" required
                       placeholder="Kopi Susu Aren" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="block text-sm">Kategori menu *
                <select name="kategori" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">Pilih kategori sajian</option>
                    @foreach ($label as $k => $v)
                        <option value="{{ $k }}" @selected(old('_form') === 'tambah' && old('kategori') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Harga jual (Rp) *
                <input type="number" min="0" step="1" name="harga" value="{{ old('_form') === 'tambah' ? old('harga') : '' }}" required
                       placeholder="25000" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <fieldset class="space-y-2 text-sm">
                <legend class="mb-1">Status awal menu</legend>
                <label class="flex items-start gap-3 rounded-md border border-gray-300 p-3">
                    <input type="radio" name="status" value="aktif" class="mt-1" @checked(old('status', 'aktif') === 'aktif')>
                    <span><b>Aktif</b><br><span class="text-gray-500">Tampil dan bisa dipesan.</span></span>
                </label>
                <label class="flex items-start gap-3 rounded-md border border-gray-300 p-3">
                    <input type="radio" name="status" value="nonaktif" class="mt-1" @checked(old('status') === 'nonaktif')>
                    <span><b>Nonaktif</b><br><span class="text-gray-500">Disimpan sebagai draf, disembunyikan dari pemesanan.</span></span>
                </label>
            </fieldset>
            <p class="rounded-md bg-gray-100 px-3 py-2 text-xs text-gray-600">Resep dan takaran bahan diatur di menu Kelola Resep setelah menu dibuat.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Batal</button>
                <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Simpan menu</button>
            </div>
        </form>
    </dialog>

    {{-- Modal edit --}}
    <dialog id="dlg-edit" class="m-auto w-full max-w-lg rounded-xl p-0 backdrop:bg-black/40">
        <form method="POST" enctype="multipart/form-data" class="space-y-4 p-6">
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="_action" id="edit-action">
            <h3 class="text-lg font-bold">Ubah menu</h3>
            @if ($errors->any() && old('_form') === 'edit')
                <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
                </div>
            @endif
            <label class="block text-sm">Ganti foto (opsional)
                <input type="file" name="gambar" accept="image/png,image/jpeg"
                       class="mt-1 w-full rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-4 text-sm">
                <span class="text-xs text-gray-500">Kosongkan kalau foto tidak diganti. PNG/JPG, maks 2MB.</span>
            </label>
            <label class="block text-sm">Nama menu *
                <input type="text" name="nama_menu" id="edit-nama" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="block text-sm">Kategori menu *
                <select name="kategori" id="edit-kategori" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach ($label as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Harga jual (Rp) *
                <input type="number" min="0" step="1" name="harga" id="edit-harga" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Batal</button>
                <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Simpan</button>
            </div>
        </form>
    </dialog>

    <script>
        function bukaEdit(action, nama, kategori, harga) {
            const d = document.getElementById('dlg-edit');
            d.querySelector('form').action = action;
            document.getElementById('edit-action').value = action;
            document.getElementById('edit-nama').value = nama;
            document.getElementById('edit-kategori').value = kategori;
            document.getElementById('edit-harga').value = harga;
            d.showModal();
        }
        function editMenu(btn) {
            bukaEdit(btn.dataset.action, btn.dataset.nama, btn.dataset.kategori, btn.dataset.harga);
        }
        @if ($errors->any() && old('_form') === 'tambah')
            document.getElementById('dlg-tambah').showModal();
        @endif
        @if ($errors->any() && old('_form') === 'edit')
            bukaEdit(@json(old('_action')), @json(old('nama_menu')), @json(old('kategori')), @json(old('harga')));
        @endif
    </script>
</x-layouts.manajer>