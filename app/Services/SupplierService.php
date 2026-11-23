<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    // Ambil harga bahan dari API supplier pusat dengan batas waktu 5 detik dan fallback harga terakhir
    public function ambilHargaBahan(): array
    {
        $url    = config('services.supplier.url');
        $apiKey = config('services.supplier.api_key');

        if (empty($url) || empty($apiKey)) {
            return [
                'sukses' => false,
                'pesan'  => 'Konfigurasi URL atau API Key supplier belum diatur.',
                'data'   => [],
            ];
        }

        try {
            $endpoint = str_ends_with(rtrim($url, '/'), '/harga-bahan')
                ? rtrim($url, '/')
                : rtrim($url, '/') . '/harga-bahan';

            // Panggil API dengan batas timeout 5 detik agar tidak memblokir aplikasi jika lambat
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept'    => 'application/json',
                ])
                ->get($endpoint);

            // Fallback jika API key salah (HTTP 401)
            if ($response->status() === 401) {
                return [
                    'sukses' => false,
                    'pesan'  => 'Kunci akses supplier tidak dikenali. Sistem tetap memakai harga terakhir yang tersimpan.',
                    'data'   => [],
                ];
            }

            // Fallback jika respons server bukan 200 OK
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
            // Fallback jika koneksi gagal, timeout, atau internet putus
            Log::warning('Gagal terhubung ke API supplier: ' . $e->getMessage());

            return [
                'sukses' => false,
                'pesan'  => 'Gagal menghubungi server supplier karena gangguan koneksi. Sistem tetap memakai harga terakhir yang tersimpan.',
                'data'   => [],
            ];
        }
    }
}   