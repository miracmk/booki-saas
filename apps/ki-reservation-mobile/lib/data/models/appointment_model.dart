import 'service_model.dart';
import 'provider_model.dart';
import 'user_model.dart';

enum AppointmentStatus {
  reserved,
  confirmed,
  arrived,
  completed,
  cancelled,
  unknown;

  static AppointmentStatus fromString(String? status) {
    switch (status?.toLowerCase()) {
      case 'reserved':
      case 'booked':
        return AppointmentStatus.reserved;
      case 'confirmed':
        return AppointmentStatus.confirmed;
      case 'arrived':
        return AppointmentStatus.arrived;
      case 'completed':
        return AppointmentStatus.completed;
      case 'cancelled':
      case 'canceled':
        return AppointmentStatus.cancelled;
      default:
        return AppointmentStatus.unknown;
    }
  }

  String get labelTr {
    switch (this) {
      case AppointmentStatus.reserved:
        return 'Beklemede';
      case AppointmentStatus.confirmed:
        return 'Onaylandı';
      case AppointmentStatus.arrived:
        return 'Geldi';
      case AppointmentStatus.completed:
        return 'Tamamlandı';
      case AppointmentStatus.cancelled:
        return 'İptal Edildi';
      case AppointmentStatus.unknown:
        return 'Belirsiz';
    }
  }
}

class AppointmentModel {
  final int id;
  final DateTime startDatetime;
  final DateTime endDatetime;
  final AppointmentStatus status;
  final String? notes;
  final String? location;
  final int serviceId;
  final int providerId;
  final int customerId;
  final ServiceModel? service;
  final ProviderModel? provider;
  final UserModel? customer;

  const AppointmentModel({
    required this.id,
    required this.startDatetime,
    required this.endDatetime,
    required this.status,
    this.notes,
    this.location,
    required this.serviceId,
    required this.providerId,
    required this.customerId,
    this.service,
    this.provider,
    this.customer,
  });

  bool get isUpcoming => startDatetime.isAfter(DateTime.now());
  bool get isPast => endDatetime.isBefore(DateTime.now());
  bool get isCancelled => status == AppointmentStatus.cancelled;

  factory AppointmentModel.fromJson(Map<String, dynamic> json) {
    final startRaw = json['start'] ?? json['start_datetime'] ?? DateTime.now().toIso8601String();
    final endRaw = json['end'] ?? json['end_datetime'] ?? DateTime.now().add(const Duration(minutes: 30)).toIso8601String();

    return AppointmentModel(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      startDatetime: DateTime.tryParse(startRaw.toString()) ?? DateTime.now(),
      endDatetime: DateTime.tryParse(endRaw.toString()) ?? DateTime.now().add(const Duration(minutes: 30)),
      status: AppointmentStatus.fromString(json['status']?.toString()),
      notes: json['notes']?.toString(),
      location: json['location']?.toString(),
      serviceId: json['serviceId'] is int
          ? json['serviceId'] as int
          : int.tryParse(json['serviceId']?.toString() ?? json['id_services']?.toString() ?? '0') ?? 0,
      providerId: json['providerId'] is int
          ? json['providerId'] as int
          : int.tryParse(json['providerId']?.toString() ?? json['id_users_provider']?.toString() ?? '0') ?? 0,
      customerId: json['customerId'] is int
          ? json['customerId'] as int
          : int.tryParse(json['customerId']?.toString() ?? json['id_users_customer']?.toString() ?? '0') ?? 0,
      service: json['service'] is Map<String, dynamic> ? ServiceModel.fromJson(json['service']) : null,
      provider: json['provider'] is Map<String, dynamic> ? ProviderModel.fromJson(json['provider']) : null,
      customer: json['customer'] is Map<String, dynamic> ? UserModel.fromJson(json['customer']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'start': startDatetime.toIso8601String(),
      'end': endDatetime.toIso8601String(),
      'status': status.name,
      'notes': notes,
      'location': location,
      'serviceId': serviceId,
      'providerId': providerId,
      'customerId': customerId,
    };
  }
}

