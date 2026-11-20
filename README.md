# Sistem Informasi Manajemen dan Pemesanan Kafe (cafe-ukk)

> **Status dokumen:** Dokumen implementasi final sistem kafe (Backend Laravel & Integrasi Mobile). Berisi seluruh arsitektur, basis data, aturan bisnis, dan panduan pengujian UKK RPL 2026/2027.

---

## 1. Ringkasan Sistem

**Nama aplikasi:** Cafe UKK (nama repositori/proyek: `cafe-ukk` / "Kedai Kopi Senja")

**Deskripsi singkat:** Sistem informasi manajemen dan pemesanan kafe. Backend dan antarmuka web untuk staf dibangun dengan Laravel, terintegrasi dengan layanan aplikasi pelanggan (*mobile* berbasis Flutter) melalui RESTful API.

**Tujuan dan fungsi utama:**
- Mengelola data master kafe: bahan baku, menu, dan resep (komposisi bahan per menu).
- Mencatat stok masuk bahan baku dan memantau batas minimum stok.
- Memproses pesanan dengan validasi ketersediaan stok bahan berdasarkan takaran resep.
- Menghitung struk secara otomatis: subtotal, service charge (5%), pajak (10%), dan total bayar.
- Menyajikan laporan HPP (harga pokok penjualan) dan margin keuntungan untuk manajer.
- Mengintegrasikan harga bahan dari layanan Supplier eksternal dengan mekanisme cadangan (*fallback*) harga terakhir.

**Masalah yang diselesaikan:**
- Pesanan ditolak secara otomatis jika stok bahan baku tidak mencukupi resep, mencegah pesanan yang tidak dapat dipenuhi.
- Stok bahan berkurang dan kembali otomatis sesuai takaran resep dalam transaksi database (termasuk saat pesanan dibatalkan), menghilangkan pencatatan manual yang rawan selisih.
- Perhitungan biaya, HPP, margin, dan struk konsisten dan dapat ditelusuri secara transparan.

**Jenis pengguna:**

| Peran | Wewenang |
|---|---|
| Manajer | Mengelola menu, resep, bahan baku, stok masuk, sinkronisasi harga supplier, dan melihat laporan HPP/margin. |
| Kasir | Membuat pesanan, mengubah status pesanan berjenjang (Baru → Diproses → Selesai), membatalkan pesanan berstatus Baru, dan mereset password pelanggan. |
| Pelanggan | Mendaftar dan login lewat aplikasi mobile/API, memesan menu dengan memilih nomor meja, mengganti password, dan memantau status pesanan secara *real-time*. |

**Alur kerja dari pemesanan hingga selesai:**
1. Pesanan dibuat oleh pelanggan (aplikasi mobile/API) atau oleh kasir di web.
2. Sistem memvalidasi: nomor meja 1 sampai 10, status menu aktif, dan kecukupan stok seluruh bahan baku berdasarkan resep.
3. Jika valid, pesanan tersimpan berstatus `baru`, stok bahan langsung berkurang, dan total biaya dihitung.
4. Kasir memproses pesanan: mengubah status dari `baru` menjadi `diproses`, lalu `selesai`.
5. Pesanan berstatus `baru` dapat dibatalkan; status menjadi `dibatalkan` dan stok bahan otomatis dikembalikan utuh.
6. Pelanggan memantau status pengerjaan secara langsung dan melihat struk digital.

---

## 2. Teknologi yang Digunakan

| Komponen | Teknologi | Status | Alasan singkat |
|---|---|---|---|
| Bahasa backend | PHP 8.2 / 8.3 | Ditentukan | Persyaratan minimum Laravel 11/13. |
| Framework backend | Laravel 11/13 | Ditentukan | Menyediakan ORM Eloquent, migration, seeder, middleware, dan pengujian bawaan. |
| Database relasional | MySQL, database `cafe_ukk` | Ditentukan | Mendukung foreign key dan transaksi atomik (`DB::transaction`) untuk aturan stok. |
| REST API | Laravel API + Laravel Sanctum (token) | Ditentukan | Autentikasi Bearer Token untuk aplikasi mobile pelanggan. |
| Frontend web staf | Blade + Tailwind CSS (Vite) | Ditentukan | Antarmuka kasir dan manajer kafe yang responsif dan cepat. |
| Aplikasi Mobile | Flutter 3.x (Dart) | Ditentukan | Aplikasi mobile pemesanan pelanggan di folder `pengerjaan/app_cafe_ukk`. |
| Package penting | `laravel/sanctum` (autentikasi API token); PHPUnit (pengujian bawaan Laravel) | Ditentukan | Mendukung endpoint pelanggan dan pengujian otomatis. |

