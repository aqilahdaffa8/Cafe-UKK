class MenuModel {
  final int idMenu;
  final String? kode;
  final String namaMenu;
  final String kategori;
  final int harga;
  final String? gambarUrl;
  final bool tersedia;

  MenuModel({
    required this.idMenu,
    this.kode,
    required this.namaMenu,
    required this.kategori,
    required this.harga,
    this.gambarUrl,
    required this.tersedia,
  });

  factory MenuModel.fromJson(Map<String, dynamic> json) {
    return MenuModel(
      idMenu: json['id_menu'] as int,
      kode: json['kode'] as String?,
      namaMenu: json['nama_menu'] as String? ?? 'Menu',
      kategori: json['kategori'] as String? ?? 'minuman',
      harga: (json['harga'] as num?)?.toInt() ?? 0,
      gambarUrl: json['gambar_url'] as String?,
      // Cegah error TypeError bila bernilai boolean, int (1/0), atau null
      tersedia: json['tersedia'] == true ||
          json['tersedia'] == 1 ||
          json['aktif'] == true ||
          json['aktif'] == 1 ||
          (json['tersedia'] == null && json['aktif'] == null),
    );
  }
}