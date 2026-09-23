import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'auth_provider.dart';
import 'appointments_provider.dart';
import '../data/models/appointment_model.dart';
import '../data/models/customer_crm_model.dart';
import '../data/models/live_operations_model.dart';
import '../data/models/station_model.dart';
import '../data/repositories/operations_repository.dart';

final operationsRepositoryProvider = Provider<OperationsRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return OperationsRepository(apiClient);
});

// Periodic auto-refresh for Live Operations (10 seconds)
final liveOperationsProvider = FutureProvider.autoDispose<LiveOperationsData>((ref) async {
  final repo = ref.watch(operationsRepositoryProvider);
  
  // Timer to auto-refresh every 10 seconds for real-time operations
  final timer = Timer(const Duration(seconds: 10), () {
    ref.invalidateSelf();
  });
  ref.onDispose(() => timer.cancel());

  return await repo.getLiveOperations();
});

// Floor Plan Provider
final floorPlanProvider = FutureProvider.autoDispose<List<StationModel>>((ref) async {
  final repo = ref.watch(operationsRepositoryProvider);
  return await repo.getFloorPlan();
});

// View mode toggle for Calendar: 'timeline' or 'floor_map'
enum CalendarViewMode { timeline, floorMap }

class CalendarViewModeNotifier extends Notifier<CalendarViewMode> {
  @override
  CalendarViewMode build() => CalendarViewMode.timeline;

  void setMode(CalendarViewMode mode) => state = mode;
  void toggle() => state = state == CalendarViewMode.timeline ? CalendarViewMode.floorMap : CalendarViewMode.timeline;
}

final calendarViewModeProvider =
    NotifierProvider<CalendarViewModeNotifier, CalendarViewMode>(CalendarViewModeNotifier.new);

// Customer Mini-CRM Profile Provider
final customerCrmProvider = FutureProvider.family<CustomerCrmModel, int>((ref, customerId) async {
  final repo = ref.watch(operationsRepositoryProvider);
  return await repo.getCustomerMiniCrm(customerId);
});

// Optimistic Status Actions Service
class OperationsService {
  final Ref ref;

  OperationsService(this.ref);

  Future<void> changeStatusWithUndo({
    required BuildContext context,
    required int appointmentId,
    required AppointmentStatus newStatus,
    required AppointmentStatus previousStatus,
    String? customerName,
  }) async {
    // 1. Haptic feedback
    HapticFeedback.lightImpact();

    // 2. Perform API call
    final repo = ref.read(operationsRepositoryProvider);

    try {
      await repo.updateStatus(appointmentId, newStatus.name);

      // Invalidate relevant providers to fetch fresh synced state
      ref.invalidate(agendaAppointmentsProvider);
      ref.invalidate(liveOperationsProvider);
      ref.invalidate(floorPlanProvider);

      if (!context.mounted) return;

      // 3. Show undoable SnackBar
      ScaffoldMessenger.of(context).clearSnackBars();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  '${customerName ?? "Randevu"} durumu: "${newStatus.labelTr}" yapıldı.',
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          backgroundColor: const Color(0xFF0F172A),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          duration: const Duration(seconds: 4),
          action: SnackBarAction(
            label: 'GERİ AL',
            textColor: const Color(0xFF38BDF8),
            onPressed: () async {
              HapticFeedback.mediumImpact();
              try {
                await repo.updateStatus(appointmentId, previousStatus.name);
                ref.invalidate(agendaAppointmentsProvider);
                ref.invalidate(liveOperationsProvider);
                ref.invalidate(floorPlanProvider);

                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Durum geri alındı: "${previousStatus.labelTr}"'),
                      duration: const Duration(seconds: 2),
                      behavior: SnackBarBehavior.floating,
                    ),
                  );
                }
              } catch (_) {}
            },
          ),
        ),
      );
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Durum güncellenemedi: $e'),
          backgroundColor: Colors.red.shade700,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }
}

final operationsServiceProvider = Provider<OperationsService>((ref) {
  return OperationsService(ref);
});

