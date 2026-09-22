import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../data/models/appointment_model.dart';

class AppointmentCard extends StatelessWidget {
  final AppointmentModel appointment;
  final VoidCallback? onTap;
  final Function(String status)? onStatusChanged;
  final bool showStaffActions;

  const AppointmentCard({
    super.key,
    required this.appointment,
    this.onTap,
    this.onStatusChanged,
    this.showStaffActions = false,
  });

  Color _getStatusColor(AppointmentStatus status) {
    switch (status) {
      case AppointmentStatus.confirmed:
        return AppTheme.accentSuccess;
      case AppointmentStatus.arrived:
        return AppTheme.secondaryColor;
      case AppointmentStatus.completed:
        return Colors.purple;
      case AppointmentStatus.cancelled:
        return AppTheme.accentDanger;
      case AppointmentStatus.reserved:
      default:
        return AppTheme.accentWarning;
    }
  }

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('d MMMM yyyy, EEEE', 'tr_TR');
    final timeFormat = DateFormat('HH:mm');

    final dateStr = dateFormat.format(appointment.startDatetime);
    final timeStr =
        '${timeFormat.format(appointment.startDatetime)} - ${timeFormat.format(appointment.endDatetime)}';

    final statusColor = _getStatusColor(appointment.status);

    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header: Tarih & Durum Etiketi
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Icon(
                        Icons.calendar_today_rounded,
                        size: 15,
                        color: AppTheme.textSecondaryLight,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        dateStr,
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppTheme.textSecondaryLight,
                        ),
                      ),
                    ],
                  ),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      appointment.status.labelTr,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Hizmet ve Saat
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.spa_rounded,
                      color: AppTheme.primaryColor,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          appointment.service?.name ?? 'Randevu #${appointment.id}',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.textPrimaryLight,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            const Icon(
                              Icons.access_time_rounded,
                              size: 14,
                              color: AppTheme.primaryColor,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              timeStr,
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppTheme.primaryColor,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  if (appointment.service != null)
                    Text(
                      appointment.service!.formattedPrice,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.textPrimaryLight,
                      ),
                    ),
                ],
              ),

              const Divider(height: 24, color: AppTheme.borderLight),

              // Alt Bilgiler: Uzman veya Müşteri
              Row(
                children: [
                  CircleAvatar(
                    radius: 12,
                    backgroundColor: AppTheme.borderLight,
                    child: const Icon(
                      Icons.person_rounded,
                      size: 14,
                      color: AppTheme.textSecondaryLight,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      showStaffActions
                          ? (appointment.customer?.fullName ?? 'Müşteri #${appointment.customerId}')
                          : (appointment.provider?.fullName ?? 'Uzman Personel'),
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        color: AppTheme.textPrimaryLight,
                      ),
                    ),
                  ),
                ],
              ),

              // Personel Hızlı Durum Butonları (İşletme/Personel Görünümü)
              if (showStaffActions && onStatusChanged != null && !appointment.isCancelled) ...[
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  children: [
                    if (appointment.status != AppointmentStatus.confirmed)
                      _actionChip(
                        context,
                        label: 'Onayla',
                        color: AppTheme.accentSuccess,
                        icon: Icons.check_circle_outline,
                        onTap: () => onStatusChanged!('confirmed'),
                      ),
                    if (appointment.status != AppointmentStatus.arrived &&
                        appointment.status != AppointmentStatus.completed)
                      _actionChip(
                        context,
                        label: 'Geldi',
                        color: AppTheme.secondaryColor,
                        icon: Icons.directions_walk_rounded,
                        onTap: () => onStatusChanged!('arrived'),
                      ),
                    if (appointment.status != AppointmentStatus.completed)
                      _actionChip(
                        context,
                        label: 'Tamamlandı',
                        color: Colors.purple,
                        icon: Icons.task_alt_rounded,
                        onTap: () => onStatusChanged!('completed'),
                      ),
                    _actionChip(
                      context,
                      label: 'İptal',
                      color: AppTheme.accentDanger,
                      icon: Icons.close_rounded,
                      onTap: () => onStatusChanged!('cancelled'),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _actionChip(
    BuildContext context, {
    required String label,
    required Color color,
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: color),
            const SizedBox(width: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