---

## 3. Cara Menjalankan Server

**Persyaratan:**
- PHP 8.2 / 8.3 dan Composer.
- MySQL / MariaDB.
- Git.
- Node.js (v18+) dan npm (untuk kompilasi aset frontend staf: Tailwind CSS & Vite).

**Langkah Instalasi:**

```bash
# 1. Masuk ke direktori backend
cd pengerjaan/cafe-ukk

# 2. Instal dependency PHP
composer install

# 3. Instal dependency JavaScript & build aset CSS
npm install
npm run build

# 4. Siapkan environment
cp .env.example .env
php artisan key:generate
```

**Konfigurasi database:** buat database `cafe_ukk` di MySQL, lalu sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cafe_ukk
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 5. Jalankan migration dan seeder (termasuk impor data CSV awal)
php artisan migrate:fresh --seed

# 6. (Opsional) Jalankan impor CSV manual jika diperlukan:
php artisan app:import-csv

# 7. Jalankan server backend
php artisan serve
```

- **Seeder yang tersedia:** 
  - `UserSeeder`: Akun default Manajer, Kasir, dan Pelanggan demo.
  - `ImportCsvData`: Mengimpor data master bahan, menu, dan resep dari `database/data/csv/`.
  - `StokDemoSeeder`: Mengisi stok awal bahan baku untuk keperluan pengujian.
- **Alamat akses aplikasi web:** `http://127.0.0.1:8000`
- **Menjalankan pengujian otomatis:**
  ```bash
  php artisan test
  ```

---

## 4. Aplikasi Mobile Pelanggan (Flutter)

Aplikasi mobile untuk pelanggan tersedia di folder berdampingan: `../app_cafe_ukk`.

- **Lokasi Proyek Mobile:** `pengerjaan/app_cafe_ukk/`
- **Cara Menjalankan:**
  ```bash
  cd ../app_cafe_ukk
  flutter pub get
  flutter run
  ```
- **Cara Membangun APK:**
  ```bash
  flutter build apk --release
  # Output APK berada di: build/app/outputs/flutter-apk/app-release.apk
  ```
- **Pengaturan Alamat Server / API:**
  Diatur pada file `lib/constant.dart`:
  - Emulator Android: `http://10.0.2.2:8000/api`
  - Browser / Desktop: `http://127.0.0.1:8000/api`
  - HP Fisik (Satu Wi-Fi): `http://<IP_LAPTOP>:8000/api`
- **Menjalankan Server Laravel untuk Akses HP Fisik:**
  ```bash
  php artisan serve --host=0.0.0.0 --port=8000
  ```

---

## 5. Akun Pengujian

| Peran | Nama | Nomor HP (`no_telp`) | Password | Hak Akses Utama |
|---|---|---|---|---|
| Manajer | pa manajer | `081234567890` | `password` | Kelola master bahan, menu, resep, stok masuk, sync supplier, laporan HPP. |
| Kasir | pa kasir | `081234567891` | `password` | Terima pesanan, update status (Baru/Proses/Selesai), reset password pelanggan. |
| Pelanggan | pa pelanggan | `08008000800` | `password` | Akses mobile, pesan menu, pilih meja via dropdown, ganti password, struk digital. |

*Catatan: Pelanggan baru juga dapat mendaftar mandiri secara langsung melalui form registrasi di aplikasi mobile.*
Akun di atas khusus pengujian, login memakai **nomor telepon (`no_telp`)**.

---

## 6. Letak Berkas dan Struktur Proyek

