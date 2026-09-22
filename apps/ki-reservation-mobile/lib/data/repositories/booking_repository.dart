import 'package:dio/dio.dart';
import '../../core/constants/api_constants.dart';
import '../../core/network/api_client.dart';
import '../models/service_model.dart';
import '../models/provider_model.dart';
import '../models/appointment_model.dart';

class BookingRepository {
  final ApiClient _apiClient;

  BookingRepository({required ApiClient apiClient}) : _apiClient = apiClient;

  Future<List<ServiceModel>> getServices() async {
    try {
      final response = await _apiClient.dio.get(ApiConstants.servicesEndpoint);
      if (response.data is List) {
        return (response.data as List)
            .map((s) => ServiceModel.fromJson(s as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (e) {
      throw Exception('Hizmetler yüklenirken hata oluştu: $e');
    }
  }

  Future<List<ProviderModel>> getProviders() async {
    try {
      final response = await _apiClient.dio.get(ApiConstants.providersEndpoint);
      if (response.data is List) {
        return (response.data as List)
            .map((p) => ProviderModel.fromJson(p as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (e) {
      throw Exception('Uzmanlar yüklenirken hata oluştu: $e');
    }
  }

  Future<List<String>> getAvailableHours({
    required int providerId,
    required int serviceId,
    required String date, // YYYY-MM-DD
  }) async {
    try {
      final response = await _apiClient.dio.get(
        ApiConstants.availabilitiesEndpoint,
        queryParameters: {
          'providerId': providerId,
          'serviceId': serviceId,
          'date': date,
        },
      );

      if (response.data is List) {
        return (response.data as List).map((h) => h.toString()).toList();
      }
      return [];
    } catch (e) {
      throw Exception('Müsait saatler alınamadı: $e');
    }
  }

  Future<AppointmentModel> bookAppointment({
    required int serviceId,
    required int providerId,
    required int customerId,
    required String startDateTime, // YYYY-MM-DD HH:mm:ss
    String? notes,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        ApiConstants.appointmentsEndpoint,
        data: {
          'serviceId': serviceId,
          'providerId': providerId,
          'customerId': customerId,
          'start': startDateTime,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        },
      );

      if (response.data is Map<String, dynamic>) {
        return AppointmentModel.fromJson(response.data as Map<String, dynamic>);
      }
      throw Exception('Geçersiz sunucu yanıtı.');
    } on DioException catch (e) {
      final msg = e.response?.data is Map ? e.response?.data['message']?.toString() : e.message;
      throw Exception(msg ?? 'Randevu oluşturulamadı.');
    } catch (e) {
      throw Exception('Randevu oluşturma hatası: $e');
    }
  }
}

