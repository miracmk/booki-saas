import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/appointments_provider.dart';
import '../../../providers/booking_provider.dart';
import '../../widgets/tenant_badge.dart';
import '../../widgets/appointment_card.dart';

class CustomerHomeScreen extends ConsumerWidget {
  const CustomerHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;
    final appointmentsAsync = ref.watch(appointmentsListProvider);
    final servicesAsync = ref.watch(servicesListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const TenantBadge(),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () {
              ref.invalidate(appointmentsListProvider);
              ref.invalidate(servicesListProvider);
            },
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(appointmentsListProvider);
            ref.invalidate(servicesListProvider);
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Karşılama Kartı
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Merhaba, ${user?.firstName ?? 'Değerli Müşterimiz'} 👋',
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontSize: 22,
                                fontWeight: FontWeight.w800,
                              ),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Bugün kendinize bir iyilik yapın',
                          style: TextStyle(
                            color: AppTheme.textSecondaryLight,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Yaklaşan Randevu Kartı (Varsa)
                appointmentsAsync.when(
                  data: (appointments) {
                    final upcoming = appointments
                        .where((a) => a.isUpcoming && !a.isCancelled)
                        .toList();

                    if (upcoming.isEmpty) {
                      return _buildNoAppointmentCard(context, ref);
                    }

                    final nextAppt = upcoming.first;
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Yaklaşan Randevunuz',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.textPrimaryLight,
                          ),
                        ),
                        const SizedBox(height: 10),
                        AppointmentCard(
                          appointment: nextAppt,
                          onTap: () {},
                        ),
                      ],
                    );
                  },
                  loading: () => const Center(
                    child: Padding(
                      padding: EdgeInsets.all(24),
                      child: CircularProgressIndicator(),
                    ),
                  ),
                  error: (err, stack) => _buildNoAppointmentCard(context, ref),
                ),

                const SizedBox(height: 28),

                // Popüler Hizmetler Başlığı
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Hizmetlerimiz',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.textPrimaryLight,
                      ),
                    ),
                    Text(
                      'Tümünü Gör',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.primaryColor,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Hizmetler Listesi
                servicesAsync.when(
                  data: (services) {
                    if (services.isEmpty) {
                      return const Center(
                        child: Padding(
                          padding: EdgeInsets.all(16),
                          child: Text('Kayıtlı hizmet bulunamadı.'),
                        ),
                      );
                    }

                    return ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: services.length > 5 ? 5 : services.length,
                      itemBuilder: (context, index) {
                        final service = services[index];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 10),
                          child: ListTile(
                            leading: Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: AppTheme.primaryColor.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(
                                Icons.spa_rounded,
                                color: AppTheme.primaryColor,
                              ),
                            ),
                            title: Text(
                              service.name,
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                              ),
                            ),
                            subtitle: Text(
                              '${service.formattedDuration} • ${service.formattedPrice}',
                              style: const TextStyle(
                                color: AppTheme.textSecondaryLight,
                                fontSize: 13,
                              ),
                            ),
                            trailing: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 14, vertical: 8),
                                textStyle: const TextStyle(fontSize: 12),
                              ),
                              onPressed: () {
                                ref
                                    .read(bookingWizardProvider.notifier)
                                    .selectService(service);
                                DefaultTabController.of(context).animateTo(1);
                              },
                              child: const Text('Seç'),
                            ),
                          ),
                        );
                      },
                    );
                  },
                  loading: () => const Center(
                    child: Padding(
                      padding: EdgeInsets.all(20),
                      child: CircularProgressIndicator(),
                    ),
                  ),
                  error: (e, _) => Center(
                    child: Text('Hizmetler yüklenemedi: $e'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildNoAppointmentCard(BuildContext context, WidgetRef ref) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppTheme.primaryColor.withValues(alpha: 0.08),
            AppTheme.secondaryColor.withValues(alpha: 0.05),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: AppTheme.primaryColor.withValues(alpha: 0.15),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.event_available_rounded,
                  color: AppTheme.primaryColor,
                  size: 24,
                ),
              ),
              const SizedBox(width: 14),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Aktif Randevunuz Yok',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.textPrimaryLight,
                      ),
                    ),
                    SizedBox(height: 2),
                    Text(
                      'Birkaç tıkla dilediğiniz güne randevu alın.',
                      style: TextStyle(
                        fontSize: 13,
                        color: AppTheme.textSecondaryLight,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