```text
cafe-ukk/
├── app/
│   ├── Console/Commands/
│   │   └── ImportCsvData.php       command impor CSV (bahan, menu, resep)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Manajer/            BahanController, MenuController, ResepController, StokMasukController, LaporanController
│   │   │   ├── Kasir/              PesananController, PelangganController (reset password)
│   │   │   ├── Api/                AuthController, MenuController, PesananController
│   │   │   └── Auth/               WebLoginController, PasswordController
│   │   ├── Middleware/
│   │   │   └── CheckRole.php       pembatasan hak akses berdasarkan peran pengguna
│   │   └── Requests/               RegisterRequest, LoginRequest
│   ├── Models/                     User, Bahan, Menu, Resep, StokMasuk, Pesanan, DetailPesanan
│   └── Services/
│       ├── PesananService.php      validasi stok resep, hitung struk, transaksi atomik, pembatalan
│       └── SupplierService.php     integrasi API supplier, timeout 5 detik, mekanisme fallback
├── bootstrap/app.php               konfigurasi middleware alias 'role'
├── database/
│   ├── data/csv/                   bahan.csv, menu.csv, resep.csv (data awal)
│   ├── migrations/                 7 tabel beserta relasi foreign key
│   └── seeders/                    UserSeeder, StokDemoSeeder, DatabaseSeeder
├── routes/
│   ├── web.php                     rute web staf (manajer dan kasir)
│   └── api.php                     rute RESTful API pelanggan mobile
└── tests/Feature/                  pengujian peran dan fitur sistem
```

---

## 7. Keputusan Implementasi dan Aturan Bisnis

### 7.1 Struktur data

| Keputusan | Penjelasan | Status |
|---|---|---|
| Tujuh tabel | `users`, `bahans`, `menus`, `reseps`, `stok_masuks`, `pesanans`, `detail_pesanans`. | Ditentukan |
| Primary key | Memakai nama kolom khusus (`id_pengguna`, `id_bahan`, `id_menu`, `id_resep`, `id_stok_masuk`, `id_pesanan`, `id_detail_pesanan`) bertipe `bigint unsigned`. Setiap model mendeklarasikan `$primaryKey`. | Ditentukan |
| Tipe data stok | `jumlah_stok`, `batas_minimum`, `takaran`, dan `jumlah` (stok masuk) bertipe **decimal** agar mendukung takaran pecahan/desimal. Harga, subtotal, dan nomor meja bertipe integer. | Ditentukan |
| Enum | Peran (`manajer`, `kasir`, `pelanggan`), satuan (`g`, `ml`, `pcs`), status pesanan (`baru`, `diproses`, `selesai`, `dibatalkan`), metode pembayaran (`tunai`, `qris`). | Ditentukan |
| Kolom autentikasi pengguna | `nama` dan `email` nullable; `no_telp` unik dan wajib; `wajib_ganti_password` default `false`. | Ditentukan |
| Aturan hapus (`onDelete`) | `reseps.id_menu` dan `detail_pesanans.id_pesanan`: cascade. `detail_pesanans.id_menu`, `pesanans.id_pengguna`, `reseps.id_bahan`, `stok_masuks.id_bahan`, dan `stok_masuks.id_pengguna`: restrict, agar riwayat penjualan dan stok tidak hilang. Menu yang tidak dijual dinonaktifkan (kolom `aktif`), bukan dihapus fisik. | Ditentukan |

### 7.2 Autentikasi, password, dan hak akses

| Keputusan | Penjelasan | Status |
|---|---|---|
| Identitas login | Nomor telepon (`no_telp`) bersama password, untuk pelanggan (API). | Ditentukan |
| Identitas login staf | Email (`email`) bersama password, untuk staf (kasir dan manajer di web). | Ditentukan |
| Penyimpanan password | Selalu di-hash satu arah (`Hash::make` pada seeder dan cast `hashed` pada model). | Ditentukan |
| Peran saat registrasi | Akun yang mendaftar lewat jalur pelanggan dipaksa berperan `pelanggan` di sisi server. Input `peran` dari request diabaikan. | Ditentukan |
| Pembatasan hak akses | Middleware `CheckRole` dengan alias `role`, dipakai di route, misalnya `role:manajer` atau `role:kasir`. Tidak login ditolak (401/redirect), peran tidak sesuai ditolak (403). | Ditentukan |
| Autentikasi API pelanggan | Token melalui Laravel Sanctum (`createToken`), disimpan pada `shared_preferences` di aplikasi mobile. | Ditentukan |
| Reset password oleh kasir | Kasir mereset password pelanggan ke password sementara, kolom `wajib_ganti_password` diset `true`. Saat login di mobile, pelanggan wajib langsung diarahkan ke form ganti password baru. | Ditentukan |
| Format validasi nomor HP | Wajib diawali `08` atau `+62` (dinormalisasi otomatis ke `08`), panjang 10 sampai 13 digit angka murni, dan harus unik (`RegisterRequest.php`). | Ditentukan |
| Aturan pembuatan password | Minimal 8 karakter, wajib string, dan wajib konfirmasi (`password_confirmation`) yang cocok. | Ditentukan |

