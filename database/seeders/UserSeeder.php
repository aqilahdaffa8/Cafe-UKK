<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'nama' => 'pa manajer',
            'no_telp' => '081234567890',
            'password' => Hash::make('password'),
            'peran' => 'manajer',
        ]);

        DB::table('users')->insert([
            'nama' => 'pa kasir',
            'no_telp' => '081234567891',
            'password' => Hash::make('password'),
            'peran' => 'kasir',
        ]);
    }
}
