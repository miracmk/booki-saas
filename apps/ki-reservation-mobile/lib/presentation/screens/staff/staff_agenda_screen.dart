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
    final selectedDate = ref.watch(selectedAgendaDateProvider);
    final appointmentsAsync = ref.watch(agendaAppointmentsProvider);
    final authState = ref.watch(authProvider);
    final user = authState.user;

    final now = DateTime.now();
    final isToday = selectedDate.year == now.year &&
        selectedDate.month == now.month &&
        selectedDate.day == now.day;
    final isYesterday = selectedDate.year == now.year &&
        selectedDate.month == now.month &&
        selectedDate.day == now.subtract(const Duration(days: 1)).day;
    final isTomorrow = selectedDate.year == now.year &&
        selectedDate.month == now.month &&
        selectedDate.day == now.add(const Duration(days: 1)).day;

    String dateLabelPrefix = '';
    if (isToday) {
      dateLabelPrefix = 'Bugün, ';
    } else if (isYesterday) {
      dateLabelPrefix = 'Dün, ';
    } else if (isTomorrow) {
      dateLabelPrefix = 'Yarın, ';
    }

    final dateFormatted =
        '$dateLabelPrefix${DateFormat('d MMMM yyyy, EEEE', 'tr_TR').format(selectedDate)}';

    return Scaffold(
      appBar: AppBar(
        title: const TenantBadge(),
        actions: [
          IconButton(
            tooltip: 'Yenile',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.invalidate(agendaAppointmentsProvider),
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(agendaAppointmentsProvider);
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Personel Karşılama & Rol Bilgisi
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            user?.firstName != null && user!.firstName.isNotEmpty
                                ? 'Merhaba, ${user.firstName}'
                                : 'İşletme Ajandası',
                            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Randevu ve müşteri akışını takip edin',
                            style: TextStyle(
                              color: AppTheme.textSecondaryLight,
                              fontSize: 13,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
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
                const SizedBox(height: 18),

                // Tarih Seçici Çubuğu (Date Navigation Bar)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppTheme.borderLight),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.03),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.chevron_left_rounded),
                        tooltip: 'Önceki Gün',
                        onPressed: () {
                          ref.read(selectedAgendaDateProvider.notifier).setDate(
                              selectedDate.subtract(const Duration(days: 1)));
                        },
                      ),
                      Expanded(
                        child: InkWell(
                          onTap: () async {
                            final picked = await showDatePicker(
                              context: context,
                              initialDate: selectedDate,
                              firstDate: DateTime.now().subtract(const Duration(days: 365)),
                              lastDate: DateTime.now().add(const Duration(days: 365)),
                            );
                            if (picked != null) {
                              ref.read(selectedAgendaDateProvider.notifier).setDate(picked);
                            }
                          },
                          borderRadius: BorderRadius.circular(10),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 6),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(
                                  Icons.calendar_today_rounded,
                                  size: 16,
                                  color: AppTheme.primaryColor,
                                ),
                                const SizedBox(width: 8),
                                Flexible(
                                  child: Text(
                                    dateFormatted,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 13,
                                      color: AppTheme.textPrimaryLight,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.chevron_right_rounded),
                        tooltip: 'Sonraki Gün',
                        onPressed: () {
                          ref.read(selectedAgendaDateProvider.notifier).setDate(
                              selectedDate.add(const Duration(days: 1)));
                        },
                      ),
                    ],
                  ),
                ),

                if (!isToday) ...[
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton.icon(
                      onPressed: () {
                        ref.read(selectedAgendaDateProvider.notifier).setDate(DateTime.now());
                      },
                      icon: const Icon(Icons.today_rounded, size: 16),
                      label: const Text('Bugüne Dön', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                      style: TextButton.styleFrom(
                        visualDensity: VisualDensity.compact,
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      ),
                    ),
                  ),
                ],

                const SizedBox(height: 16),

                // İstatistik Sayaçları (Stats Summary)
                appointmentsAsync.when(
                  data: (list) {
                    final total = list.length;
                    final pending = list
                        .where((a) =>
                            a.status == AppointmentStatus.reserved ||
                            a.status == AppointmentStatus.confirmed)
                        .length;
                    final completed = list
                        .where((a) =>
                            a.status == AppointmentStatus.completed ||
                            a.status == AppointmentStatus.arrived)
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
                  loading: () => Row(
                    children: [
                      _kpiCard(
                        label: 'Toplam',
                        count: '-',
                        color: AppTheme.primaryColor,
                        icon: Icons.event_note_rounded,
                      ),
                      const SizedBox(width: 12),
                      _kpiCard(
                        label: 'Bekleyen',
                        count: '-',
                        color: AppTheme.accentWarning,
                        icon: Icons.hourglass_top_rounded,
                      ),
                      const SizedBox(width: 12),
                      _kpiCard(
                        label: 'Geldi/Bitti',
                        count: '-',
                        color: AppTheme.accentSuccess,
                        icon: Icons.check_circle_outline_rounded,
                      ),
                    ],
                  ),
                  error: (_, __) => const SizedBox.shrink(),
                ),

                const SizedBox(height: 24),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isToday ? 'Bugünkü Randevular' : 'Randevu Listesi',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.textPrimaryLight,
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.add_circle_outline_rounded, color: AppTheme.primaryColor),
                      tooltip: 'Hızlı Randevu Ekle',
                      onPressed: () {
                        ref.read(staffNavIndexProvider.notifier).setIndex(2);
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Randevu Listesi Alanı
                appointmentsAsync.when(
                  data: (appointments) {
                    if (appointments.isEmpty) {
                      return Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 36),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppTheme.borderLight),
                        ),
                        child: Column(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: AppTheme.primaryColor.withValues(alpha: 0.08),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.calendar_today_outlined,
                                size: 40,
                                color: AppTheme.primaryColor,
                              ),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isToday
                                  ? 'Bugün için planlanmış randevu yok'
                                  : 'Bu tarihte planlanmış randevu yok',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                                color: AppTheme.textPrimaryLight,
                              ),
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              'Yeni bir randevu ekleyebilir veya takvimden diğer günleri inceleyebilirsiniz.',
                              style: TextStyle(
                                fontSize: 13,
                                color: AppTheme.textSecondaryLight,
                              ),
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 20),
                            ElevatedButton.icon(
                              onPressed: () {
                                ref.read(staffNavIndexProvider.notifier).setIndex(2);
                              },
                              icon: const Icon(Icons.add_rounded),
                              label: const Text('Randevu Oluştur'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.primaryColor,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                            ),
                          ],
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
                              ref.invalidate(agendaAppointmentsProvider);
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
                  loading: () => Container(
                    padding: const EdgeInsets.symmetric(vertical: 40),
                    alignment: Alignment.center,
                    child: Column(
                      children: const [
                        CircularProgressIndicator(strokeWidth: 3),
                        SizedBox(height: 16),
                        Text(
                          'Randevular yükleniyor...',
                          style: TextStyle(
                            color: AppTheme.textSecondaryLight,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),
                  error: (e, _) => Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: AppTheme.accentDanger.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppTheme.accentDanger.withValues(alpha: 0.2)),
                    ),
                    child: Column(
                      children: [
                        const Icon(
                          Icons.error_outline_rounded,
                          color: AppTheme.accentDanger,
                          size: 40,
                        ),
                        const SizedBox(height: 12),
                        const Text(
                          'Randevular Yüklenemedi',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.textPrimaryLight,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Sunucu ile bağlantı kurulamadı veya bir hata oluştu.',
                          style: const TextStyle(
                            fontSize: 13,
                            color: AppTheme.textSecondaryLight,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => ref.invalidate(agendaAppointmentsProvider),
                          icon: const Icon(Icons.refresh_rounded, size: 18),
                          label: const Text('Tekrar Dene'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.primaryColor,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                        ),
                      ],
                    ),
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
