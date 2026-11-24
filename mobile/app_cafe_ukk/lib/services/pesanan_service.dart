import 'dart:convert';
import 'package:http/http.dart' as http;
import '../constant.dart';
import '../models/pesanan_model.dart';
import 'auth_service.dart';

class PesananService {
  final AuthService _authService = AuthService();

  //Ambil Riwayat Pesanan Pelanggan
  Future<List<PesananModel>> getRiwayatPesanan() async {
    final token = await _authService.getToken();
    final url = Uri.parse('${AppConstants.baseUrl}/pesanan');

    final response = await http.get(
      url,
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final List list = data['data'];
      return list.map((item) => PesananModel.fromJson(item)).toList();
    } else {
      throw Exception('Gagal memuat riwayat pesanan.');
    }
  }

  //Buat Pesanan Baru
  Future<Map<String, dynamic>> buatPesanan({
    required int nomorMeja,
    required String metodePembayaran,
    required List<Map<String, dynamic>> items,
  }) async {
    final token = await _authService.getToken();
    final url = Uri.parse('${AppConstants.baseUrl}/pesanan');

    final response = await http.post(
      url,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'nomor_meja': nomorMeja,
        'metode_pembayaran': metodePembayaran,
        'items': items,
      }),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 201 && data['sukses'] == true) {
      return {
        'sukses': true,
        'pesan': data['pesan'],
        'data': PesananModel.fromJson(data['data']),
      };
    } else {
      return {
        'sukses': false,
        'pesan': data['pesan'] ?? 'Pesanan gagal diproses.',
      };
    }
  }
}
