import '../../core/constants/api_constants.dart';
import '../../core/network/api_client.dart';
import '../models/customer_crm_model.dart';
import '../models/live_operations_model.dart';
import '../models/station_model.dart';

class OperationsRepository {
  final ApiClient _apiClient;

  const OperationsRepository(this._apiClient);

  Future<LiveOperationsData> getLiveOperations() async {
    final response = await _apiClient.dio.get(ApiConstants.operationsLiveEndpoint);
    if (response.data is Map<String, dynamic>) {
      return LiveOperationsData.fromJson(response.data as Map<String, dynamic>);
    }
    throw Exception('Canlı operasyon verisi alınamadı.');
  }

  Future<Map<String, dynamic>> updateStatus(int appointmentId, String status) async {
    final response = await _apiClient.dio.post(
      ApiConstants.operationsStatusEndpoint,
      data: {
        'appointment_id': appointmentId,
        'status': status,
      },
    );
    if (response.data is Map<String, dynamic>) {
      return response.data as Map<String, dynamic>;
    }
    return {'success': true};
  }

  Future<Map<String, dynamic>> checkinWithQr(String qrToken) async {
    final response = await _apiClient.dio.post(
      ApiConstants.operationsCheckinEndpoint,
      data: {
        'qr_token': qrToken,
      },
    );
    if (response.data is Map<String, dynamic>) {
      return response.data as Map<String, dynamic>;
    }
    throw Exception('QR check-in işlemi başarısız.');
  }

  Future<List<StationModel>> getFloorPlan() async {
    final response = await _apiClient.dio.get(ApiConstants.operationsFloorPlanEndpoint);
    if (response.data is Map<String, dynamic> && response.data['stations'] is List) {
      final list = response.data['stations'] as List;
      return list.map((item) => StationModel.fromJson(item as Map<String, dynamic>)).toList();
    }
    return const [];
  }

  Future<CustomerCrmModel> getCustomerMiniCrm(int customerId) async {
    final response = await _apiClient.dio.get('${ApiConstants.customersEndpoint}/$customerId/mini-crm');
    if (response.data is Map<String, dynamic>) {
      return CustomerCrmModel.fromJson(response.data as Map<String, dynamic>);
    }
    throw Exception('Müşteri CRM profili yüklenemedi.');
  }

  Future<Map<String, dynamic>> validateConflict({
    required String startDatetime,
    required String endDatetime,
    int? providerId,
    int? stationId,
    int? excludeId,
  }) async {
    final response = await _apiClient.dio.post(
      ApiConstants.operationsValidateConflictEndpoint,
      data: {
        'start_datetime': startDatetime,
        'end_datetime': endDatetime,
        if (providerId != null && providerId > 0) 'provider_id': providerId,
        if (stationId != null && stationId > 0) 'station_id': stationId,
        if (excludeId != null && excludeId > 0) 'exclude_appointment_id': excludeId,
      },
    );
    if (response.data is Map<String, dynamic>) {
      return response.data as Map<String, dynamic>;
    }
    return {'has_conflict': false};
  }

  Future<List<StationModel>> getStations() async {
    final response = await _apiClient.dio.get(ApiConstants.stationsEndpoint);
    if (response.data is List) {
      final list = response.data as List;
      return list.map((item) => StationModel.fromJson(item as Map<String, dynamic>)).toList();
    }
    return const [];
  }
}

