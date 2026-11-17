@php
    $user = auth()->user(); //mengambil data user yang sedang login
    $nama = $user->nama ?: 'Manajer';
    $inisial = strtoupper(mb_substr($nama, 0, 2));
    $menu = [
        ['Kelola Menu', 'manajer.menu.index'],
        ['Kelola Resep', 'manajer.resep.index'],
        ['Kelola Bahan & Stok', 'manajer.bahan.index'],
        ['Stok Masuk', 'manajer.stok-masuk.index'],
        ['Laporan', 'manajer.laporan.index'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Manajer' }} | Kedai Kopi Senja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans text-gray-900">
<header class="sticky top-0 z-20 flex h-14 items-center border-b border-gray-200 bg-white px-6 print:hidden">
    <span class="text-sm font-bold">Kedai Kopi Senja</span>
</header>

<div class="flex">
    {{-- Sidebar: setinggi sisa layar dan menempel, tidak ikut panjang halaman --}}
    <aside class="sticky top-14 flex h-[calc(100vh-3.5rem)] w-60 shrink-0 flex-col self-start border-r border-gray-200 bg-gray-100 print:hidden">
        <div class="flex-1 overflow-y-auto p-3">
            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Navigasi Utama</p>
            <nav class="space-y-1 text-sm">
                @foreach ($menu as [$label, $rute])
                    @php $aktif = request()->routeIs($rute); @endphp
                    <a href="{{ Route::has($rute) ? route($rute) : '#' }}"
                       class="block rounded-md px-3 py-2 {{ $aktif ? 'bg-black font-semibold text-white' : 'text-gray-700 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="border-t border-gray-200 p-3">
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-300 text-xs font-bold">{{ $inisial }}</span>
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold">{{ $nama }}</p>
                    <p class="text-[11px] text-gray-500">Manajer</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs hover:bg-gray-50">Logout</button>
            </form>
            <a href="{{ route('password.edit') }}"class="mt-2 block text-center text-xs text-gray-500 hover:text-gray-900 hover:underline">
                Ubah password
            </a>
        </div>
    </aside>

    <div class="flex min-h-[calc(100vh-3.5rem)] min-w-0 flex-1 flex-col">
        <main class="flex-1 p-8">
            <h2 class="text-2xl font-bold">{{ $title ?? '' }}</h2>
            @isset($subtitle)
                <p class="mb-6 mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
            @endisset
            @if (session('sukses'))
                <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
            @endif
            {{ $slot }}
        </main>
        <footer class="border-t border-gray-200 px-8 py-4 text-xs text-gray-500 print:hidden">
            &copy; {{ date('Y') }} Kedai Kopi Senja
        </footer>
    </div>
</div>
</body>
</html>