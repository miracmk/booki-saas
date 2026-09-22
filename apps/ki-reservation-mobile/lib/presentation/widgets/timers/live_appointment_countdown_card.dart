import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/appointment_model.dart';
import '../../../data/models/live_operations_model.dart';
import '../../../providers/operations_provider.dart';

class LiveAppointmentCountdownCard extends ConsumerStatefulWidget {
  final UpcomingCountdownItem item;
  final VoidCallback? onCheckinTap;
  final VoidCallback? onCustomerTap;

  const LiveAppointmentCountdownCard({
    super.key,
    required this.item,
    this.onCheckinTap,
    this.onCustomerTap,
  });

  @override
  ConsumerState<LiveAppointmentCountdownCard> createState() =>
      _LiveAppointmentCountdownCardState();
}

class _LiveAppointmentCountdownCardState
    extends ConsumerState<LiveAppointmentCountdownCard>
    with SingleTickerProviderStateMixin {
  late Timer _timer;
  late int _remainingSeconds;
  late AnimationController _pulseController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();
    _remainingSeconds = widget.item.remainingSeconds;

    // Tick every 1 second
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      if (_remainingSeconds > 0) {
        setState(() {
          _remainingSeconds--;
        });
      } else {
        _timer.cancel();
      }
    });

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat(reverse: true);

    _pulseAnimation = Tween<double>(begin: 0.95, end: 1.05).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );
  }

  @override
  void didUpdateWidget(covariant LiveAppointmentCountdownCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.item.remainingSeconds != widget.item.remainingSeconds) {
      _remainingSeconds = widget.item.remainingSeconds;
    }
  }

  @override
  void dispose() {
    _timer.cancel();
    _pulseController.dispose();
    super.dispose();
  }

  String _formatCountdown(int totalSeconds) {
    if (totalSeconds <= 0) return 'Randevu Saati Geldi!';
    final hours = totalSeconds ~/ 3600;
    final minutes = (totalSeconds % 3600) ~/ 60;
    final seconds = totalSeconds % 60;

    if (hours > 0) {
      return '$hours sa $minutes dk $seconds sn';
    }
    return '$minutes dk $seconds sn';
  }

  @override
  Widget build(BuildContext context) {
    final isImminent = _remainingSeconds <= 900; // Son 15 dakika

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isImminent
              ? [const Color(0xFF1E1B4B), const Color(0xFF312E81)]
              : [const Color(0xFF0F172A), const Color(0xFF1E293B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isImminent ? const Color(0xFF818CF8).withValues(alpha: 0.4) : const Color(0xFF334155),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: isImminent ? const Color(0xFF6366F1).withValues(alpha: 0.25) : Colors.black.withValues(alpha: 0.2),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Üst Başlık & Canlı Rozet
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    ScaleTransition(
                      scale: _pulseAnimation,
                      child: Container(
                        width: 10,
                        height: 10,
                        decoration: BoxDecoration(
                          color: isImminent ? const Color(0xFFF43F5E) : const Color(0xFF10B981),
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: (isImminent ? const Color(0xFFF43F5E) : const Color(0xFF10B981))
                                  .withValues(alpha: 0.6),
                              blurRadius: 8,
                              spreadRadius: 2,
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Text(
                      'YAKLAŞAN RANDEVU',
                      style: TextStyle(
                        color: Color(0xFF94A3B8),
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1.1,
                      ),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.table_restaurant_rounded, size: 14, color: Color(0xFF38BDF8)),
                      const SizedBox(width: 5),
                      Text(
                        widget.item.stationName,
                        style: const TextStyle(
                          color: Color(0xFFE2E8F0),
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),

            // Saniyelik Canlı Sayaç
            Row(
              crossAxisAlignment: CrossAxisAlignment.baseline,
              textBaseline: TextBaseline.alphabetic,
              children: [
                const Icon(Icons.timer_outlined, color: Color(0xFF38BDF8), size: 22),
                const SizedBox(width: 8),
                Text(
                  _formatCountdown(_remainingSeconds),
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -0.5,
                    shadows: [
                      Shadow(
                        color: Colors.blue.withValues(alpha: 0.3),
                        blurRadius: 10,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 6),
                const Text(
                  'kaldı',
                  style: TextStyle(
                    color: Color(0xFF94A3B8),
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Müşteri & Hizmet Bilgisi
            Row(
              children: [
                CircleAvatar(
                  radius: 18,
                  backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.3),
                  child: Text(
                    widget.item.customerName.isNotEmpty ? widget.item.customerName[0].toUpperCase() : 'M',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      InkWell(
                        onTap: widget.onCustomerTap,
                        child: Text(
                          widget.item.customerName,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        '${widget.item.serviceName} • ${widget.item.serviceDuration} dk • ${widget.item.providerName}',
                        style: const TextStyle(
                          color: Color(0xFF94A3B8),
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),

            // Hızlı Eylemler (Kesintisiz Çalışan Butonlar)
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: widget.onCheckinTap ??
                        () {
                          ref.read(operationsServiceProvider).changeStatusWithUndo(
                                context: context,
                                appointmentId: widget.item.id,
                                newStatus: AppointmentStatus.arrived,
                                previousStatus: AppointmentStatus.fromString(widget.item.status),
                                customerName: widget.item.customerName,
                              );
                        },
                    icon: const Icon(Icons.qr_code_scanner_rounded, size: 16),
                    label: const Text('Geldi / Check-in', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                if (widget.onCustomerTap != null)
                  IconButton(
                    tooltip: 'Müşteri Profili (CRM)',
                    onPressed: widget.onCustomerTap,
                    icon: const Icon(Icons.person_outline_rounded, color: Colors.white),
                    style: IconButton.styleFrom(
                      backgroundColor: Colors.white.withValues(alpha: 0.1),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
