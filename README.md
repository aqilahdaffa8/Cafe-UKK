# Sistem Informasi Manajemen dan Pemesanan Kafe (cafe-ukk)

> **Status dokumen:** draf awal. Hanya berisi hal yang sudah ditentukan atau sudah dibahas selama pengerjaan.
> Bagian bertanda `[ISI NANTI]` sengaja dikosongkan sampai implementasinya benar-benar ada.
>
> **Legenda status keputusan:**
> - **Ditentukan**: sudah diputuskan dan/atau sudah diterapkan.
> - **Rencana**: sudah dibahas, belum dikerjakan atau belum dikonfirmasi.
> - **Belum ditentukan**: belum ada keputusan.

---

## 1. Ringkasan Sistem

**Nama aplikasi:** Cafe UKK (nama repositori/proyek: `cafe-ukk`) `[sesuaikan jika ada nama resmi]`

**Deskripsi singkat:** Sistem informasi manajemen dan pemesanan kafe. Backend dan antarmuka web untuk staf dibangun dengan Laravel, terintegrasi dengan layanan aplikasi pelanggan (mobile) melalui REST API.

**Tujuan dan fungsi utama:**
- Mengelola data master kafe: bahan baku, menu, dan resep (komposisi bahan per menu).
- Mencatat stok masuk bahan baku dan memantau batas minimum stok.
- Memproses pesanan dengan validasi ketersediaan stok berdasarkan resep.
- Menghitung struk: subtotal, service charge, pajak, dan total.
- Menyajikan laporan HPP (harga pokok penjualan) dan margin untuk manajer.
- Mengintegrasikan harga bahan dari layanan Supplier dengan cadangan harga terakhir.

**Masalah yang diselesaikan:**
- Pesanan tidak boleh diterima jika bahan tidak cukup, sehingga tidak ada pesanan yang tidak bisa dipenuhi.
- Stok bahan berkurang dan kembali otomatis sesuai resep (termasuk saat pesanan dibatalkan), sehingga pencatatan tidak manual.
- Perhitungan biaya dan margin menjadi konsisten dan dapat ditelusuri.

**Jenis pengguna:**

| Peran | Wewenang |
|---|---|
| Manajer | Mengelola menu, resep, bahan baku, stok masuk, dan melihat laporan HPP/margin. |
| Kasir | Membuat pesanan, mengubah status pesanan berjenjang (Baru, Diproses, Selesai), membatalkan pesanan berstatus Baru, dan mereset password pelanggan. |
| Pelanggan | Mendaftar dan login lewat aplikasi mobile/API, memesan, dan memantau status pesanan. |

**Alur kerja dari pemesanan hingga selesai:**
1. Pesanan dibuat oleh pelanggan (aplikasi mobile/API) atau oleh kasir.
2. Sistem memvalidasi: nomor meja 1 sampai 10, menu aktif, dan stok bahan cukup untuk seluruh item berdasarkan resep.
3. Jika valid, pesanan tersimpan berstatus `baru`, stok bahan berkurang, dan total dihitung.
4. Kasir mengubah status: `baru` menjadi `diproses`, lalu `selesai`.
5. Pesanan berstatus `baru` dapat dibatalkan; status menjadi `dibatalkan` dan stok bahan dikembalikan.
6. Pelanggan memantau status dan melihat struk.

---

## 2. Teknologi yang Digunakan

| Komponen | Teknologi | Status | Alasan singkat |
|---|---|---|---|
| Bahasa backend | PHP 8.3 | Ditentukan | Persyaratan minimum Laravel 13. |
| Framework backend | Laravel 13.42.0 | Ditentukan | Menyediakan ORM, migration, seeder, middleware, dan pengujian bawaan. |
| Database relasional | MySQL, database `cafe_ukk` | Ditentukan | Mendukung foreign key dan transaksi yang dibutuhkan aturan stok. |
| REST API | Laravel API + Laravel Sanctum (token) | Rencana | Autentikasi token untuk aplikasi mobile. |
| Frontend web staf | `[ISI NANTI]` | Belum ditentukan | |
| Aplikasi Android | `[ISI NANTI]` | Belum ditentukan | |
| Package penting | `laravel/sanctum` (rencana); PHPUnit (pengujian bawaan Laravel) | Rencana | |

---

## 3. Cara Menjalankan Server

**Persyaratan:**
- PHP 8.3 dan Composer.
- MySQL.
- Git.
- Node.js dan npm: `[ISI NANTI, hanya jika frontend memerlukannya]`.

