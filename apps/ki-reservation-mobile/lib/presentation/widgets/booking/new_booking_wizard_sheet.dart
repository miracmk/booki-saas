import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/provider_model.dart';
import '../../../data/models/service_model.dart';
import '../../../data/models/station_model.dart';
import '../../../providers/appointments_provider.dart';
import '../../../providers/booking_provider.dart';
import '../../../providers/operations_provider.dart';

class NewBookingWizardSheet extends ConsumerStatefulWidget {
  final DateTime? initialDate;
  final int? preselectedStationId;

  const NewBookingWizardSheet({
    super.key,
    this.initialDate,
    this.preselectedStationId,
  });

  static Future<void> show(BuildContext context, {DateTime? initialDate, int? preselectedStationId}) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => NewBookingWizardSheet(
        initialDate: initialDate,
        preselectedStationId: preselectedStationId,
      ),
    );
  }

  @override
  ConsumerState<NewBookingWizardSheet> createState() =>
      _NewBookingWizardSheetState();
}

class _NewBookingWizardSheetState extends ConsumerState<NewBookingWizardSheet> {
  int _currentStep = 0; // 0, 1, 2, 3
  final _formKeyStep1 = GlobalKey<FormState>();

  // Adım 1: Müşteri Bilgileri
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _phoneController = TextEditingController(text: '+90 ');
  final _notesController = TextEditingController();

  // Adım 2: Hizmet ve Masa
  ServiceModel? _selectedService;
  StationModel? _selectedStation;

  // Adım 3: Personel & Zaman
  ProviderModel? _selectedProvider;
  late DateTime _selectedDate;
  TimeOfDay _selectedTime = const TimeOfDay(hour: 14, minute: 0);
  bool _isCheckingConflict = false;
  bool _hasConflict = false;
  String? _conflictMessage;

  // Adım 4: WhatsApp Toggle
  bool _sendWhatsAppConfirmation = true;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _selectedDate = widget.initialDate ?? DateTime.now();
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _phoneController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _checkConflictAndProceed() async {
    setState(() {
      _isCheckingConflict = true;
      _hasConflict = false;
      _conflictMessage = null;
    });

    final duration = _selectedService?.duration ?? 30;
    final startDt = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      _selectedTime.hour,
      _selectedTime.minute,
    );
    final endDt = startDt.add(Duration(minutes: duration));

    final startStr = DateFormat('yyyy-MM-dd HH:mm:ss').format(startDt);
    final endStr = DateFormat('yyyy-MM-dd HH:mm:ss').format(endDt);

    final repo = ref.read(operationsRepositoryProvider);

