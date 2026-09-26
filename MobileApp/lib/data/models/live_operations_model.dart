class LiveOperationsMetrics {
  final int totalAppointments;
  final int occupancyRate;
  final int activeTablesCount;
  final int pendingCount;
  final int completedCount;
  final int totalStations;
  final bool whatsappStatus;
  final String serverTime;

  const LiveOperationsMetrics({
    this.totalAppointments = 0,
    this.occupancyRate = 0,
    this.activeTablesCount = 0,
    this.pendingCount = 0,
    this.completedCount = 0,
    this.totalStations = 0,
    this.whatsappStatus = false,
    this.serverTime = '',
  });

  factory LiveOperationsMetrics.fromJson(Map<String, dynamic> json) {
    return LiveOperationsMetrics(
      totalAppointments: json['total_appointments'] is int ? json['total_appointments'] as int : int.tryParse(json['total_appointments']?.toString() ?? '0') ?? 0,
      occupancyRate: json['occupancy_rate'] is int ? json['occupancy_rate'] as int : int.tryParse(json['occupancy_rate']?.toString() ?? '0') ?? 0,
      activeTablesCount: json['active_tables_count'] is int ? json['active_tables_count'] as int : int.tryParse(json['active_tables_count']?.toString() ?? '0') ?? 0,
      pendingCount: json['pending_count'] is int ? json['pending_count'] as int : int.tryParse(json['pending_count']?.toString() ?? '0') ?? 0,
      completedCount: json['completed_count'] is int ? json['completed_count'] as int : int.tryParse(json['completed_count']?.toString() ?? '0') ?? 0,
      totalStations: json['total_stations'] is int ? json['total_stations'] as int : int.tryParse(json['total_stations']?.toString() ?? '0') ?? 0,
      whatsappStatus: json['whatsapp_status'] == true || json['whatsapp_status'] == 1,
      serverTime: json['server_time']?.toString() ?? '',
    );
  }
}

class UpcomingCountdownItem {
  final int id;
  final String hash;
  final String customerName;
  final String customerPhone;
  final int customerId;
  final String serviceName;
  final int serviceDuration;
  final String providerName;
  final int stationId;
  final String stationName;
  final String startDatetime;
  final int remainingSeconds;
  final String status;

  const UpcomingCountdownItem({
    required this.id,
    this.hash = '',
    required this.customerName,
    this.customerPhone = '',
    required this.customerId,
    required this.serviceName,
    this.serviceDuration = 30,
    required this.providerName,
    this.stationId = 0,
    this.stationName = 'Masa Belirtilmemiş',
    required this.startDatetime,
    required this.remainingSeconds,
    this.status = 'reserved',
  });

  factory UpcomingCountdownItem.fromJson(Map<String, dynamic> json) {
    return UpcomingCountdownItem(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      hash: json['hash']?.toString() ?? '',
      customerName: json['customer_name']?.toString() ?? 'Müşteri',
      customerPhone: json['customer_phone']?.toString() ?? '',
      customerId: json['customer_id'] is int ? json['customer_id'] as int : int.tryParse(json['customer_id']?.toString() ?? '0') ?? 0,
      serviceName: json['service_name']?.toString() ?? 'Hizmet',
      serviceDuration: json['service_duration'] is int ? json['service_duration'] as int : int.tryParse(json['service_duration']?.toString() ?? '30') ?? 30,
      providerName: json['provider_name']?.toString() ?? 'Personel',
      stationId: json['station_id'] is int ? json['station_id'] as int : int.tryParse(json['station_id']?.toString() ?? '0') ?? 0,
      stationName: json['station_name']?.toString() ?? 'Masa Belirtilmemiş',
      startDatetime: json['start_datetime']?.toString() ?? '',
      remainingSeconds: json['remaining_seconds'] is int ? json['remaining_seconds'] as int : int.tryParse(json['remaining_seconds']?.toString() ?? '0') ?? 0,
      status: json['status']?.toString() ?? 'reserved',
    );
  }
}

class ActiveTableTimerItem {
  final int id;
  final String customerName;
  final String customerPhone;
  final int customerId;
  final String serviceName;
  final int serviceDuration;
  final String stationName;
  final int stationId;
  final String startDatetime;
  final int elapsedSeconds;
  final bool isOverdue;
  final int targetDurationSeconds;
  final String status;

  const ActiveTableTimerItem({
    required this.id,
    required this.customerName,
    this.customerPhone = '',
    required this.customerId,
    required this.serviceName,
    this.serviceDuration = 30,
    required this.stationName,
    this.stationId = 0,
    required this.startDatetime,
    required this.elapsedSeconds,
    this.isOverdue = false,
    required this.targetDurationSeconds,
    this.status = 'arrived',
  });

  factory ActiveTableTimerItem.fromJson(Map<String, dynamic> json) {
    return ActiveTableTimerItem(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      customerName: json['customer_name']?.toString() ?? 'Misafir',
      customerPhone: json['customer_phone']?.toString() ?? '',
      customerId: json['customer_id'] is int ? json['customer_id'] as int : int.tryParse(json['customer_id']?.toString() ?? '0') ?? 0,
      serviceName: json['service_name']?.toString() ?? 'Hizmet / Masa',
      serviceDuration: json['service_duration'] is int ? json['service_duration'] as int : int.tryParse(json['service_duration']?.toString() ?? '30') ?? 30,
      stationName: json['station_name']?.toString() ?? 'Masa',
      stationId: json['station_id'] is int ? json['station_id'] as int : int.tryParse(json['station_id']?.toString() ?? '0') ?? 0,
      startDatetime: json['start_datetime']?.toString() ?? '',
      elapsedSeconds: json['elapsed_seconds'] is int ? json['elapsed_seconds'] as int : int.tryParse(json['elapsed_seconds']?.toString() ?? '0') ?? 0,
      isOverdue: json['is_overdue'] == true || json['is_overdue'] == 1,
      targetDurationSeconds: json['target_duration_seconds'] is int ? json['target_duration_seconds'] as int : int.tryParse(json['target_duration_seconds']?.toString() ?? '1800') ?? 1800,
      status: json['status']?.toString() ?? 'arrived',
    );
  }
}

class LiveOperationsData {
  final LiveOperationsMetrics metrics;
  final List<UpcomingCountdownItem> upcomingCountdowns;
  final List<ActiveTableTimerItem> activeTableTimers;

  const LiveOperationsData({
    required this.metrics,
    required this.upcomingCountdowns,
    required this.activeTableTimers,
  });

  factory LiveOperationsData.fromJson(Map<String, dynamic> json) {
    final metricsData = json['metrics'] is Map<String, dynamic>
        ? LiveOperationsMetrics.fromJson(json['metrics'])
        : const LiveOperationsMetrics();

    final upcomingList = <UpcomingCountdownItem>[];
    if (json['upcoming_countdowns'] is List) {
      for (final item in json['upcoming_countdowns']) {
        if (item is Map<String, dynamic>) {
          upcomingList.add(UpcomingCountdownItem.fromJson(item));
        }
      }
    }

    final activeList = <ActiveTableTimerItem>[];
    if (json['active_table_timers'] is List) {
      for (final item in json['active_table_timers']) {
        if (item is Map<String, dynamic>) {
          activeList.add(ActiveTableTimerItem.fromJson(item));
        }
      }
    }

    return LiveOperationsData(
      metrics: metricsData,
      upcomingCountdowns: upcomingList,
      activeTableTimers: activeList,
    );
  }
}

