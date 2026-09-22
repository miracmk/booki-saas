import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/booking_provider.dart';
import '../../../providers/operations_provider.dart';
import '../auth/tenant_selection_screen.dart';

class StaffProfileScreen extends ConsumerStatefulWidget {
  const StaffProfileScreen({super.key});

  @override
  ConsumerState<StaffProfileScreen> createState() => _StaffProfileScreenState();
}

class _StaffProfileScreenState extends ConsumerState<StaffProfileScreen> {
  // Operational Settings State
  int _slotInterval = 30; // 15, 30, 45, 60 dk
  TimeOfDay _weekdayStart = const TimeOfDay(hour: 9, minute: 0);
  TimeOfDay _weekdayEnd = const TimeOfDay(hour: 19, minute: 0);
  TimeOfDay _weekendStart = const TimeOfDay(hour: 10, minute: 0);
  TimeOfDay _weekendEnd = const TimeOfDay(hour: 18, minute: 0);
  bool _whatsappEnabled = true;
  int _reminderHoursBefore = 2; // 2 veya 24 saat

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final user = authState.user;
    final tenant = authState.currentTenant;
    final providersAsync = ref.watch(providersListProvider);
    final floorPlanAsync = ref.watch(floorPlanProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Ayarlar & Yönetim Paneli', style: TextStyle(fontWeight: FontWeight.w800)),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // 1. Paket & Abonelik Durumu (%0 Komisyon Vurgusu)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.15),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFF10B981).withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.4)),
                          ),
                          child: const Text(
                            'ENTERPRISE PAKET',
                            style: TextStyle(
                              color: Color(0xFF34D399),
                              fontSize: 11,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 1.0,
                            ),
                          ),
                        ),
                        const Row(
                          children: [
                            Icon(Icons.verified_rounded, color: Color(0xFF38BDF8), size: 16),
                            SizedBox(width: 4),
                            Text(
                              'Aktif Abonelik',
                              style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontWeight: FontWeight.w600),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      '%0 Komisyon ile Sınırsız Büyüme',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Tüm randevu ve masa rezervasyonlarınızda sıfır aracı komisyonu ile doğrudan kazanın.',
                      style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, height: 1.4),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              // 2. Personel & Şube Bilgisi
              Card(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: AppTheme.borderLight)),
                elevation: 0,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 28,
                        backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.12),
                        child: Text(
                          user?.firstName.isNotEmpty == true ? user!.firstName[0].toUpperCase() : 'Y',
                          style: const TextStyle(color: AppTheme.primaryColor, fontWeight: FontWeight.w800, fontSize: 20),
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?.fullName ?? 'İşletme Yöneticisi',
                              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: Color(0xFF0F172A)),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              tenant?.companyName ?? 'BooKi Salon & Spa',
                              style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              user?.email ?? '',
                              style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // 3. Çalışma Saatleri & Randevu Aralık Periyodu
              _sectionTitle('İşletme Profili & Çalışma Saatleri'),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppTheme.borderLight),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Hafta içi saatleri
                    _timeSettingRow(
                      label: 'Hafta İçi Mesai',
                      timeRange: '${_formatTime(_weekdayStart)} - ${_formatTime(_weekdayEnd)}',
                      onTap: () async {
                        final start = await showTimePicker(context: context, initialTime: _weekdayStart);
                        if (start != null && context.mounted) {
                          final end = await showTimePicker(context: context, initialTime: _weekdayEnd);
                          if (end != null) {
                            setState(() {
                              _weekdayStart = start;
                              _weekdayEnd = end;
                            });
                          }
                        }
                      },
                    ),
                    const Divider(height: 20),
                    // Hafta sonu saatleri
                    _timeSettingRow(
                      label: 'Hafta Sonu Mesai',
                      timeRange: '${_formatTime(_weekendStart)} - ${_formatTime(_weekendEnd)}',
                      onTap: () async {
                        final start = await showTimePicker(context: context, initialTime: _weekendStart);
                        if (start != null && context.mounted) {
                          final end = await showTimePicker(context: context, initialTime: _weekendEnd);
                          if (end != null) {
                            setState(() {
                              _weekendStart = start;
                              _weekendEnd = end;
                            });
                          }
                        }
                      },
                    ),
                    const Divider(height: 20),
                    // Randevu Aralık Periyodu (15/30/45/60 dk)
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Randevu Slot Aralığı', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                            Text('Takvim saat dilimi aralığı', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                          ],
                        ),
                        Wrap(
                          spacing: 6,
                          children: [15, 30, 45, 60].map((period) {
                            final isSelected = _slotInterval == period;
                            return ChoiceChip(
                              label: Text('$period dk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: isSelected ? Colors.white : const Color(0xFF0F172A))),
                              selected: isSelected,
                              selectedColor: const Color(0xFF0F172A),
                              onSelected: (val) {
                                HapticFeedback.selectionClick();
                                setState(() => _slotInterval = period);
                              },
                            );
                          }).toList(),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // 4. Kapasite & Kaynak Yapılandırması (Masa & Cihaz Tanımları)
              _sectionTitle('Kapasite & Kaynak Yapılandırması'),
              const SizedBox(height: 10),
              floorPlanAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, s) => Text('Kapasite bilgisi yüklenemedi: $e'),
                data: (stations) {
                  return Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppTheme.borderLight),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('${stations.length} Aktif İstasyon / Masa Tanımlı', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
                                const Text('Restoran masa & salon cihaz kapasitesi', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                              ],
                            ),
                            ElevatedButton.icon(
                              onPressed: () {
                                HapticFeedback.lightImpact();
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Yeni masa / cihaz ekleme modülü açıldı.'), behavior: SnackBarBehavior.floating),
                                );
                              },
                              icon: const Icon(Icons.add_rounded, size: 14),
                              label: const Text('Masa Ekle', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF0F172A),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: stations.map((st) {
                            return Chip(
                              avatar: const Icon(Icons.table_restaurant_rounded, size: 14, color: Color(0xFF64748B)),
                              label: Text('${st.name} (${st.capacity} Kişilik)', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                              backgroundColor: const Color(0xFFF8FAFC),
                              side: const BorderSide(color: Color(0xFFE2E8F0)),
                            );
                          }).toList(),
                        ),
                      ],
                    ),
                  );
                },
              ),
              const SizedBox(height: 20),

              // 5. Ekip & İzin Yönetimi
              _sectionTitle('Ekip & İzin Yönetimi'),
              const SizedBox(height: 10),
              providersAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, s) => Text('Ekip yüklenemedi: $e'),
                data: (providers) {
                  return Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppTheme.borderLight),
                    ),
                    child: ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: providers.length,
                      separatorBuilder: (ctx, i) => const Divider(height: 1),
                      itemBuilder: (ctx, i) {
                        final p = providers[i];
                        return ListTile(
                          leading: CircleAvatar(
                            radius: 16,
                            backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                            child: Text(
                              p.name.isNotEmpty ? p.name[0].toUpperCase() : 'U',
                              style: const TextStyle(color: AppTheme.primaryColor, fontWeight: FontWeight.bold, fontSize: 12),
                            ),
                          ),
                          title: Text(p.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                          subtitle: Text(p.email, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                          trailing: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: const Color(0xFF10B981).withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text('Aktif Görevde', style: TextStyle(color: Color(0xFF10B981), fontSize: 10, fontWeight: FontWeight.bold)),
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
              const SizedBox(height: 20),

              // 6. Bildirim & WhatsApp Entegrasyon Ayarları
              _sectionTitle('Bildirim & WhatsApp Entegrasyonu'),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppTheme.borderLight),
                ),
                child: Column(
                  children: [
                    SwitchListTile.adaptive(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Otomatik WhatsApp Bildirimleri', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                      subtitle: const Text('Rezervasyon onay ve QR giriş kartı gönderimi', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                      value: _whatsappEnabled,
                      activeTrackColor: const Color(0xFF25D366),
                      onChanged: (val) {
                        HapticFeedback.selectionClick();
                        setState(() => _whatsappEnabled = val);
                      },
                    ),
                    const Divider(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Otomatik Hatırlatma Zamanı', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                        DropdownButton<int>(
                          value: _reminderHoursBefore,
                          underline: const SizedBox.shrink(),
                          items: const [
                            DropdownMenuItem(value: 2, child: Text('2 Saat Önce', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold))),
                            DropdownMenuItem(value: 24, child: Text('24 Saat Önce', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold))),
                          ],
                          onChanged: (val) {
                            if (val != null) setState(() => _reminderHoursBefore = val);
                          },
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Şube Değiştir & Çıkış Yap Butonları
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    HapticFeedback.lightImpact();
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const TenantSelectionScreen()),
                    );
                  },
                  icon: const Icon(Icons.swap_horiz_rounded),
                  label: const Text('İşletme / Şube Değiştir', style: TextStyle(fontWeight: FontWeight.w700)),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () async {
                    HapticFeedback.mediumImpact();
                    await ref.read(authProvider.notifier).logout();
                  },
                  icon: const Icon(Icons.logout_rounded, size: 18),
                  label: const Text('Güvenli Çıkış Yap', style: TextStyle(fontWeight: FontWeight.w700)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red.shade50,
                    foregroundColor: Colors.red.shade700,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  Widget _sectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14, color: Color(0xFF0F172A)),
    );
  }

  Widget _timeSettingRow({
    required String label,
    required String timeRange,
    required VoidCallback onTap,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
        InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(8),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xFFCBD5E1)),
            ),
            child: Row(
              children: [
                const Icon(Icons.access_time_rounded, size: 14, color: Color(0xFF0F172A)),
                const SizedBox(width: 6),
                Text(timeRange, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12, color: Color(0xFF0F172A))),
              ],
            ),
          ),
        ),
      ],
    );
  }

  String _formatTime(TimeOfDay tod) {
    return '${tod.hour.toString().padLeft(2, '0')}:${tod.minute.toString().padLeft(2, '0')}';
  }
}
