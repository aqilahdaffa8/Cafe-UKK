import 'dart:convert';
import 'package:http/http.dart' as http;
import '../constant.dart';
import '../models/menu_model.dart';

class MenuService {
  // Mengambil daftar menu aktif dari backend
  Future<List<MenuModel>> getMenu({String? kategori}) async {
    var urlString = '${AppConstants.baseUrl}/menu';
    if (kategori != null && kategori != 'semua') {
      urlString += '?kategori=$kategori';
    }

    final url = Uri.parse(urlString);
    final response = await http.get(
      url,
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final List list = data['data'];
      return list.map((item) => MenuModel.fromJson(item)).toList();
    } else {
      throw Exception('Gagal memuat daftar menu kafe.');
    }
  }
}