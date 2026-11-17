@php
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp
<x-layouts.manajer title="Kelola Bahan & Stok" subtitle="Pantau ketersediaan bahan baku, tetapkan batas minimum, dan lacak harga pembelian terkini.">

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    <div class="mb-4 flex justify-end">
        <button type="button" onclick="document.getElementById('dlg-tambah').showModal()"
                class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">+ Tambah Bahan</button>
    </div>

    <div class="mb-6 grid grid-cols-3 gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total jenis bahan</p>
            <p class="mt-2 text-3xl font-bold">{{ $total }} <span class="text-sm font-normal text-gray-500">Bahan</span></p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stok aman</p>
            <p class="mt-2 text-3xl font-bold">{{ $total - $menipis }} <span class="text-sm font-normal text-gray-500">Bahan</span></p>
        </div>
        <div class="rounded-lg border-2 border-black bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stok menipis</p>
            <p class="mt-2 text-3xl font-bold">{{ $menipis }} <span class="text-sm font-normal text-gray-500">Bahan (&le; batas min)</span></p>
        </div>
    </div>

    <form method="GET" class="mb-4 flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-4">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama bahan atau kode..."
               class="flex-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
        <select name="status" onchange="this.form.submit()" class="rounded-md border border-gray-200 px-3 py-2 text-sm">
            <option value="semua" @selected($status === 'semua')>Semua status</option>
            <option value="menipis" @selected($status === 'menipis')>Hanya menipis</option>
            <option value="aman" @selected($status === 'aman')>Hanya aman</option>
        </select>
        <a href="{{ route('manajer.bahan.index') }}" class="rounded-md border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">Reset</a>
    </form>

    <div id="tabel-bahan" class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">No</th>
                <th class="px-4 py-3">Nama bahan &amp; kode</th>
                <th class="px-4 py-3">Satuan</th>
                <th class="px-4 py-3 text-right">Stok saat ini</th>
                <th class="px-4 py-3 text-right">Batas min.</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Harga terakhir &amp; tanggal</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($bahans as $b)
                @php $menipisRow = (float) $b->jumlah_stok <= (float) $b->batas_minimum; @endphp
                <tr>
                    <td class="px-4 py-3 text-gray-500">{{ $bahans->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3">
                        <p class="font-semibold">{{ $b->nama_bahan }}</p>
                        <p class="text-xs text-gray-500">{{ $b->kode }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $b->satuan }}</td>
                    <td class="px-4 py-3 text-right font-bold">{{ $angka($b->jumlah_stok) }} {{ $b->satuan }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ $angka($b->batas_minimum) }} {{ $b->satuan }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded px-2 py-0.5 text-[10px] font-bold {{ $menipisRow ? 'bg-black text-white' : 'border border-gray-300 bg-gray-100 text-gray-700' }}">
                            {{ $menipisRow ? 'MENIPIS' : 'AMAN' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <p>Rp {{ $angka($b->harga_terakhir) }} / {{ $b->satuan }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $b->tanggal_harga ? \Illuminate\Support\Carbon::parse($b->tanggal_harga)->translatedFormat('d M Y') : '-' }}
                        </p>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button type="button" onclick="aturBatas(this)"
                                data-action="{{ route('manajer.bahan.batas', $b) }}"
                                data-nama="{{ $b->nama_bahan }}"
                                data-batas="{{ $b->batas_minimum + 0 }}"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">Atur batas</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada bahan yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $bahans->fragment('tabel-bahan')->links() }}</div>

    {{-- Modal tambah bahan --}}
    <dialog id="dlg-tambah" class="m-auto w-full max-w-md rounded-xl p-0 backdrop:bg-black/40">
        <form method="POST" action="{{ route('manajer.bahan.store') }}" class="space-y-4 p-6">
            @csrf
            <h3 class="text-lg font-bold">Tambah bahan</h3>
            <label class="block text-sm">Nama bahan
                <input type="text" name="nama_bahan" value="{{ old('nama_bahan') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="block text-sm">Satuan dasar
                <select name="satuan" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach (['g', 'ml', 'pcs'] as $s)
                        <option value="{{ $s }}" @selected(old('satuan') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm">Stok awal
                    <input type="number" step="0.01" min="0" name="jumlah_stok" value="{{ old('jumlah_stok', 0) }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="block text-sm">Batas minimum
                    <input type="number" step="0.01" min="0" name="batas_minimum" value="{{ old('batas_minimum', 0) }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
            </div>
            <label class="block text-sm">Harga per satuan (Rp)
                <input type="number" step="0.01" min="0" name="harga" value="{{ old('harga') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <p class="text-xs text-gray-500">Kode bahan dibuat otomatis.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Batal</button>
                <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- Modal atur batas --}}
    <dialog id="dlg-batas" class="m-auto w-full max-w-sm rounded-xl p-0 backdrop:bg-black/40">
        <form method="POST" class="space-y-4 p-6">
            @csrf @method('PATCH')
            <h3 class="text-lg font-bold">Atur batas minimum</h3>
            <p id="nama-batas" class="text-sm text-gray-500"></p>
            <input type="number" step="0.01" min="0" name="batas_minimum" required class="w-full rounded-md border border-gray-300 px-3 py-2">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Batal</button>
                <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Simpan</button>
            </div>
        </form>
    </dialog>

    <script>
        function aturBatas(btn) {
            const d = document.getElementById('dlg-batas');
            d.querySelector('form').action = btn.dataset.action;
            d.querySelector('[name=batas_minimum]').value = btn.dataset.batas;
            document.getElementById('nama-batas').textContent = btn.dataset.nama;
            d.showModal();
        }
        @if (old('nama_bahan') !== null)
            document.getElementById('dlg-tambah').showModal();
        @endif
    </script>
</x-layouts.manajer>