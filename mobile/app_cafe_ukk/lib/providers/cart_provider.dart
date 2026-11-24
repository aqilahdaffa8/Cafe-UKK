import 'package:flutter/material.dart';
import '../models/menu_model.dart';

// Class pembungkus item keranjang (Menu apa dan berapa jumlah porsinya)
class CartItem {
  final MenuModel menu;
  int jumlah;

  CartItem({
    required this.menu,
    this.jumlah = 1,
  });

  // Total harga per baris menu (Harga satuan x Jumlah porsi)
  int get subtotal => menu.harga * jumlah;
}

class CartProvider with ChangeNotifier {
  // Map untuk menyimpan keranjang: Key = idMenu, Value = CartItem
  final Map<int, CartItem> _items = {};

  // Mengambil daftar item keranjang dalam bentuk List
  List<CartItem> get items => _items.values.toList();

  // Menghitung total porsi semua menu di keranjang (untuk angka badge di ikon keranjang)
  int get totalItemCount {
    int total = 0;
    _items.forEach((key, item) {
      total += item.jumlah;
    });
    return total;
  }

  // 1. Hitung Subtotal
  int get subtotal {
    int total = 0;
    _items.forEach((key, item) {
      total += item.subtotal;
    });
    return total;
  }

  // 2. Service Charge 5% 
  int get serviceCharge => (subtotal * 0.05).round();

  // 3. Pajak 10% dari (Subtotal + Service Charge) sesuai aturan UKK
  int get pajak => ((subtotal + serviceCharge) * 0.10).round();

  // 4. Total akhir
  int get totalBayar => subtotal + serviceCharge + pajak;

  // Method Tambah Menu ke Keranjang
  void tambahItem(MenuModel menu) {
    if (!menu.tersedia) return; // Jangan izinkan jika stok bahan habis

    if (_items.containsKey(menu.idMenu)) {
      // Jika menu sudah ada di keranjang, tambah jumlahnya +1
      _items[menu.idMenu]!.jumlah += 1;
    } else {
      // Jika menu belum ada, buat baris baru
      _items[menu.idMenu] = CartItem(menu: menu, jumlah: 1);
    }
    notifyListeners(); // Beritahu UI agar update
  }

  // Method Kurangi Jumlah Menu
  void kurangiItem(int idMenu) {
    if (!_items.containsKey(idMenu)) return;

    if (_items[idMenu]!.jumlah > 1) {
      _items[idMenu]!.jumlah -= 1;
    } else {
      // Jika jumlahnya tinggal 1 dan dikurangi lagi, hapus dari keranjang
      _items.remove(idMenu);
    }
    notifyListeners();
  }

  // Method Hapus Satu Menu Langsung
  void hapusItem(int idMenu) {
    _items.remove(idMenu);
    notifyListeners();
  }

  // Method Kosongkan Keranjang (setelah pesanan berhasil checkout)
  void bersihkanKeranjang() {
    _items.clear();
    notifyListeners();
  }

  // Mengubah keranjang ke format JSON API Laravel
  List<Map<String, dynamic>> toApiJson() {
    return _items.values.map((item) {
      return {
        'id_menu': item.menu.idMenu,
        'jumlah': item.jumlah,
      };
    }).toList();
  }
}