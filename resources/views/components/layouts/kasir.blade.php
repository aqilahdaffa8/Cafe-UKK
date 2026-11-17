@php
    $user = auth()->user();
    $nama = $user->nama ?: 'Kasir';
    $inisial = strtoupper(mb_substr($nama, 0, 2));
    $menu = [
        ['Buat Pesanan', 'kasir.pesanan.create'],
        ['Riwayat Pesanan', 'kasir.pesanan.index'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Kasir' }} | Kedai Kopi Senja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans text-gray-900">
    <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-6 print:hidden">
        <span class="text-sm font-bold">Kedai Kopi Senja</span>
        <button type="button" id="btnResetPw"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">
            Reset Password Pelanggan
        </button>
    </header>

    <div class="flex">
        <aside class="sticky top-14 flex h-[calc(100vh-3.5rem)] w-56 shrink-0 flex-col self-start border-r border-gray-200 bg-gray-100 print:hidden">
            <div class="flex-1 overflow-y-auto p-3">
                <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Navigasi Utama</p>
                <nav class="space-y-1 text-sm">
                    @foreach ($menu as [$label, $rute])
                        @php $aktif = request()->routeIs($rute); @endphp
                        <a href="{{ route($rute) }}"
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
                        <p class="text-[11px] text-gray-500">Kasir</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs hover:bg-gray-50">Logout</button>
                </form>
                <a href="{{ route('password.edit') }}"
                class="mt-2 block text-center text-xs text-gray-500 hover:text-gray-900 hover:underline">
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
    <dialog id="dlgReset" class="m-auto w-full max-w-md rounded-xl p-0 backdrop:bg-black/40 print:hidden">
        <div class="space-y-4 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold">Reset password pelanggan</h3>
                    <p class="text-sm text-gray-500">Cari pelanggan lewat nomor telepon, lalu buat password sementara.</p>
                </div>
                <button type="button" aria-label="Tutup"
                        onclick="document.getElementById('dlgReset').close()"
                        class="-mr-2 -mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-xl leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                    &times;
                </button>
            </div>

            <p id="rpGalat" class="hidden rounded-md bg-red-50 px-3 py-2 text-sm text-red-700"></p>
            <p id="rpSukses" class="hidden rounded-md bg-green-50 px-3 py-2 text-sm text-green-700"></p>

            <form id="rpFormCari" class="flex items-end gap-2">
                <label class="flex flex-1 flex-col gap-1 text-sm">
                    <span>Nomor telepon</span>
                    <input type="tel" id="rpTelp" placeholder="081234567890" autocomplete="off"
                        class="h-10 rounded-md border border-gray-300 px-3 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                </label>
                <button type="submit" id="rpBtnCari"
                        class="h-10 rounded-md bg-black px-4 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50">
                    Cari
                </button>
            </form>

            <div id="rpHasil" class="hidden space-y-4">
                <div class="rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                    <p class="font-semibold" id="rpNama"></p>
                    <p class="text-gray-600" id="rpNo"></p>
                </div>

                <form id="rpFormReset" class="space-y-3">
                    <label class="flex flex-col gap-1 text-sm">
                        <span>Password sementara baru</span>
                        <div class="flex gap-2">
                            <input type="text" id="rpPassword" autocomplete="off" required minlength="8"
                                class="h-10 flex-1 rounded-md border border-gray-300 px-3 font-mono focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                            <button type="button" id="rpAcak"
                                    class="h-10 rounded-md border border-gray-300 px-3 text-sm hover:bg-gray-50">Acak</button>
                        </div>
                    </label>
                    <p class="text-xs text-gray-500">
                        Pelanggan akan diminta mengganti password ini saat login berikutnya.
                        Sampaikan password langsung kepada pelanggan.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('dlgReset').close()"
                                class="h-10 rounded-md border border-gray-300 px-4 text-sm hover:bg-gray-50">Batal</button>
                        <button type="submit" id="rpBtnSimpan"
                                class="h-10 rounded-md bg-black px-4 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50">
                            Simpan &amp; Reset
                        </button>
                    </div>
                </form>
            </div>

            <div id="rpSelesai" class="hidden">
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('dlgReset').close()"
                            class="h-10 rounded-md bg-black px-4 text-sm font-medium text-white hover:bg-gray-800">Tutup</button>
                </div>
            </div>
        </div>
    </dialog>
    <script>
        (function () {
            const CSRF     = @json(csrf_token());
            const URL_CARI = @json(route('kasir.pelanggan.cari'));
            const $ = (id) => document.getElementById(id);
            let urlReset = null;

            const tampil = (id, ya) => $(id).classList.toggle('hidden', !ya);
            const pesan  = (id, teks) => { $(id).textContent = teks; tampil(id, teks !== ''); };

            function acak() {
                // Tanpa karakter yang mudah tertukar (0/O, 1/l/I) supaya mudah dibacakan ke pelanggan
                const abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
                const b = new Uint32Array(10);
                crypto.getRandomValues(b);
                return Array.from(b, (n) => abjad[n % abjad.length]).join('');
            }

            async function kirim(url, metode, badan) {
                const res = await fetch(url, {
                    method: metode,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify(badan),
                });
                const data = await res.json().catch(() => ({}));
                return { res, data };
            }

            function galatDari(res, data) {
                if (res.status === 422 && data.errors) return Object.values(data.errors)[0][0];
                if (res.status === 429) return 'Terlalu banyak percobaan. Tunggu sebentar, lalu coba lagi.';
                if (res.status === 419) return 'Sesi sudah habis. Muat ulang halaman, lalu coba lagi.';
                return data.pesan || 'Terjadi kesalahan. Coba lagi.';
            }

            function ulang() {
                urlReset = null;
                $('rpTelp').value = '';
                $('rpPassword').value = '';
                pesan('rpGalat', '');
                pesan('rpSukses', '');
                tampil('rpHasil', false);
                tampil('rpSelesai', false);
            }

            $('btnResetPw').addEventListener('click', function () {
                ulang();
                $('dlgReset').showModal();
                $('rpTelp').focus();
            });

            // Nomor diubah setelah ketemu: hasil lama tidak boleh dipakai untuk menyimpan
            $('rpTelp').addEventListener('input', function () {
                urlReset = null;
                tampil('rpHasil', false);
            });

            $('rpAcak').addEventListener('click', function () { $('rpPassword').value = acak(); });

            $('rpFormCari').addEventListener('submit', async function (e) {
                e.preventDefault();
                pesan('rpGalat', '');
                pesan('rpSukses', '');
                tampil('rpSelesai', false);

                const telp = $('rpTelp').value.trim();
                if (telp === '') return pesan('rpGalat', 'Isi nomor telepon dulu.');

                $('rpBtnCari').disabled = true;
                try {
                    const { res, data } = await kirim(URL_CARI, 'POST', { no_telp: telp });
                    if (!res.ok) {
                        tampil('rpHasil', false);
                        return pesan('rpGalat', galatDari(res, data));
                    }
                    urlReset = data.url;
                    $('rpNama').textContent = data.nama || 'Pelanggan tanpa nama';
                    $('rpNo').textContent   = data.no_telp;
                    $('rpPassword').value   = acak();
                    tampil('rpHasil', true);
                } catch (err) {
                    pesan('rpGalat', 'Tidak bisa menghubungi server. Periksa koneksi.');
                } finally {
                    $('rpBtnCari').disabled = false;
                }
            });

            $('rpFormReset').addEventListener('submit', async function (e) {
                e.preventDefault();
                pesan('rpGalat', '');
                if (!urlReset) return pesan('rpGalat', 'Cari pelanggan dulu.');

                const password = $('rpPassword').value;
                $('rpBtnSimpan').disabled = true;
                try {
                    const { res, data } = await kirim(urlReset, 'PATCH', { password: password });
                    if (!res.ok) return pesan('rpGalat', galatDari(res, data));

                    pesan('rpSukses', 'Password berhasil direset. Password sementara: ' + password);
                    tampil('rpHasil', false);
                    tampil('rpSelesai', true);
                    urlReset = null;
                } catch (err) {
                    pesan('rpGalat', 'Tidak bisa menghubungi server. Periksa koneksi.');
                } finally {
                    $('rpBtnSimpan').disabled = false;
                }
            });
        })();
    </script>
</body>
</html>