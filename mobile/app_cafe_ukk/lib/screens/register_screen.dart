import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import 'menu_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _noTelp = TextEditingController();
  final _password = TextEditingController();
  final _konfirmasi = TextEditingController();
  bool _isLoading = false;

  void _register() async {
    final telp = _noTelp.text.trim();
    final pass = _password.text;
    final konf = _konfirmasi.text;

    if (telp.isEmpty || pass.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nomor handphone dan kata sandi wajib diisi.')),
      );
      return;
    }

    if (pass != konf) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Konfirmasi kata sandi tidak cocok.')),
      );
      return;
    }

    setState(() => _isLoading = true);
    final res = await AuthService().register(
      noTelp: telp,
      password: pass,
      passwordConfirmation: konf,
    );
    setState(() => _isLoading = false);

    if (res['sukses'] == true) {
      if (!mounted) return;
      Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => const MenuScreen()), (_) => false);
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['pesan']), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Daftar Akun', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text('Buat Akun Pelanggan', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              const Text('Cukup daftar dengan nomor handphone untuk memesan.', style: TextStyle(color: Colors.grey, fontSize: 13)),
              const SizedBox(height: 24),

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

              TextField(
                controller: _password,
                obscureText: true,
                cursorColor: Colors.black,
                decoration: const InputDecoration(
                  labelText: 'Kata Sandi (min. 8 karakter)',
                  border: OutlineInputBorder(),
                  focusedBorder: OutlineInputBorder(borderSide: BorderSide(color: Colors.black, width: 1.5)),
                  prefixIcon: Icon(Icons.lock_outline, size: 20),
                ),
              ),
              const SizedBox(height: 16),

              TextField(
                controller: _konfirmasi,
                obscureText: true,
                cursorColor: Colors.black,
                decoration: const InputDecoration(
                  labelText: 'Ulangi Kata Sandi',
                  border: OutlineInputBorder(),
                  focusedBorder: OutlineInputBorder(borderSide: BorderSide(color: Colors.black, width: 1.5)),
                  prefixIcon: Icon(Icons.lock_outline, size: 20),
                ),
              ),
              const SizedBox(height: 24),

              ElevatedButton(
                onPressed: _isLoading ? null : _register,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.black,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                ),
                child: _isLoading
                    ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text('Daftar Sekarang →', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
              const SizedBox(height: 20),

              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('Sudah punya akun? ', style: TextStyle(fontSize: 13, color: Colors.grey)),
                  GestureDetector(
                    onTap: () => Navigator.pop(context),
                    child: const Text('Masuk di sini', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, decoration: TextDecoration.underline)),
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