**Langkah:**

```bash
# 1. Clone repository
git clone <URL-REPOSITORI> cafe-ukk
cd cafe-ukk

# 2. Instal dependency
composer install

# 3. Siapkan environment
cp .env.example .env
php artisan key:generate
```

**4. Konfigurasi database:** buat database `cafe_ukk` di MySQL, lalu sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cafe_ukk
DB_USERNAME=<username>
DB_PASSWORD=<password>
```

```bash
# 5. Jalankan migration dan seeder
php artisan migrate:fresh --seed

# 6. Impor data awal CSV
# [ISI NANTI] Ditunda karena file CSV belum tersedia.

# 7. Jalankan server
php artisan serve
```

- **Seeder yang tersedia saat ini:** `UserSeeder` (akun Manajer dan Kasir). Seeder data demo lain: `[ISI NANTI]`.
- **Alamat akses aplikasi web:** `http://127.0.0.1:8000` (alamat bawaan `php artisan serve`).
- **Menjalankan pengujian otomatis:**

```bash
php artisan test
```

---

## 4. Cara Memasang APK

Belum tersedia. `[ISI NANTI]`

- Lokasi file APK: `/apk` (sesuai ketentuan dokumen proyek).
- Versi minimum Android: `[ISI NANTI]`
- Cara mengatur alamat server/API di aplikasi: `[ISI NANTI]`
- Cara memastikan HP terhubung ke server (satu jaringan, server dijalankan dengan `php artisan serve --host=0.0.0.0`): `[verifikasi saat aplikasi mobile ada]`
- Alamat API yang digunakan aplikasi: `[ISI NANTI]`

---

## 5. Akun Pengujian

| Peran | Username / Nomor HP | Password |
|---|---|---|
| Manajer | `[isi sesuai UserSeeder]` | `[isi sesuai UserSeeder]` |
| Kasir | `[isi sesuai UserSeeder]` | `[isi sesuai UserSeeder]` |
| Pelanggan | `[daftar sendiri lewat API/aplikasi, atau akun demo dari seeder]` | `[ISI NANTI]` |

Akun di atas khusus pengujian, bukan akun pribadi. Login memakai **nomor telepon (`no_telp`)**.

---

## 6. Letak Berkas dan Struktur Proyek

Struktur yang sudah ada dan yang direncanakan:

```text
cafe-ukk/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Manager/            (rencana) kelola menu, bahan, resep, stok masuk, laporan
│   │   │   ├── Cashier/            (rencana) pesanan dan reset password pelanggan
│   │   │   └── Api/                (rencana) registrasi, login, dan pesanan pelanggan
│   │   └── Middleware/
│   │       └── CheckRole.php       pembatasan hak akses berdasarkan peran
│   ├── Models/                     User, Bahan, Menu, Resep, StokMasuk, Pesanan, DetailPesanan
│   └── Services/                   (rencana) PesananService: validasi stok, hitung total, batal
├── bootstrap/app.php               pendaftaran alias middleware `role`
├── database/
│   ├── migrations/                 7 tabel beserta relasi foreign key
│   └── seeders/                    UserSeeder, DatabaseSeeder
├── routes/
│   ├── web.php                     (rencana) route web staf
│   └── api.php                     (rencana) route REST API pelanggan
├── tests/Feature/                  CheckRoleTest (dan pengujian lain menyusul)
├── docs/
│   ├── erd/                        diagram Entity Relationship
│   └── uml/                        class diagram (Menu, Resep, Bahan, Pesanan); use case
└── apk/                            file APK aplikasi Android
```

Folder importer CSV: `[ISI NANTI]`.

---

## 7. Keputusan Implementasi dan Aturan Bisnis

### 7.1 Struktur data

