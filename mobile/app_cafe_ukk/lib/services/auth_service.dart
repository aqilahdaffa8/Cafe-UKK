import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../constant.dart';
import '../models/user_model.dart';

class AuthService {
  //key untuk menyimpan token di memori HP
  static const String _tokenKey = 'token_auth';

  //ambil token yang tersimpan di HP
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  //simpan token ke HP
  Future<void> _saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  //hapus token saat logout
  Future<void> _removeToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  //registrasi Pelanggan Baru
  Future<Map<String, dynamic>> register({
    required String noTelp,
    required String password,
    required String passwordConfirmation,
  }) async {
    final url = Uri.parse('${AppConstants.baseUrl}/register');

    final response = await http.post(
      url,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'no_telp': noTelp,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 201 && data['sukses'] == true) {
      // Simpan token otomatis jika register berhasil
      final token = data['data']['token'];
      await _saveToken(token);
      return {'sukses': true, 'pesan': data['pesan']};
    } else {
      return {'sukses': false, 'pesan': data['pesan'] ?? 'Registrasi gagal.'};
    }
  }

  //Login Pelanggan
  Future<Map<String, dynamic>> login({
    required String noTelp,
    required String password,
  }) async {
    final url = Uri.parse('${AppConstants.baseUrl}/login');

    final response = await http.post(
      url,
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode({
        'no_telp': noTelp,
        'password': password,
      }),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['sukses'] == true) {
      final token = data['data']['token'];
      await _saveToken(token);
      final user = UserModel.fromJson(data['data']['user']);
      return {
        'sukses': true,
        'pesan': data['pesan'],
        'wajib_ganti_password': user.wajibGantiPassword,
        'user': user,
      };
    } else {
      return {'sukses': false, 'pesan': data['pesan'] ?? 'Login gagal.'};
    }
  }

  // Ganti Password Pelanggan (Wajib saat direset kasir atau inisiatif sendiri)
  Future<Map<String, dynamic>> gantiPassword({
    required String passwordLama,
    required String passwordBaru,
    required String passwordKonfirmasi,
  }) async {
    final token = await getToken();
    if (token == null) {
      return {'sukses': false, 'pesan': 'Sesi login tidak ditemukan.'};
    }

    final url = Uri.parse('${AppConstants.baseUrl}/ganti-password');
    try {
      final response = await http.post(
        url,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'password_lama': passwordLama,
          'password': passwordBaru,
          'password_confirmation': passwordKonfirmasi,
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['sukses'] == true) {
        return {'sukses': true, 'pesan': data['pesan']};
      } else {
        String pesan = data['pesan'] ?? 'Gagal memperbarui password.';
        if (data['errors'] != null && data['errors'] is Map) {
          final errors = data['errors'] as Map;
          final firstError = errors.values.first;
          if (firstError is List && firstError.isNotEmpty) {
            pesan = firstError.first.toString();
          }
        }
        return {'sukses': false, 'pesan': pesan};
      }
    } catch (e) {
      return {'sukses': false, 'pesan': 'Koneksi bermasalah: $e'};
    }
  }

  //Ambil Profil User yang Sedang Login
  Future<UserModel?> getProfil() async {
    final token = await getToken();
    if (token == null) return null;

    final url = Uri.parse('${AppConstants.baseUrl}/profil');
    final response = await http.get(
      url,
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return UserModel.fromJson(data['data']);
    }
    return null;
  }

  //Logout
  Future<void> logout() async {
    final token = await getToken();
    if (token != null) {
      final url = Uri.parse('${AppConstants.baseUrl}/logout');
      await http.post(
        url,
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token', //tiket masuk sanctum
        },
      );
    }
    await _removeToken();
  }
} 