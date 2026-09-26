import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/appointments_provider.dart';
import '../../../providers/operations_provider.dart';

class QrScannerDialog extends ConsumerStatefulWidget {
  const QrScannerDialog({super.key});

  static Future<void> show(BuildContext context) {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const QrScannerDialog(),
    );
  }

  @override
  ConsumerState<QrScannerDialog> createState() => _QrScannerDialogState();
}

class _QrScannerDialogState extends ConsumerState<QrScannerDialog> {
  final MobileScannerController _scannerController = MobileScannerController(
    detectionSpeed: DetectionSpeed.noDuplicates,
    facing: CameraFacing.back,
    torchEnabled: false,
  );

  final TextEditingController _manualInputController = TextEditingController();
  bool _isLoading = false;
  bool _torchOn = false;
  String? _errorMessage;

  @override
  void dispose() {
    _scannerController.dispose();
    _manualInputController.dispose();
    super.dispose();
  }

  Future<void> _processToken(String token) async {
    if (_isLoading) return;

    final trimmed = token.trim();
    if (trimmed.isEmpty) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    HapticFeedback.mediumImpact();

    final repo = ref.read(operationsRepositoryProvider);

    try {
      final res = await repo.checkinWithQr(trimmed);
      ref.invalidate(agendaAppointmentsProvider);
      ref.invalidate(liveOperationsProvider);
      ref.invalidate(floorPlanProvider);

      if (!mounted) return;

      HapticFeedback.heavyImpact();

      // Show Success Dialog
      await showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle_rounded, color: Color(0xFF10B981), size: 28),
              ),
              const SizedBox(width: 10),
              const Text('Giriş Onaylandı', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                res['message']?.toString() ?? 'Giriş işlemi başarıyla tamamlandı.',
                style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF1E293B)),
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  children: [
                    _infoRow('Misafir:', res['customer_name']?.toString() ?? '-'),
                    const Divider(height: 12),
                    _infoRow('Hizmet:', res['service_name']?.toString() ?? '-'),
                    const Divider(height: 12),
                    _infoRow('Durum:', res['status']?.toString() ?? 'Geldi'),
                  ],
                ),
              ),
            ],
          ),
          actions: [
            ElevatedButton(
              onPressed: () {
                Navigator.of(ctx).pop();
                Navigator.of(context).pop();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryColor,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text('Tamam & Kapat', style: TextStyle(fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      );
    } catch (e) {
      if (!mounted) return;
      HapticFeedback.vibrate();
      setState(() {
        _errorMessage = 'Doğrulama Hatası: Geçersiz veya bulunamayan QR kod.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Widget _infoRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Color(0xFF64748B), fontSize: 13)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF0F172A), fontSize: 13)),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      clipBehavior: Clip.antiAlias,
      child: Container(
        color: const Color(0xFF0F172A),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Üst Bar
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.qr_code_scanner_rounded, color: Color(0xFF38BDF8), size: 22),
                      SizedBox(width: 8),
                      Text(
                        'QR Kod Doğrulama & Check-in',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded, color: Colors.white70),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ],
              ),
            ),

            // Kamera Görünümü & Çerçeve
            SizedBox(
              height: 260,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  MobileScanner(
                    controller: _scannerController,
                    onDetect: (capture) {
                      final barcodes = capture.barcodes;
                      for (final barcode in barcodes) {
                        final raw = barcode.rawValue;
                        if (raw != null && raw.isNotEmpty) {
                          _processToken(raw);
                          break;
                        }
                      }
                    },
                  ),

                  // Hizalama Çerçevesi
                  Container(
                    width: 190,
                    height: 190,
                    decoration: BoxDecoration(
                      border: Border.all(color: const Color(0xFF38BDF8), width: 2.5),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF0284C7).withValues(alpha: 0.35),
                          blurRadius: 20,
                          spreadRadius: 2,
                        ),
                      ],
                    ),
                  ),

                  // Flaş & Kamera Değiştir Kontrolleri
                  Positioned(
                    bottom: 12,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          style: IconButton.styleFrom(
                            backgroundColor: Colors.black.withValues(alpha: 0.6),
                          ),
                          icon: Icon(
                            _torchOn ? Icons.flash_on_rounded : Icons.flash_off_rounded,
                            color: _torchOn ? Colors.amber : Colors.white,
                          ),
                          onPressed: () async {
                            await _scannerController.toggleTorch();
                            setState(() {
                              _torchOn = !_torchOn;
                            });
                          },
                        ),
                        const SizedBox(width: 14),
                        IconButton(
                          style: IconButton.styleFrom(
                            backgroundColor: Colors.black.withValues(alpha: 0.6),
                          ),
                          icon: const Icon(Icons.flip_camera_ios_rounded, color: Colors.white),
                          onPressed: () => _scannerController.switchCamera(),
                        ),
                      ],
                    ),
                  ),

                  if (_isLoading)
                    Container(
                      color: Colors.black.withValues(alpha: 0.6),
                      child: const Center(
                        child: CircularProgressIndicator(color: Color(0xFF38BDF8)),
                      ),
                    ),
                ],
              ),
            ),

            // Hata Bildirimi
            if (_errorMessage != null)
              Container(
                margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.red.shade900.withValues(alpha: 0.5),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.red.shade400),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline_rounded, color: Colors.redAccent, size: 18),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _errorMessage!,
                        style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),

            // Alt Bölüm: Manuel Kod Girişi & Test Simülatörü Butonları
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _manualInputController,
                          style: const TextStyle(color: Colors.white, fontSize: 13),
                          decoration: InputDecoration(
                            hintText: 'veya Kod / Randevu No Girin',
                            hintStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                            filled: true,
                            fillColor: const Color(0xFF1E293B),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(color: Color(0xFF334155)),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton(
                        onPressed: () => _processToken(_manualInputController.text),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF2563EB),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        child: const Text('Doğrula', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Hızlı Test Hazır Değerleri
                  Wrap(
                    spacing: 8,
                    runSpacing: 6,
                    alignment: WrapAlignment.center,
                    children: [
                      _presetChip('APPT:1'),
                      _presetChip('APPT:2'),
                      _presetChip('MEMB:1'),
                      _presetChip('+905550000000'),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _presetChip(String label) {
    return ActionChip(
      label: Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF38BDF8), fontWeight: FontWeight.w700)),
      backgroundColor: const Color(0xFF1E293B),
      side: const BorderSide(color: Color(0xFF334155)),
      onPressed: () {
        _manualInputController.text = label;
        _processToken(label);
      },
    );
  }
}

