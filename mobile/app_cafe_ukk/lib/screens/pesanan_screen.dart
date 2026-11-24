import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/pesanan_model.dart';
import '../services/auth_service.dart';
import '../services/pesanan_service.dart';
import 'login_screen.dart';

class PesananScreen extends StatefulWidget {
  const PesananScreen({super.key});

  @override
  State<PesananScreen> createState() => _PesananScreenState();
}

class _PesananScreenState extends State<PesananScreen> {
  final _pesananService = PesananService();
  final _rp = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
  late Future<List<PesananModel>> _future;
  Timer? _pollingTimer;

  @override
  void initState() {
    super.initState();
    _muat();
    _mulaiAutoRefresh();
  }

  // Polling otomatis setiap 4 detik untuk menyinkronkan perubahan status kasir
  void _mulaiAutoRefresh() {
    _pollingTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (mounted) {
        _muatSilent();
      }
    });
  }

  void _muat() {
    setState(() {
      _future = _pesananService.getRiwayatPesanan();
    });
  }

  // Refresh di latar belakang tanpa memicu loading spinner agar UI tidak berkedip
  void _muatSilent() async {
    try {
      final data = await _pesananService.getRiwayatPesanan();
      if (mounted) {
        setState(() {
          _future = Future.value(data);
        });
      }
    } catch (_) {
      // Abaikan error sementara saat auto-refresh
    }
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  void _konfirmasiLogout() async {
    final keluar = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar dari Akun?'),
        content: const Text('Apakah Anda yakin ingin keluar dari akun pelanggan ini?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal', style: TextStyle(color: Colors.grey))),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.black),
            child: const Text('Ya, Keluar', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (keluar == true) {
      await AuthService().logout();
      if (!mounted) return;
      Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => const LoginScreen()), (_) => false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      appBar: AppBar(
        title: Row(
          children: [
            const Text('Status Pesanan Saya', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: const BoxDecoration(
                color: Color(0xFFE8F5E9),
                borderRadius: BorderRadius.all(Radius.circular(10)),
              ),
            ),
          ],
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        automaticallyImplyLeading: false, // Menghilangkan tombol back karena sudah ada bottom nav bar
      ),
      body: FutureBuilder<List<PesananModel>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator(color: Colors.black));
          }

          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Text('Gagal memuat pesanan:\n${snapshot.error}', textAlign: TextAlign.center, style: const TextStyle(color: Colors.red)),
              ),
            );
          }

          final list = snapshot.data ?? [];
          if (list.isEmpty) return const Center(child: Text('Belum ada pesanan aktif.'));

          return RefreshIndicator(
            onRefresh: () async => _muat(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: list.length,
              separatorBuilder: (ctx, i) => const SizedBox(height: 20),
              itemBuilder: (ctx, i) => _buildKartuStruk(list[i]),
            ),
          );
        },
      ),

      // BOTTOM NAVIGATION BAR (Navigasi Mulus Bolak-Balik)
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 1, // Sedang di tab PESANAN AKTIF
        selectedItemColor: Colors.black,
        unselectedItemColor: Colors.grey,
        onTap: (i) {
          if (i == 0) Navigator.pop(context); // Kembali ke MENU
          if (i == 2) _konfirmasiLogout();
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.storefront), label: 'MENU'),
          BottomNavigationBarItem(icon: Icon(Icons.receipt), label: 'PESANAN AKTIF'),
          BottomNavigationBarItem(icon: Icon(Icons.logout), label: 'KELUAR'),
        ],
      ),
    );
  }

  // Tampilan Struk Digital & Status
  Widget _buildKartuStruk(PesananModel p) {
    final status = p.status.toLowerCase();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // 1. INFO HEADER PESANAN
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Meja 0${p.nomorMeja}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              Text('#ORD-2026-${p.idPesanan}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.grey)),
            ],
          ),
          const SizedBox(height: 14),

          // 2. TIMELINE PROGRES STATUS HORIZONTAL
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _stepLingkaran('Baru', status == 'baru' || status == 'diproses' || status == 'selesai'),
              _garisProgres(status == 'diproses' || status == 'selesai'),
              _stepLingkaran('Diproses', status == 'diproses' || status == 'selesai'),
              _garisProgres(status == 'selesai'),
              _stepLingkaran('Selesai', status == 'selesai'),
            ],
          ),
          const SizedBox(height: 14),

          // 3. PESAN BARISTA / STATUS
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(6)),
            child: Row(
              children: [
                const Icon(Icons.coffee, size: 16, color: Colors.grey),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    status == 'dibatalkan'
                        ? 'Pesanan telah dibatalkan.'
                        : (status == 'selesai'
                            ? 'Pesanan sudah selesai. Selamat menikmati!'
                            : (status == 'diproses'
                                ? 'Barista sedang meracik pesanan Anda di meja 0${p.nomorMeja}.'
                                : 'Pesanan diterima, menunggu diproses barista.')),
                    style: const TextStyle(fontSize: 11, color: Colors.black87),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 24),

          // 4. STRUK NOTA KAFE
          const Center(
            child: Column(
              children: [
                Text('KEDAI KOPI SENJA', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, letterSpacing: 1)),
                Text('Jl. Senja Kopi No. 10', style: TextStyle(fontSize: 10, color: Colors.grey)),
              ],
            ),
          ),
          const SizedBox(height: 12),

          ...p.items.map((item) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 2),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('${item.jumlahMenu}x ${item.menu?.namaMenu ?? "Item"}', style: const TextStyle(fontSize: 12)),
                  Text(_rp.format(item.hargaSatuan * item.jumlahMenu), style: const TextStyle(fontSize: 12)),
                ],
              ),
            );
          }),
          const Divider(height: 16),

          _barisStruk('Subtotal', _rp.format(p.subtotal)),
          _barisStruk('Service Charge (5%)', _rp.format(p.service)),
          _barisStruk('Pajak (10%)', _rp.format(p.pajak)),
          const Divider(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('TOTAL PEMBAYARAN', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              Text(_rp.format(p.totalHarga), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            ],
          ),
          const SizedBox(height: 8),
          Text('Metode Bayar: ${p.metodePembayaran.toUpperCase()}', style: const TextStyle(fontSize: 11, color: Colors.grey)),

          // 5. BARCODE NOTA
          const SizedBox(height: 16),
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade300)),
              child: const Text('||| | | |||| | ||| || |||', style: TextStyle(letterSpacing: 4, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _stepLingkaran(String label, bool aktif) {
    return Column(
      children: [
        CircleAvatar(
          radius: 10,
          backgroundColor: aktif ? Colors.black : Colors.grey.shade200,
          child: Icon(Icons.check, size: 12, color: aktif ? Colors.white : Colors.grey),
        ),
        const SizedBox(height: 4),
        Text(label, style: TextStyle(fontSize: 10, fontWeight: aktif ? FontWeight.bold : FontWeight.normal)),
      ],
    );
  }

  Widget _garisProgres(bool aktif) {
    return Container(
      width: 40,
      height: 2,
      color: aktif ? Colors.black : Colors.grey.shade200,
    );
  }

  Widget _barisStruk(String label, String nilai) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
          Text(nilai, style: const TextStyle(fontSize: 11)),
        ],
      ),
    );
  }
}