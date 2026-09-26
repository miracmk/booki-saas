import 'service_model.dart';
import 'provider_model.dart';
import 'user_model.dart';

enum AppointmentStatus {
  reserved,
  confirmed,
  arrived,
  inProgress,
  completed,
  noShow,
  cancelled,
  unknown;

  static AppointmentStatus fromString(String? status) {
    switch (status?.toLowerCase()) {
      case 'reserved':
      case 'booked':
      case 'beklemede':
        return AppointmentStatus.reserved;
      case 'confirmed':
      case 'onaylandı':
        return AppointmentStatus.confirmed;
      case 'arrived':
      case 'geldi':
      case 'checked-in':
      case 'checked_in':
        return AppointmentStatus.arrived;
      case 'in_progress':
      case 'inprogress':
      case 'in progress':
      case 'başladı':
        return AppointmentStatus.inProgress;
      case 'completed':
      case 'tamamlandı':
      case 'closed':
        return AppointmentStatus.completed;
      case 'no_show':
      case 'no-show':
      case 'noshow':
      case 'gelmedi':
        return AppointmentStatus.noShow;
      case 'cancelled':
      case 'canceled':
      case 'iptal':
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
      case AppointmentStatus.inProgress:
        return 'Başladı';
      case AppointmentStatus.completed:
        return 'Tamamlandı';
      case AppointmentStatus.noShow:
        return 'Gelmedi';
      case AppointmentStatus.cancelled:
        return 'İptal Edildi';
      case AppointmentStatus.unknown:
        return 'Belirsiz';
    }
  }
}

class AppointmentModel {
  final int id;
  final String hash;
  final DateTime startDatetime;
  final DateTime endDatetime;
  final AppointmentStatus status;
  final String? notes;
  final String? location;
  final int serviceId;
  final int providerId;
  final int customerId;
  final int? stationId;
  final String? stationName;
  final ServiceModel? service;
  final ProviderModel? provider;
  final UserModel? customer;

  const AppointmentModel({
    required this.id,
    this.hash = '',
    required this.startDatetime,
    required this.endDatetime,
    required this.status,
    this.notes,
    this.location,
    required this.serviceId,
    required this.providerId,
    required this.customerId,
    this.stationId,
    this.stationName,
    this.service,
    this.provider,
    this.customer,
  });

  bool get isUpcoming => startDatetime.isAfter(DateTime.now());
  bool get isPast => endDatetime.isBefore(DateTime.now());
  bool get isCancelled => status == AppointmentStatus.cancelled;
  bool get isActive => status == AppointmentStatus.arrived || status == AppointmentStatus.inProgress;

  int get durationMinutes {
    return endDatetime.difference(startDatetime).inMinutes.clamp(15, 480);
  }

  AppointmentModel copyWith({
    int? id,
    String? hash,
    DateTime? startDatetime,
    DateTime? endDatetime,
    AppointmentStatus? status,
    String? notes,
    String? location,
    int? serviceId,
    int? providerId,
    int? customerId,
    int? stationId,
    String? stationName,
    ServiceModel? service,
    ProviderModel? provider,
    UserModel? customer,
  }) {
    return AppointmentModel(
      id: id ?? this.id,
      hash: hash ?? this.hash,
      startDatetime: startDatetime ?? this.startDatetime,
      endDatetime: endDatetime ?? this.endDatetime,
      status: status ?? this.status,
      notes: notes ?? this.notes,
      location: location ?? this.location,
      serviceId: serviceId ?? this.serviceId,
      providerId: providerId ?? this.providerId,
      customerId: customerId ?? this.customerId,
      stationId: stationId ?? this.stationId,
      stationName: stationName ?? this.stationName,
      service: service ?? this.service,
      provider: provider ?? this.provider,
      customer: customer ?? this.customer,
    );
  }

  factory AppointmentModel.fromJson(Map<String, dynamic> json) {
    final startRaw = json['start'] ?? json['start_datetime'] ?? DateTime.now().toIso8601String();
    final endRaw = json['end'] ?? json['end_datetime'] ?? DateTime.now().add(const Duration(minutes: 30)).toIso8601String();

    final idVal = json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0;
    final hashVal = json['hash']?.toString() ?? 'APPT:$idVal';

    return AppointmentModel(
      id: idVal,
      hash: hashVal,
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
      stationId: json['stationId'] is int
          ? json['stationId'] as int
          : int.tryParse(json['stationId']?.toString() ?? json['id_stations']?.toString() ?? ''),
      stationName: json['stationName']?.toString() ?? json['station_name']?.toString() ?? json['location']?.toString(),
      service: json['service'] is Map<String, dynamic> ? ServiceModel.fromJson(json['service']) : null,
      provider: json['provider'] is Map<String, dynamic> ? ProviderModel.fromJson(json['provider']) : null,
      customer: json['customer'] is Map<String, dynamic> ? UserModel.fromJson(json['customer']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'hash': hash,
      'start': startDatetime.toIso8601String(),
      'end': endDatetime.toIso8601String(),
      'status': status.name,
      'notes': notes,
      'location': location,
      'serviceId': serviceId,
      'providerId': providerId,
      'customerId': customerId,
      'stationId': stationId,
      'stationName': stationName,
    };
  }
}
