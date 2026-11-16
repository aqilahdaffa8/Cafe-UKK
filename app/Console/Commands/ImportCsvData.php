<?php

namespace App\Console\Commands;

use App\Models\Bahan;
use App\Models\Menu;
use App\Models\Resep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportCsvData extends Command
{
    protected $signature = 'app:import-csv {--path=database/data/csv : Folder tempat file CSV berada}';

    protected $description = 'Mengimpor data awal bahan, menu, dan resep dari file CSV ke database';

    // satuan di CSV => [satuan dasar, pengali ke satuan dasar]
    private const SATUAN = [
        'g'     => ['g', 1],
        'kg'    => ['g', 1000],
        'ml'    => ['ml', 1],
        'liter' => ['ml', 1000],
        'pcs'   => ['pcs', 1],
    ];

    private const KATEGORI = ['makanan', 'minuman', 'lainnya'];

    public function handle(): int
    {
        $folder = base_path($this->option('path'));

        $berkas = [
            'bahan' => $folder . '/bahan.csv',
            'menu'  => $folder . '/menu.csv',
            'resep' => $folder . '/resep.csv',
        ];

        foreach ($berkas as $nama => $path) {
            if (! File::exists($path)) {
                $this->error("File {$nama}.csv tidak ditemukan di: {$path}");
                return self::FAILURE;
            }
        }

        $this->info("Memulai impor CSV dari: {$folder}");
        $this->newLine();

        $laporan = [
            'bahan' => ['berhasil' => 0, 'ada' => 0, 'dilewati' => 0],
            'menu'  => ['berhasil' => 0, 'ada' => 0, 'dilewati' => 0],
            'resep' => ['berhasil' => 0, 'ada' => 0, 'dilewati' => 0],
            'typo'  => [],
            'pesan' => [],
        ];

        try {
            DB::transaction(function () use ($berkas, &$laporan) {
                $this->imporBahan($berkas['bahan'], $laporan);
                $petaMenu = $this->imporMenu($berkas['menu'], $laporan);
                $this->imporResep($berkas['resep'], $petaMenu, $laporan);
            });
        } catch (\Throwable $e) {
            $this->error('Impor dibatalkan karena kesalahan tak terduga: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->tampilkanLaporan($laporan);

        return self::SUCCESS;
    }

    private function imporBahan(string $path, array &$laporan): void
    {
        $this->info('--> Mengimpor Bahan...');

        $kolom = ['kode', 'nama_bahan', 'stok_awal', 'satuan', 'harga_per_satuan'];
        $kodeDiBerkas = [];
        $namaDiBerkas = [];

        foreach ($this->bacaCsv($path, 'bahan', $kolom, $laporan) as $baris => $row) {
            $kode   = trim($row['kode']);
            $nama   = trim($row['nama_bahan']);
            $satuan = strtolower(trim($row['satuan']));
            $stok   = trim($row['stok_awal']);
            $harga  = trim($row['harga_per_satuan']);

            if ($kode === '' || $nama === '') {
                $this->lewati($laporan, 'bahan', $baris, 'kode atau nama bahan kosong.');
                continue;
            }
            if (! isset(self::SATUAN[$satuan])) {
                $this->lewati($laporan, 'bahan', $baris, "satuan '{$satuan}' tidak dikenal.");
                continue;
            }
            if (! is_numeric($stok) || (float) $stok < 0) {
                $this->lewati($laporan, 'bahan', $baris, "stok awal '{$stok}' tidak valid.");
                continue;
            }
            if (! is_numeric($harga) || (float) $harga < 0) {
                $this->lewati($laporan, 'bahan', $baris, "harga '{$harga}' tidak valid.");
                continue;
            }

            $kunciNama = $this->normalisasiNama($nama);
            if (isset($kodeDiBerkas[$kode]) || isset($namaDiBerkas[$kunciNama])) {
                $this->lewati($laporan, 'bahan', $baris, "data ganda di berkas (kode '{$kode}' atau nama '{$nama}').");
                continue;
            }
            $kodeDiBerkas[$kode]      = true;
            $namaDiBerkas[$kunciNama] = true;

            // Bahan yang sudah ada tidak ditimpa (stok dan harga bisa sudah berubah)
            if (Bahan::where('kode', $kode)->exists()) {
                $laporan['bahan']['ada']++;
                continue;
            }
            if (Bahan::where('nama_bahan', $nama)->exists()) {
                $this->lewati($laporan, 'bahan', $baris, "nama '{$nama}' sudah dipakai bahan lain.");
                continue;
            }

            [$satuanDasar, $pengali] = self::SATUAN[$satuan];
            $stokDasar  = (float) $stok * $pengali;
            $hargaDasar = (float) $harga / $pengali;

            $bahan = new Bahan();
            $bahan->kode           = $kode;
            $bahan->nama_bahan     = $nama;
            $bahan->satuan         = $satuanDasar;
            $bahan->jumlah_stok    = $stokDasar;
            $bahan->batas_minimum  = max(1, (int) round($stokDasar * 0.1));
            $bahan->harga_terakhir = $hargaDasar;
            $bahan->tanggal_harga  = now()->toDateString();
            $bahan->save();

            $laporan['bahan']['berhasil']++;
        }
    }

    /**
     * Mengembalikan peta kode menu => model Menu, dipakai untuk menghubungkan resep.
     */
    private function imporMenu(string $path, array &$laporan): array
    {
        $this->info('--> Mengimpor Menu...');

        $kolom = ['kode', 'nama_menu', 'kategori', 'harga_jual'];
        $peta = [];
        $namaDiBerkas = [];

        foreach ($this->bacaCsv($path, 'menu', $kolom, $laporan) as $baris => $row) {
            $kode     = trim($row['kode']);
            $nama     = trim($row['nama_menu']);
            $kategori = strtolower(trim($row['kategori']));
            $harga    = trim($row['harga_jual']);

            if ($kode === '' || $nama === '') {
                $this->lewati($laporan, 'menu', $baris, 'kode atau nama menu kosong.');
                continue;
            }
            if (! in_array($kategori, self::KATEGORI, true)) {
                $this->lewati($laporan, 'menu', $baris, "kategori '{$kategori}' tidak dikenal.");
                continue;
            }
            if (! is_numeric($harga) || (float) $harga <= 0) {
                $this->lewati($laporan, 'menu', $baris, "harga jual '{$harga}' tidak valid.");
                continue;
            }

            $kunciNama = $this->normalisasiNama($nama);
            if (isset($peta[$kode]) || isset($namaDiBerkas[$kunciNama])) {
                $this->lewati($laporan, 'menu', $baris, "data ganda di berkas (kode '{$kode}' atau nama '{$nama}').");
                continue;
            }
            $namaDiBerkas[$kunciNama] = true;

            $menu = Menu::where('nama_menu', $nama)->first();

            if ($menu) {
                $laporan['menu']['ada']++;
            } else {
                $menu = new Menu();
                $menu->nama_menu = $nama;
                $menu->kategori  = $kategori;
                $menu->harga     = (int) round((float) $harga);
                $menu->aktif     = true;
                $menu->save();

                $laporan['menu']['berhasil']++;
            }

            $peta[$kode] = $menu;
        }

        return $peta;
    }

    private function imporResep(string $path, array $petaMenu, array &$laporan): void
    {
        $this->info('--> Mengimpor Resep...');

        $kolom = ['kode_menu', 'nama_bahan', 'takaran', 'satuan'];

        $petaBahan = Bahan::all()
            ->keyBy(fn (Bahan $b) => $this->normalisasiNama($b->nama_bahan))
            ->all();

        $pasanganDiBerkas = [];

        foreach ($this->bacaCsv($path, 'resep', $kolom, $laporan) as $baris => $row) {
            $kodeMenu  = trim($row['kode_menu']);
            $namaBahan = trim($row['nama_bahan']);
            $takaran   = trim($row['takaran']);
            $satuan    = strtolower(trim($row['satuan']));

            if (! isset($petaMenu[$kodeMenu])) {
                $this->lewati($laporan, 'resep', $baris, "menu '{$kodeMenu}' tidak ditemukan.");
                continue;
            }
            if (! is_numeric($takaran) || (float) $takaran <= 0) {
                $this->lewati($laporan, 'resep', $baris, "takaran '{$takaran}' tidak valid.");
                continue;
            }
            if (! isset(self::SATUAN[$satuan])) {
                $this->lewati($laporan, 'resep', $baris, "satuan '{$satuan}' tidak dikenal.");
                continue;
            }

            $bahan = $this->cariBahan($namaBahan, $petaBahan, $baris, $laporan);

            if (! $bahan) {
                $this->lewati($laporan, 'resep', $baris, "bahan '{$namaBahan}' tidak ditemukan (menu {$kodeMenu}).");
                continue;
            }

            [$satuanDasar, $pengali] = self::SATUAN[$satuan];

            if ($satuanDasar !== $bahan->satuan) {
                $this->lewati($laporan, 'resep', $baris, "satuan '{$satuan}' tidak cocok dengan bahan '{$bahan->nama_bahan}' ({$bahan->satuan}).");
                continue;
            }

            $menu = $petaMenu[$kodeMenu];

            $pasangan = $menu->id_menu . '-' . $bahan->id_bahan;
            if (isset($pasanganDiBerkas[$pasangan])) {
                $this->lewati($laporan, 'resep', $baris, "resep ganda ({$menu->nama_menu} dan {$bahan->nama_bahan}).");
                continue;
            }
            $pasanganDiBerkas[$pasangan] = true;

            if (Resep::where('id_menu', $menu->id_menu)->where('id_bahan', $bahan->id_bahan)->exists()) {
                $laporan['resep']['ada']++;
                continue;
            }

            $resep = new Resep();
            $resep->id_menu  = $menu->id_menu;
            $resep->id_bahan = $bahan->id_bahan;
            $resep->takaran  = (float) $takaran * $pengali;
            $resep->save();

            $laporan['resep']['berhasil']++;
        }
    }

    /**
     * Membaca CSV. Baris dengan jumlah kolom salah dilewati dan dilaporkan.
     * Hasil: [nomor baris di berkas => data].
     */
    private function bacaCsv(string $path, string $jenis, array $kolomWajib, array &$laporan): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Tidak dapat membuka file {$jenis}.csv");
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException("File {$jenis}.csv kosong.");
        }

        $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        $header    = array_map('trim', $header);

        $kurang = array_diff($kolomWajib, $header);
        if ($kurang) {
            fclose($handle);
            throw new \RuntimeException("File {$jenis}.csv kekurangan kolom: " . implode(', ', $kurang));
        }

        $rows  = [];
        $baris = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $baris++;

            if ($data === [null]) {
                continue; // baris kosong
            }

            if (count($data) !== count($header)) {
                $this->lewati($laporan, $jenis, $baris, 'jumlah kolom tidak sesuai.');
                continue;
            }

            $rows[$baris] = array_combine($header, $data);
        }

        fclose($handle);

        return $rows;
    }

    private function cariBahan(string $namaInput, array $petaBahan, int $baris, array &$laporan): ?Bahan
    {
        $kunci = $this->normalisasiNama($namaInput);

        // 1. Cocok persis
        if (isset($petaBahan[$kunci])) {
            return $petaBahan[$kunci];
        }

        // 2. Cocok mirip (typo), minimal 90% dan hanya jika satu kandidat terbaik
        $skor = [];
        foreach (array_keys($petaBahan) as $nama) {
            similar_text($kunci, (string) $nama, $persen);
            $skor[$nama] = $persen;
        }

        if (! $skor) {
            return null;
        }

        arsort($skor);
        $namaTerbaik   = array_key_first($skor);
        $persenTerbaik = $skor[$namaTerbaik];

        if ($persenTerbaik < 90.0) {
            return null;
        }

        $kembar = array_filter($skor, fn ($p) => abs($p - $persenTerbaik) < 0.01);
        if (count($kembar) > 1) {
            return null; // ambigu, tidak ditebak
        }

        $bahan = $petaBahan[$namaTerbaik];

        $laporan['typo'][] = "Baris {$baris}: '{$namaInput}' dicocokkan ke '{$bahan->nama_bahan}' (" . round($persenTerbaik, 1) . '%)';

        return $bahan;
    }

    private function normalisasiNama(string $nama): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($nama)));
    }

    private function lewati(array &$laporan, string $jenis, int $baris, string $alasan): void
    {
        $laporan[$jenis]['dilewati']++;
        $laporan['pesan'][] = "[{$jenis}] Baris {$baris}: {$alasan}";
    }

    private function tampilkanLaporan(array $laporan): void
    {
        $this->newLine();
        $this->info('=========================================');
        $this->info('           RINGKASAN IMPOR CSV           ');
        $this->info('=========================================');

        $this->table(
            ['Data', 'Berhasil', 'Sudah ada', 'Dilewati'],
            [
                ['Bahan', $laporan['bahan']['berhasil'], $laporan['bahan']['ada'], $laporan['bahan']['dilewati']],
                ['Menu',  $laporan['menu']['berhasil'],  $laporan['menu']['ada'],  $laporan['menu']['dilewati']],
                ['Resep', $laporan['resep']['berhasil'], $laporan['resep']['ada'], $laporan['resep']['dilewati']],
            ]
        );

        if ($laporan['typo']) {
            $this->newLine();
            $this->warn('Typo nama bahan yang dikoreksi (kemiripan >= 90%):');
            foreach ($laporan['typo'] as $pesan) {
                $this->line(" - {$pesan}");
            }
        }

        if ($laporan['pesan']) {
            $this->newLine();
            $this->warn('Baris yang dilewati:');
            foreach ($laporan['pesan'] as $pesan) {
                $this->line(" - {$pesan}");
            }
        } else {
            $this->newLine();
            $this->info('Tidak ada baris yang dilewati.');
        }

        $log  = 'LOG IMPOR CSV - ' . now()->toDateTimeString() . PHP_EOL . PHP_EOL;
        foreach (['bahan' => 'Bahan', 'menu' => 'Menu', 'resep' => 'Resep'] as $k => $label) {
            $log .= "{$label}: berhasil {$laporan[$k]['berhasil']}, sudah ada {$laporan[$k]['ada']}, dilewati {$laporan[$k]['dilewati']}" . PHP_EOL;
        }
        $log .= PHP_EOL . 'TYPO DIKOREKSI:' . PHP_EOL . implode(PHP_EOL, $laporan['typo']) . PHP_EOL;
        $log .= PHP_EOL . 'BARIS DILEWATI:' . PHP_EOL . implode(PHP_EOL, $laporan['pesan']) . PHP_EOL;

        File::put(storage_path('app/laporan_impor.txt'), $log);

        $this->newLine();
        $this->line('Laporan tersimpan di: storage/app/laporan_impor.txt');
    }
}