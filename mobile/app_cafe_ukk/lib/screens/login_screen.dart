import 'package:flutter/material.dart';
import '../constant.dart';
import '../services/auth_service.dart';
import 'register_screen.dart';
import 'menu_screen.dart';
import 'ganti_password_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _noTelp = TextEditingController();
  final _password = TextEditingController();
  bool _isLoading = false;
  bool _obscure = true;

  void _login() async {
    final telp = _noTelp.text.trim();
    final pass = _password.text;

    if (telp.isEmpty || pass.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nomor handphone dan kata sandi wajib diisi.')),
      );
      return;
    }

    setState(() => _isLoading = true);
    final res = await AuthService().login(noTelp: telp, password: pass);
    setState(() => _isLoading = false);

    if (res['sukses'] == true) {
      if (!mounted) return;
      if (res['wajib_ganti_password'] == true) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (_) => const GantiPasswordScreen(isWajib: true)),
        );
      } else {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const MenuScreen()));
      }
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['pesan']), backgroundColor: Colors.red),
      );
    }
  }

  void _showServerConfigDialog() {
    final controller = TextEditingController(text: AppConstants.baseUrl);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Pengaturan Server IP', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Masukkan alamat IP/Host server backend Laravel:',
              style: TextStyle(fontSize: 12, color: Colors.grey),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              decoration: const InputDecoration(
                hintText: 'http://192.168.1.26:8000/api',
                labelText: 'Base URL API',
                border: OutlineInputBorder(),
              ),
              style: const TextStyle(fontSize: 13),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () async {
              await AppConstants.resetBaseUrl();
              if (!ctx.mounted) return;
              Navigator.pop(ctx);
              setState(() {});
              if (!mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Server di-reset ke default.')),
              );
            },
            child: const Text('Reset', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.black, foregroundColor: Colors.white),
            onPressed: () async {
              final val = controller.text.trim();
              if (val.isNotEmpty) {
                await AppConstants.setBaseUrl(val);
                if (!ctx.mounted) return;
                Navigator.pop(ctx);
                setState(() {});
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Server diubah ke: ${AppConstants.baseUrl}')),
                );
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Kedai Kopi Senja', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_outlined, color: Colors.black87),
            tooltip: 'Pengaturan Server',
            onPressed: _showServerConfigDialog,
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text('Masuk Pelanggan', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              const Text('Masuk untuk memesan dan memantau status pesanan.', style: TextStyle(color: Colors.grey, fontSize: 13)),
              const SizedBox(height: 24),

              // Form Nomor HP
              TextField(
                controller: _noTelp,
                keyboardType: TextInputType.phone,
                cursorColor: Colors.black,
                decoration: const InputDecoration(
                  labelText: 'Nomor Handphone',
                  hintText: '08xxxxxxxxxx',
                  border: OutlineInputBorder(),
                  focusedBorder: OutlineInputBorder(borderSide: BorderSide(color: Colors.black, width: 1.5)),
                  prefixIcon: Icon(Icons.phone, size: 20),
                ),
              ),
              const SizedBox(height: 16),

              // Form Kata Sandi
              TextField(
                controller: _password,
                obscureText: _obscure,
                cursorColor: Colors.black,
                decoration: InputDecoration(
                  labelText: 'Kata Sandi',
                  border: const OutlineInputBorder(),
                  focusedBorder: const OutlineInputBorder(borderSide: BorderSide(color: Colors.black, width: 1.5)),
                  prefixIcon: const Icon(Icons.lock_outline, size: 20),
                  suffixIcon: IconButton(
                    icon: Icon(_obscure ? Icons.visibility_off : Icons.visibility, size: 20),
                    onPressed: () => setState(() => _obscure = !_obscure),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Tombol Masuk
              ElevatedButton(
                onPressed: _isLoading ? null : _login,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.black,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                ),
                child: _isLoading
                    ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text('Masuk →', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
              const SizedBox(height: 16),

              // Info Lupa Password Kasir
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  border: Border.all(color: Colors.grey.shade200),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Text(
                  'Jika lupa password, silakan minta reset password sementara ke Kasir.',
                  style: TextStyle(fontSize: 11, color: Colors.grey),
                ),
              ),
              const SizedBox(height: 20),

              // Link Daftar
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('Belum punya akun? ', style: TextStyle(fontSize: 13, color: Colors.grey)),
                  GestureDetector(
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterScreen())),
                    child: const Text('Daftar Sekarang', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, decoration: TextDecoration.underline)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}