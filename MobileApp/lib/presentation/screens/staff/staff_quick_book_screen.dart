import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/service_model.dart';
import '../../../data/models/provider_model.dart';
import '../../../data/models/station_model.dart';
import '../../../providers/booking_provider.dart';
import '../../../providers/appointments_provider.dart';

class StaffQuickBookScreen extends ConsumerStatefulWidget {
  const StaffQuickBookScreen({super.key});

  @override
  ConsumerState<StaffQuickBookScreen> createState() =>
      _StaffQuickBookScreenState();
}

class _StaffQuickBookScreenState extends ConsumerState<StaffQuickBookScreen> {
  final _formKey = GlobalKey<FormState>();
  final _customerNameController = TextEditingController();
  final _customerPhoneController = TextEditingController();
  final _notesController = TextEditingController();

  ServiceModel? _selectedService;
  StationModel? _selectedStation;
  ProviderModel? _selectedProvider;
  DateTime _selectedDate = DateTime.now();
  String? _selectedSlot;
  List<String> _slots = [];
  bool _isLoadingSlots = false;
  bool _isSaving = false;

  @override
  void dispose() {
    _customerNameController.dispose();
    _customerPhoneController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _fetchSlots() async {
    if (_selectedService == null || _selectedProvider == null) return;

    setState(() {
      _isLoadingSlots = true;
    });

    try {
      final repo = ref.read(bookingRepositoryProvider);
      final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
      final slots = await repo.getAvailableHours(
        providerId: _selectedProvider!.id,
        serviceId: _selectedService!.id,
        date: dateStr,
        stationId: _selectedStation?.id,
      );

      setState(() {
        _slots = slots;
        _selectedSlot = slots.isNotEmpty ? slots.first : null;
        _isLoadingSlots = false;
      });
    } catch (e) {
      setState(() {
        _slots = [];
        _selectedSlot = null;
        _isLoadingSlots = false;
      });
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedService == null ||
        _selectedProvider == null ||
        _selectedSlot == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lütfen hizmet, uzman ve saat seçin.')),
      );
      return;
    }

    setState(() {
      _isSaving = true;
    });

    try {
      final repo = ref.read(bookingRepositoryProvider);
      final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
      final startDateTime = '$dateStr $_selectedSlot:00';

      final notes =
          'Müşteri: ${_customerNameController.text.trim()} | Tel: ${_customerPhoneController.text.trim()} | Not: ${_notesController.text.trim()}';

      await repo.bookAppointment(
        serviceId: _selectedService!.id,
        providerId: _selectedProvider!.id,
        customerId: 1, // Default walk-in customer
        startDateTime: startDateTime,
        stationId: _selectedStation?.id,
        notes: notes,
      );

      ref.invalidate(todayAppointmentsProvider);
      ref.invalidate(appointmentsListProvider);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Hızlı randevu başarıyla eklendi!'),
            backgroundColor: AppTheme.accentSuccess,
          ),
        );
        _customerNameController.clear();
        _customerPhoneController.clear();
        _notesController.clear();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Hata: $e')));
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSaving = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final servicesAsync = ref.watch(servicesListProvider);
    final providersAsync = ref.watch(providersListProvider);
    final stationsAsync = ref.watch(stationsListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Hızlı Randevu Girişi (Walk-in)')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Yeni Randevu / Kapı Müşterisi',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: 16),

                // Müşteri Adı
                TextFormField(
                  controller: _customerNameController,
                  decoration: const InputDecoration(
                    labelText: 'Müşteri Adı Soyadı',
                    prefixIcon: Icon(Icons.person_outline_rounded),
                  ),
                  validator: (v) => v == null || v.trim().isEmpty
                      ? 'Müşteri adı gerekli'
                      : null,
                ),
                const SizedBox(height: 14),

