@php
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $tabs = [
        'semua'      => 'Semua (' . $jumlahSemua . ')',
        'baru'       => 'Baru (' . ($hitung['baru'] ?? 0) . ')',
        'diproses'   => 'Diproses (' . ($hitung['diproses'] ?? 0) . ')',
        'selesai'    => 'Selesai (' . ($hitung['selesai'] ?? 0) . ')',
        'dibatalkan' => 'Dibatalkan (' . ($hitung['dibatalkan'] ?? 0) . ')',
    ];
    $badge = [
        'baru'       => 'bg-black text-white',
        'diproses'   => 'border border-black text-gray-900',
        'selesai'    => 'bg-gray-200 text-gray-800',
        'dibatalkan' => 'border border-dashed border-gray-400 text-gray-500',
    ];
@endphp
<x-layouts.kasir title="Riwayat & Status Pesanan" subtitle="Pantau antrean, rincian transaksi, dan pembaruan status pesanan.">

    @error('aksi')
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="mb-4 grid grid-cols-2 gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Selesai hari ini</p>
            <p class="mt-2 text-2xl font-bold">{{ $selesaiHariIni }} <span class="text-sm font-normal text-gray-500">pesanan</span></p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total diterima hari ini</p>
            <p class="mt-2 text-2xl font-bold">{{ $rp($diterimaHariIni) }}</p>
            <p class="text-xs text-gray-500">Termasuk service dan pajak</p>
        </div>
    </div>

    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nomor pesanan, meja, atau nama menu..."
                   class="h-10 flex-1 rounded-md border border-gray-200 bg-gray-50 px-3 text-sm">
            <select name="meja" onchange="this.form.submit()" class="h-10 rounded-md border border-gray-200 px-3 text-sm">
                <option value="semua">Semua meja</option>
                @for ($i = 1; $i <= $jumlahMeja; $i++)
                    <option value="{{ $i }}" @selected($meja === (string) $i)>Meja {{ $i }}</option>
                @endfor
            </select>
            <button class="h-10 rounded-md bg-black px-4 text-sm font-medium text-white hover:bg-gray-800">Cari</button>
            <a href="{{ route('kasir.pesanan.index') }}"
               class="flex h-10 items-center rounded-md border border-gray-200 px-3 text-sm hover:bg-gray-50">Reset</a>
        </form>
        <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
            @foreach ($tabs as $k => $teks)
                <a href="{{ route('kasir.pesanan.index', array_merge(request()->except(['page', 'status']), ['status' => $k])) }}"
                   class="rounded-full border px-4 py-1.5 text-sm {{ $status === $k ? 'border-black bg-black font-semibold text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                    {{ $teks }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">Pesanan</th>
                <th class="px-4 py-3">Meja</th>
                <th class="px-4 py-3">Waktu</th>
                <th class="px-4 py-3">Pembuat</th>
                <th class="px-4 py-3">Rincian item</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3">Bayar</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($pesanans as $p)
                @php
                    $batal = $p->status === 'dibatalkan';
                    $awal  = $p->detailPesanans->take(2);
                    $sisa  = $p->detailPesanans->slice(2);
                @endphp
                <tr class="{{ $batal ? 'bg-gray-50 text-gray-400' : '' }}">
                    <td class="px-4 py-3 font-semibold {{ $batal ? 'line-through' : '' }}">{{ $p->kode }}</td>
                    <td class="px-4 py-3">Meja {{ $p->nomor_meja }}</td>
                    <td class="px-4 py-3">
                        <p>{{ $p->created_at->format('H:i') }}</p>
                        <p class="text-xs text-gray-500">{{ $p->created_at->translatedFormat('d M Y') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded border border-gray-300 px-2 py-0.5 text-xs">
                            {{ $p->pembuat?->isPelanggan() ? 'Pelanggan' : 'Kasir' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @foreach ($awal as $d)
                            <p>{{ $d->jumlah_menu }}x {{ $d->menu->nama_menu }}</p>
                        @endforeach
                        @if ($sisa->isNotEmpty())
                            <details class="text-xs text-gray-500">
                                <summary class="cursor-pointer">+{{ $sisa->count() }} lainnya</summary>
                                @foreach ($sisa as $d)
                                    <p>{{ $d->jumlah_menu }}x {{ $d->menu->nama_menu }}</p>
                                @endforeach
                            </details>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold {{ $batal ? 'line-through' : '' }}">{{ $rp($p->total_harga) }}</td>
                    <td class="px-4 py-3">{{ $p->metode_pembayaran === 'qris' ? 'QRIS' : 'Tunai' }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase {{ $badge[$p->status] }}">{{ $p->status }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            @if ($p->status === 'baru')
                                <form method="POST" action="{{ route('kasir.pesanan.proses', $p) }}">
                                    @csrf @method('PATCH')
                                    <button class="rounded-md bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Proses</button>
                                </form>
                                <form method="POST" action="{{ route('kasir.pesanan.batal', $p) }}"
                                      onsubmit="return confirm('Batalkan pesanan {{ $p->kode }}? Stok bahan akan dikembalikan.')">
                                    @csrf @method('PATCH')
                                    <button class="rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">Batalkan</button>
                                </form>
                            @elseif ($p->status === 'diproses')
                                <form method="POST" action="{{ route('kasir.pesanan.selesai', $p) }}">
                                    @csrf @method('PATCH')
                                    <button class="rounded-md bg-black px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">Tandai selesai</button>
                                </form>
                            @elseif ($p->status === 'selesai')
                                <a href="{{ route('kasir.pesanan.struk', $p) }}" target="_blank" rel="noopener"
                                   class="rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">Cetak struk</a>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Tidak ada pesanan yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            Menampilkan <b>{{ $pesanans->firstItem() ?? 0 }}-{{ $pesanans->lastItem() ?? 0 }}</b>
            dari <b>{{ $pesanans->total() }}</b> pesanan
        </div>
    </div>
    <div class="mt-4">{{ $pesanans->links() }}</div>
</x-layouts.kasir>