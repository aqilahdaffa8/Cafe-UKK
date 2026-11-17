@php
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $pesanan->kode }} | Kedai Kopi Senja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 py-8 font-mono text-gray-900 print:bg-white print:py-0">

    <div class="mx-auto w-full max-w-xs bg-white p-5 text-sm shadow print:shadow-none">
        <div class="text-center">
            <p class="text-base font-bold">Kedai Kopi Senja</p>
            <p class="text-xs text-gray-500">Struk pembayaran</p>
        </div>

        <div class="my-3 border-t border-dashed border-gray-400"></div>

        <div class="space-y-0.5 text-xs">
            <p class="flex justify-between"><span>Pesanan</span><span>{{ $pesanan->kode }}</span></p>
            <p class="flex justify-between"><span>Tanggal</span><span>{{ $pesanan->created_at->translatedFormat('d M Y, H:i') }}</span></p>
            <p class="flex justify-between"><span>Meja</span><span>{{ $pesanan->nomor_meja }}</span></p>
            <p class="flex justify-between">
                <span>Dibuat oleh</span>
                <span>{{ $pesanan->pembuat?->isPelanggan() ? 'Pelanggan' : 'Kasir' }}</span>
            </p>
        </div>

        <div class="my-3 border-t border-dashed border-gray-400"></div>

        <div class="space-y-2">
            @foreach ($pesanan->detailPesanans as $d)
                <div>
                    <p class="font-semibold">{{ $d->menu->nama_menu }}</p>
                    <p class="flex justify-between text-xs">
                        <span>{{ $d->jumlah_menu }} x {{ $rp($d->harga_satuan) }}</span>
                        <span>{{ $rp($d->jumlah_menu * $d->harga_satuan) }}</span>
                    </p>
                </div>
            @endforeach
        </div>

        <div class="my-3 border-t border-dashed border-gray-400"></div>

        <div class="space-y-0.5 text-xs">
            <p class="flex justify-between"><span>Subtotal</span><span>{{ $rp($pesanan->subtotal) }}</span></p>
            <p class="flex justify-between"><span>Service ({{ $servicePersen }}%)</span><span>{{ $rp($pesanan->service) }}</span></p>
            <p class="flex justify-between"><span>Pajak ({{ $pajakPersen }}%)</span><span>{{ $rp($pesanan->pajak) }}</span></p>
        </div>
        <p class="mt-2 flex justify-between border-t border-gray-400 pt-2 text-base font-bold">
            <span>Total</span><span>{{ $rp($pesanan->total_harga) }}</span>
        </p>
        <p class="mt-1 flex justify-between text-xs">
            <span>Pembayaran</span><span>{{ $pesanan->metode_pembayaran === 'qris' ? 'QRIS' : 'Tunai' }}</span>
        </p>

        <div class="my-3 border-t border-dashed border-gray-400"></div>
        <p class="text-center text-xs">Terima kasih, sampai jumpa lagi!</p>
    </div>

    <div class="mx-auto mt-4 flex max-w-xs gap-2 font-sans print:hidden">
        <button type="button" onclick="window.print()"
                class="h-10 flex-1 rounded-md bg-black text-sm font-medium text-white hover:bg-gray-800">Cetak</button>
        <button type="button" onclick="window.close()"
                class="h-10 flex-1 rounded-md border border-gray-300 bg-white text-sm hover:bg-gray-50">Tutup</button>
    </div>
</body>
</html>