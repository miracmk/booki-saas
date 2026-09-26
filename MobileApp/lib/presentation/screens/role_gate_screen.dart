import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/auth_provider.dart';
import 'auth/login_screen.dart';
import 'auth/tenant_selection_screen.dart';
import 'customer/customer_shell.dart';
import 'staff/staff_shell.dart';

class RoleGateScreen extends ConsumerWidget {
  const RoleGateScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);

    // 1. Kullanıcı birden fazla işletmede bulunduysa işletme seçimi göster
    if (authState.multipleTenants && authState.availableTenants.isNotEmpty) {
      return const TenantSelectionScreen();
    }

    // 2. Giriş yapılmadıysa giriş ekranı göster
    if (!authState.isAuthenticated || authState.user == null) {
      return const LoginScreen();
    }

    // 3. Giriş yapıldıysa role göre ilgili kabuğu (Shell) aç
    final user = authState.user!;
    if (user.isCustomer) {
      return const CustomerShell();
    } else {
      return const StaffShell();
    }
  }
}

