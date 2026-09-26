import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/tenant_model.dart';
import '../../../providers/auth_provider.dart';

class TenantSelectionScreen extends ConsumerStatefulWidget {
  final bool isSwitchMode;
  final Function(TenantModel selected)? onSelected;

  const TenantSelectionScreen({
    super.key,
    this.isSwitchMode = false,
    this.onSelected,
  });

  @override
  ConsumerState<TenantSelectionScreen> createState() =>
      _TenantSelectionScreenState();
}

class _TenantSelectionScreenState extends ConsumerState<TenantSelectionScreen> {
  final _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final tenants = authState.availableTenants;

    final filteredTenants = tenants.where((t) {
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      return t.displayName.toLowerCase().contains(q) ||
          t.subdomain.toLowerCase().contains(q);
    }).toList();

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.isSwitchMode ? 'İşletme Değiştir' : 'İşletme Seçin'),
        leading: widget.isSwitchMode
            ? null
            : IconButton(
                icon: const Icon(Icons.arrow_back),
                onPressed: () {
                  ref.read(authProvider.notifier).logout();
                },
              ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Hangi işletme için devam etmek istiyorsunuz?',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 6),
              Text(
                'Hesabınız aşağıdaki işletmelerde kayıtlıdır. İşlem yapmak istediğiniz salonu veya kliniği seçin.',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 20),

              // Arama Çubuğu
              if (tenants.length > 3) ...[
                TextField(
                  controller: _searchController,
                  decoration: const InputDecoration(
                    hintText: 'İşletme ara...',
                    prefixIcon: Icon(Icons.search_rounded),
                  ),
                  onChanged: (val) {
                    setState(() {
                      _searchQuery = val;
                    });
                  },
                ),
                const SizedBox(height: 16),
              ],

              // İşletme Kartları Listesi
              Expanded(
                child: filteredTenants.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.business_outlined,
                              size: 48,
                              color: AppTheme.textSecondaryLight,
                            ),
                            const SizedBox(height: 12),
                            Text(
                              'İşletme bulunamadı.',
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ],
                        ),
                      )
                    : ListView.builder(
                        itemCount: filteredTenants.length,
                        itemBuilder: (context, index) {
                          final tenant = filteredTenants[index];
                          return _buildTenantCard(tenant);
                        },
                      ),
              ),

              if (authState.isLoading)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Center(child: CircularProgressIndicator()),
                ),

              if (authState.errorMessage != null) ...[
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppTheme.accentDanger.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    authState.errorMessage!,
                    style: const TextStyle(
                      color: AppTheme.accentDanger,
                      fontSize: 13,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTenantCard(TenantModel tenant) {
    String roleLabel = 'Müşteri';
    Color roleColor = AppTheme.secondaryColor;

    if (tenant.role == 'admin') {
      roleLabel = 'Yönetici';
      roleColor = AppTheme.primaryColor;
    } else if (tenant.role == 'provider') {
      roleLabel = 'Uzman Personel';
      roleColor = AppTheme.accentSuccess;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () async {
          if (widget.onSelected != null) {
            widget.onSelected!(tenant);
          } else {
            await ref.read(authProvider.notifier).selectTenant(tenant);
          }
        },
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.storefront_rounded,
                  color: AppTheme.primaryColor,
                  size: 24,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      tenant.displayName,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.textPrimaryLight,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      tenant.subdomain,
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppTheme.textSecondaryLight,
                      ),
                    ),
                  ],
                ),
              ),
              if (tenant.role != null) ...[
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: roleColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    roleLabel,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: roleColor,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
              ],
              const Icon(
                Icons.chevron_right_rounded,
                color: AppTheme.textSecondaryLight,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

