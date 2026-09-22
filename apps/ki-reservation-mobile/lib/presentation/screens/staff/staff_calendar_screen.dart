import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/appointments_provider.dart';
import '../../widgets/appointment_card.dart';

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
    final appointmentsAsync = ref.watch(
      appointmentsListProvider,
    );

    return Scaffold(
      appBar: AppBar(
        title: const Text('Takvim & Randevu Yönetimi'),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today_rounded),
            onPressed: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: _selectedDate,
                firstDate: DateTime.now().subtract(const Duration(days: 365)),
                lastDate: DateTime.now().add(const Duration(days: 365)),
              );
              if (picked != null) {
                setState(() {
                  _selectedDate = picked;
                });
                ref.read(appointmentsFilterProvider.notifier).setFilter(
                    AppointmentFilter(date: DateFormat('yyyy-MM-dd').format(picked)));
              }
            },
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Yatay Tarih Şeridi (Horizontal Date Strip)
            Container(
              height: 90,
              padding: const EdgeInsets.symmetric(vertical: 8),
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
                      .subtract(const Duration(days: 3))
                      .add(Duration(days: index));
                  final isSelected =
                      DateFormat('yyyy-MM-dd').format(day) == dateStr;

                  return GestureDetector(
                    onTap: () {
                      setState(() {
                        _selectedDate = day;
                      });
                      ref.read(appointmentsFilterProvider.notifier).setFilter(
                            AppointmentFilter(
                              date: DateFormat('yyyy-MM-dd').format(day),
                            ),
                          );
                    },
                    child: Container(
                      width: 56,
                      margin: const EdgeInsets.symmetric(horizontal: 4),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? AppTheme.primaryColor
                            : AppTheme.surfaceLight,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: isSelected
                              ? AppTheme.primaryColor
                              : AppTheme.borderLight,
                        ),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            DateFormat('E', 'tr_TR').format(day),
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: isSelected
                                  ? Colors.white70
                                  : AppTheme.textSecondaryLight,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            DateFormat('d').format(day),
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                              color: isSelected
                                  ? Colors.white
                                  : AppTheme.textPrimaryLight,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),

            // Seçilen Günün Başlığı
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    DateFormat('d MMMM yyyy, EEEE', 'tr_TR')
                        .format(_selectedDate),
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.textPrimaryLight,
                    ),
                  ),
                ],
              ),
            ),

            // Randevular Listesi
            Expanded(
              child: appointmentsAsync.when(
                data: (appointments) {
                  // Seçilen güne ait randevuları filtrele
                  final dayAppointments = appointments.where((a) {
                    return DateFormat('yyyy-MM-dd').format(a.startDatetime) ==
                        dateStr;
                  }).toList()
                    ..sort((a, b) => a.startDatetime.compareTo(b.startDatetime));

                  if (dayAppointments.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(
                            Icons.event_busy_rounded,
                            size: 48,
                            color: AppTheme.textSecondaryLight,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'Bu tarihte kayıtlı randevu bulunmuyor.',
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ],
                      ),
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 8),
                    itemCount: dayAppointments.length,
                    itemBuilder: (context, index) {
                      final appt = dayAppointments[index];
                      return AppointmentCard(
                        appointment: appt,
                        showStaffActions: true,
                        onStatusChanged: (newStatus) async {
                          await ref
                              .read(appointmentRepositoryProvider)
                              .updateStatus(appt.id, newStatus);
                          ref.invalidate(appointmentsListProvider);
                        },
                      );
                    },
                  );
                },
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(
                  child: Text('Randevular yüklenemedi: $e'),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
