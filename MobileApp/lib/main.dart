import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'core/storage/storage_service.dart';
import 'core/theme/app_theme.dart';
import 'providers/auth_provider.dart';
import 'presentation/screens/role_gate_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Türkçe tarih formatı başlatma
  await initializeDateFormatting('tr_TR', null);

  // Yerel depolama başlatma
  final storageService = await StorageService.init();

  runApp(
    ProviderScope(
      overrides: [
        storageServiceProvider.overrideWithValue(storageService),
      ],
      child: const BooKiApp(),
    ),
  );
}

class BooKiApp extends StatelessWidget {
  const BooKiApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'BooKi Rezervasyon',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const RoleGateScreen(),
    );
  }
}
