import 'package:intl/intl.dart';
import '../../core/constants/api_constants.dart';
import '../../core/network/api_client.dart';
import '../models/appointment_model.dart';

class AppointmentRepository {
  final ApiClient _apiClient;

  AppointmentRepository({required this._apiClient});

  Future<List<AppointmentModel>> getAppointments({
    String? date,
    String? from,
    String? till,
    int? customerId,
    int? providerId,
  }) async {
    try {
      final query = <String, dynamic>{
        'with': 'service,provider,customer',
      };
      if (date != null) query['date'] = date;
      if (from != null) query['from'] = from;
      if (till != null) query['till'] = till;
      if (customerId != null) query['customerId'] = customerId;
      if (providerId != null) query['providerId'] = providerId;

      final response = await _apiClient.dio.get(
        ApiConstants.appointmentsEndpoint,
        queryParameters: query,
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

  Future<AppointmentModel> createAppointment({
    required int serviceId,
    required int providerId,
    required DateTime startDatetime,
    required DateTime endDatetime,
    int? stationId,
    String? notes,
    Map<String, dynamic>? customer,
  }) async {
    try {
      final payload = {
        'id_services': serviceId,
        'id_users_provider': providerId,
        'start_datetime': DateFormat('yyyy-MM-dd HH:mm:ss').format(startDatetime),
        'end_datetime': DateFormat('yyyy-MM-dd HH:mm:ss').format(endDatetime),
        if (stationId != null && stationId > 0) 'id_stations': stationId,
        'notes': ?notes,
        'customer': ?customer,
      };

      final response = await _apiClient.dio.post(
        ApiConstants.appointmentsEndpoint,
        data: payload,
      );

      if (response.data is Map<String, dynamic>) {
        return AppointmentModel.fromJson(response.data as Map<String, dynamic>);
      }
      throw Exception('Geçersiz sunucu yanıtı.');
    } catch (e) {
      throw Exception('Randevu oluşturulamadı: $e');
    }
  }

  Future<bool> cancelAppointment(int appointmentId) async {
    return await updateStatus(appointmentId, 'cancelled');
  }
}
