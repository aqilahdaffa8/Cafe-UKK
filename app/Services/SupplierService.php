<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    /**
     * Mengambil daftar harga bahan terbaru dari API supplier pusat.
     *
     * @return array [
     *    'sukses'    => bool,
     *    'pesan'     => string,
     *    'diperbarui'=> string|null,
     *    'data'      => array
     * ]
     */
    public function ambilHargaBahan(): array
    {
        $url    = config('services.supplier.url');
        $apiKey = config('services.supplier.api_key');

        // Validasi konfigurasi
        if (empty($url) || empty($apiKey)) {
            return [
                'sukses' => false,
                'pesan'  => 'Konfigurasi URL atau API Key supplier belum diatur.',
                'data'   => [],
            ];
        }

        try {
            // Panggil API dengan timeout 5 detik
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept'    => 'application/json',
                ])
                ->get($url);

            // Jika API Key ditolak (401)
            if ($response->status() === 401) {
                return [
                    'sukses' => false,
                    'pesan'  => 'Kunci akses supplier tidak dikenali. Sistem tetap memakai harga terakhir yang tersimpan.',
                    'data'   => [],
                ];
            }

            // Jika respons bukan 200 OK
            if (! $response->successful()) {
                return [
                    'sukses' => false,
                    'pesan'  => 'Layanan data supplier sedang dalam pemeliharaan. Sistem tetap memakai harga terakhir yang tersimpan.',
                    'data'   => [],
                ];
            }

            $json = $response->json();

            return [
                'sukses'     => true,
                'pesan'      => 'Berhasil terhubung ke supplier.',
                'diperbarui' => $json['diperbarui'] ?? now()->toDateString(),
                'data'       => $json['data'] ?? [],
            ];

        } catch (\Exception $e) {
            // Tangkap timeout, server mati, internet putus, dll.
            Log::warning('Gagal terhubung ke API supplier: ' . $e->getMessage());

            return [
                'sukses' => false,
                'pesan'  => 'Gagal menghubungi server supplier karena gangguan koneksi. Sistem tetap memakai harga terakhir yang tersimpan.',
                'data'   => [],
            ];
        }
    }
}   