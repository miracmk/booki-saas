import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'auth_provider.dart';
import '../data/models/service_model.dart';
import '../data/models/provider_model.dart';
import '../data/models/appointment_model.dart';
import '../data/repositories/booking_repository.dart';

final bookingRepositoryProvider = Provider<BookingRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return BookingRepository(apiClient: apiClient);
});

final servicesListProvider = FutureProvider<List<ServiceModel>>((ref) async {
  final repo = ref.watch(bookingRepositoryProvider);
  return await repo.getServices();
});

final providersListProvider = FutureProvider<List<ProviderModel>>((ref) async {
  final repo = ref.watch(bookingRepositoryProvider);
  return await repo.getProviders();
});

class BookingWizardState {
  final int currentStep; // 0: Service, 1: Provider, 2: DateTime, 3: Confirm, 4: Success
  final ServiceModel? selectedService;
  final ProviderModel? selectedProvider;
  final DateTime selectedDate;
  final String? selectedSlot;
  final List<String> availableSlots;
  final bool isLoadingSlots;
  final bool isSubmitting;
  final String? notes;
  final String? errorMessage;
  final AppointmentModel? bookedAppointment;

  BookingWizardState({
    this.currentStep = 0,
    this.selectedService,
    this.selectedProvider,
    DateTime? selectedDate,
    this.selectedSlot,
    this.availableSlots = const [],
    this.isLoadingSlots = false,
    this.isSubmitting = false,
    this.notes,
    this.errorMessage,
    this.bookedAppointment,
  }) : selectedDate = selectedDate ?? DateTime.now();

  BookingWizardState copyWith({
    int? currentStep,
    ServiceModel? selectedService,
    ProviderModel? selectedProvider,
    DateTime? selectedDate,
    String? selectedSlot,
    List<String>? availableSlots,
    bool? isLoadingSlots,
    bool? isSubmitting,
    String? notes,
    String? errorMessage,
    AppointmentModel? bookedAppointment,
  }) {
    return BookingWizardState(
      currentStep: currentStep ?? this.currentStep,
      selectedService: selectedService ?? this.selectedService,
      selectedProvider: selectedProvider ?? this.selectedProvider,
      selectedDate: selectedDate ?? this.selectedDate,
      selectedSlot: selectedSlot ?? this.selectedSlot,
      availableSlots: availableSlots ?? this.availableSlots,
      isLoadingSlots: isLoadingSlots ?? this.isLoadingSlots,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      notes: notes ?? this.notes,
      errorMessage: errorMessage,
      bookedAppointment: bookedAppointment ?? this.bookedAppointment,
    );
  }
}

class BookingWizardNotifier extends Notifier<BookingWizardState> {
  @override
  BookingWizardState build() {
    return BookingWizardState();
  }

  void selectService(ServiceModel service) {
    state = state.copyWith(
      selectedService: service,
      currentStep: 1,
      errorMessage: null,
    );
  }

  void selectProvider(ProviderModel provider) {
    state = state.copyWith(
      selectedProvider: provider,
      currentStep: 2,
      errorMessage: null,
    );
    loadAvailableSlots();
  }

  void selectDate(DateTime date) {
    state = state.copyWith(
      selectedDate: date,
      selectedSlot: null,
      errorMessage: null,
    );
    loadAvailableSlots();
  }

  void selectSlot(String slot) {
    state = state.copyWith(
      selectedSlot: slot,
      errorMessage: null,
    );
  }

  void setNotes(String notes) {
    state = state.copyWith(notes: notes);
  }

  void goToStep(int step) {
    state = state.copyWith(currentStep: step, errorMessage: null);
  }

  Future<void> loadAvailableSlots() async {
    final service = state.selectedService;
    final provider = state.selectedProvider;
    if (service == null || provider == null) return;

    state = state.copyWith(isLoadingSlots: true, errorMessage: null);

    try {
      final repo = ref.read(bookingRepositoryProvider);
      final dateStr = DateFormat('yyyy-MM-dd').format(state.selectedDate);
      final slots = await repo.getAvailableHours(
        providerId: provider.id,
        serviceId: service.id,
        date: dateStr,
      );

      state = state.copyWith(
        isLoadingSlots: false,
        availableSlots: slots,
        selectedSlot: slots.isNotEmpty ? slots.first : null,
      );
    } catch (e) {
      state = state.copyWith(
        isLoadingSlots: false,
        availableSlots: [],
        errorMessage: 'Saatler yüklenirken hata oluştu: $e',
      );
    }
  }

  Future<bool> confirmBooking(int customerId) async {
    final service = state.selectedService;
    final provider = state.selectedProvider;
    final slot = state.selectedSlot;

    if (service == null || provider == null || slot == null) {
      state = state.copyWith(errorMessage: 'Lütfen tüm rezervasyon adımlarını tamamlayın.');
      return false;
    }

    state = state.copyWith(isSubmitting: true, errorMessage: null);

    try {
      final repo = ref.read(bookingRepositoryProvider);
      final dateStr = DateFormat('yyyy-MM-dd').format(state.selectedDate);
      final startDateTime = '$dateStr $slot:00';

      final appt = await repo.bookAppointment(
        serviceId: service.id,
        providerId: provider.id,
        customerId: customerId,
        startDateTime: startDateTime,
        notes: state.notes,
      );

      state = state.copyWith(
        isSubmitting: false,
        bookedAppointment: appt,
        currentStep: 4, // Success step
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isSubmitting: false,
        errorMessage: 'Randevu kaydedilemedi: $e',
      );
      return false;
    }
  }

  void reset() {
    state = BookingWizardState();
  }
}

final bookingWizardProvider =
    NotifierProvider<BookingWizardNotifier, BookingWizardState>(
        BookingWizardNotifier.new);

