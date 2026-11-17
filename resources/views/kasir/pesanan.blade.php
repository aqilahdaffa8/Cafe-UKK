@php
    $rp    = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $label = ['minuman' => 'Minuman', 'makanan' => 'Makanan', 'lainnya' => 'Lainnya'];
    $tabAktif = 'border-black bg-black font-semibold text-white';
    $tabBiasa = 'border-gray-300 text-gray-700 hover:bg-gray-50';
@endphp
<x-layouts.kasir title="Buat Pesanan Baru" subtitle="Pilih menu untuk ditambahkan ke struk pesanan.">

    <div class="flex items-start gap-6">

        {{-- KIRI: daftar menu --}}
        <div class="min-w-0 flex-1">
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
                <input type="text" id="cariMenu" placeholder="Cari nama menu..."
                       class="h-10 w-full rounded-md border border-gray-300 bg-gray-50 px-3 text-sm">
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" data-kategori="semua"
                            class="tab-kategori rounded-full border px-4 py-1.5 text-sm {{ $tabAktif }}">
                        Semua ({{ $menus->count() }})
                    </button>
                    @foreach ($label as $k => $v)
                        <button type="button" data-kategori="{{ $k }}"
                                class="tab-kategori rounded-full border px-4 py-1.5 text-sm {{ $tabBiasa }}">
                            {{ $v }} ({{ $perKategori[$k] ?? 0 }})
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($menus->isEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">
                    Belum ada menu aktif. Minta manajer mengisi resep dan mengaktifkan menu.
                </div>
            @else
                <div id="gridMenu" class="grid grid-cols-2 gap-4 xl:grid-cols-3">
                    @foreach ($menus as $m)
                        <div class="kartu-menu flex flex-col rounded-lg border border-gray-200 bg-white p-3"
                             data-id="{{ $m->id_menu }}" data-nama="{{ $m->nama_menu }}"
                             data-harga="{{ $m->harga }}" data-kategori="{{ $m->kategori }}">
                            @if ($m->gambar)
                                <img src="{{ asset('storage/' . $m->gambar) }}" alt="{{ $m->nama_menu }}"
                                     class="mb-3 h-28 w-full rounded-md border border-gray-200 object-cover">
                            @else
                                <span class="mb-3 flex h-28 w-full items-center justify-center rounded-md border border-gray-200 bg-gray-100 text-3xl font-bold text-gray-400">
                                    {{ strtoupper(mb_substr($m->nama_menu, 0, 1)) }}
                                </span>
                            @endif
                            <p class="font-semibold leading-tight">{{ $m->nama_menu }}</p>
                            <p class="text-xs text-gray-500">{{ $label[$m->kategori] ?? $m->kategori }}</p>
                            <div class="mt-auto flex items-center justify-between gap-2 pt-3">
                                <span class="text-sm font-bold">{{ $rp($m->harga) }}</span>
                                <button type="button"
                                        class="btn-tambah rounded-md border border-black px-3 py-1.5 text-xs font-medium hover:bg-black hover:text-white">
                                    Tambah +
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p id="menuKosong" class="hidden rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">
                    Tidak ada menu yang cocok.
                </p>
            @endif
        </div>

        {{-- KANAN: ringkasan pesanan --}}
        <div class="sticky top-[4.5rem] flex max-h-[calc(100vh-6rem)] w-96 shrink-0 flex-col self-start rounded-lg border border-gray-200 bg-white">
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3">
                <h3 class="text-sm font-bold">Ringkasan Pesanan</h3>
                <span id="jumlahItem" class="rounded-full border border-gray-300 px-2 py-0.5 text-xs">0 item</span>
            </div>

            <div class="shrink-0 border-b border-gray-200 p-4">
                <label class="flex flex-col gap-1 text-sm">
                    <span>Nomor meja</span>
                    <select id="meja" class="h-10 rounded-md border border-gray-300 bg-white px-3">
                        <option value="">Pilih nomor meja...</option>
                        @for ($i = 1; $i <= $jumlahMeja; $i++)
                            <option value="{{ $i }}">Meja {{ $i }}</option>
                        @endfor
                    </select>
                </label>
            </div>

            <div id="keranjang" class="min-h-0 flex-1 space-y-2 overflow-y-auto p-4"></div>

            <div class="shrink-0 space-y-1 border-t border-gray-200 p-4 text-sm">
                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span id="tSubtotal">Rp 0</span></div>
                <div class="flex justify-between text-gray-600"><span>Service ({{ $servicePersen }}%)</span><span id="tService">Rp 0</span></div>
                <div class="flex justify-between text-gray-600"><span>Pajak ({{ $pajakPersen }}%)</span><span id="tPajak">Rp 0</span></div>
                <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold">
                    <span>Total</span><span id="tTotal">Rp 0</span>
                </div>
            </div>

            <div class="shrink-0 space-y-3 border-t border-gray-200 p-4">
                <fieldset>
                    <legend class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Metode pembayaran</legend>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2">
                            <input type="radio" name="metode" value="tunai" checked> Tunai
                        </label>
                        <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2">
                            <input type="radio" name="metode" value="qris"> QRIS
                        </label>
                    </div>
                </fieldset>

                <p id="galat" class="hidden rounded-md bg-red-50 px-3 py-2 text-xs text-red-700"></p>

                <button type="button" id="btnProses"
                        class="h-10 w-full rounded-md bg-black text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50">
                    Proses Pesanan
                </button>
                <button type="button" id="btnKosongkan"
                        class="h-10 w-full rounded-md border border-gray-300 text-sm hover:bg-gray-50">
                    Batal / Kosongkan
                </button>
            </div>
        </div>
    </div>

    {{-- Modal pesanan ditolak --}}
    <dialog id="dlgDitolak" class="m-auto w-full max-w-md rounded-xl p-0 backdrop:bg-black/40">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-bold">Pesanan ditolak</h3>
            <p id="ditolakPesan" class="text-sm text-gray-600"></p>
            <div id="ditolakDaftar" class="hidden">
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Rincian kekurangan bahan baku</p>
                <div id="ditolakIsi" class="space-y-2"></div>
                <p class="mt-3 rounded-md bg-gray-100 px-3 py-2 text-xs text-gray-600">
                    Kurangi jumlah pesanan, atau minta manajer mencatat stok masuk.
                </p>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="this.closest('dialog').close()"
                        class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                    Sesuaikan pesanan
                </button>
            </div>
        </div>
    </dialog>

    <script>
        const URL_SIMPAN = @json(route('kasir.pesanan.store'));
        const CSRF       = @json(csrf_token());
        const SERVICE    = @json($servicePersen);
        const PAJAK      = @json($pajakPersen);

        const rp  = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        const num = (n) => Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 });
        const el  = (tag, kelas, teks) => {
            const e = document.createElement(tag);
            if (kelas) e.className = kelas;
            if (teks !== undefined) e.textContent = teks;
            return e;
        };
        const $ = (id) => document.getElementById(id);

        // ---------- Filter menu ----------
        const TAB_AKTIF = ['border-black', 'bg-black', 'font-semibold', 'text-white'];
        const TAB_BIASA = ['border-gray-300', 'text-gray-700', 'hover:bg-gray-50'];
        let kategoriAktif = 'semua';

        function saring() {
            const kata = $('cariMenu').value.toLowerCase().trim();
            let tampil = 0;
            document.querySelectorAll('.kartu-menu').forEach(function (k) {
                const cocok = (kategoriAktif === 'semua' || k.dataset.kategori === kategoriAktif)
                    && k.dataset.nama.toLowerCase().includes(kata);
                k.style.display = cocok ? '' : 'none';
                if (cocok) tampil++;
            });
            const kosong = $('menuKosong');
            if (kosong) kosong.classList.toggle('hidden', tampil > 0);
        }

        $('cariMenu').addEventListener('input', saring);
        document.querySelectorAll('.tab-kategori').forEach(function (tab) {
            tab.addEventListener('click', function () {
                kategoriAktif = tab.dataset.kategori;
                document.querySelectorAll('.tab-kategori').forEach(function (t) {
                    const aktif = t === tab;
                    t.classList.remove(...TAB_AKTIF, ...TAB_BIASA);
                    t.classList.add(...(aktif ? TAB_AKTIF : TAB_BIASA));
                });
                saring();
            });
        });

        // ---------- Keranjang ----------
        const cart = new Map(); // id_menu => { id, nama, harga, jumlah }

        document.querySelectorAll('.btn-tambah').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const k = btn.closest('.kartu-menu');
                const id = Number(k.dataset.id);
                const ada = cart.get(id);
                if (ada) {
                    ada.jumlah = Math.min(99, ada.jumlah + 1);
                } else {
                    cart.set(id, { id, nama: k.dataset.nama, harga: Number(k.dataset.harga), jumlah: 1 });
                }
                render();
            });
        });

        function ubahJumlah(id, selisih) {
            const it = cart.get(id);
            if (!it) return;
            it.jumlah = Math.min(99, it.jumlah + selisih);
            if (it.jumlah < 1) cart.delete(id);
            render();
        }

        function tombolQty(teks, aksi) {
            const b = el('button', 'h-7 w-7 rounded-md border border-gray-300 text-sm hover:bg-gray-50', teks);
            b.type = 'button';
            b.addEventListener('click', aksi);
            return b;
        }

        function render() {
            const wadah = $('keranjang');
            wadah.replaceChildren();

            if (cart.size === 0) {
                wadah.append(el('p', 'py-8 text-center text-sm text-gray-500', 'Belum ada item. Pilih menu di sebelah kiri.'));
            }

            let subtotal = 0, jumlahItem = 0;
            cart.forEach(function (it) {
                subtotal   += it.harga * it.jumlah;
                jumlahItem += it.jumlah;

                const baris = el('div', 'rounded-md border border-gray-200 p-3');

                const atas = el('div', 'flex items-start justify-between gap-2');
                const info = el('div', 'min-w-0');
                info.append(
                    el('p', 'text-sm font-semibold', it.nama),
                    el('p', 'text-xs text-gray-500', rp(it.harga) + ' / porsi')
                );
                const hapus = el('button', 'text-xs text-red-600 hover:underline', 'Hapus');
                hapus.type = 'button';
                hapus.addEventListener('click', function () { cart.delete(it.id); render(); });
                atas.append(info, hapus);

                const bawah = el('div', 'mt-2 flex items-center justify-between');
                const qty = el('div', 'flex items-center gap-1');
                qty.append(
                    tombolQty('-', function () { ubahJumlah(it.id, -1); }),
                    el('span', 'w-8 text-center text-sm font-medium', String(it.jumlah)),
                    tombolQty('+', function () { ubahJumlah(it.id, 1); })
                );
                bawah.append(qty, el('span', 'text-sm font-bold', rp(it.harga * it.jumlah)));

                baris.append(atas, bawah);
                wadah.append(baris);
            });

            // Pratinjau saja: angka resmi dihitung ulang di server
            const service = Math.round(subtotal * SERVICE / 100);
            const pajak   = Math.round(subtotal * PAJAK / 100);
            $('tSubtotal').textContent = rp(subtotal);
            $('tService').textContent  = rp(service);
            $('tPajak').textContent    = rp(pajak);
            $('tTotal').textContent    = rp(subtotal + service + pajak);
            $('jumlahItem').textContent = jumlahItem + ' item';
        }

        $('btnKosongkan').addEventListener('click', function () {
            if (cart.size === 0 || confirm('Kosongkan semua item di keranjang?')) {
                cart.clear();
                $('meja').value = '';
                tampilGalat('');
                render();
            }
        });

        // ---------- Kirim pesanan ----------
        function tampilGalat(teks) {
            const g = $('galat');
            g.textContent = teks;
            g.classList.toggle('hidden', teks === '');
        }

        function bukaDitolak(pesan, kekurangan) {
            $('ditolakPesan').textContent = pesan;
            const isi = $('ditolakIsi');
            isi.replaceChildren();

            kekurangan.forEach(function (b) {
                const baris = el('div', 'flex items-center justify-between gap-3 rounded-md border border-gray-200 p-3');
                const kiri = el('div', 'min-w-0');
                kiri.append(
                    el('p', 'text-sm font-semibold', b.nama_bahan),
                    el('p', 'text-xs text-gray-500',
                        'Tersedia ' + num(b.tersedia) + ' ' + b.satuan + ' \u2022 Dibutuhkan ' + num(b.dibutuhkan) + ' ' + b.satuan)
                );
                baris.append(kiri, el('span', 'shrink-0 rounded bg-black px-2 py-1 text-xs font-bold text-white',
                    'Kurang ' + num(b.kurang) + ' ' + b.satuan));
                isi.append(baris);
            });

            $('ditolakDaftar').classList.toggle('hidden', kekurangan.length === 0);
            $('dlgDitolak').showModal();
        }

        $('btnProses').addEventListener('click', async function () {
            tampilGalat('');

            if (cart.size === 0) return tampilGalat('Keranjang masih kosong.');
            if (!$('meja').value) return tampilGalat('Pilih nomor meja dulu.');

            const items = Array.from(cart.values()).map(function (i) {
                return { id_menu: i.id, jumlah: i.jumlah };
            });

            const tombol = $('btnProses');
            tombol.disabled = true;
            tombol.textContent = 'Memproses...';

            try {
                const res = await fetch(URL_SIMPAN, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({
                        nomor_meja: Number($('meja').value),
                        metode_pembayaran: document.querySelector('input[name=metode]:checked').value,
                        items: items,
                    }),
                });
                const data = await res.json().catch(function () { return {}; });

                if (res.ok && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (res.status === 422 && data.pesan) {
                    bukaDitolak(data.pesan, data.kekurangan || []);
                } else if (res.status === 422 && data.errors) {
                    tampilGalat(Object.values(data.errors)[0][0]);
                } else if (res.status === 419) {
                    tampilGalat('Sesi sudah habis. Muat ulang halaman, lalu coba lagi.');
                } else {
                    tampilGalat('Terjadi kesalahan di server. Coba lagi.');
                }
            } catch (e) {
                tampilGalat('Tidak bisa menghubungi server. Periksa koneksi.');
            } finally {
                tombol.disabled = false;
                tombol.textContent = 'Proses Pesanan';
            }
        });

        render();
    </script>
</x-layouts.kasir>