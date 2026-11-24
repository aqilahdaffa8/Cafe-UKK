# Kedai Kopi Senja — Aplikasi Pemesanan Kafe Pelanggan (Flutter)

Aplikasi *mobile* pemesanan kafe khusus pelanggan berbasis **Flutter**, terhubung secara *real-time* ke backend Laravel (`cafe-ukk`) melalui RESTful API.

---

## ☕ Fitur Utama Aplikasi

### 1. Autentikasi Pelanggan & Keamanan
* **Pendaftaran Akun:** Pelanggan dapat mendaftar dengan Nama, Nomor Telepon, Email, dan Password.
* **Login No. Telepon:** Menggunakan nomor HP (`no_telp`) dan password sesuai ketentuan UKK.
* **Wajib Ganti Password (Reset Kasir):** Jika kasir mereset password pelanggan ke password sementara, aplikasi mendeteksi flag `wajib_ganti_password` saat login dan otomatis mengunci akses ke form pembuatan password baru (min 8 karakter) sebelum diizinkan mengakses menu.
* **Ganti Password Mandiri:** Ikon ganti password tersedia di AppBar halaman utama bagi pelanggan yang ingin memperbarui kata sandi secara berkala.
* **Penyimpanan Sesi:** Token otentikasi disimpan aman menggunakan `shared_preferences` untuk auto-login.
* **Logout Terkonfirmasi:** Dilengkapi dialog konfirmasi modal untuk mencegah ketidaksengajaan keluar akun.

### 2. Katalog Menu Interaktif
* **Tampilan Grid 2 Kolom:** Kartu menu rapi dengan ikon representatif, kategori, dan tombol tambah cepat.
* **Pencarian Real-Time:** Filter instan berdasarkan nama menu melalui *search bar*.
* **Filter Kategori Cepat:** Chip filter (*Semua*, *Kopi / Minuman*, *Makanan*).
* **Format Mata Uang Rupiah (IDR):** Seluruh nominal harga diformat rapi dengan pemisah ribuan (contoh: `Rp 18.000`) menggunakan package `intl`.
* **Deteksi Stok & Status Habis:** Menu otomatis berlabel `HABIS` dan tombol tambah dinonaktifkan jika stok bahan baku di backend tidak mencukupi resep.

### 3. Keranjang & Manajemen State
* **State Management Provider:** Pengelolaan kuantitas dan keranjang terpusat melalui `CartProvider`.
* **Floating Cart Bar:** Bar mengambang di bawah layar menampilkan total item dan subtotal harga secara *real-time*.

### 4. Checkout & Pemesanan
* **Pilihan Nomor Meja (Dropdown Option):** Pemilihan meja 01 s/d 10 menggunakan *dropdown* yang rapi dan tervalidasi.
* **Metode Pembayaran:** Pilihan metode pembayaran Tunai (*Cash*) atau QRIS.
* **Validasi Stok Backend:** Menolak pesanan secara otomatis dengan pesan informatif jika bahan baku habis.

### 5. Pelacakan Pesanan & Struk Digital
* **Sinkronisasi Otomatis Real-Time (Live Sync):** Layar riwayat pesanan otomatis melakukan polling senyap (*silent background fetch*) setiap 4 detik lengkap dengan badge `LIVE` di header. Setiap kali kasir memperbarui status pesanan di web, tampilan stepper di HP pelanggan langsung bergerak seketika tanpa perlu ditarik manual.
* **Linimasa Status Pesanan:** Stepper visual progres pesanan (*Baru* → *Diproses* → *Selesai*).
* **Pesan Dinamis Barista:** Menampilkan status terkini pengerjaan pesanan berdasarkan nomor meja pelanggan.
* **Struk Nota Kafe Lengkap:** Rincian biaya transparan mencakup:
  * Subtotal item
  * Service Charge (5%)
  * Pajak PB1/PPN (10%)
  * Total Akhir Pembayaran
  * Barcode nota digital
* **Status Pesanan:** Sesuai ketentuan Soal UKK, pelanggan hanya dapat memantau status pesanan (`Baru` → `Diproses` → `Selesai`) dan struk digital. Pembatalan pesanan hanya dapat dilakukan oleh Kasir di kasir utama.
* **Bottom Navigation Bar:** Navigasi cepat bolak-balik antara katalog Menu, Pesanan Aktif, dan Keluar.

