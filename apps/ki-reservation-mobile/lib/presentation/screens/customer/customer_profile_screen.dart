import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/auth_provider.dart';
import '../auth/tenant_selection_screen.dart';

class CustomerProfileScreen extends ConsumerWidget {
  const CustomerProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;
    final tenant = authState.currentTenant;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Profil & Ayarlar'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              // Profil Kartı
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 32,
                        backgroundColor:
                            AppTheme.primaryColor.withValues(alpha: 0.12),
                        child: Text(
                          user?.firstName.isNotEmpty == true
                              ? user!.firstName[0].toUpperCase()
                              : 'M',
                          style: const TextStyle(
                            color: AppTheme.primaryColor,
                            fontWeight: FontWeight.w800,
                            fontSize: 24,
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?.fullName ?? 'Müşteri',
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                                color: AppTheme.textPrimaryLight,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              user?.email ?? '',
                              style: const TextStyle(
                                fontSize: 13,
                                color: AppTheme.textSecondaryLight,
                              ),
                            ),
                            if (user?.phoneNumber != null &&
                                user!.phoneNumber!.isNotEmpty) ...[
                              const SizedBox(height: 2),
                              Text(
                                user.phoneNumber!,
                                style: const TextStyle(
                                  fontSize: 13,
                                  color: AppTheme.textSecondaryLight,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Aktif İşletme & Değiştirme
              Card(
                child: ListTile(
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
                  leading: Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(
                      Icons.storefront_rounded,
                      color: AppTheme.primaryColor,
                    ),
                  ),
                  title: const Text(
                    'Aktif İşletme',
                    style: TextStyle(fontSize: 12, color: AppTheme.textSecondaryLight),
                  ),
                  subtitle: Text(
                    tenant?.displayName ?? 'İşletme Belirtilmedi',
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.textPrimaryLight,
                    ),
                  ),
                  trailing: OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                    ),
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => TenantSelectionScreen(
                            isSwitchMode: true,
                            onSelected: (selected) {
                              ref
                                  .read(authProvider.notifier)
                                  .switchTenant(selected);
                              Navigator.of(context).pop();
                            },
                          ),
                        ),
                      );
                    },
                    child: const Text('Değiştir'),
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Bildirimler & Güvenlik
              Card(
                child: Column(
                  children: [
                    ListTile(
                      leading: const Icon(Icons.notifications_outlined),
                      title: const Text('Randevu Hatırlatmaları'),
                      subtitle: const Text('SMS ve Push bildirimler aktif'),
                      trailing: const Icon(Icons.check_circle_rounded,
                          color: AppTheme.accentSuccess, size: 20),
                    ),
                    const Divider(height: 1),
                    ListTile(
                      leading: const Icon(Icons.lock_outline_rounded),
                      title: const Text('Şifre Değiştir'),
                      trailing: const Icon(Icons.chevron_right_rounded),
                      onTap: () {},
                    ),
                    const Divider(height: 1),
                    ListTile(
                      leading: const Icon(Icons.info_outline_rounded),
                      title: const Text('Uygulama Hakkında'),
                      subtitle: const Text('BooKi Mobil v1.0.0'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Çıkış Yap Butonu
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.accentDanger,
                  side: const BorderSide(color: AppTheme.accentDanger),
                  minimumSize: const Size.fromHeight(50),
                ),
                icon: const Icon(Icons.logout_rounded, size: 20),
                label: const Text('Oturumu Kapat'),
                onPressed: () => _confirmLogout(context, ref),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Çıkış Yap'),
        content: const Text(
          'Oturumunuz kapatılacaktır. Devam etmek istiyor musunuz?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('İptal'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.accentDanger,
            ),
            onPressed: () {
              Navigator.of(ctx).pop();
              ref.read(authProvider.notifier).logout();
            },
            child: const Text('Çıkış Yap'),
          ),
        ],
      ),
    );
  }
}

