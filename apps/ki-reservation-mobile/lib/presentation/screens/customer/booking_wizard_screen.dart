import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/station_model.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/booking_provider.dart';
import '../../../providers/appointments_provider.dart';

class BookingWizardScreen extends ConsumerWidget {
  const BookingWizardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(bookingWizardProvider);
    final notifier = ref.read(bookingWizardProvider.notifier);
    final authState = ref.watch(authProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Randevu Al'),
        actions: [
          if (state.currentStep > 0 && state.currentStep < 5)
            TextButton(
              onPressed: () => notifier.reset(),
              child: const Text('Sıfırla'),
            ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // İlerleme Göstergesi (Step Indicator)
            if (state.currentStep < 5)
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  children: [
                    _stepIndicator(0, 'Hizmet', state.currentStep >= 0),
                    _stepDivider(state.currentStep >= 1),
                    _stepIndicator(1, 'Mekan/Oda', state.currentStep >= 1),
                    _stepDivider(state.currentStep >= 2),
                    _stepIndicator(2, 'Uzman', state.currentStep >= 2),
                    _stepDivider(state.currentStep >= 3),
                    _stepIndicator(3, 'Tarih', state.currentStep >= 3),
                    _stepDivider(state.currentStep >= 4),
                    _stepIndicator(4, 'Onay', state.currentStep >= 4),
                  ],
                ),
              ),

            // Hata Bildirimi
            if (state.errorMessage != null)
              Container(
                margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.accentDanger.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline,
                        color: AppTheme.accentDanger, size: 18),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        state.errorMessage!,
                        style: const TextStyle(
                            color: AppTheme.accentDanger, fontSize: 13),
                      ),
                    ),
                  ],
                ),
              ),

            // Adım İçerikleri
            Expanded(
              child: _buildCurrentStepView(context, ref, state, notifier, authState),
            ),
          ],
        ),
      ),
    );
  }

  Widget _stepIndicator(int step, String label, bool isActive) {
    return Expanded(
      child: Column(
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isActive ? AppTheme.primaryColor : Colors.grey.shade200,
            ),
            child: Center(
              child: Text(
                '${step + 1}',
                style: TextStyle(
                  color: isActive ? Colors.white : AppTheme.textSecondaryLight,
                  fontWeight: FontWeight.w700,
                  fontSize: 12,
                ),
              ),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
              color: isActive ? AppTheme.primaryColor : AppTheme.textSecondaryLight,
            ),
          ),
        ],
      ),
    );
  }

  Widget _stepDivider(bool isActive) {
    return Container(
      width: 20,
      height: 2,
      margin: const EdgeInsets.only(bottom: 16),
      color: isActive ? AppTheme.primaryColor : Colors.grey.shade300,
    );
  }

  Widget _buildCurrentStepView(
    BuildContext context,
    WidgetRef ref,
    BookingWizardState state,
    BookingWizardNotifier notifier,
    AuthState authState,
  ) {
    switch (state.currentStep) {
      case 0:
        return _buildServiceSelection(context, ref, notifier);
      case 1:
        return _buildStationSelection(context, ref, state, notifier);
      case 2:
        return _buildProviderSelection(context, ref, state, notifier);
      case 3:
        return _buildDateTimeSelection(context, state, notifier);
      case 4:
        return _buildConfirmationStep(context, ref, state, notifier, authState);
      case 5:
        return _buildSuccessStep(context, ref, state, notifier);
      default:
        return const SizedBox.shrink();
    }
  }

  // STEP 0: Hizmet Seçimi
  Widget _buildServiceSelection(
    BuildContext context,
    WidgetRef ref,
    BookingWizardNotifier notifier,
  ) {
    final servicesAsync = ref.watch(servicesListProvider);

    return servicesAsync.when(
      data: (services) {
        if (services.isEmpty) {
          return const Center(child: Text('Kayıtlı hizmet bulunamadı.'));
        }

        return ListView.builder(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          itemCount: services.length,
          itemBuilder: (context, index) {
            final service = services[index];
            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: ListTile(
                contentPadding: const EdgeInsets.all(16),
                leading: Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(Icons.spa_rounded,
                      color: AppTheme.primaryColor, size: 26),
                ),
                title: Text(
                  service.name,
                  style: const TextStyle(
                      fontWeight: FontWeight.w700, fontSize: 16),
                ),
                subtitle: Padding(
                  padding: const EdgeInsets.only(top: 6),
                  child: Row(
                    children: [
                      const Icon(Icons.access_time_rounded,
                          size: 14, color: AppTheme.textSecondaryLight),
                      const SizedBox(width: 4),
                      Text(service.formattedDuration,
                          style: const TextStyle(fontSize: 13)),
                      const SizedBox(width: 12),
                      const Icon(Icons.payments_outlined,
                          size: 14, color: AppTheme.textSecondaryLight),
                      const SizedBox(width: 4),
                      Text(service.formattedPrice,
                          style: const TextStyle(
                              fontSize: 13, fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
                trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
                onTap: () => notifier.selectService(service),
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(child: Text('Hizmetler yüklenemedi: $e')),
    );
  }

  // STEP 1: İstasyon / Mekan / Oda / Masa / Kort / Cihaz Seçimi
  Widget _buildStationSelection(
    BuildContext context,
    WidgetRef ref,
    BookingWizardState state,
    BookingWizardNotifier notifier,
  ) {
    final stationsAsync = ref.watch(stationsListProvider);

    return stationsAsync.when(
      data: (stations) {
        return ListView(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          children: [
            const Padding(
              padding: EdgeInsets.only(bottom: 12),
              child: Text(
                'Oda / Masa / Kort / İstasyon / Cihaz',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.textPrimaryLight,
                ),
              ),
            ),
            // Seçenek: Otomatik Atama / Fark Etmez
            Card(
              margin: const EdgeInsets.only(bottom: 12),
              elevation: state.selectedStation == null ? 2 : 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
                side: BorderSide(
                  color: state.selectedStation == null
                      ? AppTheme.primaryColor
                      : Colors.grey.shade300,
                  width: state.selectedStation == null ? 2 : 1,
                ),
              ),
              child: ListTile(
                contentPadding: const EdgeInsets.all(16),
                leading: Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.auto_awesome_rounded,
                      color: AppTheme.primaryColor, size: 24),
                ),
                title: const Text(
                  'Fark Etmez (Otomatik Atansın)',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                ),
                subtitle: const Text(
                  'Sistem en uygun müsait oda/istasyon/masayı otomatik belirlesin.',
                  style: TextStyle(fontSize: 12, color: AppTheme.textSecondaryLight),
                ),
                trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
                onTap: () => notifier.selectStation(null),
              ),
            ),
            if (stations.isNotEmpty) ...[
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  'Belirli Bir Alan Seçin:',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.textSecondaryLight,
                  ),
                ),
              ),
              ...stations.map((st) {
                final isSelected = state.selectedStation?.id == st.id;
                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  elevation: isSelected ? 2 : 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                    side: BorderSide(
                      color: isSelected ? AppTheme.primaryColor : Colors.grey.shade200,
                      width: isSelected ? 2 : 1,
                    ),
                  ),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    leading: Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: st.status.color.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(Icons.meeting_room_outlined, color: st.status.color, size: 22),
                    ),
                    title: Text(
                      st.name,
                      style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                    ),
                    subtitle: Row(
                      children: [
                        Text('Kapasite: ${st.capacity} Kişi',
                            style: const TextStyle(fontSize: 12, color: AppTheme.textSecondaryLight)),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: st.status.color.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            st.status.labelTr,
                            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: st.status.color),
                          ),
                        ),
                      ],
                    ),
                    trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
                    onTap: () => notifier.selectStation(st),
                  ),
                );
              }),
            ],
          ],
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('İstasyonlar yüklenemedi: $e'),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: () => notifier.selectStation(null),
              child: const Text('Otomatik Atama ile Devam Et'),
            ),
          ],
        ),
      ),
    );
  }

  // STEP 2: Uzman Seçimi
  Widget _buildProviderSelection(
    BuildContext context,
    WidgetRef ref,
    BookingWizardState state,
    BookingWizardNotifier notifier,
  ) {
    final providersAsync = ref.watch(providersListProvider);

    return providersAsync.when(
      data: (providers) {
        // Seçilen hizmeti verebilen uzmanlar (veya hepsi)
        final matching = providers.where((p) {
          if (state.selectedService == null || p.serviceIds.isEmpty) return true;
          return p.serviceIds.contains(state.selectedService!.id);
        }).toList();

        final displayList = matching.isNotEmpty ? matching : providers;

        return ListView.builder(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          itemCount: displayList.length,
          itemBuilder: (context, index) {
            final provider = displayList[index];
            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: ListTile(
                contentPadding: const EdgeInsets.all(16),
                leading: CircleAvatar(
                  radius: 24,
                  backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                  child: Text(
                    provider.firstName.isNotEmpty
                        ? provider.firstName[0].toUpperCase()
                        : 'U',
                    style: const TextStyle(
                      color: AppTheme.primaryColor,
                      fontWeight: FontWeight.w700,
                      fontSize: 18,
                    ),
                  ),
                ),
                title: Text(
                  provider.fullName,
                  style: const TextStyle(
                      fontWeight: FontWeight.w700, fontSize: 16),
                ),
                subtitle: Text(
                  provider.email.isNotEmpty ? provider.email : 'Uzman Personel',
                  style: const TextStyle(
                      fontSize: 13, color: AppTheme.textSecondaryLight),
                ),
                trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 16),
                onTap: () => notifier.selectProvider(provider),
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(child: Text('Uzmanlar yüklenemedi: $e')),
    );
  }

  // STEP 2: Tarih & Saat Dilimi Seçimi
  Widget _buildDateTimeSelection(
    BuildContext context,
    BookingWizardState state,
    BookingWizardNotifier notifier,
  ) {
    final now = DateTime.now();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Tarih Seçin',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: AppTheme.textPrimaryLight,
            ),
          ),
          const SizedBox(height: 12),

          // Yatay Gün Seçici (Sonraki 14 gün)
          SizedBox(
            height: 80,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: 14,
              itemBuilder: (context, index) {
                final date = now.add(Duration(days: index));
                final isSelected = DateFormat('yyyy-MM-dd').format(date) ==
                    DateFormat('yyyy-MM-dd').format(state.selectedDate);

                return GestureDetector(
                  onTap: () => notifier.selectDate(date),
                  child: Container(
                    width: 60,
                    margin: const EdgeInsets.only(right: 10),
                    decoration: BoxDecoration(
                      color: isSelected
                          ? AppTheme.primaryColor
                          : Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: isSelected
                            ? AppTheme.primaryColor
                            : AppTheme.borderLight,
                      ),
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          DateFormat('E', 'tr_TR').format(date),
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: isSelected
                                ? Colors.white70
                                : AppTheme.textSecondaryLight,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          DateFormat('d').format(date),
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: isSelected
                                ? Colors.white
                                : AppTheme.textPrimaryLight,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),

          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Müsait Randevu Saatleri',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.textPrimaryLight,
                ),
              ),
              if (state.isLoadingSlots)
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
            ],
          ),
          const SizedBox(height: 12),

          if (state.isLoadingSlots)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(32),
                child: CircularProgressIndicator(),
              ),
            )
          else if (state.availableSlots.isEmpty)
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppTheme.borderLight),
              ),
              child: const Center(
                child: Text(
                  'Bu tarihte müsait saat bulunamadı. Lütfen başka bir gün seçin.',
                  style: TextStyle(color: AppTheme.textSecondaryLight),
                ),
              ),
            )
          else
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: state.availableSlots.map((slot) {
                final isSelected = state.selectedSlot == slot;
                return ChoiceChip(
                  label: Text(slot),
                  selected: isSelected,
                  selectedColor: AppTheme.primaryColor,
                  labelStyle: TextStyle(
                    color: isSelected ? Colors.white : AppTheme.textPrimaryLight,
                    fontWeight: FontWeight.w700,
                  ),
                  onSelected: (_) => notifier.selectSlot(slot),
                );
              }).toList(),
            ),

          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: state.selectedSlot == null
                ? null
                : () => notifier.goToStep(4),
            style: ElevatedButton.styleFrom(
              minimumSize: const Size.fromHeight(50),
            ),
            child: const Text('Özete İlerle'),
          ),
        ],
      ),
    );
  }

  // STEP 4: Onay & Özet
  Widget _buildConfirmationStep(
    BuildContext context,
    WidgetRef ref,
    BookingWizardState state,
    BookingWizardNotifier notifier,
    AuthState authState,
  ) {
    final dateFormat = DateFormat('d MMMM yyyy, EEEE', 'tr_TR');
    final dateStr = dateFormat.format(state.selectedDate);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Randevu Özeti',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: AppTheme.textPrimaryLight,
            ),
          ),
          const SizedBox(height: 16),

          Card(
            child: Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                children: [
                  _summaryRow(
                    icon: Icons.spa_rounded,
                    label: 'Hizmet',
                    value: state.selectedService?.name ?? '',
                    subValue:
                        '${state.selectedService?.formattedDuration} • ${state.selectedService?.formattedPrice}',
                  ),
                  const Divider(height: 24),
                  _summaryRow(
                    icon: Icons.meeting_room_outlined,
                    label: 'Oda / Masa / Kort / Cihaz',
                    value: state.selectedStation?.name ?? 'Otomatik Atama (Fark Etmez)',
                  ),
                  const Divider(height: 24),
                  _summaryRow(
                    icon: Icons.person_rounded,
                    label: 'Uzman',
                    value: state.selectedProvider?.fullName ?? '',
                  ),
                  const Divider(height: 24),
                  _summaryRow(
                    icon: Icons.calendar_today_rounded,
                    label: 'Tarih & Saat',
                    value: '$dateStr - Saat: ${state.selectedSlot}',
                  ),
                  const Divider(height: 24),
                  _summaryRow(
                    icon: Icons.storefront_rounded,
                    label: 'İşletme',
                    value: authState.currentTenant?.displayName ?? '',
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Müşteri Notu
          TextField(
            decoration: const InputDecoration(
              labelText: 'Randevu Notu (İsteğe Bağlı)',
              hintText: 'Özel talebiniz veya notunuz varsa ekleyebilirsiniz...',
            ),
            maxLines: 3,
            onChanged: (val) => notifier.setNotes(val),
          ),
          const SizedBox(height: 24),

          ElevatedButton(
            onPressed: state.isSubmitting
                ? null
                : () async {
                    final customerId = authState.user?.id ?? 0;
                    final success = await notifier.confirmBooking(customerId);
                    if (success) {
                      ref.invalidate(appointmentsListProvider);
                    }
                  },
            child: state.isSubmitting
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      color: Colors.white,
                    ),
                  )
                : const Text('Randevuyu Onayla ve Kaydet'),
          ),
        ],
      ),
    );
  }

  Widget _summaryRow({
    required IconData icon,
    required String label,
    required String value,
    String? subValue,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 20, color: AppTheme.primaryColor),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppTheme.textSecondaryLight,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.textPrimaryLight,
                ),
              ),
              if (subValue != null) ...[
                const SizedBox(height: 2),
                Text(
                  subValue,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.primaryColor,
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  // STEP 4: Başarılı Ekranı
  Widget _buildSuccessStep(
    BuildContext context,
    WidgetRef ref,
    BookingWizardState state,
    BookingWizardNotifier notifier,
  ) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppTheme.accentSuccess.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.check_circle_rounded,
                size: 56,
                color: AppTheme.accentSuccess,
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Randevunuz Oluşturuldu!',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: AppTheme.textPrimaryLight,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Randevu detaylarınız kaydedildi. Randevularım sayfasından durumunu takip edebilirsiniz.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: AppTheme.textSecondaryLight,
              ),
            ),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: () {
                notifier.reset();
              },
              child: const Text('Yeni Randevu Al'),
            ),
          ],
        ),
      ),
    );
  }
}
