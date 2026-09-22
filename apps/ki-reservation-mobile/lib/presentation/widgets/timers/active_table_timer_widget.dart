import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../data/models/appointment_model.dart';
import '../../../data/models/live_operations_model.dart';
import '../../../providers/operations_provider.dart';

class ActiveTableTimerWidget extends ConsumerStatefulWidget {
  final ActiveTableTimerItem item;
  final VoidCallback? onCompleteTap;
  final VoidCallback? onCustomerTap;

  const ActiveTableTimerWidget({
    super.key,
    required this.item,
    this.onCompleteTap,
    this.onCustomerTap,
  });

  @override
  ConsumerState<ActiveTableTimerWidget> createState() =>
      _ActiveTableTimerWidgetState();
}

class _ActiveTableTimerWidgetState extends ConsumerState<ActiveTableTimerWidget>
    with SingleTickerProviderStateMixin {
  late Timer _timer;
  late int _elapsedSeconds;
  late AnimationController _pulseController;
  late Animation<double> _pulseBorderAnimation;

  @override
  void initState() {
    super.initState();
    _elapsedSeconds = widget.item.elapsedSeconds;

    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        _elapsedSeconds++;
      });
    });

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );

    _pulseBorderAnimation = Tween<double>(begin: 1.0, end: 3.5).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );

    if (widget.item.isOverdue || _elapsedSeconds >= widget.item.targetDurationSeconds) {
      _pulseController.repeat(reverse: true);
    }
  }

  @override
  void didUpdateWidget(covariant ActiveTableTimerWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.item.elapsedSeconds != widget.item.elapsedSeconds) {
      _elapsedSeconds = widget.item.elapsedSeconds;
    }
    final isOverdue = _elapsedSeconds >= widget.item.targetDurationSeconds;
    if (isOverdue && !_pulseController.isAnimating) {
      _pulseController.repeat(reverse: true);
    } else if (!isOverdue && _pulseController.isAnimating) {
      _pulseController.stop();
      _pulseController.reset();
    }
  }

  @override
  void dispose() {
    _timer.cancel();
    _pulseController.dispose();
    super.dispose();
  }

  String _formatElapsed(int totalSeconds) {
    final hours = totalSeconds ~/ 3600;
    final minutes = (totalSeconds % 3600) ~/ 60;
    final seconds = totalSeconds % 60;

    final hStr = hours.toString().padLeft(2, '0');
    final mStr = minutes.toString().padLeft(2, '0');
    final sStr = seconds.toString().padLeft(2, '0');

    if (hours > 0) {
      return '$hStr:$mStr:$sStr';
    }
    return '$mStr:$sStr';
  }

  @override
  Widget build(BuildContext context) {
    final targetSeconds = widget.item.targetDurationSeconds;
    final isOverdue = _elapsedSeconds >= targetSeconds;
    final isWarning = !isOverdue && _elapsedSeconds >= (targetSeconds * 0.8);

    Color borderColor = const Color(0xFFE2E8F0);
    Color badgeColor = const Color(0xFF10B981);
    if (isOverdue) {
      borderColor = const Color(0xFFEF4444);
      badgeColor = const Color(0xFFEF4444);
    } else if (isWarning) {
      borderColor = const Color(0xFFF59E0B);
      badgeColor = const Color(0xFFF59E0B);
    }

    return AnimatedBuilder(
      animation: _pulseBorderAnimation,
      builder: (context, child) {
        return Container(
          width: 250,
          margin: const EdgeInsets.only(right: 12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isOverdue ? borderColor : borderColor.withValues(alpha: 0.6),
              width: isOverdue ? _pulseBorderAnimation.value : 1.2,
            ),
            boxShadow: [
              BoxShadow(
                color: isOverdue
                    ? borderColor.withValues(alpha: 0.25)
                    : Colors.black.withValues(alpha: 0.04),
                blurRadius: isOverdue ? 12 : 6,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: child,
        );
      },
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            // Masa Başlığı ve Durum Rozeti
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: badgeColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(Icons.table_restaurant_rounded, size: 16, color: badgeColor),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      widget.item.stationName,
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: badgeColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    isOverdue ? 'Süre Aşıldı!' : (isWarning ? 'Son Dk' : 'Aktif'),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      color: badgeColor,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),

            // Süre Sayacı
            Row(
              children: [
                Icon(
                  Icons.hourglass_bottom_rounded,
                  size: 18,
                  color: isOverdue ? const Color(0xFFEF4444) : const Color(0xFF64748B),
                ),
                const SizedBox(width: 6),
                Text(
                  _formatElapsed(_elapsedSeconds),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    color: isOverdue ? const Color(0xFFEF4444) : const Color(0xFF0F172A),
                    letterSpacing: -0.5,
                  ),
                ),
                const SizedBox(width: 6),
                Text(
                  '/ ${widget.item.serviceDuration} dk',
                  style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Misafir & Hizmet
            InkWell(
              onTap: widget.onCustomerTap,
              child: Text(
                widget.item.customerName,
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF1E293B)),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            Text(
              widget.item.serviceName,
              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 10),

            // Tamamla Butonu (Kesintisiz Eylem)
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: widget.onCompleteTap ??
                    () {
                      ref.read(operationsServiceProvider).changeStatusWithUndo(
                            context: context,
                            appointmentId: widget.item.id,
                            newStatus: AppointmentStatus.completed,
                            previousStatus: AppointmentStatus.fromString(widget.item.status),
                            customerName: widget.item.customerName,
                          );
                    },
                icon: const Icon(Icons.check_circle_outline_rounded, size: 15),
                label: const Text('Tamamla & Masayı Boşalt', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: isOverdue ? const Color(0xFFEF4444) : const Color(0xFF0F172A),
                  side: BorderSide(color: isOverdue ? const Color(0xFFEF4444) : const Color(0xFFCBD5E1)),
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
