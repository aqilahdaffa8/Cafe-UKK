<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Bahan;

class StokDemoSeeder extends Seeder
{
    private const SATUAN = [
        'g' => ['g', 1],
        'kg' => ['g', 1000],
        'ml' => ['ml', 1],
        'liter' => ['ml', 1000],
        'pcs' => ['pcs', 1],
    ];

    public function run(): void
    {
        $path = database_path('data/seeder/seed_stok_demo.csv');

        if (!file_exists($path)) {
            $this->command->warn("File not found: $path");
            return;
        }

        $baris  = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_map('trim', array_shift($baris));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF");

        foreach ($baris as $data) {
            if (count($data) !== count($header)) {
                $this->command->warn('Baris dilewati: jumlah kolom tidak sesuai.');
                continue;
            }

            $row    = array_combine($header, $data);
            $nama   = trim($row['nama_bahan']);
            $jumlah = trim($row['jumlah']);
            $satuan = strtolower(trim($row['satuan']));

            if (! is_numeric($jumlah) || ! isset(self::SATUAN[$satuan])) {
                $this->command->warn("Baris '{$nama}' dilewati: jumlah atau satuan tidak valid.");
                continue;
            }

            $bahan = Bahan::where('nama_bahan', $nama)->first();

            if (! $bahan) {
                $this->command->warn("Bahan '{$nama}' tidak ditemukan. Jalankan impor CSV terlebih dahulu.");
                continue;
            }

            [$satuanDasar, $pengali] = self::SATUAN[$satuan];

            if ($satuanDasar !== $bahan->satuan) {
                $this->command->warn("Satuan '{$satuan}' tidak cocok dengan bahan '{$nama}' ({$bahan->satuan}).");
                continue;
            }

            $bahan->jumlah_stok = (float) $jumlah * $pengali;
            $bahan->save();

            $this->command->info("Stok demo: {$nama} diset ke {$bahan->jumlah_stok} {$bahan->satuan}");
        }
        
    }
}
