import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'auth_provider.dart';
import '../data/models/appointment_model.dart';
import '../data/repositories/appointment_repository.dart';

final appointmentRepositoryProvider = Provider<AppointmentRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return AppointmentRepository(apiClient: apiClient);
});

// Parameter class for filtering appointments
class AppointmentFilter {
  final String? date;
  final String? from;
  final String? till;
  final int? providerId;
  final int? customerId;

  const AppointmentFilter({
    this.date,
    this.from,
    this.till,
    this.providerId,
    this.customerId,
  });

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AppointmentFilter &&
          runtimeType == other.runtimeType &&
          date == other.date &&
          from == other.from &&
          till == other.till &&
          providerId == other.providerId &&
          customerId == other.customerId;

  @override
  int get hashCode =>
      date.hashCode ^
      from.hashCode ^
      till.hashCode ^
      providerId.hashCode ^
      customerId.hashCode;
}

class AppointmentFilterNotifier extends Notifier<AppointmentFilter> {
  @override
  AppointmentFilter build() {
    return const AppointmentFilter();
  }

  void setFilter(AppointmentFilter filter) {
    state = filter;
  }
}

final appointmentsFilterProvider =
    NotifierProvider<AppointmentFilterNotifier, AppointmentFilter>(
        AppointmentFilterNotifier.new);

final appointmentsListProvider =
    FutureProvider<List<AppointmentModel>>((ref) async {
  final repo = ref.watch(appointmentRepositoryProvider);
  final filter = ref.watch(appointmentsFilterProvider);
  final authState = ref.watch(authProvider);

  // If customer, ensure customerId is passed or handled by API
  int? customerId = filter.customerId;
  if (authState.user?.isCustomer == true) {
    customerId = authState.user?.id;
  }

  return await repo.getAppointments(
    date: filter.date,
    from: filter.from,
    till: filter.till,
    providerId: filter.providerId,
    customerId: customerId,
  );
});

// Today's appointments for staff agenda
final todayAppointmentsProvider =
    FutureProvider<List<AppointmentModel>>((ref) async {
  final repo = ref.watch(appointmentRepositoryProvider);
  final todayStr = DateFormat('yyyy-MM-dd').format(DateTime.now());

  return await repo.getAppointments(date: todayStr);
});

