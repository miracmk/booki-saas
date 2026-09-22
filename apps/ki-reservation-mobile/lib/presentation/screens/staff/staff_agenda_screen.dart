import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/appointments_provider.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/operations_provider.dart';
import '../../widgets/tenant_badge.dart';
import '../../widgets/appointment_card.dart';
import '../../widgets/booking/new_booking_wizard_sheet.dart';
import '../../widgets/crm/customer_crm_bottom_sheet.dart';
import '../../widgets/qr/qr_scanner_dialog.dart';
import '../../widgets/timers/active_table_timer_widget.dart';
import '../../widgets/timers/live_appointment_countdown_card.dart';

class StaffAgendaScreen extends ConsumerWidget {
  const StaffAgendaScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final selectedDate = ref.watch(selectedAgendaDateProvider);
    final appointmentsAsync = ref.watch(agendaAppointmentsProvider);
    final liveOpsAsync = ref.watch(liveOperationsProvider);
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
          // QR Okutma Butonu
          IconButton(
            tooltip: 'Kamera ile QR Okut (Check-in)',
            icon: const Icon(Icons.qr_code_scanner_rounded, color: Color(0xFF0F172A)),
            onPressed: () {
              HapticFeedback.lightImpact();
              QrScannerDialog.show(context);
            },
          ),
          IconButton(
            tooltip: 'Yenile',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () {
              ref.invalidate(agendaAppointmentsProvider);
              ref.invalidate(liveOperationsProvider);
              ref.invalidate(floorPlanProvider);
            },
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          HapticFeedback.lightImpact();
          NewBookingWizardSheet.show(context, initialDate: selectedDate);
        },
        backgroundColor: const Color(0xFF0F172A),
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded),
        label: const Text('Yeni Rezervasyon', style: TextStyle(fontWeight: FontWeight.w800)),
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(agendaAppointmentsProvider);
            ref.invalidate(liveOperationsProvider);
            ref.invalidate(floorPlanProvider);
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
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
                                : 'Canlı Operasyon Merkezi',
                            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                  fontWeight: FontWeight.w800,
                                  color: const Color(0xFF0F172A),
                                ),
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Online randevu & masa akışını gerçek zamanlı yönetin',
                            style: TextStyle(
                              color: AppTheme.textSecondaryLight,
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: const Color(0xFF10B981).withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.bolt_rounded, size: 14, color: Color(0xFF10B981)),
                          SizedBox(width: 4),
                          Text(
                            '%0 Komisyon',
                            style: TextStyle(
                              color: Color(0xFF10B981),
                              fontWeight: FontWeight.w800,
                              fontSize: 11,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // 1. CANLI GERİ SAYIM KARTI (Upcoming Countdown Card)
                liveOpsAsync.when(
                  loading: () => const SizedBox.shrink(),
                  error: (e, s) => const SizedBox.shrink(),
                  data: (liveData) {
                    if (liveData.upcomingCountdowns.isEmpty) {
                      return const SizedBox.shrink();
                    }
                    final nearest = liveData.upcomingCountdowns.first;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: LiveAppointmentCountdownCard(
                        item: nearest,
                        onCheckinTap: () {
                          QrScannerDialog.show(context);
                        },
                        onCustomerTap: () {
                          CustomerCrmBottomSheet.show(
                            context,
                            customerId: nearest.customerId,
                            fallbackName: nearest.customerName,
                            fallbackPhone: nearest.customerPhone,
                          );
                        },
                      ),
                    );
                  },
                ),

                // 2. AKTİF MASA / SEANS SÜRESİ KRONOMETRELERİ (Horizontal Elapsed Timers)
                liveOpsAsync.when(
                  loading: () => const SizedBox.shrink(),
                  error: (e, s) => const SizedBox.shrink(),
                  data: (liveData) {
                    if (liveData.activeTableTimers.isEmpty) {
                      return const SizedBox.shrink();
                    }
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Row(
                              children: [
                                Icon(Icons.timer_outlined, size: 16, color: Color(0xFF0F172A)),
                                SizedBox(width: 6),
                                Text(
                                  'Aktif Masalar & Seans Süreleri',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 14,
                                    color: Color(0xFF0F172A),
                                  ),
                                ),
                              ],
                            ),
                            Text(
                              '${liveData.activeTableTimers.length} Masa Dolu',
                              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w700),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        SizedBox(
                          height: 180,
                          child: ListView.builder(
                            scrollDirection: Axis.horizontal,
                            itemCount: liveData.activeTableTimers.length,
                            itemBuilder: (ctx, idx) {
                              final item = liveData.activeTableTimers[idx];
                              return ActiveTableTimerWidget(
                                item: item,
                                onCustomerTap: () {
                                  CustomerCrmBottomSheet.show(
                                    context,
                                    customerId: item.customerId,
                                    fallbackName: item.customerName,
                                    fallbackPhone: item.customerPhone,
                                  );
                                },
                              );
                            },
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],
                    );
                  },
                ),

                // 3. GÜNÜN ÖZETİ VE METRİKLER (KPI Cards)
                liveOpsAsync.when(
                  loading: () => const SizedBox.shrink(),
                  error: (e, s) => const SizedBox.shrink(),
                  data: (liveData) {
                    final m = liveData.metrics;
                    return Container(
                      padding: const EdgeInsets.all(14),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppTheme.borderLight),
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              _kpiMetric(
                                label: 'Toplam Randevu',
                                value: '${m.totalAppointments}',
                                icon: Icons.calendar_today_rounded,
                                color: const Color(0xFF2563EB),
                              ),
                              _kpiMetric(
                                label: 'Doluluk Oranı',
                                value: '%${m.occupancyRate}',
                                icon: Icons.pie_chart_outline_rounded,
                                color: const Color(0xFF8B5CF6),
                              ),
                              _kpiMetric(
                                label: 'Aktif Masa',
                                value: '${m.activeTablesCount}',
                                icon: Icons.table_restaurant_rounded,
                                color: const Color(0xFF10B981),
                              ),
                            ],
                          ),
                          const Divider(height: 18),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Icon(
                                    Icons.mark_chat_read_rounded,
                                    size: 15,
                                    color: m.whatsappStatus ? const Color(0xFF25D366) : Colors.grey,
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    m.whatsappStatus ? 'WhatsApp API: Aktif' : 'WhatsApp API: Pasif',
                                    style: TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w700,
                                      color: m.whatsappStatus ? const Color(0xFF16A34A) : Colors.grey.shade600,
                                    ),
                                  ),
                                ],
                              ),
                              Text(
                                '${m.pendingCount} Bekleyen Onay',
                                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFFF59E0B)),
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                ),

                // Tarih Seçici Çubuğu
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppTheme.borderLight),
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
                                  size: 15,
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

                // Randevu Akışı Listesi
                const Text(
                  'Günün Randevu Akışı',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 10),

                appointmentsAsync.when(
                  loading: () => const Center(
                    child: Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: CircularProgressIndicator(),
                    ),
                  ),
                  error: (error, stack) => Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      children: [
                        const Icon(Icons.error_outline_rounded, color: Colors.red, size: 32),
                        const SizedBox(height: 8),
                        Text('Randevular yüklenirken hata oluştu:\n$error', textAlign: TextAlign.center),
                      ],
                    ),
                  ),
                  data: (appointments) {
                    if (appointments.isEmpty) {
                      return Container(
                        padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 20),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppTheme.borderLight),
                        ),
                        child: Center(
                          child: Column(
                            children: [
                              const Icon(Icons.event_busy_rounded, size: 40, color: Color(0xFF94A3B8)),
                              const SizedBox(height: 10),
                              const Text(
                                'Bu tarihte planlanmış randevu bulunmuyor.',
                                style: TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                              ),
                              const SizedBox(height: 12),
                              ElevatedButton.icon(
                                onPressed: () {
                                  NewBookingWizardSheet.show(context, initialDate: selectedDate);
                                },
                                icon: const Icon(Icons.add_rounded, size: 16),
                                label: const Text('Randevu Ekle'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF0F172A),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: appointments.length,
                      itemBuilder: (ctx, idx) {
                        final appt = appointments[idx];
                        return AppointmentCard(
                          appointment: appt,
                          showStaffActions: true,
                          onTap: () {
                            CustomerCrmBottomSheet.show(
                              context,
                              customerId: appt.customerId,
                              fallbackName: appt.customer?.fullName ?? 'Müşteri',
                              fallbackPhone: appt.customer?.phone ?? '',
                            );
                          },
                        );
                      },
                    );
                  },
                ),

                const SizedBox(height: 80), // Fab padding
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _kpiMetric({
    required String label,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
              Text(
                label,
                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: color),
          ),
        ],
      ),
    );
  }
}