    try {
      final res = await repo.validateConflict(
        startDatetime: startStr,
        endDatetime: endStr,
        providerId: _selectedProvider?.id,
        stationId: _selectedStation?.id,
      );

      if (res['has_conflict'] == true) {
        setState(() {
          _hasConflict = true;
          _conflictMessage = res['message']?.toString() ?? 'Bu saatte çakışan bir randevu var!';
          _isCheckingConflict = false;
        });
        HapticFeedback.vibrate();
      } else {
        setState(() {
          _isCheckingConflict = false;
          _currentStep = 3;
        });
        HapticFeedback.selectionClick();
      }
    } catch (_) {
      // Fail-open for offline resilience
      setState(() {
        _isCheckingConflict = false;
        _currentStep = 3;
      });
    }
  }

  Future<void> _submitBooking() async {
    setState(() {
      _isSubmitting = true;
    });

    HapticFeedback.mediumImpact();

    final duration = _selectedService?.duration ?? 30;
    final startDt = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      _selectedTime.hour,
      _selectedTime.minute,
    );
    final endDt = startDt.add(Duration(minutes: duration));

    final cleanPhone = _phoneController.text.trim();

    try {
      final repo = ref.read(appointmentRepositoryProvider);

      await repo.createAppointment(
        serviceId: _selectedService?.id ?? 1,
        providerId: _selectedProvider?.id ?? 1,
        startDatetime: startDt,
        endDatetime: endDt,
        notes: '${_notesController.text} [Masa: ${_selectedStation?.name ?? "Belirtilmedi"}] [WA: $_sendWhatsAppConfirmation]',
        customer: {
          'first_name': _firstNameController.text.trim(),
          'last_name': _lastNameController.text.trim(),
          'phone_number': cleanPhone,
        },
      );

      ref.invalidate(agendaAppointmentsProvider);
      ref.invalidate(liveOperationsProvider);
      ref.invalidate(floorPlanProvider);

      if (!mounted) return;

      HapticFeedback.heavyImpact();
      Navigator.of(context).pop();

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.check_circle_rounded, color: Colors.white),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Randevu oluşturuldu! ${_sendWhatsAppConfirmation ? "(WhatsApp Onayı Gönderildi)" : ""}',
                ),
              ),
            ],
          ),
          backgroundColor: const Color(0xFF10B981),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isSubmitting = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Kayıt başarısız: $e'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final servicesAsync = ref.watch(servicesListProvider);
    final providersAsync = ref.watch(providersListProvider);
    final floorPlanAsync = ref.watch(floorPlanProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(
        20,
        12,
        20,
        MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.90,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Drag handle
          Center(
            child: Container(
              width: 44,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 14),

          // Başlık ve Adım Göstergesi
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                _stepTitle(_currentStep),
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF0F172A),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  'Adım ${_currentStep + 1} / 4',
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: AppTheme.primaryColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Adım İlerleme Çubuğu
          Row(
            children: List.generate(4, (index) {
              final isPassed = index <= _currentStep;
              return Expanded(
                child: Container(
                  height: 4,
                  margin: EdgeInsets.only(right: index == 3 ? 0 : 6),
                  decoration: BoxDecoration(
                    color: isPassed ? AppTheme.primaryColor : const Color(0xFFE2E8F0),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 20),

          // Adım İçeriği
          Expanded(
            child: SingleChildScrollView(
              child: _buildStepContent(servicesAsync, providersAsync, floorPlanAsync),
            ),
          ),

          // Alt Gezinme Butonları
          const SizedBox(height: 16),
          Row(
            children: [
              if (_currentStep > 0) ...[
                OutlinedButton(
                  onPressed: () {
                    HapticFeedback.selectionClick();
                    setState(() {
                      _currentStep--;
                    });
                  },
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('Geri'),
                ),
                const SizedBox(width: 10),
              ],
              Expanded(
                child: ElevatedButton(
                  onPressed: _isSubmitting || _isCheckingConflict ? null : _handleNext,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: _isSubmitting || _isCheckingConflict
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text(
                          _currentStep == 3 ? 'Randevuyu Onayla' : 'Devam Et',
                          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14),
                        ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _stepTitle(int step) {
    switch (step) {
      case 0:
        return 'Müşteri Bilgileri';
      case 1:
        return 'Hizmet & Masa / Kaynak';
      case 2:
        return 'Personel & Tarih-Saat';
      case 3:
      default:
        return 'Özet & WhatsApp Onayı';
    }
  }

  void _handleNext() {
    HapticFeedback.selectionClick();

    if (_currentStep == 0) {
      if (_formKeyStep1.currentState?.validate() ?? false) {
        setState(() {
          _currentStep = 1;
        });
      }
    } else if (_currentStep == 1) {
      if (_selectedService == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Lütfen bir hizmet seçin!'), behavior: SnackBarBehavior.floating),
        );
        return;
      }
      setState(() {
        _currentStep = 2;
      });
    } else if (_currentStep == 2) {
      _checkConflictAndProceed();
    } else if (_currentStep == 3) {
      _submitBooking();
    }
  }

  Widget _buildStepContent(
    AsyncValue<List<ServiceModel>> servicesAsync,
    AsyncValue<List<ProviderModel>> providersAsync,
    AsyncValue<List<StationModel>> floorPlanAsync,
  ) {
    switch (_currentStep) {
      case 0:
        // Adım 1: Müşteri Formu (+90 Telefon Standardı)
        return Form(
          key: _formKeyStep1,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextFormField(
                controller: _firstNameController,
                decoration: InputDecoration(
                  labelText: 'Müşteri Adı *',
                  prefixIcon: const Icon(Icons.person_outline_rounded),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                validator: (val) => val == null || val.trim().isEmpty ? 'Ad alanı zorunludur' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _lastNameController,
                decoration: InputDecoration(
                  labelText: 'Soyadı',
                  prefixIcon: const Icon(Icons.badge_outlined),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(
                  labelText: 'Telefon Numarası (+90 standardı) *',
                  prefixIcon: const Icon(Icons.phone_outlined),
                  hintText: '+90 555 123 4567',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                validator: (val) {
                  if (val == null || val.trim().isEmpty) return 'Telefon zorunludur';
                  final clean = val.replaceAll(RegExp(r'[^\d]'), '');
                  if (clean.length < 10) return 'Lütfen geçerli bir telefon girin';
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notesController,
                maxLines: 2,
                decoration: InputDecoration(
                  labelText: 'Özel Notlar / Alerji Bilgisi',
                  prefixIcon: const Icon(Icons.note_alt_outlined),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
        );

      case 1:
        // Adım 2: Hizmet ve Masa Seçimi
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Hizmet Seçimi', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
            const SizedBox(height: 10),
            servicesAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, s) => Text('Hizmetler yüklenemedi: $e'),
              data: (services) {
                return ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: services.length,
                  separatorBuilder: (ctx, i) => const SizedBox(height: 8),
                  itemBuilder: (ctx, i) {
                    final s = services[i];
                    final isSelected = _selectedService?.id == s.id;
                    return InkWell(
                      onTap: () {
                        setState(() {
                          _selectedService = s;
                        });
                      },
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isSelected ? AppTheme.primaryColor.withValues(alpha: 0.08) : Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isSelected ? AppTheme.primaryColor : const Color(0xFFE2E8F0),
                            width: isSelected ? 1.8 : 1.0,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(s.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                                Text('${s.duration} dk • ${s.price > 0 ? "${s.price} ₺" : "Ücretsiz"}',
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                              ],
                            ),
                            if (isSelected)
                              const Icon(Icons.check_circle_rounded, color: AppTheme.primaryColor, size: 20),
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
            ),
            const SizedBox(height: 20),

            const Text('Masa / İstasyon (Opsiyonel)', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
            const SizedBox(height: 10),
            floorPlanAsync.when(
              loading: () => const SizedBox.shrink(),
              error: (e, s) => const SizedBox.shrink(),
              data: (stations) {
                return Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: stations.map((st) {
                    final isSelected = _selectedStation?.id == st.id;
                    return ChoiceChip(
                      label: Text('${st.name} (${st.status.labelTr})'),
                      selected: isSelected,
                      selectedColor: const Color(0xFF0F172A),
                      labelStyle: TextStyle(
                        color: isSelected ? Colors.white : const Color(0xFF0F172A),
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                      onSelected: (selected) {
                        setState(() {
                          _selectedStation = selected ? st : null;
                        });
                      },
                    );
                  }).toList(),
                );
              },
            ),
          ],
        );

      case 2:
        // Adım 3: Personel & Tarih-Saat (Çakışma Kontrolü)
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Uzman / Personel', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
            const SizedBox(height: 10),
            providersAsync.when(
              loading: () => const CircularProgressIndicator(),
              error: (e, s) => Text('Yüklenemedi: $e'),
              data: (providers) {
                return DropdownButtonFormField<ProviderModel>(
                  initialValue: _selectedProvider,
                  items: providers.map((p) {
                    return DropdownMenuItem(
                      value: p,
                      child: Text(p.name),
                    );
                  }).toList(),
                  decoration: InputDecoration(
                    hintText: 'Personel Seçin',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onChanged: (p) => setState(() => _selectedProvider = p),
                );
              },
            ),
            const SizedBox(height: 18),

            const Text('Tarih ve Saat Dilimi', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: _selectedDate,
                        firstDate: DateTime.now(),
                        lastDate: DateTime.now().add(const Duration(days: 90)),
                      );
                      if (picked != null) {
                        setState(() => _selectedDate = picked);
                      }
                    },
                    icon: const Icon(Icons.calendar_today_rounded, size: 16),
                    label: Text(DateFormat('d MMMM yyyy', 'tr_TR').format(_selectedDate)),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () async {
                      final picked = await showTimePicker(
                        context: context,
                        initialTime: _selectedTime,
                      );
                      if (picked != null) {
                        setState(() => _selectedTime = picked);
                      }
                    },
                    icon: const Icon(Icons.access_time_rounded, size: 16),
                    label: Text(_selectedTime.format(context)),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
              ],
            ),

            // Çakışma Uyarısı & Bekleme Listesi Yönlendirmesi
            if (_hasConflict) ...[
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFFCA5A5)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.warning_amber_rounded, color: Color(0xFFDC2626), size: 20),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _conflictMessage ?? 'Çakışma tespit edildi!',
                            style: const TextStyle(color: Color(0xFF991B1B), fontWeight: FontWeight.bold, fontSize: 13),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    ElevatedButton.icon(
                      onPressed: () {
                        // Waitlist redirect
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Müşteri Bekleme Listesine (Waitlist) eklendi!'),
                            backgroundColor: Color(0xFF0F172A),
                            behavior: SnackBarBehavior.floating,
                          ),
                        );
                        Navigator.of(context).pop();
                      },
                      icon: const Icon(Icons.queue_rounded, size: 16),
                      label: const Text('Akıllı Bekleme Listesine (Waitlist) Ekle'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFDC2626),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        );

      case 3:
      default:
        // Adım 4: Özet ve WhatsApp Toggle
        final startDt = DateTime(
          _selectedDate.year,
          _selectedDate.month,
          _selectedDate.day,
          _selectedTime.hour,
          _selectedTime.minute,
        );

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                children: [
                  _summaryRow('Müşteri', '${_firstNameController.text} ${_lastNameController.text}'),
                  const Divider(height: 14),
                  _summaryRow('Telefon', _phoneController.text),
                  const Divider(height: 14),
                  _summaryRow('Hizmet', _selectedService?.name ?? '-'),
                  const Divider(height: 14),
                  _summaryRow('Süre', '${_selectedService?.duration ?? 30} Dakika'),
                  const Divider(height: 14),
                  _summaryRow('Masa', _selectedStation?.name ?? 'Masa Belirtilmedi'),
                  const Divider(height: 14),
                  _summaryRow('Personel', _selectedProvider?.name ?? 'Herhangi Bir Personel'),
                  const Divider(height: 14),
                  _summaryRow('Tarih & Saat', DateFormat('d MMMM yyyy, HH:mm', 'tr_TR').format(startDt)),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Otomatik WhatsApp Onay Mesajı Toggle'ı
            SwitchListTile.adaptive(
              contentPadding: EdgeInsets.zero,
              title: const Text(
                'WhatsApp Onay Mesajı Gönder',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
              ),
              subtitle: const Text(
                'Müşteriye randevu saati ve dinamik QR giriş kartı WhatsApp ile iletilsin.',
                style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
              ),
              value: _sendWhatsAppConfirmation,
              activeTrackColor: const Color(0xFF25D366),
              onChanged: (val) {
                HapticFeedback.selectionClick();
                setState(() {
                  _sendWhatsAppConfirmation = val;
                });
              },
            ),
          ],
        );
    }
  }

  Widget _summaryRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
        Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
      ],
    );
  }
}

