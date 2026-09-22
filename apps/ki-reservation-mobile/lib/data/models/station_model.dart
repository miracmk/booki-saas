import 'package:flutter/material.dart';

enum StationStatus {
  empty,      // Yeşil
  reserved,   // Mavi
  occupied,   // Kırmızı
  overdue;    // Turuncu / Nabız uyarısı

  static StationStatus fromString(String? val) {
    switch (val?.toLowerCase()) {
      case 'occupied':
      case 'dolu':
        return StationStatus.occupied;
      case 'reserved':
      case 'rezerve':
        return StationStatus.reserved;
      case 'overdue':
      case 'süresi aşan':
      case 'asan':
        return StationStatus.overdue;
      case 'empty':
      case 'boş':
      default:
        return StationStatus.empty;
    }
  }

  String get labelTr {
    switch (this) {
      case StationStatus.empty:
        return 'Boş';
      case StationStatus.reserved:
        return 'Rezerve';
      case StationStatus.occupied:
        return 'Dolu';
      case StationStatus.overdue:
        return 'Süresi Aşan';
    }
  }

  Color get color {
    switch (this) {
      case StationStatus.empty:
        return const Color(0xFF10B981); // Emerald Green
      case StationStatus.reserved:
        return const Color(0xFF3B82F6); // Blue
      case StationStatus.occupied:
        return const Color(0xFFEF4444); // Red
      case StationStatus.overdue:
        return const Color(0xFFF59E0B); // Amber / Orange
    }
  }
}

class StationModel {
  final int id;
  final String name;
  final int capacity;
  final bool isActive;
  final StationStatus status;
  final String? activeGuest;
  final int elapsedSeconds;
  final bool isOverdue;
  final int? remainingToReservation;
  final Map<String, dynamic>? activeAppointment;

  const StationModel({
    required this.id,
    required this.name,
    this.capacity = 4,
    this.isActive = true,
    this.status = StationStatus.empty,
    this.activeGuest,
    this.elapsedSeconds = 0,
    this.isOverdue = false,
    this.remainingToReservation,
    this.activeAppointment,
  });

  factory StationModel.fromJson(Map<String, dynamic> json) {
    return StationModel(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? 'Masa',
      capacity: json['capacity'] is int ? json['capacity'] as int : int.tryParse(json['capacity']?.toString() ?? '4') ?? 4,
      isActive: json['is_active'] == true || json['is_active'] == 1 || json['is_active'] == '1',
      status: StationStatus.fromString(json['status']?.toString()),
      activeGuest: json['active_guest']?.toString(),
      elapsedSeconds: json['elapsed_seconds'] is int ? json['elapsed_seconds'] as int : int.tryParse(json['elapsed_seconds']?.toString() ?? '0') ?? 0,
      isOverdue: json['is_overdue'] == true || json['is_overdue'] == 1,
      remainingToReservation: json['remaining_to_reservation'] is int ? json['remaining_to_reservation'] as int : null,
      activeAppointment: json['active_appointment'] is Map<String, dynamic> ? json['active_appointment'] : null,
    );
  }
}
