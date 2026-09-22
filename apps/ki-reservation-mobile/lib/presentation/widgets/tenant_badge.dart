import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/auth_provider.dart';
import '../../core/theme/app_theme.dart';
import '../screens/auth/tenant_selection_screen.dart';

class TenantBadge extends ConsumerWidget {
  final bool allowSwitch;

  const TenantBadge({super.key, this.allowSwitch = true});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final tenant = authState.currentTenant;

    if (tenant == null) return const SizedBox.shrink();

    return InkWell(
      onTap: allowSwitch
          ? () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => TenantSelectionScreen(
                    isSwitchMode: true,
                    onSelected: (selected) {
                      ref.read(authProvider.notifier).switchTenant(selected);
                      Navigator.of(context).pop();
                    },
                  ),
                ),
              );
            }
          : null,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: AppTheme.primaryColor.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: AppTheme.primaryColor.withValues(alpha: 0.2),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.storefront_rounded,
              size: 16,
              color: AppTheme.primaryColor,
            ),
            const SizedBox(width: 6),
            Text(
              tenant.displayName,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppTheme.primaryColor,
              ),
            ),
            if (allowSwitch) ...[
              const SizedBox(width: 4),
              const Icon(
                Icons.arrow_drop_down_rounded,
                size: 18,
                color: AppTheme.primaryColor,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