### 7.3 Pesanan, stok, dan perhitungan keuangan

| Keputusan | Penjelasan | Status |
|---|---|---|
| Validasi pemesanan | Nomor meja bilangan bulat 1 sampai 10. Pelanggan hanya boleh memiliki maksimal 1 pesanan aktif berstatus `baru`. | Ditentukan |
| Pembuat pesanan | Kolom `id_pengguna` pada pesanan menyimpan pembuat pesanan (pelanggan atau kasir). | Ditentukan |
| Validasi stok resep | Kebutuhan bahan = jumlah porsi dikali takaran resep, dijumlahkan per bahan. Jika ada bahan yang kurang, seluruh pesanan ditolak dan tidak ada data yang berubah. | Ditentukan |
| Konsistensi data | Penyimpanan pesanan, detail, dan pengurangan stok dibungkus dalam transaksi database atomik (`DB::transaction`). | Ditentukan |
| Perubahan status | Berjenjang: `baru` → `diproses` → `selesai`. Perubahan status melompat atau mundur ditolak. | Ditentukan |
| Pembatalan dan stok | Hanya pesanan berstatus `baru` yang dapat dibatalkan. Status menjadi `dibatalkan` dan seluruh stok bahan baku otomatis dikembalikan sesuai resep dalam transaksi database. | Ditentukan |
| Perhitungan struk | Subtotal = jumlah (harga satuan x jumlah). Service charge = 5% dari subtotal. Pajak = 10% dari (subtotal + service charge). Total = subtotal + service + pajak. | Ditentukan |
| Pembulatan | Hasil perhitungan service charge dan pajak dibulatkan ke bawah/integer terdekat (`round` / cast integer) agar nominal selalu bulat dalam satuan Rupiah. | Ditentukan |
| Harga jual pada struk lama | Harga saat transaksi disalin ke `detail_pesanans.harga_satuan`, sehingga perubahan harga menu master tidak mengubah data struk historis. | Ditentukan |
| Status menu otomatis "Habis" | Menu otomatis berlabel `HABIS` dan tidak dapat dipesan apabila salah satu bahan baku dalam resepnya memiliki sisa stok lebih kecil dari takaran yang dibutuhkan. | Ditentukan |

### 7.4 HPP, margin, dan Supplier

| Keputusan | Penjelasan | Status |
|---|---|---|
| Perhitungan HPP | HPP per menu = jumlah dari (takaran bahan x `harga_terakhir` bahan) untuk seluruh komposisi bahan pada resep. | Ditentukan |
| Perhitungan margin | Margin keuntungan = harga jual menu dikurangi HPP. | Ditentukan |
| Satuan harga bahan | Kolom `harga_terakhir` pada tabel `bahans` menyimpan harga per satuan dasar bahan (`g`, `ml`, atau `pcs`) sesuai data Supplier. | Ditentukan |
| Harga Supplier | Harga bahan diperbarui dari layanan API Supplier pusat dan disimpan di kolom `harga_terakhir` beserta `tanggal_harga`. | Ditentukan |
| Fallback harga Supplier | Batas waktu (timeout) koneksi HTTP ke Supplier adalah 5 detik. Jika server Supplier gagal dihubungi atau timeout, sistem mempertahankan `harga_terakhir` yang sudah ada di database tanpa menimpanya dan memberi notifikasi fallback yang aman. | Ditentukan |

### 7.5 Impor CSV

Data CSV awal telah tersedia di folder `database/data/csv/` (`bahan.csv`, `menu.csv`, `resep.csv`) dan diintegrasikan melalui command `app:import-csv`:

| Keputusan | Penjelasan | Status |
|---|---|---|
| Normalisasi satuan | Satuan `kg` dikonversi ke `g` (dikalikan 1000), `liter` dikonversi ke `ml` (dikalikan 1000); satuan `pcs` tetap. | Ditentukan |
| Normalisasi teks | Pencocokan nama bahan dan menu dilakukan secara *case-insensitive* dengan pembersihan spasi berlebih (*trim*). | Ditentukan |
| Penanganan bahan baru | Bahan yang terdapat pada resep tetapi belum ada di database akan dibuat otomatis dengan nilai default stok awal 0 dan batas minimum 0. | Ditentukan |

