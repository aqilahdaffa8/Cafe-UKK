<?php

namespace App\Services;

use App\Exceptions\PesananDitolak;
use App\Models\Bahan;
use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PesananService
{
    public const SERVICE_PERSEN = 5;
    public const PAJAK_PERSEN   = 10;
    public const JUMLAH_MEJA    = 10;

    // Hitung rincian biaya struk (service 5% dan pajak 10% dari subtotal + service)
    public function hitung(int $subtotal): array
    {
        $service = (int) round($subtotal * self::SERVICE_PERSEN / 100);
        $pajak   = (int) round(($subtotal + $service) * self::PAJAK_PERSEN / 100);

        return [
            'subtotal'    => $subtotal,
            'service'     => $service,
            'pajak'       => $pajak,
            'total_harga' => $subtotal + $service + $pajak,
        ];
    }

    // Buat pesanan baru, validasi stok bahan resep gabungan, dan potong stok secara atomik
    public function buat(User $pembuat, int $nomorMeja, string $metode, array $items): Pesanan
    {
        if ($nomorMeja < 1 || $nomorMeja > self::JUMLAH_MEJA) {
            throw new PesananDitolak('Nomor meja tidak valid.');
        }
        if (! in_array($metode, Pesanan::METODE_PEMBAYARAN, true)) {
            throw new PesananDitolak('Metode pembayaran tidak valid.');
        }

        $items = $this->gabungkan($items);

        // DB::transaction menjamin pesanan tersimpan dan stok terpotong bersamaan
        return DB::transaction(function () use ($pembuat, $nomorMeja, $metode, $items) {
            // lockForUpdate mengunci baris data di database untuk mencegah tabrakan data (race condition)
            User::whereKey($pembuat->getKey())->lockForUpdate()->first();

            // Pelanggan hanya boleh punya 1 pesanan aktif berstatus Baru
            if ($pembuat->isPelanggan()
                && Pesanan::where('id_pengguna', $pembuat->getKey())
                    ->where('status', Pesanan::STATUS_BARU)->exists()) {
                throw new PesananDitolak('Anda masih memiliki pesanan aktif yang sedang menunggu diproses kasir.');
            }

            $menus = Menu::with('reseps')->whereIn('id_menu', array_keys($items))->get()->keyBy('id_menu');

            foreach ($items as $idMenu => $jumlah) {
                $menu = $menus->get($idMenu);

                if (! $menu) {
                    throw new PesananDitolak('Ada menu yang tidak ditemukan.');
                }
                if (! $menu->aktif) {
                    throw new PesananDitolak("Menu {$menu->nama_menu} sedang tidak tersedia.");
                }
                if ($menu->reseps->isEmpty()) {
                    if ($pembuat->isPelanggan()) {
                        throw new PesananDitolak("Menu {$menu->nama_menu} sedang tidak tersedia.");
                    }
                    throw new PesananDitolak("Menu {$menu->nama_menu} belum punya resep.");
                }
            }

            // Hitung kebutuhan bahan gabungan dari seluruh item (takaran resep x jumlah porsi)
            $kebutuhan = [];
            foreach ($items as $idMenu => $jumlah) {
                foreach ($menus[$idMenu]->reseps as $resep) {
                    $kebutuhan[$resep->id_bahan] = ($kebutuhan[$resep->id_bahan] ?? 0)
                        + (float) $resep->takaran * $jumlah;
                }
            }
            ksort($kebutuhan); // Urutkan ID bahan agar penguncian database berurutan dan mencegah deadlock

            $bahans = Bahan::whereIn('id_bahan', array_keys($kebutuhan))
                ->orderBy('id_bahan')->lockForUpdate()->get()->keyBy('id_bahan');

            // Cek ketersediaan stok bahan baku
            $kekurangan = [];
            foreach ($kebutuhan as $idBahan => $butuh) {
                $bahan = $bahans[$idBahan];
                $butuh = round($butuh, 2);
                $ada   = round((float) $bahan->jumlah_stok, 2);

                if ($ada < $butuh) {
                    $kekurangan[] = [
                        'id_bahan'   => $bahan->id_bahan,
                        'nama_bahan' => $bahan->nama_bahan,
                        'satuan'     => $bahan->satuan,
                        'tersedia'   => $ada,
                        'dibutuhkan' => $butuh,
                        'kurang'     => round($butuh - $ada, 2),
                    ];
                }
            }

            if ($kekurangan !== []) {
                if ($pembuat->isPelanggan()) {
                    // Berikan notifikasi yang relevan untuk pelanggan (sebutkan nama menu yang habis, bukan rincian gram bahan dapur)
                    $idBahanKurang = array_column($kekurangan, 'id_bahan');
                    $menuKurang = [];
                    foreach ($items as $idMenu => $jumlah) {
                        $menuObj = $menus[$idMenu];
                        foreach ($menuObj->reseps as $resep) {
                            if (in_array($resep->id_bahan, $idBahanKurang, true)) {
                                $menuKurang[] = $menuObj->nama_menu;
                                break;
                            }
                        }
                    }
                    $menuKurang = array_unique($menuKurang);
                    $teksMenu = !empty($menuKurang) ? implode(', ', $menuKurang) : 'yang Anda pesan';

                    throw new PesananDitolak("Maaf, stok {$teksMenu} tidak mencukupi. Silakan kurangi pesanan atau pilih menu lain.", $kekurangan);
                }

                throw new PesananDitolak('Stok bahan baku tidak cukup untuk pesanan ini.', $kekurangan);
            }

            $subtotal = 0;
            foreach ($items as $idMenu => $jumlah) {
                $subtotal += $menus[$idMenu]->harga * $jumlah;
            }

            $pesanan = Pesanan::create([
                'id_pengguna'       => $pembuat->getKey(),
                'nomor_meja'        => $nomorMeja,
                'metode_pembayaran' => $metode,
                'status'            => Pesanan::STATUS_BARU,
            ] + $this->hitung($subtotal));

            // Simpan detail pesanan dan kunci harga satuan saat transaksi terjadi
            foreach ($items as $idMenu => $jumlah) {
                DetailPesanan::create([
                    'id_pesanan'   => $pesanan->id_pesanan,
                    'id_menu'      => $idMenu,
                    'jumlah_menu'  => $jumlah,
                    'harga_satuan' => $menus[$idMenu]->harga,
                ]);
            }

            // Potong stok bahan baku di database
            foreach ($kebutuhan as $idBahan => $butuh) {
                $bahan = $bahans[$idBahan];
                $bahan->jumlah_stok = round((float) $bahan->jumlah_stok - round($butuh, 2), 2);
                $bahan->save();
            }

            return $pesanan->load('detailPesanans.menu');
        });
    }

    // Ubah status pesanan secara berjenjang (Baru -> Diproses -> Selesai)
    public function ubahStatus(Pesanan $pesanan, string $statusBaru): Pesanan
    {
        $alur = [
            Pesanan::STATUS_BARU     => Pesanan::STATUS_DIPROSES,
            Pesanan::STATUS_DIPROSES => Pesanan::STATUS_SELESAI,
        ];

        return DB::transaction(function () use ($pesanan, $statusBaru, $alur) {
            $p = Pesanan::lockForUpdate()->findOrFail($pesanan->getKey());

            // Tolak jika alur status melompat atau mundur
            if (($alur[$p->status] ?? null) !== $statusBaru) {
                throw new PesananDitolak("Pesanan berstatus {$p->status} tidak bisa diubah menjadi {$statusBaru}.");
            }

            $p->update(['status' => $statusBaru]);

            return $p;
        });
    }

    // Batalkan pesanan status Baru dan kembalikan stok bahan sesuai resep
    public function batalkan(Pesanan $pesanan): Pesanan
    {
        return DB::transaction(function () use ($pesanan) {
            $p = Pesanan::with('detailPesanans.menu.reseps')
                ->lockForUpdate()->findOrFail($pesanan->getKey());

            if ($p->status !== Pesanan::STATUS_BARU) {
                throw new PesananDitolak('Hanya pesanan berstatus Baru yang bisa dibatalkan.');
            }

            // Hitung seluruh bahan yang harus dipulihkan
            $kembali = [];
            foreach ($p->detailPesanans as $detail) {
                foreach ($detail->menu->reseps as $resep) {
                    $kembali[$resep->id_bahan] = ($kembali[$resep->id_bahan] ?? 0)
                        + (float) $resep->takaran * $detail->jumlah_menu;
                }
            }
            ksort($kembali);

            $bahans = Bahan::whereIn('id_bahan', array_keys($kembali))
                ->orderBy('id_bahan')->lockForUpdate()->get()->keyBy('id_bahan');

            // Kembalikan stok bahan baku
            foreach ($kembali as $idBahan => $jumlah) {
                $bahan = $bahans[$idBahan];
                $bahan->jumlah_stok = round((float) $bahan->jumlah_stok + round($jumlah, 2), 2);
                $bahan->save();
            }

            $p->update(['status' => Pesanan::STATUS_DIBATALKAN]);

            return $p;
        });
    }

    // Gabungkan item menu yang sama dan akumulasi jumlah porsinya
    private function gabungkan(array $items): array
    {
        $hasil = [];

        foreach ($items as $item) {
            $id     = (int) ($item['id_menu'] ?? 0);
            $jumlah = (int) ($item['jumlah'] ?? 0);

            if ($id < 1 || $jumlah < 1) {
                throw new PesananDitolak('Ada item pesanan yang tidak valid.');
            }

            $hasil[$id] = ($hasil[$id] ?? 0) + $jumlah;
        }

        if ($hasil === []) {
            throw new PesananDitolak('Pesanan masih kosong.');
        }

        return $hasil;
    }
}