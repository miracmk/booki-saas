import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/operations_provider.dart';

class CustomerCrmBottomSheet extends ConsumerWidget {
  final int customerId;
  final String fallbackName;
  final String fallbackPhone;

  const CustomerCrmBottomSheet({
    super.key,
    required this.customerId,
    this.fallbackName = 'Müşteri',
    this.fallbackPhone = '',
  });

  static Future<void> show(
    BuildContext context, {
    required int customerId,
    String fallbackName = 'Müşteri',
    String fallbackPhone = '',
  }) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => CustomerCrmBottomSheet(
        customerId: customerId,
        fallbackName: fallbackName,
        fallbackPhone: fallbackPhone,
      ),
    );
  }

  Future<void> _launch(BuildContext context, String? urlString) async {
    if (urlString == null || urlString.isEmpty) return;
    final uri = Uri.parse(urlString);
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Açılamadı: $urlString')),
          );
        }
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Hata: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final crmAsync = ref.watch(customerCrmProvider(customerId));

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.85,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Drag Handle
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
          const SizedBox(height: 16),

          crmAsync.when(
            loading: () => const Center(
              child: Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: CircularProgressIndicator(),
              ),
            ),
            error: (err, stack) => Center(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 30),
                child: Column(
                  children: [
                    const Icon(Icons.error_outline_rounded, color: Colors.amber, size: 36),
                    const SizedBox(height: 8),
                    Text('Müşteri CRM profili: $fallbackName'),
                    const SizedBox(height: 4),
                    Text(fallbackPhone, style: const TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 16),
                    _actionButtons(context, fallbackPhone, null, null),
                  ],
                ),
              ),
            ),
            data: (crm) {
              final scoreColor = crm.noShowScore >= 80
                  ? const Color(0xFF10B981)
                  : (crm.noShowScore >= 50 ? const Color(0xFFF59E0B) : const Color(0xFFEF4444));

              return Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Müşteri Başlığı & No-Show Rozeti
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          CircleAvatar(
                            radius: 26,
                            backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.15),
                            child: Text(
                              crm.name.isNotEmpty ? crm.name[0].toUpperCase() : 'M',
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w800,
                                color: AppTheme.primaryColor,
                              ),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  crm.name,
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w800,
                                    color: Color(0xFF0F172A),
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  crm.phone,
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF64748B),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          // No-Show Skoru Rozeti
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            decoration: BoxDecoration(
                              color: scoreColor.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: scoreColor.withValues(alpha: 0.3)),
                            ),
                            child: Column(
                              children: [
                                Text(
                                  '${crm.noShowScore}%',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w900,
                                    color: scoreColor,
                                  ),
                                ),
                                const Text(
                                  'Güven Skoru',
                                  style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: Color(0xFF64748B)),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),

                      // Tek Tıkla WhatsApp & Arama Aksiyonları
                      _actionButtons(context, crm.phone, crm.whatsappUrl, crm.callUrl),
                      const SizedBox(height: 20),

                      // CRM Metrikleri (Ziyaret, Harcama, Gelmedi)
                      Row(
                        children: [
                          _crmMetricCard(
                            label: 'Toplam Ziyaret',
                            value: '${crm.totalVisits}',
                            icon: Icons.storefront_rounded,
                            color: const Color(0xFF2563EB),
                          ),
                          const SizedBox(width: 10),
                          _crmMetricCard(
                            label: 'Toplam Harcama',
                            value: '${crm.totalSpent.toStringAsFixed(0)} ${crm.currency}',
                            icon: Icons.payments_outlined,
                            color: const Color(0xFF10B981),
                          ),
                          const SizedBox(width: 10),
                          _crmMetricCard(
                            label: 'Gelmedi (No-Show)',
                            value: '${crm.noShows}',
                            icon: Icons.person_off_outlined,
                            color: const Color(0xFFEF4444),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),

                      // Alerji & Özel Notlar
                      const Text(
                        'Alerji & Özel Notlar',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFFBEB),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFFDE68A)),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.info_outline_rounded, color: Color(0xFFD97706), size: 18),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                crm.allergyNotes.isNotEmpty ? crm.allergyNotes : 'Özel bir alerji/not kaydedilmemiş.',
                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF92400E)),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),

                      // Geçmiş Randevular
                      const Text(
                        'Önceki Randevu Geçmişi',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 8),
                      if (crm.pastAppointments.isEmpty)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 12),
                          child: Text('Geçmiş randevu kaydı bulunamadı.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                        )
                      else
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: crm.pastAppointments.length,
                          separatorBuilder: (ctx, idx) => const Divider(height: 12),
                          itemBuilder: (ctx, idx) {
                            final item = crm.pastAppointments[idx];
                            return Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item['service_name']?.toString() ?? 'Hizmet',
                                      style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF1E293B)),
                                    ),
                                    Text(
                                      item['start_datetime']?.toString() ?? '',
                                      style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                    ),
                                  ],
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: Colors.grey.shade100,
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text(
                                    item['status']?.toString() ?? 'Tamamlandı',
                                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                                  ),
                                ),
                              ],
                            );
                          },
                        ),
                    ],
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _actionButtons(BuildContext context, String phone, String? whatsappUrl, String? callUrl) {
    final cleanPhone = phone.replaceAll(RegExp(r'[^\d+]'), '');
    final waUrl = whatsappUrl ?? (cleanPhone.isNotEmpty ? 'https://wa.me/${cleanPhone.replaceFirst('+', '')}' : null);
    final telUrl = callUrl ?? (cleanPhone.isNotEmpty ? 'tel:$cleanPhone' : null);

    return Row(
      children: [
        // WhatsApp Butonu
        Expanded(
          child: ElevatedButton.icon(
            onPressed: () {
              HapticFeedback.lightImpact();
              _launch(context, waUrl);
            },
            icon: const Icon(Icons.chat_bubble_outline_rounded, size: 16),
            label: const Text('WhatsApp Mesaj', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12)),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF25D366),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 11),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              elevation: 0,
            ),
          ),
        ),
        const SizedBox(width: 10),
        // Telefon Arama Butonu
        Expanded(
          child: OutlinedButton.icon(
            onPressed: () {
              HapticFeedback.lightImpact();
              _launch(context, telUrl);
            },
            icon: const Icon(Icons.call_rounded, size: 16),
            label: const Text('Hemen Ara', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12)),
            style: OutlinedButton.styleFrom(
              foregroundColor: const Color(0xFF0F172A),
              side: const BorderSide(color: Color(0xFFCBD5E1)),
              padding: const EdgeInsets.symmetric(vertical: 11),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ),
      ],
    );
  }

  Widget _crmMetricCard({
    required String label,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: 16, color: color),
            const SizedBox(height: 6),
            Text(
              value,
              style: TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: color),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}
