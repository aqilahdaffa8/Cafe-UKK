@php
    $angka = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp
<x-layouts.manajer title="Stok Masuk" subtitle="Catat pembelian bahan baku. Stok bahan akan otomatis bertambah.">

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('manajer.stok-masuk.store') }}"
          class="mb-6 rounded-lg border border-gray-200 bg-white p-5">
        @csrf
        <h3 class="mb-4 text-sm font-bold">Catat stok masuk</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <label class="md:col-span-2 block text-sm">Bahan
                <select name="id_bahan" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">Pilih bahan...</option>
                    @foreach ($bahans as $b)
                        <option value="{{ $b->id_bahan }}" @selected(old('id_bahan') == $b->id_bahan)>
                            {{ $b->nama_bahan }} ({{ $b->kode }}, {{ $b->satuan }})
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Jumlah
                <input type="number" step="0.01" min="0.01" name="jumlah" value="{{ old('jumlah') }}" required
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="block text-sm">Tanggal
                <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                       max="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
        </div>
        <div class="mt-4 flex justify-end">
            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Simpan</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">No</th>
                <th class="px-4 py-3">Tanggal</th>
                <th class="px-4 py-3">Bahan</th>
                <th class="px-4 py-3 text-right">Jumlah</th>
                <th class="px-4 py-3">Dicatat oleh</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($riwayat as $r)
                <tr>
                    <td class="px-4 py-3 text-gray-500">{{ $riwayat->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($r->tanggal)->translatedFormat('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <p class="font-semibold">{{ $r->bahan?->nama_bahan ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $r->bahan?->kode }}</p>
                    </td>
                    <td class="px-4 py-3 text-right font-bold">{{ $angka($r->jumlah) }} {{ $r->bahan?->satuan }}</td>
                    <td class="px-4 py-3">{{ $r->pengguna?->nama ?: 'Pengguna #' . $r->id_pengguna }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada stok masuk.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $riwayat->links() }}</div>
</x-layouts.manajer>