import '../../core/constants/api_constants.dart';
import '../../core/network/api_client.dart';
import '../models/appointment_model.dart';

class AppointmentRepository {
  final ApiClient _apiClient;

  AppointmentRepository({required ApiClient apiClient}) : _apiClient = apiClient;

  Future<List<AppointmentModel>> getAppointments({
    String? date,
    String? from,
    String? till,
    int? customerId,
    int? providerId,
  }) async {
    try {
      final response = await _apiClient.dio.get(
        ApiConstants.appointmentsEndpoint,
        queryParameters: {
          if (date != null) 'date': date,
          if (from != null) 'from': from,
          if (till != null) 'till': till,
          if (customerId != null) 'customerId': customerId,
          if (providerId != null) 'providerId': providerId,
          'with[]': ['service', 'provider', 'customer'],
        },
      );

      if (response.data is List) {
        return (response.data as List)
            .map((a) => AppointmentModel.fromJson(a as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (e) {
      throw Exception('Randevular yüklenemedi: $e');
    }
  }

  Future<bool> updateStatus(int appointmentId, String status) async {
    try {
      final response = await _apiClient.dio.put(
        '${ApiConstants.appointmentsEndpoint}/$appointmentId',
        data: {'status': status},
      );
      return response.statusCode == 200;
    } catch (e) {
      throw Exception('Randevu durumu güncellenemedi: $e');
    }
  }

  Future<bool> cancelAppointment(int appointmentId) async {
    return await updateStatus(appointmentId, 'cancelled');
  }
}
