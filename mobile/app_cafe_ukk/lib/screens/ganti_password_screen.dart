import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';
import 'menu_screen.dart';

class GantiPasswordScreen extends StatefulWidget {
  final bool isWajib;

  const GantiPasswordScreen({super.key, this.isWajib = false});

  @override
  State<GantiPasswordScreen> createState() => _GantiPasswordScreenState();
}

class _GantiPasswordScreenState extends State<GantiPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _passLamaController = TextEditingController();
  final _passBaruController = TextEditingController();
  final _konfirmasiController = TextEditingController();

  bool _obscureLama = true;
  bool _obscureBaru = true;
  bool _obscureKonfirmasi = true;
  bool _isLoading = false;

  @override
  void dispose() {
    _passLamaController.dispose();
    _passBaruController.dispose();
    _konfirmasiController.dispose();
    super.dispose();
  }

  void _simpanPassword() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final res = await AuthService().gantiPassword(
      passwordLama: _passLamaController.text,
      passwordBaru: _passBaruController.text,
      passwordKonfirmasi: _konfirmasiController.text,
    );

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (res['sukses'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['pesan'] ?? 'Password berhasil diperbarui!'),
          backgroundColor: Colors.black,
        ),
      );

      // Setelah berhasil, arahkan ke MenuScreen
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const MenuScreen()),
        (_) => false,
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['pesan'] ?? 'Gagal mengganti password.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _logout() async {
    await AuthService().logout();
    if (!mounted) return;
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      // Jika wajib ganti password, cegah tombol back Android
      canPop: !widget.isWajib,
      child: Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          backgroundColor: Colors.white,
          elevation: 0,
          title: Text(
            widget.isWajib ? 'Wajib Ganti Password' : 'Ganti Password',
            style: const TextStyle(color: Colors.black, fontSize: 16, fontWeight: FontWeight.bold),
          ),
          automaticallyImplyLeading: !widget.isWajib,
          actions: [
            if (widget.isWajib)
              IconButton(
                tooltip: 'Keluar Akun',
                icon: const Icon(Icons.logout, color: Colors.black87),
                onPressed: _logout,
              ),
          ],
        ),
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Banner Peringatan jika wajib
                  if (widget.isWajib) ...[
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.black87),
                      ),
                      child: const Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.shield_outlined, size: 24, color: Colors.black),
                          SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Password Sementara Terdeteksi',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                ),
                                SizedBox(height: 4),
                                Text(
                                  'Kasir telah mereset password akun Anda. Demi keamanan, Anda wajib membuat password baru sebelum dapat memesan menu.',
                                  style: TextStyle(fontSize: 12, color: Colors.black87),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],

                  // Field 1: Password Lama / Sementara
                  Text(
                    widget.isWajib ? 'Password Sementara (dari Kasir)' : 'Password Lama',
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                  ),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _passLamaController,
                    obscureText: _obscureLama,
                    decoration: InputDecoration(
                      hintText: widget.isWajib ? 'Masukkan password dari kasir' : 'Masukkan password lama',
                      hintStyle: const TextStyle(fontSize: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      prefixIcon: const Icon(Icons.lock_outline, size: 20),
                      suffixIcon: IconButton(
                        icon: Icon(_obscureLama ? Icons.visibility_off : Icons.visibility, size: 20),
                        onPressed: () => setState(() => _obscureLama = !_obscureLama),
                      ),
                    ),
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Password lama wajib diisi.';
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),

                  // Field 2: Password Baru
                  const Text('Password Baru', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _passBaruController,
                    obscureText: _obscureBaru,
                    decoration: InputDecoration(
                      hintText: 'Minimal 8 karakter',
                      hintStyle: const TextStyle(fontSize: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      prefixIcon: const Icon(Icons.lock_reset, size: 20),
                      suffixIcon: IconButton(
                        icon: Icon(_obscureBaru ? Icons.visibility_off : Icons.visibility, size: 20),
                        onPressed: () => setState(() => _obscureBaru = !_obscureBaru),
                      ),
                    ),
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Password baru wajib diisi.';
                      if (v.length < 8) return 'Password minimal 8 karakter.';
                      if (v == _passLamaController.text) {
                        return 'Password baru tidak boleh sama dengan password lama.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),

                  // Field 3: Konfirmasi Password Baru
                  const Text('Konfirmasi Password Baru', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _konfirmasiController,
                    obscureText: _obscureKonfirmasi,
                    decoration: InputDecoration(
                      hintText: 'Ulangi password baru',
                      hintStyle: const TextStyle(fontSize: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      prefixIcon: const Icon(Icons.check_circle_outline, size: 20),
                      suffixIcon: IconButton(
                        icon: Icon(_obscureKonfirmasi ? Icons.visibility_off : Icons.visibility, size: 20),
                        onPressed: () => setState(() => _obscureKonfirmasi = !_obscureKonfirmasi),
                      ),
                    ),
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Konfirmasi password wajib diisi.';
                      if (v != _passBaruController.text) {
                        return 'Konfirmasi password tidak cocok.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 28),

                  // Tombol Simpan
                  ElevatedButton(
                    onPressed: _isLoading ? null : _simpanPassword,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.black,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: _isLoading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          )
                        : const Text(
                            'Simpan Password Baru',
                            style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                  ),

                  if (widget.isWajib) ...[
                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: _logout,
                      child: const Text(
                        'Keluar dari Akun',
                        style: TextStyle(color: Colors.grey, fontSize: 12),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
