import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/appointment_model.dart';
import '../../../data/models/station_model.dart';
import '../../../providers/appointments_provider.dart';
import '../../../providers/operations_provider.dart';
import '../../widgets/appointment_card.dart';
import '../../widgets/booking/new_booking_wizard_sheet.dart';
import '../../widgets/crm/customer_crm_bottom_sheet.dart';
import '../../widgets/qr/qr_scanner_dialog.dart';

class StaffCalendarScreen extends ConsumerStatefulWidget {
  const StaffCalendarScreen({super.key});

  @override
  ConsumerState<StaffCalendarScreen> createState() => _StaffCalendarScreenState();
}

class _StaffCalendarScreenState extends ConsumerState<StaffCalendarScreen> {
  DateTime _selectedDate = DateTime.now();

  @override
  Widget build(BuildContext context) {
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
    final appointmentsAsync = ref.watch(agendaAppointmentsProvider);
    final floorPlanAsync = ref.watch(floorPlanProvider);
    final viewMode = ref.watch(calendarViewModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Takvim & Yerleşim Planı', style: TextStyle(fontWeight: FontWeight.w800)),
        actions: [
          IconButton(
            tooltip: 'Kamera ile QR Oku',
            icon: const Icon(Icons.qr_code_scanner_rounded),
            onPressed: () => QrScannerDialog.show(context),
          ),
          IconButton(
            tooltip: 'Tarih Seç',
            icon: const Icon(Icons.calendar_month_rounded),
            onPressed: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: _selectedDate,
                firstDate: DateTime.now().subtract(const Duration(days: 365)),
                lastDate: DateTime.now().add(const Duration(days: 365)),
              );
              if (picked != null) {
                setState(() => _selectedDate = picked);
                ref.read(selectedAgendaDateProvider.notifier).setDate(picked);
              }
            },
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          HapticFeedback.lightImpact();
          NewBookingWizardSheet.show(context, initialDate: _selectedDate);
        },
        backgroundColor: const Color(0xFF0F172A),
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded),
        label: const Text('Yeni Rezervasyon', style: TextStyle(fontWeight: FontWeight.w800)),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // 1. Çift Görünüm Seçici (Timeline vs Floor Map Toggle)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              child: Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(14),
                ),
                padding: const EdgeInsets.all(4),
                child: Row(
                  children: [
                    Expanded(
                      child: _toggleTab(
                        label: 'Zaman Çizelgesi',
                        icon: Icons.view_timeline_outlined,
                        isActive: viewMode == CalendarViewMode.timeline,
                        onTap: () {
                          ref.read(calendarViewModeProvider.notifier).setMode(CalendarViewMode.timeline);
                        },
                      ),
                    ),
                    Expanded(
                      child: _toggleTab(
                        label: 'Masa / Yerleşim',
                        icon: Icons.grid_view_rounded,
                        isActive: viewMode == CalendarViewMode.floorMap,
                        onTap: () {
                          ref.read(calendarViewModeProvider.notifier).setMode(CalendarViewMode.floorMap);
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // 2. Yatay Tarih Seçici Şeridi
            Container(
              height: 80,
              padding: const EdgeInsets.symmetric(vertical: 6),
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(bottom: BorderSide(color: AppTheme.borderLight)),
              ),
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: 30,
                itemBuilder: (context, index) {
                  final day = DateTime.now()
                      .subtract(const Duration(days: 2))
                      .add(Duration(days: index));
                  final isSelected = DateFormat('yyyy-MM-dd').format(day) == dateStr;

                  return GestureDetector(
                    onTap: () {
                      HapticFeedback.selectionClick();
                      setState(() => _selectedDate = day);
                      ref.read(selectedAgendaDateProvider.notifier).setDate(day);
                    },
                    child: Container(
                      width: 58,
                      margin: const EdgeInsets.only(right: 8),
                      decoration: BoxDecoration(
                        color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFE2E8F0),
                        ),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            DateFormat('EEE', 'tr_TR').format(day).toUpperCase(),
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: isSelected ? const Color(0xFF94A3B8) : const Color(0xFF64748B),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            DateFormat('d').format(day),
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                              color: isSelected ? Colors.white : const Color(0xFF0F172A),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),

            // 3. Ana Görünüm Alanı (Timeline veya Floor Map)
            Expanded(
              child: viewMode == CalendarViewMode.timeline
                  ? _buildTimelineView(appointmentsAsync)
                  : _buildFloorMapView(floorPlanAsync),
            ),
          ],
        ),
      ),
    );
  }

  Widget _toggleTab({
    required String label,
    required IconData icon,
    required bool isActive,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 9),
        decoration: BoxDecoration(
          color: isActive ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
          boxShadow: isActive
              ? [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 4, offset: const Offset(0, 2))]
              : null,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 16,
              color: isActive ? const Color(0xFF0F172A) : const Color(0xFF64748B),
            ),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w800,
                color: isActive ? const Color(0xFF0F172A) : const Color(0xFF64748B),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // --- 1. ZAMAN ÇİZELGESİ (TIMELINE / RESOURCE VIEW) ---
  Widget _buildTimelineView(AsyncValue<List<AppointmentModel>> appointmentsAsync) {
    return appointmentsAsync.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, s) => Center(child: Text('Takvim yüklenemedi: $e')),
      data: (appointments) {
        // Saat dilimleri: 09:00 - 20:00
        final hours = List.generate(12, (index) => 9 + index);

        return ListView.builder(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          itemCount: hours.length,
          itemBuilder: (context, idx) {
            final hour = hours[idx];
            final hourStr = '${hour.toString().padLeft(2, '0')}:00';

            // Bu saate düşen randevuları bul
            final slotAppointments = appointments.where((a) {
              return a.startDatetime.hour == hour;
            }).toList();

            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Saat Etiketi
                  SizedBox(
                    width: 50,
                    child: Text(
                      hourStr,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ),

                  // Randevu Kartları veya Boş Dilim
                  Expanded(
                    child: slotAppointments.isEmpty
                        ? InkWell(
                            onTap: () {
                              final slotDt = DateTime(
                                _selectedDate.year,
                                _selectedDate.month,
                                _selectedDate.day,
                                hour,
                                0,
                              );
                              NewBookingWizardSheet.show(context, initialDate: slotDt);
                            },
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              height: 48,
                              decoration: BoxDecoration(
                                color: const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: const Color(0xFFE2E8F0),
                                  style: BorderStyle.solid,
                                ),
                              ),
                              alignment: Alignment.centerLeft,
                              padding: const EdgeInsets.symmetric(horizontal: 12),
                              child: const Row(
                                children: [
                                  Icon(Icons.add_circle_outline_rounded, size: 14, color: Color(0xFF94A3B8)),
                                  SizedBox(width: 6),
                                  Text(
                                    'Boş Saat (Randevu Oluştur)',
                                    style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                                  ),
                                ],
                              ),
                            ),
                          )
                        : Column(
                            children: slotAppointments.map((appt) {
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
                            }).toList(),
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  // --- 2. MASA / YERLEŞİM PLANI (INTERACTIVE FLOOR MAP) ---
  Widget _buildFloorMapView(AsyncValue<List<StationModel>> floorPlanAsync) {
    return floorPlanAsync.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, s) => Center(child: Text('Kroki yüklenemedi: $e')),
      data: (stations) {
        if (stations.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.table_restaurant_outlined, size: 48, color: Color(0xFF94A3B8)),
                const SizedBox(height: 12),
                const Text(
                  'Tanımlı masa veya istasyon bulunamadı.',
                  style: TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                ),
                const SizedBox(height: 12),
                ElevatedButton(
                  onPressed: () => ref.invalidate(floorPlanProvider),
                  child: const Text('Yenile'),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () async => ref.invalidate(floorPlanProvider),
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Durum Renk Açıklaması (Legend)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _LegendItem(color: Color(0xFF10B981), label: 'Boş'),
                      _LegendItem(color: Color(0xFF3B82F6), label: 'Rezerve'),
                      _LegendItem(color: Color(0xFFEF4444), label: 'Dolu'),
                      _LegendItem(color: Color(0xFFF59E0B), label: 'Süresi Aşan'),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Masa Izgarası (Floor Grid)
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: stations.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 1.25,
                    crossAxisSpacing: 12,
                    mainAxisSpacing: 12,
                  ),
                  itemBuilder: (ctx, idx) {
                    final st = stations[idx];
                    return _floorTableCard(st);
                  },
                ),
                const SizedBox(height: 80),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _floorTableCard(StationModel st) {
    final statusColor = st.status.color;

    return InkWell(
      onTap: () {
        HapticFeedback.lightImpact();
        _showTableActionSheet(st);
      },
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: statusColor, width: 2),
          boxShadow: [
            BoxShadow(
              color: statusColor.withValues(alpha: 0.15),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            // Üst İsim ve Kapasite
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    st.name,
                    style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: Color(0xFF0F172A)),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    st.status.labelTr,
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: statusColor),
                  ),
                ),
              ],
            ),

            // Misafir Bilgisi veya Kapasite
            if (st.activeGuest != null)
              Text(
                st.activeGuest!,
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: Color(0xFF1E293B)),
                overflow: TextOverflow.ellipsis,
              )
            else
              Row(
                children: [
                  const Icon(Icons.people_alt_outlined, size: 14, color: Color(0xFF64748B)),
                  const SizedBox(width: 4),
                  Text(
                    '${st.capacity} Kişilik Kapasite',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                  ),
                ],
              ),

            // Süre veya Aksiyon İpucu
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (st.elapsedSeconds > 0)
                  Text(
                    '${(st.elapsedSeconds / 60).toStringAsFixed(0)} dk aktif',
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: statusColor),
                  )
                else
                  const Text(
                    'Dokun: İşlem Yap',
                    style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                  ),
                Icon(Icons.arrow_forward_ios_rounded, size: 12, color: statusColor),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _showTableActionSheet(StationModel st) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(st.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: st.status.color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(8)),
                  child: Text(st.status.labelTr, style: TextStyle(fontWeight: FontWeight.bold, color: st.status.color)),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ListTile(
              leading: const Icon(Icons.add_task_rounded, color: Color(0xFF0F172A)),
              title: const Text('Bu Masaya Randevu / Rezervasyon Oluştur'),
              onTap: () {
                Navigator.of(ctx).pop();
                NewBookingWizardSheet.show(context, initialDate: _selectedDate, preselectedStationId: st.id);
              },
            ),
            ListTile(
              leading: const Icon(Icons.qr_code_scanner_rounded, color: Color(0xFF2563EB)),
              title: const Text('Gelen Misafiri Check-in Yap & Masaya Al'),
              onTap: () {
                Navigator.of(ctx).pop();
                QrScannerDialog.show(context);
              },
            ),
            if (st.status == StationStatus.occupied || st.status == StationStatus.overdue)
              ListTile(
                leading: const Icon(Icons.check_circle_outline_rounded, color: Color(0xFF10B981)),
                title: const Text('Masayı Boşalt / Seansı Tamamla'),
                onTap: () {
                  Navigator.of(ctx).pop();
                  final apptId = st.activeAppointment?['id'] is int ? st.activeAppointment!['id'] as int : 0;
                  if (apptId > 0) {
                    ref.read(operationsServiceProvider).changeStatusWithUndo(
                          context: context,
                          appointmentId: apptId,
                          newStatus: AppointmentStatus.completed,
                          previousStatus: AppointmentStatus.arrived,
                          customerName: st.activeGuest,
                        );
                  }
                },
              ),
          ],
        ),
      ),
    );
  }
}

class _LegendItem extends StatelessWidget {
  final Color color;
  final String label;

  const _LegendItem({required this.color, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF334155)),
        ),
      ],
    );
  }
}
