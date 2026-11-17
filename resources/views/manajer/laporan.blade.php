@php
    $rp    = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $pct   = fn ($n) => number_format((float) $n, 1, ',', '.') . '%';
    $opsiPeriode = [
        'hari_ini'   => 'Hari ini',
        '7_hari'     => '7 hari terakhir',
        'bulan_ini'  => 'Bulan ini',
        'bulan_lalu' => 'Bulan lalu',
        'custom'     => 'Rentang tanggal sendiri',
    ];
@endphp
<x-layouts.manajer title="Laporan Operasional" subtitle="Penjualan menu, bahan menipis, dan analisis margin HPP.">

    <div class="mb-4 flex justify-end print:hidden">
        <button type="button" onclick="window.print()"
                class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Cetak laporan</button>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="mb-6 grid grid-cols-4 gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total omset periode</p>
            <p class="mt-2 text-2xl font-bold">{{ $rp($totalOmset) }}</p>
            <p class="text-xs text-gray-500">{{ number_format($totalPorsi, 0, ',', '.') }} porsi terjual</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Rata-rata margin</p>
            <p class="mt-2 text-2xl font-bold">{{ $pct($rataMargin) }}</p>
            <p class="text-xs text-gray-500">Berbobot penjualan</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Perlu restok</p>
            <p class="mt-2 text-2xl font-bold">{{ $menipis->total() }} jenis bahan</p>
            <p class="text-xs text-gray-500">Stok di bawah batas minimum</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Estimasi HPP terpakai</p>
            <p class="mt-2 text-2xl font-bold">{{ $rp($hppTerpakai) }}</p>
            <p class="text-xs text-gray-500">{{ $pct($persenHpp) }} dari omset</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" id="formFilter"
          class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 print:hidden">
        <label class="flex flex-col gap-1 text-sm">
            <span>Dari tanggal</span>
            <input type="date" name="dari" value="{{ $dari->toDateString() }}"
                   class="h-10 rounded-md border border-gray-300 px-3">
        </label>
        <label class="flex flex-col gap-1 text-sm">
            <span>Sampai tanggal</span>
            <input type="date" name="sampai" value="{{ $sampai->toDateString() }}"
                   class="h-10 rounded-md border border-gray-300 px-3">
        </label>
        <label class="flex flex-col gap-1 text-sm">
            <span>Periode cepat</span>
            <select name="periode" id="periode" class="h-10 rounded-md border border-gray-300 px-3">
                @foreach ($opsiPeriode as $k => $v)
                    <option value="{{ $k }}" @selected($periode === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </label>
        <button class="h-10 rounded-md bg-black px-4 text-sm font-medium text-white hover:bg-gray-800">Tampilkan</button>
        <a href="{{ route('manajer.laporan.index') }}"
           class="flex h-10 items-center rounded-md border border-gray-300 px-4 text-sm hover:bg-gray-50">Reset</a>
    </form>

    <p class="mb-3 text-sm text-gray-600">
        Periode: <b>{{ $dari->translatedFormat('d M Y') }}</b> sampai <b>{{ $sampai->translatedFormat('d M Y') }}</b>
        (hanya pesanan berstatus selesai)
    </p>

    {{-- 1. Penjualan per menu --}}
    <div id="penjualan" class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <h3 class="border-b border-gray-200 px-4 py-3 text-sm font-bold">1. Penjualan per menu
            <span class="ml-2 rounded border border-gray-300 px-2 py-0.5 text-xs font-normal">{{ $penjualan->total() }} menu terjual</span>
        </h3>
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">No</th>
                <th class="px-4 py-3">Nama menu</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3 text-right">Harga rata-rata</th>
                <th class="px-4 py-3 text-right">Porsi terjual</th>
                <th class="px-4 py-3 text-right">Pendapatan</th>
                <th class="px-4 py-3 text-right">Kontribusi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($penjualan as $p)
                <tr>
                    <td class="px-4 py-3 text-gray-500">{{ $penjualan->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $p->nama_menu }}</td>
                    <td class="px-4 py-3 capitalize">{{ $p->kategori }}</td>
                    <td class="px-4 py-3 text-right">{{ $rp($p->harga_rata) }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ number_format($p->porsi, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-bold">{{ $rp($p->pendapatan) }}</td>
                    <td class="px-4 py-3 text-right">{{ $pct($p->kontribusi) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada penjualan selesai pada periode ini.</td></tr>
            @endforelse
            </tbody>
            @if ($penjualan->isNotEmpty())
                <tfoot class="border-t-2 border-black bg-gray-50 font-bold">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right uppercase">Total penjualan</td>
                    <td class="px-4 py-3 text-right">{{ number_format($totalPorsi, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">{{ $rp($totalOmset) }}</td>
                    <td class="px-4 py-3 text-right">100%</td>
                </tr>
                </tfoot>
            @endif
        </table>
        <div class="border-t border-gray-200 px-4 py-3 print:hidden">
            {{ $penjualan->fragment('penjualan')->links() }}
        </div>
    </div>

    {{-- 2. Bahan menipis --}}
    <div id="menipis" class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <h3 class="border-b border-gray-200 px-4 py-3 text-sm font-bold">2. Bahan menipis (stok &le; batas minimum)
            <span class="ml-2 rounded border border-gray-300 px-2 py-0.5 text-xs font-normal">{{ $menipis->total() }} bahan</span>
            <span class="ml-2 text-xs font-normal text-gray-500">Keadaan saat ini, tidak tergantung periode.</span>
        </h3>
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">No</th>
                <th class="px-4 py-3">Bahan</th>
                <th class="px-4 py-3 text-right">Stok saat ini</th>
                <th class="px-4 py-3 text-right">Batas minimum</th>
                <th class="px-4 py-3 text-right">Selisih</th>
                <th class="px-4 py-3">Status</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($menipis as $b)
                <tr>
                    <td class="px-4 py-3 text-gray-500">{{ $menipis->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3">
                        <p class="font-semibold">{{ $b->nama_bahan }}</p>
                        <p class="text-xs text-gray-500">{{ $b->kode }}</p>
                    </td>
                    <td class="px-4 py-3 text-right">{{ $angka($b->jumlah_stok) }} {{ $b->satuan }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ $angka($b->batas_minimum) }} {{ $b->satuan }}</td>
                    <td class="px-4 py-3 text-right font-bold">{{ $angka($b->jumlah_stok - $b->batas_minimum) }} {{ $b->satuan }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded bg-black px-2 py-0.5 text-[10px] font-bold text-white">
                            {{ $b->jumlah_stok <= 0 ? 'HABIS' : 'MENIPIS' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Semua bahan stoknya masih di atas batas minimum.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-200 px-4 py-3 print:hidden">
            {{ $menipis->fragment('menipis')->links() }}
        </div>
    </div>

    {{-- 3. Analisis margin --}}
    <div id="margin" class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <h3 class="border-b border-gray-200 px-4 py-3 text-sm font-bold">3. Analisis margin per menu
            <span class="ml-2 text-xs font-normal text-gray-500">
                Margin (Rp) = Harga jual - HPP. Margin (%) = Margin / Harga jual x 100. Hanya menu yang sudah punya resep.
            </span>
        </h3>
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">No</th>
                <th class="px-4 py-3">Nama menu</th>
                <th class="px-4 py-3 text-right">Harga jual</th>
                <th class="px-4 py-3 text-right">HPP</th>
                <th class="px-4 py-3 text-right">Margin (Rp)</th>
                <th class="px-4 py-3 text-right">Margin (%)</th>
                <th class="px-4 py-3">Kelayakan</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($margin as $m)
                <tr>
                    <td class="px-4 py-3 text-gray-500">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $m->nama_menu }}</td>
                    <td class="px-4 py-3 text-right">{{ $rp($m->harga) }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $rp($m->hpp) }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ $rp($m->margin) }}</td>
                    <td class="px-4 py-3 text-right font-bold">{{ $pct($m->margin_persen) }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded border border-gray-300 px-2 py-0.5 text-xs">{{ $labelMargin($m->margin_persen) }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $margin->firstItem() + $loop->index }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada menu yang punya resep.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-200 px-4 py-3 print:hidden">
            {{ $margin->fragment('margin')->links() }}
        </div>
    </div>

    <p class="text-xs text-gray-500">
        Dicetak {{ now()->translatedFormat('d M Y, H:i') }} WIB oleh {{ auth()->user()->nama ?: 'Manajer' }}.
        HPP memakai harga terakhir tiap bahan, jadi nilai HPP terpakai adalah perkiraan.
    </p>

    <script>
        // Ubah tanggal manual => periode otomatis jadi "Rentang tanggal sendiri"
        document.querySelectorAll('input[type=date]').forEach(function (el) {
            el.addEventListener('change', function () {
                document.getElementById('periode').value = 'custom';
            });
        });
        // Pilih periode cepat => langsung tampilkan
        document.getElementById('periode').addEventListener('change', function () {
            if (this.value !== 'custom') document.getElementById('formFilter').submit();
        });
    </script>
</x-layouts.manajer>