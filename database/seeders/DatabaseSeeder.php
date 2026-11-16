<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);

        // Impor data awal CSV (bahan, menu, resep)
        $this->command->call('app:import-csv');

        // Keadaan awal untuk pengujian, dijalankan SESUDAH impor
        $this->call(StokDemoSeeder::class);
    }
}
