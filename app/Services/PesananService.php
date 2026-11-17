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

    /** Subtotal -> rincian biaya. Service dan pajak dihitung dari subtotal, dibulatkan ke rupiah. */
    public function hitung(int $subtotal): array
    {
        $service = (int) round($subtotal * self::SERVICE_PERSEN / 100);
        $pajak   = (int) round($subtotal * self::PAJAK_PERSEN / 100);

        return [
            'subtotal'    => $subtotal,
            'service'     => $service,
            'pajak'       => $pajak,
            'total_harga' => $subtotal + $service + $pajak,
        ];
    }

    /**
     * Buat pesanan baru (status Baru) dan langsung potong stok bahan.
     * $items: [['id_menu' => 1, 'jumlah' => 2], ...]
     *
     * @throws PesananDitolak
     */
    public function buat(User $pembuat, int $nomorMeja, string $metode, array $items): Pesanan
    {
        if ($nomorMeja < 1 || $nomorMeja > self::JUMLAH_MEJA) {
            throw new PesananDitolak('Nomor meja tidak valid.');
        }
        if (! in_array($metode, Pesanan::METODE_PEMBAYARAN, true)) {
            throw new PesananDitolak('Metode pembayaran tidak valid.');
        }

        $items = $this->gabungkan($items);

        return DB::transaction(function () use ($pembuat, $nomorMeja, $metode, $items) {
            // Kunci baris pembuat: dua permintaan bersamaan dari pelanggan yang sama dijalankan bergantian
            User::whereKey($pembuat->getKey())->lockForUpdate()->first();

            if ($pembuat->isPelanggan()
                && Pesanan::where('id_pengguna', $pembuat->getKey())
                    ->where('status', Pesanan::STATUS_BARU)->exists()) {
                throw new PesananDitolak('Anda masih punya pesanan berstatus Baru. Tunggu pesanan itu diproses atau batalkan dulu.');
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
                    throw new PesananDitolak("Menu {$menu->nama_menu} belum punya resep.");
                }
            }

            // Kebutuhan tiap bahan = jumlah takaran x porsi, dijumlahkan lintas semua menu di pesanan
            $kebutuhan = [];
            foreach ($items as $idMenu => $jumlah) {
                foreach ($menus[$idMenu]->reseps as $resep) {
                    $kebutuhan[$resep->id_bahan] = ($kebutuhan[$resep->id_bahan] ?? 0)
                        + (float) $resep->takaran * $jumlah;
                }
            }
            ksort($kebutuhan);

            // Kunci baris bahan (urut id supaya dua pesanan bersamaan tidak saling menunggu / deadlock)
            $bahans = Bahan::whereIn('id_bahan', array_keys($kebutuhan))
                ->orderBy('id_bahan')->lockForUpdate()->get()->keyBy('id_bahan');

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

            foreach ($items as $idMenu => $jumlah) {
                DetailPesanan::create([
                    'id_pesanan'   => $pesanan->id_pesanan,
                    'id_menu'      => $idMenu,
                    'jumlah_menu'  => $jumlah,
                    'harga_satuan' => $menus[$idMenu]->harga, // harga saat transaksi, tidak ikut berubah nanti
                ]);
            }

            foreach ($kebutuhan as $idBahan => $butuh) {
                $bahan = $bahans[$idBahan];
                $bahan->jumlah_stok = round((float) $bahan->jumlah_stok - round($butuh, 2), 2);
                $bahan->save();
            }

            return $pesanan->load('detailPesanans.menu');
        });
    }

    /**
     * Alur status: baru -> diproses -> selesai.
     *
     * @throws PesananDitolak
     */
    public function ubahStatus(Pesanan $pesanan, string $statusBaru): Pesanan
    {
        $alur = [
            Pesanan::STATUS_BARU     => Pesanan::STATUS_DIPROSES,
            Pesanan::STATUS_DIPROSES => Pesanan::STATUS_SELESAI,
        ];

        return DB::transaction(function () use ($pesanan, $statusBaru, $alur) {
            // Baca ulang dengan kunci: klik ganda atau dua kasir sekaligus tidak bisa melompati alur
            $p = Pesanan::lockForUpdate()->findOrFail($pesanan->getKey());

            if (($alur[$p->status] ?? null) !== $statusBaru) {
                throw new PesananDitolak("Pesanan berstatus {$p->status} tidak bisa diubah menjadi {$statusBaru}.");
            }

            $p->update(['status' => $statusBaru]);

            return $p;
        });
    }

    /**
     * Batalkan pesanan (hanya yang masih Baru) dan kembalikan stok bahan.
     *
     * @throws PesananDitolak
     */
    public function batalkan(Pesanan $pesanan): Pesanan
    {
        return DB::transaction(function () use ($pesanan) {
            $p = Pesanan::with('detailPesanans.menu.reseps')
                ->lockForUpdate()->findOrFail($pesanan->getKey());

            if ($p->status !== Pesanan::STATUS_BARU) {
                throw new PesananDitolak('Hanya pesanan berstatus Baru yang bisa dibatalkan.');
            }

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

            foreach ($kembali as $idBahan => $jumlah) {
                $bahan = $bahans[$idBahan];
                $bahan->jumlah_stok = round((float) $bahan->jumlah_stok + round($jumlah, 2), 2);
                $bahan->save();
            }

            $p->update(['status' => Pesanan::STATUS_DIBATALKAN]);

            return $p;
        });
    }

    /** Gabungkan menu yang sama dan pastikan tiap item valid: hasilnya [id_menu => jumlah]. */
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