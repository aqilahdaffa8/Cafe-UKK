import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/cart_provider.dart';
import '../services/pesanan_service.dart';
import 'pesanan_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _rp = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
  int _mejaDipilih = 1; // Default Meja 1
  String _metode = 'tunai'; // Default Tunai
  bool _isLoading = false;

  void _kirimPesanan(CartProvider cart) async {
    if (cart.items.isEmpty) return;

    setState(() => _isLoading = true);

    final res = await PesananService().buatPesanan(
      nomorMeja: _mejaDipilih,
      metodePembayaran: _metode,
      items: cart.toApiJson(),
    );

    setState(() => _isLoading = false);

    if (res['sukses'] == true) {
      cart.bersihkanKeranjang();
      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const PesananScreen()),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['pesan']), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);

    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      appBar: AppBar(
        title: const Text('Konfirmasi Pesanan & Meja', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
      ),
      body: cart.items.isEmpty
          ? const Center(child: Text('Keranjang masih kosong.'))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // 1. PILIHAN NOMOR MEJA (Dropdown)
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Pilih Nomor Meja Pelanggan (Wajib):',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<int>(
                          initialValue: _mejaDipilih,
                          decoration: InputDecoration(
                            prefixIcon: const Icon(Icons.table_restaurant_outlined, size: 20, color: Colors.black87),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            filled: true,
                            fillColor: const Color(0xFFF9FAFB),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                              borderSide: BorderSide(color: Colors.grey.shade300),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                              borderSide: BorderSide(color: Colors.grey.shade300),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                              borderSide: const BorderSide(color: Colors.black, width: 1.5),
                            ),
                          ),
                          icon: const Icon(Icons.keyboard_arrow_down, color: Colors.black),
                          items: List.generate(10, (i) {
                            final no = i + 1;
                            final label = no < 10 ? 'Meja 0$no' : 'Meja $no';
                            return DropdownMenuItem<int>(
                              value: no,
                              child: Text(
                                label,
                                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Colors.black87),
                              ),
                            );
                          }),
                          onChanged: (val) {
                            if (val != null) {
                              setState(() => _mejaDipilih = val);
                            }
                          },
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // 2. DAFTAR ITEM YANG DIPESAN
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.grey.shade200)),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('DAFTAR MENU DIPESAN (${cart.totalItemCount} ITEM)', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey)),
                        const Divider(height: 16),
                        ...cart.items.map((item) {
                          return Padding(
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(item.menu.namaMenu, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                      Text(_rp.format(item.menu.harga), style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                    ],
                                  ),
                                ),
                                Row(
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.remove_circle_outline, size: 20),
                                      onPressed: () => cart.kurangiItem(item.menu.idMenu),
                                    ),
                                    Text('${item.jumlah}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                    IconButton(
                                      icon: const Icon(Icons.add_circle_outline, size: 20),
                                      onPressed: () => cart.tambahItem(item.menu),
                                    ),
                                  ],
                                ),
                                SizedBox(
                                  width: 80,
                                  child: Text(_rp.format(item.subtotal), textAlign: TextAlign.right, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                ),
                              ],
                            ),
                          );
                        }),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // 3. RINCIAN BIAYA (Subtotal, Service 5%, Pajak 10%)
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.grey.shade200)),
                    child: Column(
                      children: [
                        _baris('Subtotal', _rp.format(cart.subtotal)),
                        const SizedBox(height: 6),
                        _baris('Service Charge (5%)', _rp.format(cart.serviceCharge)),
                        const SizedBox(height: 6),
                        _baris('Pajak Restoran (10%)', _rp.format(cart.pajak)),
                        const Divider(height: 20),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('TOTAL KESELURUHAN', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                            Text(_rp.format(cart.totalBayar), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // 4. PILIHAN PEMBAYARAN
                  const Text('Pilihan Pembayaran:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  const SizedBox(height: 8),
                  _opsiBayar('tunai', 'Tunai (Cash di Kasir)', 'Bayar langsung di kasir sebelum/sesudah pesanan'),
                  const SizedBox(height: 8),
                  _opsiBayar('qris', 'QRIS (Scan di Meja / Kasir)', 'Gopay, OVO, Dana, BCA, Livin'),
                  const SizedBox(height: 20),

                  // 5. TOMBOL KIRIM PESANAN
                  ElevatedButton(
                    onPressed: _isLoading ? null : () => _kirimPesanan(cart),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.black,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                    ),
                    child: _isLoading
                        ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text('Kirim Pesanan (${_rp.format(cart.totalBayar)}) →', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _baris(String label, String nilai) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
        Text(nilai, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500)),
      ],
    );
  }

  Widget _opsiBayar(String key, String judul, String sub) {
    final aktif = _metode == key;
    return GestureDetector(
      onTap: () => setState(() => _metode = key),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: aktif ? Colors.black : Colors.grey.shade300, width: aktif ? 1.5 : 1),
        ),
        child: Row(
          children: [
            Icon(aktif ? Icons.radio_button_checked : Icons.radio_button_off, size: 18, color: aktif ? Colors.black : Colors.grey),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(judul, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  Text(sub, style: const TextStyle(color: Colors.grey, fontSize: 11)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}