---

## 8. Lembar Self-Check (Pengujian Fitur)

| No. | Fitur yang Diuji | Klaim | Langkah Pengujian |
|---|---|---|---|
| 1 | Kelola bahan, menu, dan resep | Ya | Login sebagai manajer. Tambah bahan (satuan dan stok desimal), tambah menu, tambah resep yang menghubungkannya, lalu cek tersimpan. Hapus menu dan pastikan resepnya ikut terhapus secara *cascade*. Login sebagai kasir dan pastikan halaman ditolak (403). |
| 2 | Stok masuk dan batas minimum bahan | Ya | Manajer mencatat stok masuk untuk satu bahan dan pastikan `jumlah_stok` bertambah sebesar jumlah yang dicatat. Atur `batas_minimum` di atas stok dan pastikan bahan muncul di daftar peringatan bahan menipis. |
| 3 | Pemesanan beberapa item dan pengurangan stok | Ya | Buat satu pesanan berisi dua menu berbeda dengan jumlah lebih dari 1. Pastikan stok tiap bahan berkurang sebesar jumlah porsi dikali takaran resep secara akurat. |
| 4 | Penolakan pesanan ketika stok tidak cukup | Ya | Pesan dalam jumlah yang melebihi stok salah satu bahan resep. Pastikan pesanan ditolak dengan pesan jelas, tidak ada pesanan baru tersimpan, dan stok tidak berkurang. |
| 5 | Status menu otomatis Habis | Ya | Habiskan stok salah satu bahan resep menu, lalu buka aplikasi menu (web/mobile). Pastikan menu tampil dengan label `HABIS` dan tombol tambah dinonaktifkan. |
| 6 | Perubahan status dan pembatalan pesanan | Ya | Kasir mengubah pesanan `baru` → `diproses` → `selesai` berurutan. Coba melompat status dan pastikan ditolak. Batalkan pesanan `baru` dan pastikan stok kembali ke nilai semula. Coba batalkan pesanan `diproses` dan pastikan ditolak. |
| 7 | Perhitungan struk, service, pajak, dan total | Ya | Contoh: subtotal 50.000, service 2.500 (5%), pajak 5.250 (10% dari 52.500), total 57.750. Buat pesanan dengan subtotal 50.000 dan cocokkan angka pada struk kafe. |
| 8 | Perhitungan HPP dan margin | Ya | Siapkan menu dengan resep dan harga bahan yang diketahui. Hitung manual jumlah (takaran x harga bahan), lalu cocokkan HPP dan margin (harga jual dikurangi HPP) di laporan manajer. |
| 9 | Laporan dan pembatasan hak akses | Ya | Manajer dapat membuka laporan. Kasir dan pelanggan yang mengaksesnya mendapat response 403 Forbidden. |
| 10 | Impor dan normalisasi data CSV | Ya | Jalankan `php artisan app:import-csv`. Pastikan data bahan, menu, dan resep masuk ke database, serta satuan `kg` terkonversi otomatis ke `g` dan `liter` ke `ml`. |
| 11 | Integrasi Supplier dan fallback harga | Ya | Jalankan sinkronisasi harga supplier pada menu Manajer. Putuskan koneksi internet atau gunakan URL supplier dummy; pastikan sistem tetap berjalan menggunakan harga lama tanpa terjadi error/crash. |
| 12 | Registrasi dan autentikasi pelanggan | Ya | Daftar lewat API/Mobile dengan nomor telepon dan password. Daftar ulang dengan nomor sama dan pastikan ditolak (unik). Login dan pastikan menerima token Sanctum. |
| 13 | Pemesanan melalui aplikasi Android / Flutter | Ya | Pelanggan memilih menu, memilih nomor meja 1–10 via dropdown, memilih metode bayar, dan mengirim pesanan. Validasi stok berkurang otomatis dan struk dibuat. |
| 14 | Pemantauan status dan struk pelanggan | Ya | Pelanggan memantau status pesanan secara *real-time* via silent polling 4 detik (Baru → Diproses → Selesai), melihat struk lengkap (service 5% dan pajak 10%), serta membatalkan pesanan jika masih berstatus baru. |

---

