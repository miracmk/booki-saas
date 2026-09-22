import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:ki_reservation_mobile/core/storage/storage_service.dart';
import 'package:ki_reservation_mobile/providers/auth_provider.dart';
import 'package:ki_reservation_mobile/main.dart';

void main() {
  testWidgets('BooKiApp smoke test - renders login screen initially',
      (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});
    final storageService = await StorageService.init();

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          storageServiceProvider.overrideWithValue(storageService),
        ],
        child: const BooKiApp(),
      ),
    );

    await tester.pumpAndSettle();

    // Verify BooKi Rezervasyon brand title exists
    expect(find.text('BooKi Rezervasyon'), findsOneWidget);
    expect(find.text('Giriş Yap'), findsOneWidget);
  });
}
