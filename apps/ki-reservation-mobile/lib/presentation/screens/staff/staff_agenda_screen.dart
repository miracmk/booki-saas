import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/appointment_model.dart';
import '../../../providers/appointments_provider.dart';
import '../../../providers/auth_provider.dart';
import '../../widgets/tenant_badge.dart';
import '../../widgets/appointment_card.dart';

class StaffAgendaScreen extends ConsumerWidget {
  const StaffAgendaScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final todayAsync = ref.watch(todayAppointmentsProvider);
    final authState = ref.watch(authProvider);
    final user = authState.user;

    final todayFormatted =
        DateFormat('d MMMM yyyy, EEEE', 'tr_TR').format(DateTime.now());

    return Scaffold(
      appBar: AppBar(
        title: const TenantBadge(),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.invalidate(todayAppointmentsProvider),
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(todayAppointmentsProvider);
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Personel Karşılama
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Bugünün Ajandası',
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.w800,
                              ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          todayFormatted,
                          style: const TextStyle(
                            color: AppTheme.textSecondaryLight,
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        user?.role == 'admin' ? 'Yönetici' : 'Uzman',
                        style: const TextStyle(
                          color: AppTheme.primaryColor,
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // İstatistik Sayaçları (Stats Summary)
                todayAsync.when(
                  data: (list) {
                    final total = list.length;
                    final pending = list
                        .where((a) => a.status == AppointmentStatus.reserved)
                        .length;
                    final completed = list
                        .where((a) => a.status == AppointmentStatus.completed || a.status == AppointmentStatus.arrived)
                        .length;

                    return Row(
                      children: [
                        _kpiCard(
                          label: 'Toplam',
                          count: '$total',
                          color: AppTheme.primaryColor,
                          icon: Icons.event_note_rounded,
                        ),
                        const SizedBox(width: 12),
                        _kpiCard(
                          label: 'Bekleyen',
                          count: '$pending',
                          color: AppTheme.accentWarning,
                          icon: Icons.hourglass_top_rounded,
                        ),
                        const SizedBox(width: 12),
                        _kpiCard(
                          label: 'Geldi/Bitti',
                          count: '$completed',
                          color: AppTheme.accentSuccess,
                          icon: Icons.check_circle_outline_rounded,
                        ),
                      ],
                    );
                  },
                  loading: () => const SizedBox.shrink(),
                  error: (_, __) => const SizedBox.shrink(),
                ),

                const SizedBox(height: 24),
                const Text(
                  'Bugünkü Randevular',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: 12),

                // Randevu Listesi
                todayAsync.when(
                  data: (appointments) {
                    if (appointments.isEmpty) {
                      return Container(
                        padding: const EdgeInsets.all(32),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade50,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppTheme.borderLight),
                        ),
                        child: const Center(
                          child: Column(
                            children: [
                              Icon(
                                Icons.calendar_today_outlined,
                                size: 48,
                                color: AppTheme.textSecondaryLight,
                              ),
                              SizedBox(height: 12),
                              Text(
                                'Bugün için planlanmış randevu yok.',
                                style: TextStyle(
                                  color: AppTheme.textSecondaryLight,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    // Saate göre sırala
                    final sorted = [...appointments]
                      ..sort((a, b) => a.startDatetime.compareTo(b.startDatetime));

                    return ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: sorted.length,
                      itemBuilder: (context, index) {
                        final appt = sorted[index];
                        return AppointmentCard(
                          appointment: appt,
                          showStaffActions: true,
                          onStatusChanged: (newStatus) async {
                            try {
                              await ref
                                  .read(appointmentRepositoryProvider)
                                  .updateStatus(appt.id, newStatus);
                              ref.invalidate(todayAppointmentsProvider);
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Durum güncellendi: $newStatus'),
                                    duration: const Duration(seconds: 2),
                                  ),
                                );
                              }
                            } catch (e) {
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text('Hata: $e')),
                                );
                              }
                            }
                          },
                        );
                      },
                    );
                  },
                  loading: () => const Center(
                    child: Padding(
                      padding: EdgeInsets.all(32),
                      child: CircularProgressIndicator(),
                    ),
                  ),
                  error: (e, _) => Center(
                    child: Text('Randevular yüklenemedi: $e'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _kpiCard({
    required String label,
    required String count,
    required Color color,
    required IconData icon,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 22),
            const SizedBox(height: 6),
            Text(
              count,
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppTheme.textSecondaryLight,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