## 9. Fitur Opsional

| Kode | Fitur Opsional | Status | Keterangan |
|---|---|---|---|
| O1 | Ukuran Reguler dan Large | Tidak | Tidak diimplementasikan (fokus ke fitur inti UKK). |
| O2 | Promo Happy Hour | Tidak | Tidak diimplementasikan (fokus ke fitur inti UKK). |
| O3 | Penyesuaian stok bahan rusak atau opname | Tidak | Tidak diimplementasikan. |
| O4 | Laporan jam ramai | Tidak | Tidak diimplementasikan. |
| O5 | Reset password melalui email menggunakan server email tiruan | Tidak | Digantikan oleh mekanisme reset password langsung oleh Kasir sesuai spesifikasi utama soal UKK. |

---

## 10. Ringkasan Hasil Pengerjaan Sistem

**Seluruh modul utama telah selesai dikerjakan:**
- **Inisialisasi & Basis Data:** Migration 7 tabel relasional lengkap dengan *foreign key* dan *cascading constraints*.
- **Data Awal & Seeder:** `UserSeeder` (akun staf & pelanggan), `ImportCsvData` (impor data bahan/menu/resep), dan `StokDemoSeeder` (stok awal pengujian).
- **Backend & Model:** Model Eloquent lengkap dengan relasi, mass-assignment protection, dan attribute casting.
- **Keamanan & Otorisasi:** Middleware `CheckRole` (`role:manajer`, `role:kasir`, `role:pelanggan`) dan proteksi token Sanctum.
- **Web Staf (Blade):** Panel Manajer (kelola bahan, menu, resep, stok masuk, laporan HPP/margin) dan Panel Kasir (pemesanan, pengelolaan status berjenjang, pembatalan, reset password).
- **Layanan Bisnis (`Services`):**
  - `PesananService`: Validasi stok atomik, kalkulasi biaya struk, transisi status aman, dan pemulihan stok bahan otomatis.
  - `SupplierService`: Sinkronisasi harga bahan dengan timeout 5 detik dan proteksi *fallback* harga terakhir.
- **RESTful API Pelanggan:** Endpoint Register, Login, Ganti Password (wajib & mandiri), Profil, Menu, Buat Pesanan, Riwayat Pesanan, dan Pembatalan.
- **Aplikasi Mobile Pelanggan (Flutter):** Antarmuka monokrom minimalis, pencarian menu, filter kategori, format Rupiah IDR, dropdown meja 01–10, live background polling (4 detik) status pesanan, dan form ganti password.

**Keterbatasan dan kendala yang diketahui:**
- Perhitungan HPP mengacu pada `harga_terakhir` bahan (bukan metode FIFO/LIFO).
- Pembaruan status pesanan di mobile menggunakan mekanisme *Silent Background Polling* (setiap 4 detik) yang ringan dan stabil tanpa dependensi server WebSocket pihak ketiga.

---

## 11. Referensi dan Bantuan

**Referensi resmi:**
- Dokumentasi resmi Laravel 11/13 (Eloquent, Migration, Middleware, Artisan, Sanctum).
- Dokumentasi Flutter & Dart (State Management Provider, HTTP, SharedPreferences, Intl).
- Dokumentasi MySQL Relational Database.
- Dokumen Petunjuk Teknis UKK RPL 2026-2027 Soal 3 Varian C (Kafe). 

**Bantuan AI & Penyesuaian:**
- **Bagian yang dibantu AI (sekitar 80% implementasi kode):** Penulisan sintaks controller Laravel, penyusunan model dan relasi Eloquent, pembuatan endpoint RESTful API Sanctum, pengetikan widget antarmuka Flutter, penanganan error *null-safety*, serta pembuatan logika *silent polling* untuk pembaruan status pesanan.
- **Penyesuaian mandiri yang dilakukan:** Sebagian besar aturan bisnis kafe ditentukan dan dirancang langsung oleh saya (validasi nomor meja 1–10, formula perhitungan struk service 5% & pajak 10%, aturan pemotongan dan pengembalian stok bahan baku per resep, serta alur wajib ganti password pelanggan setelah direset kasir), penyesuaian nama tabel/kolom berbahasa Indonesia (`id_pengguna`, `id_bahan`, `id_menu`), pembuatan command `app:import-csv`, serta pengujian dan validasi fungsionalitas sistem secara langsung.