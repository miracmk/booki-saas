import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/models/appointment_model.dart';
import '../../providers/operations_provider.dart';

class QuickStatusActions extends ConsumerWidget {
  final AppointmentModel appointment;
  final String? customerName;

  const QuickStatusActions({
    super.key,
    required this.appointment,
    this.customerName,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentStatus = appointment.status;

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _statusPill(
            context: context,
            ref: ref,
            targetStatus: AppointmentStatus.arrived,
            currentStatus: currentStatus,
            label: 'Geldi',
            icon: Icons.how_to_reg_rounded,
            color: const Color(0xFF2563EB), // Blue
          ),
          const SizedBox(width: 8),
          _statusPill(
            context: context,
            ref: ref,
            targetStatus: AppointmentStatus.inProgress,
            currentStatus: currentStatus,
            label: 'Başladı',
            icon: Icons.play_circle_outline_rounded,
            color: const Color(0xFF8B5CF6), // Purple
          ),
          const SizedBox(width: 8),
          _statusPill(
            context: context,
            ref: ref,
            targetStatus: AppointmentStatus.completed,
            currentStatus: currentStatus,
            label: 'Tamamlandı',
            icon: Icons.check_circle_outline_rounded,
            color: const Color(0xFF10B981), // Emerald
          ),
          const SizedBox(width: 8),
          _statusPill(
            context: context,
            ref: ref,
            targetStatus: AppointmentStatus.noShow,
            currentStatus: currentStatus,
            label: 'Gelmedi',
            icon: Icons.person_off_outlined,
            color: const Color(0xFFF59E0B), // Amber
          ),
          const SizedBox(width: 8),
          _statusPill(
            context: context,
            ref: ref,
            targetStatus: AppointmentStatus.cancelled,
            currentStatus: currentStatus,
            label: 'İptal',
            icon: Icons.cancel_outlined,
            color: const Color(0xFFEF4444), // Red
          ),
        ],
      ),
    );
  }

  Widget _statusPill({
    required BuildContext context,
    required WidgetRef ref,
    required AppointmentStatus targetStatus,
    required AppointmentStatus currentStatus,
    required String label,
    required IconData icon,
    required Color color,
  }) {
    final isSelected = currentStatus == targetStatus;

    return InkWell(
      onTap: () {
        if (isSelected) return;
        ref.read(operationsServiceProvider).changeStatusWithUndo(
              context: context,
              appointmentId: appointment.id,
              newStatus: targetStatus,
              previousStatus: currentStatus,
              customerName: customerName ?? appointment.customer?.name,
            );
      },
      borderRadius: BorderRadius.circular(12),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? color : color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? color : color.withValues(alpha: 0.3),
            width: 1.2,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 14,
              color: isSelected ? Colors.white : color,
            ),
            const SizedBox(width: 5),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: isSelected ? Colors.white : color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

