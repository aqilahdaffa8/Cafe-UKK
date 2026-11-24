import 'package:shared_preferences/shared_preferences.dart';

// Konfigurasi Alamat API Backend Laravel
class AppConstants {
  static const String defaultBaseUrl = 'http://192.168.1.26:8000/api';
  static String _baseUrl = defaultBaseUrl;

  // Mengambil alamat base URL aktif
  static String get baseUrl => _baseUrl;

  // Inisialisasi saat aplikasi pertama kali dibuka (membaca dari SharedPreferences)
  static Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('custom_base_url') ?? defaultBaseUrl;
  }

  // Simpan alamat server baru
  static Future<void> setBaseUrl(String input) async {
    var formatted = input.trim();
    if (!formatted.startsWith('http://') && !formatted.startsWith('https://')) {
      formatted = 'http://$formatted';
    }
    
    // Pastikan berakhiran /api
    if (!formatted.endsWith('/api')) {
      if (formatted.endsWith('/')) {
        formatted = '${formatted}api';
      } else {
        formatted = '$formatted/api';
      }
    }
    
    _baseUrl = formatted;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('custom_base_url', _baseUrl);
  }

  // Reset ke alamat default
  static Future<void> resetBaseUrl() async {
    _baseUrl = defaultBaseUrl;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('custom_base_url');
  }
}