---

## 📁 Struktur Kode Proyek (`lib/`)

```
lib/
├── constant.dart              # Konfigurasi BASE_URL API & konstanta global
├── main.dart                  # Titik masuk aplikasi, inisialisasi tema monokrom & Provider
├── models/                    # Model data JSON to Dart
│   ├── menu_model.dart        # Model menu & parsing null-safe ketersediaan stok
│   ├── pesanan_model.dart     # Model transaksi pesanan & rincian detail item
│   └── user_model.dart        # Model data profil pengguna
├── providers/
│   └── cart_provider.dart     # State management keranjang belanja & perhitungan total
├── services/                  # Komunikasi HTTP REST API ke Laravel
│   ├── api_service.dart       # Client HTTP dasar & penyisipan Bearer Token
│   ├── auth_service.dart      # Registrasi, login, logout, dan cek profil
│   ├── menu_service.dart      # Pengambilan daftar menu & filter kategori
│   └── pesanan_service.dart   # Pembuatan pesanan, riwayat, dan detail nota digital
└── screens/                   # Antarmuka Pengguna (UI)
    ├── login_screen.dart          # Layar masuk akun
    ├── register_screen.dart       # Layar pendaftaran akun baru
    ├── ganti_password_screen.dart # Form ganti password (wajib reset kasir & mandiri)
    ├── menu_screen.dart           # Katalog menu, search, filter, & floating cart bar
    ├── checkout_screen.dart       # Konfirmasi pesanan, dropdown meja, & metode bayar
    └── pesanan_screen.dart        # Status pesanan, live sync progres, & struk nota digital
```

---

## ⚙️ Konfigurasi Alamat API (`lib/constant.dart`)

Pastikan URL API pada file `lib/constant.dart` disesuaikan dengan lingkungan pengujian Anda:

```dart
// Untuk Browser / Desktop / Linux:
const String baseUrl = 'http://127.0.0.1:8000/api';

// Untuk Android Emulator (AVD):
// const String baseUrl = 'http://10.0.2.2:8000/api';

// Untuk HP Fisik Android (satu jaringan Wi-Fi dengan laptop):
// const String baseUrl = 'http://192.168.x.x:8000/api';
```

> **Tips Pengujian di HP Fisik:** Jalankan backend Laravel dengan perintah:
> ```bash
> php artisan serve --host=0.0.0.0 --port=8000
> ```

---

## 🚀 Cara Menjalankan Aplikasi

1. **Unduh seluruh dependensi:**
   ```bash
   flutter pub get
   ```

2. **Jalankan analisis kode (pastikan 0 error):**
   ```bash
   flutter analyze
   ```

3. **Jalankan aplikasi:**
   ```bash
   flutter run
   ```

---

## 🧪 Akun untuk Pengujian Cepat

Jika database backend sudah di-seed (`UserSeeder`), gunakan akun berikut:

| Peran | No. Telepon (`no_telp`) | Password |
|---|---|---|
| Pelanggan (Demo) | `08008000800` | `password` |

*Atau lakukan registrasi mandiri langsung melalui menu daftar di aplikasi.*

---

## 📦 Dependensi Utama

* [`provider`](https://pub.dev/packages/provider): Manajemen state keranjang belanja.
* [`intl`](https://pub.dev/packages/intl): Pemformatan angka mata uang Rupiah (`id_ID`).
* [`http`](https://pub.dev/packages/http): Komunikasi HTTP client ke REST API.
* [`shared_preferences`](https://pub.dev/packages/shared_preferences): Penyimpanan persisten token sesi otentikasi.

---

## 🤖 Catatan Pengembangan (AI-Assisted)

Aplikasi klien ini dikembangkan dengan pendekatan kolaboratif:
- **Saya:** Mengatur arsitektur navigasi, penentuan konsep desain UI monokrom, perancangan form ganti password wajib, dan pengujian integrasi alur pemesanan.
- **Bantuan AI:** Membantu percepatan implementasi widget Flutter, manajemen state Provider, serta *null-safety* pada model data.