| Keputusan | Penjelasan | Status |
|---|---|---|
| Tujuh tabel | `users`, `bahans`, `menus`, `reseps`, `stok_masuks`, `pesanans`, `detail_pesanans`. | Ditentukan |
| Primary key | Memakai nama kolom khusus (`id_pengguna`, `id_bahan`, `id_menu`, `id_resep`, `id_stok_masuk`, `id_pesanan`, `id_detail_pesanan`) bertipe `bigint unsigned`. Setiap model mendeklarasikan `$primaryKey`. | Ditentukan |
| Tipe data stok | `jumlah_stok`, `batas_minimum`, `takaran`, dan `jumlah` (stok masuk) bertipe **decimal** agar mendukung pecahan. Harga, subtotal, dan nomor meja bertipe integer. | Ditentukan |
| Enum | Peran (`manajer`, `kasir`, `pelanggan`), satuan (`g`, `ml`, `pcs`), status pesanan (`baru`, `diproses`, `selesai`, `dibatalkan`), metode pembayaran (`tunai`, `qris`, huruf kecil agar konsisten). | Ditentukan |
| Kolom autentikasi pengguna | `nama` dan `email` nullable; `no_telp` unik dan wajib; `wajib_ganti_password` default `false`. | Ditentukan |
| Aturan hapus (`onDelete`) | `reseps.id_menu` dan `detail_pesanans.id_pesanan`: cascade. `detail_pesanans.id_menu`, `pesanans.id_pengguna`, `reseps.id_bahan`, `stok_masuks.id_bahan`, dan `stok_masuks.id_pengguna`: restrict, agar riwayat penjualan dan stok tidak hilang. Menu yang tidak dijual dinonaktifkan (kolom `aktif`), bukan dihapus. | Rencana (dikonfirmasi saat migrasi final) |

### 7.2 Autentikasi, password, dan hak akses

| Keputusan | Penjelasan | Status |
|---|---|---|
| Identitas login | Nomor telepon (`no_telp`) bersama password, untuk pelanggan. | Ditentukan |
| Identisan login | Email (`email`) bersama password, untuk staf (kasir dan manajer) | Ditentukan |
| Penyimpanan password | Selalu di-hash satu arah (`Hash::make` pada seeder dan cast `hashed` pada model), tidak pernah di-encrypt. | Ditentukan |
| Peran saat registrasi | Akun yang mendaftar lewat jalur pelanggan dipaksa berperan `pelanggan` di sisi server. Input `peran` dari request diabaikan. | Ditentukan |
| Pembatasan hak akses | Middleware `CheckRole` dengan alias `role`, dipakai di route, misalnya `role:manajer` atau `role:manajer,kasir`. Tidak login ditolak (401), peran tidak sesuai ditolak (403). Route dikelompokkan per peran. | Ditentukan (middleware); route per peran: Rencana |
| Autentikasi API pelanggan | Token melalui Laravel Sanctum, dengan controller registrasi dan login sendiri berbasis `no_telp`. | Rencana |
| Reset password oleh kasir | Kasir mereset password pelanggan, kolom `wajib_ganti_password` diset `true`, dan pelanggan diminta mengganti password saat login berikutnya. | Rencana |
| Format validasi nomor HP | `[ISI NANTI]` | Belum ditentukan |
| Aturan pembuatan password (panjang minimal, dsb.) | `[ISI NANTI]` | Belum ditentukan |

### 7.3 Pesanan, stok, dan perhitungan keuangan

| Keputusan | Penjelasan | Status |
|---|---|---|
| Validasi pemesanan | Nomor meja bilangan bulat 1 sampai 10. Pelanggan maksimal memiliki 1 pesanan aktif berstatus `baru`. Aturan ini hanya berlaku untuk pengguna berperan pelanggan, bukan untuk kasir yang membuat pesanan. | Ditentukan (aturan); penerapan: Rencana |
| Pembuat pesanan | Kolom `id_pengguna` pada pesanan menyimpan pembuat pesanan (pelanggan atau kasir). | Ditentukan |
| Validasi stok | Kebutuhan bahan = jumlah porsi dikali takaran resep, dijumlahkan per bahan. Jika ada bahan yang kurang, seluruh pesanan ditolak dan tidak ada data yang berubah. | Ditentukan (aturan) |
| Konsistensi data | Penyimpanan pesanan, detail, dan pengurangan stok dilakukan dalam satu transaksi database, dengan penguncian baris saat membaca stok agar pesanan bersamaan tidak sama-sama lolos. | Rencana |
| Perubahan status | Berjenjang: `baru` ke `diproses` ke `selesai`. Lompat atau mundur ditolak. | Ditentukan (aturan) |
| Pembatalan dan stok | Hanya pesanan berstatus `baru` yang dapat dibatalkan. Status menjadi `dibatalkan` dan stok bahan dikembalikan sesuai resep dalam satu transaksi. | Ditentukan (aturan) |
| Perhitungan struk | Subtotal = jumlah (harga satuan x jumlah). Service charge = 5% dari subtotal. Pajak = 10% dari (subtotal + service charge). Total = subtotal + service + pajak. Hasil disimpan di kolom `subtotal`, `service`, `pajak`, `total_harga`. | Ditentukan |
| Pembulatan | `[ISI NANTI]` (kolom bertipe integer, aturan pembulatan harus dipilih dan diterapkan di satu tempat). | Belum ditentukan |
| Harga jual pada struk lama | Harga saat transaksi disalin ke `detail_pesanans.harga_satuan`, sehingga perubahan harga menu tidak mengubah struk lama. | Ditentukan |
| Status menu otomatis "Habis" | `[ISI NANTI]` | Belum ditentukan |

