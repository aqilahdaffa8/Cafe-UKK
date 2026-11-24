import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'constant.dart';
import 'providers/cart_provider.dart';
import 'services/auth_service.dart';
import 'screens/login_screen.dart';
import 'screens/menu_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppConstants.init();
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => CartProvider()),
      ],
      child: const CafePelangganApp(),
    ),
  );
}

class CafePelangganApp extends StatelessWidget {
  const CafePelangganApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Kedai Kopi Senja',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        scaffoldBackgroundColor: Colors.white,
        colorScheme: const ColorScheme.light(
          primary: Colors.black,
          onPrimary: Colors.white,
          surface: Colors.white,
          onSurface: Colors.black,
        ),
        useMaterial3: true,
      ),
      home: const SesiCheck(),
    );
  }
}

class SesiCheck extends StatelessWidget {
  const SesiCheck({super.key});

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<String?>(
      future: AuthService().getToken(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Scaffold(body: Center(child: CircularProgressIndicator(color: Colors.black)));
        }
        return snapshot.data != null ? const MenuScreen() : const LoginScreen();
      },
    );
  }
}