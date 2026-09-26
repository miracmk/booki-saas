import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../data/models/appointment_model.dart';
import 'crm/customer_crm_bottom_sheet.dart';
import 'qr/customer_qr_pass_card.dart';
import 'quick_status_actions.dart';

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
        return const Color(0xFF2563EB);
      case AppointmentStatus.inProgress:
        return const Color(0xFF8B5CF6);
      case AppointmentStatus.completed:
        return const Color(0xFF10B981);
      case AppointmentStatus.noShow:
        return const Color(0xFFF59E0B);
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
    final customerName = appointment.customer?.fullName ?? 'Müşteri #${appointment.customerId}';

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: BorderSide(color: AppTheme.borderLight.withValues(alpha: 0.8)),
      ),
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header: Tarih & Durum & QR Butonu
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(
                      Icons.calendar_today_rounded,
                      size: 14,
                      color: AppTheme.textSecondaryLight,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      dateStr,
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.textSecondaryLight,
                      ),
                    ),
                  ],
                ),
                Row(
                  children: [
                    // Giriş Kartı (Pass) Butonu
                    IconButton(
                      tooltip: 'QR Giriş Kartı',
                      visualDensity: VisualDensity.compact,
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      icon: const Icon(Icons.qr_code_2_rounded, size: 20, color: Color(0xFF0F172A)),
                      onPressed: () {
                        CustomerQrPassCard.show(context, appointment);
                      },
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        appointment.status.labelTr,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: statusColor,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Hizmet, Saat ve Masa
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        appointment.service?.name ?? 'Rezervasyon Hizmeti',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: AppTheme.textPrimaryLight,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(
                            Icons.access_time_filled_rounded,
                            size: 14,
                            color: AppTheme.primaryColor,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            timeStr,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: AppTheme.primaryColor,
                            ),
                          ),
                          if (appointment.stationName != null && appointment.stationName!.isNotEmpty) ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                appointment.stationName!,
                                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                if (appointment.service?.formattedPrice != null)
                  Text(
                    appointment.service!.formattedPrice,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: AppTheme.textPrimaryLight,
                    ),
                  ),
              ],
            ),

            const Divider(height: 20, color: AppTheme.borderLight),

            // Alt Bilgiler: Müşteri & Mini-CRM Kartına Tıklama
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                InkWell(
                  onTap: () {
                    CustomerCrmBottomSheet.show(
                      context,
                      customerId: appointment.customerId,
                      fallbackName: customerName,
                      fallbackPhone: appointment.customer?.phone ?? '',
                    );
                  },
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 2, horizontal: 4),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 13,
                          backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.15),
                          child: const Icon(
                            Icons.person_rounded,
                            size: 14,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          customerName,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF1E293B),
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.info_outline_rounded, size: 14, color: Color(0xFF94A3B8)),
                      ],
                    ),
                  ),
                ),
                if (appointment.provider != null)
                  Text(
                    appointment.provider!.name,
                    style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                  ),
              ],
            ),

            // Personel Kesintisiz Hızlı Durum Butonları (Optimistic UI & Undo)
            if (showStaffActions && !appointment.isCancelled) ...[
              const SizedBox(height: 12),
              QuickStatusActions(
                appointment: appointment,
                customerName: customerName,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