### 7.4 HPP, margin, dan Supplier

| Keputusan | Penjelasan | Status |
|---|---|---|
| Perhitungan HPP | HPP per menu = jumlah dari (takaran bahan x `harga_terakhir` bahan) untuk semua bahan pada resep. | Rencana |
| Perhitungan margin | Margin = harga jual menu dikurangi HPP. | Rencana |
| Satuan harga bahan | `[ISI NANTI]` (perlu ditegaskan bahwa `harga_terakhir` adalah harga per satuan dasar `g`, `ml`, atau `pcs`). | Belum ditentukan |
| Harga Supplier | Harga bahan diperbarui dari layanan Supplier dan disimpan di `harga_terakhir` dengan tanggalnya di `tanggal_harga`. | Rencana |
| Fallback harga Supplier | Jika server Supplier tidak merespons, sistem memakai `harga_terakhir` yang sudah ada di database dan tidak menimpanya. | Ditentukan (aturan); penerapan: Rencana |
| Batas waktu dan penanda fallback | `[ISI NANTI]` | Belum ditentukan |

### 7.5 Impor CSV (ditunda)

File CSV belum tersedia, sehingga bagian ini belum dikerjakan.

| Keputusan | Penjelasan | Status |
|---|---|---|
| Normalisasi satuan | kg dikonversi ke g, liter ke ml; pcs tetap. | Ditentukan (aturan); penerapan: Ditunda |
| Pencocokan nama bahan yang salah ketik | `[ISI NANTI]` | Belum ditentukan |
| Penanganan bahan yang tidak ditemukan | `[ISI NANTI]` | Belum ditentukan |

---

## 8. Lembar Self-Check (Pengujian Fitur)

Kolom **Klaim** diisi jujur sesuai kondisi saat ini. Perbarui menjadi "Ya" hanya setelah langkah pengujian benar-benar berhasil dijalankan.

| No. | Fitur yang Diuji | Klaim | Langkah Pengujian |
|---|---|---|---|
| 1 | Kelola bahan, menu, dan resep | Tidak | Login sebagai manajer. Tambah bahan (satuan dan stok desimal), tambah menu, tambah resep yang menghubungkannya, lalu cek tersimpan. Hapus menu dan pastikan resepnya ikut terhapus. Login sebagai kasir dan pastikan halaman ini ditolak (403). |
| 2 | Stok masuk dan batas minimum bahan | Tidak | Manajer mencatat stok masuk untuk satu bahan dan pastikan `jumlah_stok` bertambah sebesar jumlah yang dicatat. Atur `batas_minimum` di atas stok dan pastikan bahan muncul di daftar bahan menipis. |
| 3 | Pemesanan beberapa item dan pengurangan stok | Tidak | Buat satu pesanan berisi dua menu berbeda dengan jumlah lebih dari 1. Pastikan stok tiap bahan berkurang sebesar jumlah porsi dikali takaran resep, dibandingkan dengan hitungan manual. |
| 4 | Penolakan pesanan ketika stok tidak cukup | Tidak | Pesan dalam jumlah yang melebihi stok salah satu bahan. Pastikan pesanan ditolak dengan pesan jelas, tidak ada pesanan baru tersimpan, dan stok tidak berubah. |
| 5 | Status menu otomatis Habis | Tidak | `[ISI NANTI]` Habiskan stok salah satu bahan resep menu, lalu pastikan menu tampil sebagai Habis dan tidak bisa dipesan. |
| 6 | Perubahan status dan pembatalan pesanan | Tidak | Kasir mengubah pesanan `baru`, `diproses`, `selesai` berurutan. Coba melompat status dan pastikan ditolak. Batalkan pesanan `baru` dan pastikan stok kembali ke nilai semula. Coba batalkan pesanan `diproses` dan pastikan ditolak. |
| 7 | Perhitungan struk, service, pajak, dan total | Tidak | Contoh: subtotal 50.000, service 2.500 (5%), pajak 5.250 (10% dari 52.500), total 57.750. Buat pesanan dengan subtotal 50.000 dan cocokkan angka di struk. |
| 8 | Perhitungan HPP dan margin | Tidak | Siapkan menu dengan resep dan harga bahan yang diketahui. Hitung manual jumlah (takaran x harga bahan), lalu cocokkan HPP dan margin (harga jual dikurangi HPP) di laporan. |
| 9 | Laporan dan pembatasan hak akses | Tidak | Manajer dapat membuka laporan. Kasir dan pelanggan yang mengaksesnya mendapat 403. Jalankan `php artisan test` untuk pengujian peran. |
| 10 | Impor dan normalisasi data CSV | Tidak | `[ISI NANTI]` Ditunda, file CSV belum tersedia. |
| 11 | Integrasi Supplier dan fallback harga | Tidak | `[ISI NANTI]` Perbarui harga bahan saat Supplier aktif dan cek `harga_terakhir` serta `tanggal_harga` berubah. Matikan atau putuskan layanan Supplier, perbarui lagi, dan pastikan harga lama tetap dipakai tanpa error. |
| 12 | Registrasi dan autentikasi pelanggan | Tidak | Daftar lewat API dengan nomor telepon dan password. Daftar ulang dengan nomor sama dan pastikan ditolak. Login dan pastikan menerima token. Kirim `peran=manajer` saat daftar dan pastikan akun tetap `pelanggan`. |
| 13 | Pemesanan melalui aplikasi Android | Tidak | `[ISI NANTI]` Belum ada aplikasi Android. |
| 14 | Pemantauan status dan struk pelanggan | Tidak | Pelanggan membuat pesanan lewat API, lalu memantau status saat kasir mengubahnya, dan membuka struk. Coba pesanan kedua berstatus `baru` dan pastikan ditolak. Coba nomor meja 0 dan 11 dan pastikan ditolak. |

