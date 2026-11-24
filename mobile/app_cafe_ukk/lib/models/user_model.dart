class UserModel {
  final int idPengguna;
  final String? nama;
  final String noTelp;
  final String? email;
  final String peran;
  final bool wajibGantiPassword;

  UserModel({
    required this.idPengguna,
    this.nama,
    required this.noTelp,
    this.email,
    required this.peran,
    this.wajibGantiPassword = false,
  });

  // Mengubah data JSON dari API menjadi objek UserModel
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      idPengguna: json['id_pengguna'] as int,
      nama: json['nama'] as String?,
      noTelp: json['no_telp'] as String,
      email: json['email'] as String?,
      peran: json['peran'] as String,
      wajibGantiPassword: json['wajib_ganti_password'] == true ||
          json['wajib_ganti_password'] == 1,
    );
  }
}