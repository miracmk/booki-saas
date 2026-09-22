import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/appointment_model.dart';
import '../../../providers/appointments_provider.dart';
import '../../widgets/appointment_card.dart';

class CustomerAppointmentsScreen extends ConsumerWidget {
  const CustomerAppointmentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final appointmentsAsync = ref.watch(appointmentsListProvider);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Randevularım'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Yaklaşanlar'),
              Tab(text: 'Geçmiş'),
            ],
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh_rounded),
              onPressed: () => ref.invalidate(appointmentsListProvider),
            ),
          ],
        ),
        body: appointmentsAsync.when(
          data: (appointments) {
            final now = DateTime.now();
            final upcoming = appointments
                .where((a) => a.startDatetime.isAfter(now) && !a.isCancelled)
                .toList()
              ..sort((a, b) => a.startDatetime.compareTo(b.startDatetime));

            final past = appointments
                .where((a) => a.startDatetime.isBefore(now) || a.isCancelled)
                .toList()
              ..sort((a, b) => b.startDatetime.compareTo(a.startDatetime));

            return TabBarView(
              children: [
                _buildList(context, ref, upcoming, isUpcomingTab: true),
                _buildList(context, ref, past, isUpcomingTab: false),
              ],
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline,
                      size: 48, color: AppTheme.accentDanger),
                  const SizedBox(height: 12),
                  Text('Randevular yüklenemedi:\n$e',
                      textAlign: TextAlign.center),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => ref.invalidate(appointmentsListProvider),
                    child: const Text('Tekrar Dene'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildList(
    BuildContext context,
    WidgetRef ref,
    List<AppointmentModel> items, {
    required bool isUpcomingTab,
  }) {
    if (items.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              isUpcomingTab
                  ? Icons.event_available_rounded
                  : Icons.history_rounded,
              size: 56,
              color: AppTheme.textSecondaryLight.withValues(alpha: 0.5),
            ),
            const SizedBox(height: 12),
            Text(
              isUpcomingTab
                  ? 'Yaklaşan randevunuz bulunmuyor.'
                  : 'Geçmiş randevu kaydı yok.',
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: AppTheme.textSecondaryLight,
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(appointmentsListProvider);
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        itemBuilder: (context, index) {
          final appointment = items[index];
          return AppointmentCard(
            appointment: appointment,
            onTap: () {
              if (isUpcomingTab && !appointment.isCancelled) {
                _showCancelDialog(context, ref, appointment);
              }
            },
          );
        },
      ),
    );
  }

  void _showCancelDialog(
    BuildContext context,
    WidgetRef ref,
    AppointmentModel appointment,
  ) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Randevuyu İptal Et'),
        content: Text(
          '${appointment.service?.name ?? 'Bu randevuyu'} iptal etmek istediğinizden emin misiniz?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Vazgeç'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.accentDanger,
            ),
            onPressed: () async {
              Navigator.of(ctx).pop();
              try {
                await ref
                    .read(appointmentRepositoryProvider)
                    .cancelAppointment(appointment.id);
                ref.invalidate(appointmentsListProvider);
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Randevu iptal edildi.')),
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
            child: const Text('Evet, İptal Et'),
          ),
        ],
      ),
    );
  }
}

