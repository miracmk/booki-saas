import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import 'package:ki_reservation_mobile/core/network/api_client.dart';
import 'package:ki_reservation_mobile/data/models/station_model.dart';
import 'package:ki_reservation_mobile/data/models/service_model.dart';
import 'package:ki_reservation_mobile/data/models/provider_model.dart';
import 'package:ki_reservation_mobile/data/repositories/booking_repository.dart';
import 'package:ki_reservation_mobile/providers/booking_provider.dart';

class FakeHttpClientAdapter implements HttpClientAdapter {
  final Map<String, dynamic> Function(RequestOptions options) handler;

  FakeHttpClientAdapter(this.handler);

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<List<int>>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    final res = handler(options);
    final jsonStr = res.toString();
    return ResponseBody.fromString(
      jsonStr,
      200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  group('Mobile Station & Multi-Resource Selection Tests', () {
    test('StationModel deserializes successfully with various statuses and fields', () {
      final json = {
        'id': 101,
        'name': 'Masaj Odası 1 (Vip)',
        'capacity': 2,
        'is_active': 1,
        'status': 'empty',
        'active_guest': null,
        'elapsed_seconds': 0,
      };

      final station = StationModel.fromJson(json);

      expect(station.id, 101);
      expect(station.name, 'Masaj Odası 1 (Vip)');
      expect(station.capacity, 2);
      expect(station.isActive, true);
      expect(station.status, StationStatus.empty);
      expect(station.status.labelTr, 'Boş');
    });

    test('BookingWizardNotifier advances through 6 steps including station selection', () {
      final container = ProviderContainer();
      addTearDown(container.dispose);

      final notifier = container.read(bookingWizardProvider.notifier);

      // Initial state: Step 0 (Hizmet)
      expect(container.read(bookingWizardProvider).currentStep, 0);
      expect(container.read(bookingWizardProvider).selectedService, isNull);
      expect(container.read(bookingWizardProvider).selectedStation, isNull);

      // Step 0 -> Step 1: Select Service
      const sampleService = ServiceModel(
        id: 5,
        name: 'Derin Doku Masajı',
        duration: 60,
        price: 850.0,
        currency: '₺',
      );
      notifier.selectService(sampleService);

      expect(container.read(bookingWizardProvider).currentStep, 1);
      expect(container.read(bookingWizardProvider).selectedService?.id, 5);

      // Step 1 -> Step 2: Select Station / Room / Court / Table
      const sampleStation = StationModel(
        id: 202,
        name: 'Oda 2 (Aromaterapi)',
        capacity: 1,
        status: StationStatus.empty,
      );
      notifier.selectStation(sampleStation);

      expect(container.read(bookingWizardProvider).currentStep, 2);
      expect(container.read(bookingWizardProvider).selectedStation?.id, 202);
      expect(container.read(bookingWizardProvider).selectedStation?.name, 'Oda 2 (Aromaterapi)');

      // Step 2 -> Step 3: Select Provider
      const sampleProvider = ProviderModel(
        id: 12,
        firstName: 'Zeynep',
        lastName: 'Kaya',
        email: 'zeynep@bookiapp.co',
      );
      notifier.selectProvider(sampleProvider);

      expect(container.read(bookingWizardProvider).currentStep, 3);
      expect(container.read(bookingWizardProvider).selectedProvider?.id, 12);

      // Step 3: Select Slot and proceed to Step 4 (Onay / Özet)
      notifier.selectSlot('14:30');
      expect(container.read(bookingWizardProvider).selectedSlot, '14:30');

      notifier.goToStep(4);
      expect(container.read(bookingWizardProvider).currentStep, 4);

      // Auto-assign station reset
      notifier.selectStation(null);
      expect(container.read(bookingWizardProvider).selectedStation, isNull);
    });
  });
}
