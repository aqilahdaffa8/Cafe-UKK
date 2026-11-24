import 'menu_model.dart';

// Model untuk item detail pesanan
class DetailPesananModel {
  final int idDetail;
  final int idMenu;
  final int jumlahMenu;
  final int hargaSatuan;
  final MenuModel? menu;

  DetailPesananModel({
    required this.idDetail,
    required this.idMenu,
    required this.jumlahMenu,
    required this.hargaSatuan,
    this.menu,
  });

  factory DetailPesananModel.fromJson(Map<String, dynamic> json) {
    return DetailPesananModel(
      // Cocokkan dengan nama kolom Laravel: 'id_detail_pesanan'
      idDetail: (json['id_detail_pesanan'] ?? 0) as int,
      idMenu: (json['id_menu'] ?? 0) as int,
      jumlahMenu: (json['jumlah_menu'] ?? 1) as int,
      hargaSatuan: (json['harga_satuan'] ?? 0) as int,
      menu: json['menu'] != null ? MenuModel.fromJson(json['menu']) : null,
    );
  }
}

// Model utama untuk satu transaksi pesanan pelanggan
class PesananModel {
  final int idPesanan;
  final int nomorMeja;
  final String metodePembayaran;
  final int subtotal;
  final int service;
  final int pajak;
  final int totalHarga;
  final String status;
  final String createdAt;
  final List<DetailPesananModel> items;

  PesananModel({
    required this.idPesanan,
    required this.nomorMeja,
    required this.metodePembayaran,
    required this.subtotal,
    required this.service,
    required this.pajak,
    required this.totalHarga,
    required this.status,
    required this.createdAt,
    required this.items,
  });

  factory PesananModel.fromJson(Map<String, dynamic> json) {
    var list = json['detail_pesanans'] as List? ?? [];
    List<DetailPesananModel> itemDetail =
        list.map((i) => DetailPesananModel.fromJson(i)).toList();

    return PesananModel(
      idPesanan: (json['id_pesanan'] ?? 0) as int,
      nomorMeja: (json['nomor_meja'] ?? 1) as int,
      metodePembayaran: json['metode_pembayaran'] as String? ?? 'tunai',
      subtotal: (json['subtotal'] ?? 0) as int,
      service: (json['service'] ?? 0) as int,
      pajak: (json['pajak'] ?? 0) as int,
      totalHarga: (json['total_harga'] ?? 0) as int,
      status: json['status'] as String? ?? 'baru',
      createdAt: json['created_at'] as String? ?? '',
      items: itemDetail,
    );
  }
}