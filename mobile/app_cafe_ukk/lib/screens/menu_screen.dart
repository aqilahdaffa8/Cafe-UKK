import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../models/menu_model.dart';
import '../providers/cart_provider.dart';
import '../services/auth_service.dart';
import '../services/menu_service.dart';
import 'checkout_screen.dart';
import 'pesanan_screen.dart';
import 'login_screen.dart';
import 'ganti_password_screen.dart';

class MenuScreen extends StatefulWidget {
  const MenuScreen({super.key});

  @override
  State<MenuScreen> createState() => _MenuScreenState();
}

class _MenuScreenState extends State<MenuScreen> {
  final _menuService = MenuService();
  final _rp = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
  String _kategori = 'semua';
  String _keyword = '';
  late Future<List<MenuModel>> _futureMenu;

  @override
  void initState() {
    super.initState();
    _muatData();
  }

  void _muatData() {
    setState(() {
      _futureMenu = _menuService.getMenu(kategori: _kategori == 'semua' ? null : _kategori);
    });
  }

  // Dialog konfirmasi sebelum keluar akun
  void _konfirmasiLogout() async {
    final keluar = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar dari Akun?'),
        content: const Text('Apakah Anda yakin ingin keluar dari akun pelanggan ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal', style: TextStyle(color: Colors.grey)),
          ),
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
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (_) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);

    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      // 1. HEADER (Judul & Ikon Keranjang)
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Kedai Kopi Senja', style: TextStyle(color: Colors.black, fontSize: 16, fontWeight: FontWeight.bold)),
            Text('• Meja Belum Dipilih', style: TextStyle(color: Colors.grey, fontSize: 11)),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Ganti Kata Sandi',
            icon: const Icon(Icons.lock_reset, color: Colors.black87),
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const GantiPasswordScreen(isWajib: false)),
            ),
          ),
          IconButton(
            icon: Badge(
              isLabelVisible: cart.totalItemCount > 0,
              label: Text('${cart.totalItemCount}'),
              backgroundColor: Colors.black,
              child: const Icon(Icons.shopping_bag_outlined, color: Colors.black),
            ),
            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CheckoutScreen())),
          ),
        ],
      ),
      body: Column(
        children: [
          // 2. SEARCH BAR & FILTER KATEGORI
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Column(
              children: [
                TextField(
                  onChanged: (v) => setState(() => _keyword = v.toLowerCase()),
                  decoration: InputDecoration(
                    hintText: 'Cari sajian kopi atau camilan...',
                    hintStyle: const TextStyle(fontSize: 12),
                    prefixIcon: const Icon(Icons.search, size: 18),
                    filled: true,
                    fillColor: Colors.grey.shade100,
                    contentPadding: EdgeInsets.zero,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                  ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: ['semua', 'minuman', 'makanan'].map((k) {
                    final aktif = _kategori == k;
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: FilterChip(
                        label: Text(k == 'semua' ? 'Semua' : (k == 'minuman' ? 'Kopi' : 'Makanan')),
                        selected: aktif,
                        selectedColor: Colors.black,
                        labelStyle: TextStyle(color: aktif ? Colors.white : Colors.black, fontSize: 11),
                        showCheckmark: false,
                        shape: const StadiumBorder(),
                        onSelected: (_) {
                          _kategori = k;
                          _muatData();
                        },
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),

          // 3. GRID MENU 2 KOLOM
          Expanded(
            child: FutureBuilder<List<MenuModel>>(
              future: _futureMenu,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator(color: Colors.black));
                }

                if (snapshot.hasError) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.error_outline, size: 40, color: Colors.red),
                          const SizedBox(height: 8),
                          Text('Gagal memuat menu:\n${snapshot.error}', textAlign: TextAlign.center, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                          const SizedBox(height: 12),
                          ElevatedButton(
                            onPressed: _muatData,
                            style: ElevatedButton.styleFrom(backgroundColor: Colors.black),
                            child: const Text('Coba Lagi', style: TextStyle(color: Colors.white, fontSize: 12)),
                          ),
                        ],
                      ),
                    ),
                  );
                }

                final listMenu = snapshot.data ?? [];
                final menus = listMenu.where((m) => m.namaMenu.toLowerCase().contains(_keyword)).toList();

                if (menus.isEmpty) {
                  return const Center(child: Text('Menu tidak ditemukan.'));
                }

                return GridView.builder(
                  padding: const EdgeInsets.all(12),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.72,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                  ),
                  itemCount: menus.length,
                  itemBuilder: (context, i) {
                    final item = menus[i];
                    final habis = !item.tersedia;

                    return Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // Tag & Icon
                          Expanded(
                            child: Container(
                              color: Colors.grey.shade50,
                              child: Stack(
                                children: [
                                  Center(
                                    child: Icon(
                                      item.kategori == 'makanan' ? Icons.bakery_dining : Icons.coffee,
                                      size: 40,
                                      color: Colors.grey.shade400,
                                    ),
                                  ),
                                  Positioned(
                                    top: 4, left: 4,
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                                      color: habis ? Colors.black : Colors.white,
                                      child: Text(
                                        habis ? 'HABIS' : item.kategori.toUpperCase(),
                                        style: TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: habis ? Colors.white : Colors.black),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 8),

                          // Nama & Harga
                          Text(item.namaMenu, maxLines: 1, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                          Text(_rp.format(item.harga), style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey.shade700)),
                          const SizedBox(height: 8),

                          // Tombol Tambah
                          ElevatedButton(
                            onPressed: habis ? null : () => cart.tambahItem(item),
                            style: ElevatedButton.styleFrom(backgroundColor: Colors.black, padding: EdgeInsets.zero, visualDensity: VisualDensity.compact),
                            child: Text(habis ? 'Habis' : '+ Tambah', style: const TextStyle(fontSize: 11, color: Colors.white)),
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),

      // 4. FLOATING BAR KERANJANG
      bottomSheet: cart.totalItemCount > 0
          ? Container(
              color: Colors.black,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('${cart.totalItemCount} ITEM · ${_rp.format(cart.subtotal)}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                  ElevatedButton(
                    onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CheckoutScreen())),
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.white, foregroundColor: Colors.black),
                    child: const Text('Lihat Pesanan →', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            )
          : null,

      // 5. BOTTOM NAVIGATION BAR
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 0,
        selectedItemColor: Colors.black,
        unselectedItemColor: Colors.grey,
        onTap: (i) {
          if (i == 1) Navigator.push(context, MaterialPageRoute(builder: (_) => const PesananScreen()));
          if (i == 2) _konfirmasiLogout(); // Memanggil dialog konfirmasi
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.storefront), label: 'MENU'),
          BottomNavigationBarItem(icon: Icon(Icons.receipt), label: 'PESANAN AKTIF'),
          BottomNavigationBarItem(icon: Icon(Icons.logout), label: 'KELUAR'),
        ],
      ),
    );
  }
}