---

## 9. Fitur Opsional

| Kode | Fitur Opsional | Status |
|---|---|---|
| O1 | Ukuran Reguler dan Large | Tidak |
| O2 | Promo Happy Hour | Tidak |
| O3 | Penyesuaian stok bahan rusak atau opname | Tidak |
| O4 | Laporan jam ramai | Tidak |
| O5 | Reset password melalui email menggunakan server email tiruan | Tidak |

---

## 10. Belum Selesai dan Keterbatasan

Kondisi saat ini (pengerjaan masih berjalan):

**Sudah selesai:**
- Inisialisasi proyek dan konfigurasi database lokal.
- Migration tujuh tabel beserta foreign key.
- `UserSeeder` untuk akun Manajer dan Kasir.
- Model Eloquent dan relasinya (penyelesaian dan pengujian di Tinker: `[konfirmasi]`).
- Middleware `CheckRole` beserta tesnya `[konfirmasi setelah dijalankan]`.

**Belum selesai:**
- Autentikasi (login web staf, registrasi dan login API pelanggan, wajib ganti password).
- `ManagerController` dan `CashierController` beserta route per peran.
- Service pesanan: validasi stok, perhitungan struk, perubahan status, pembatalan dan pengembalian stok.
- Laporan HPP dan margin.
- Impor dan normalisasi CSV (ditunda karena file belum tersedia).
- Integrasi API Supplier dan fallback harga.
- Antarmuka web staf (frontend).
- Aplikasi Android dan APK.
- Seeder data demo selain akun staf.

**Keterbatasan dan kendala yang diketahui:** `[ISI NANTI]`

---

## 11. Referensi dan Bantuan

**Referensi resmi:**
- Dokumentasi resmi Laravel 13.x (Eloquent, migration, middleware, pengujian).
- Dokumentasi Laravel Sanctum.
- Dokumentasi MySQL/MariaDB.
- Dokumen kebutuhan UKK RPL 2026-2027 Soal 3 Varian C (Kafe).

**Bantuan AI:**
- **AI yang digunakan:** Claude (Anthropic).
- **Bagian yang dibantu:** panduan tahapan pengerjaan backend, tinjauan skema database terhadap aturan bisnis (kolom yang kurang, tipe data, aturan `onDelete`), contoh kode model Eloquent, middleware `CheckRole`, dan tesnya, serta draf kerangka README ini.
- **Perubahan yang dilakukan terhadap hasil bantuan:** `[ISI: jelaskan penyesuaian yang Anda lakukan, misalnya penyesuaian nama kolom dengan migration, perbaikan migration, dan hal lain yang Anda ubah sendiri]`

**Referensi lain (tutorial, artikel):** `[ISI NANTI]`