                // Müşteri Telefonu
                TextFormField(
                  controller: _customerPhoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Telefon Numarası',
                    hintText: '05XX XXX XX XX',
                    prefixIcon: Icon(Icons.phone_outlined),
                  ),
                ),
                const SizedBox(height: 14),

                // Hizmet Seçimi
                servicesAsync.when(
                  data: (services) {
                    return DropdownButtonFormField<ServiceModel>(
                      initialValue: _selectedService,
                      decoration: const InputDecoration(
                        labelText: 'Hizmet Seçin',
                        prefixIcon: Icon(Icons.spa_outlined),
                      ),
                      items: services.map((s) {
                        return DropdownMenuItem(
                          value: s,
                          child: Text('${s.name} (${s.formattedPrice})'),
                        );
                      }).toList(),
                      onChanged: (val) {
                        setState(() {
                          _selectedService = val;
                        });
                        _fetchSlots();
                      },
                      validator: (v) => v == null ? 'Hizmet seçilmeli' : null,
                    );
                  },
                  loading: () => const LinearProgressIndicator(),
                  error: (e, s) => const Text('Hizmetler yüklenemedi'),
                ),
                const SizedBox(height: 14),

                // İstasyon / Oda / Masa / Kort / Cihaz Seçimi
                stationsAsync.when(
                  data: (stations) {
                    if (stations.isEmpty) return const SizedBox.shrink();
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        DropdownButtonFormField<StationModel?>(
                          initialValue: _selectedStation,
                          decoration: const InputDecoration(
                            labelText:
                                'Oda / Masa / Kort / Cihaz (İsteğe Bağlı)',
                            prefixIcon: Icon(Icons.meeting_room_outlined),
                          ),
                          items: [
                            const DropdownMenuItem<StationModel?>(
                              value: null,
                              child: Text('Otomatik Atama (Fark Etmez)'),
                            ),
                            ...stations.map((st) {
                              return DropdownMenuItem<StationModel?>(
                                value: st,
                                child: Text(
                                  '${st.name} (${st.status.labelTr} - ${st.capacity} Kişi)',
                                ),
                              );
                            }),
                          ],
                          onChanged: (val) {
                            setState(() {
                              _selectedStation = val;
                            });
                            _fetchSlots();
                          },
                        ),
                        const SizedBox(height: 14),
                      ],
                    );
                  },
                  loading: () => const SizedBox.shrink(),
                  error: (_, _) => const SizedBox.shrink(),
                ),

                // Uzman Seçimi
                providersAsync.when(
                  data: (providers) {
                    return DropdownButtonFormField<ProviderModel>(
                      initialValue: _selectedProvider,
                      decoration: const InputDecoration(
                        labelText: 'Personel / Uzman Seçin',
                        prefixIcon: Icon(Icons.badge_outlined),
                      ),
                      items: providers.map((p) {
                        return DropdownMenuItem(
                          value: p,
                          child: Text(p.fullName),
                        );
                      }).toList(),
                      onChanged: (val) {
                        setState(() {
                          _selectedProvider = val;
                        });
                        _fetchSlots();
                      },
                      validator: (v) => v == null ? 'Personel seçilmeli' : null,
                    );
                  },
                  loading: () => const LinearProgressIndicator(),
                  error: (e, s) => const Text('Personel yüklenemedi'),
                ),
                const SizedBox(height: 14),

                // Tarih Seçimi
                InkWell(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _selectedDate,
                      firstDate: DateTime.now(),
                      lastDate: DateTime.now().add(const Duration(days: 90)),
                    );
                    if (picked != null) {
                      setState(() {
                        _selectedDate = picked;
                      });
                      _fetchSlots();
                    }
                  },
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppTheme.borderLight),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            const Icon(
                              Icons.calendar_month_outlined,
                              color: AppTheme.primaryColor,
                            ),
                            const SizedBox(width: 12),
                            Text(
                              DateFormat(
                                'd MMMM yyyy, EEEE',
                                'tr_TR',
                              ).format(_selectedDate),
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 14,
                              ),
                            ),
                          ],
                        ),
                        const Icon(Icons.arrow_drop_down_rounded),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Saat Dilimleri
                const Text(
                  'Randevu Saati',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: 8),

                if (_isLoadingSlots)
                  const Center(child: CircularProgressIndicator())
                else if (_slots.isEmpty)
                  const Text(
                    'Seçilen tarihte müsait saat bulunamadı.',
                    style: TextStyle(
                      color: AppTheme.textSecondaryLight,
                      fontSize: 13,
                    ),
                  )
                else
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _slots.map((slot) {
                      final isSelected = _selectedSlot == slot;
                      return ChoiceChip(
                        label: Text(slot),
                        selected: isSelected,
                        selectedColor: AppTheme.primaryColor,
                        labelStyle: TextStyle(
                          color: isSelected
                              ? Colors.white
                              : AppTheme.textPrimaryLight,
                          fontWeight: FontWeight.w700,
                        ),
                        onSelected: (_) {
                          setState(() {
                            _selectedSlot = slot;
                          });
                        },
                      );
                    }).toList(),
                  ),

                const SizedBox(height: 16),

                // Notlar
                TextFormField(
                  controller: _notesController,
                  decoration: const InputDecoration(
                    labelText: 'Randevu Notu / Açıklama',
                    hintText: 'Özel işlem detayı...',
                  ),
                  maxLines: 2,
                ),
                const SizedBox(height: 24),

                ElevatedButton(
                  onPressed: _isSaving ? null : _submit,
                  child: _isSaving
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Randevuyu Kaydet'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
