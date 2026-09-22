import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/api_client.dart';
import '../core/storage/storage_service.dart';
import '../data/models/user_model.dart';
import '../data/models/tenant_model.dart';
import '../data/repositories/auth_repository.dart';

// Storage Service Provider (Overridden in main with initialized instance)
final storageServiceProvider = Provider<StorageService>((ref) {
  throw UnimplementedError('StorageService must be initialized first');
});

// Api Client Provider
final apiClientProvider = Provider<ApiClient>((ref) {
  final storage = ref.watch(storageServiceProvider);
  return ApiClient(storageService: storage);
});

// Auth Repository Provider
final authRepositoryProvider = Provider<AuthRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  final storage = ref.watch(storageServiceProvider);
  return AuthRepository(apiClient: apiClient, storageService: storage);
});

// Auth State
class AuthState {
  final bool isLoading;
  final bool isAuthenticated;
  final UserModel? user;
  final TenantModel? currentTenant;
  final bool multipleTenants;
  final List<TenantModel> availableTenants;
  final String? errorMessage;
  final String? savedIdentifier;
  final String? savedPassword;

  const AuthState({
    this.isLoading = false,
    this.isAuthenticated = false,
    this.user,
    this.currentTenant,
    this.multipleTenants = false,
    this.availableTenants = const [],
    this.errorMessage,
    this.savedIdentifier,
    this.savedPassword,
  });

  AuthState copyWith({
    bool? isLoading,
    bool? isAuthenticated,
    UserModel? user,
    TenantModel? currentTenant,
    bool? multipleTenants,
    List<TenantModel>? availableTenants,
    String? errorMessage,
    String? savedIdentifier,
    String? savedPassword,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      user: user ?? this.user,
      currentTenant: currentTenant ?? this.currentTenant,
      multipleTenants: multipleTenants ?? this.multipleTenants,
      availableTenants: availableTenants ?? this.availableTenants,
      errorMessage: errorMessage,
      savedIdentifier: savedIdentifier ?? this.savedIdentifier,
      savedPassword: savedPassword ?? this.savedPassword,
    );
  }
}

// Auth Notifier (Riverpod 3 Notifier)
class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    final storage = ref.watch(storageServiceProvider);
    final user = storage.getUser();
    final tenant = storage.getTenant();

    if (user != null) {
      return AuthState(
        isAuthenticated: true,
        user: user,
        currentTenant: tenant,
      );
    }
    return const AuthState();
  }

  Future<bool> login({
    required String identifier,
    required String password,
    String? tenantSubdomain,
  }) async {
    state = state.copyWith(
      isLoading: true,
      errorMessage: null,
      multipleTenants: false,
      availableTenants: [],
      savedIdentifier: identifier,
      savedPassword: password,
    );

    try {
      final repo = ref.read(authRepositoryProvider);
      final result = await repo.login(
        identifier: identifier,
        password: password,
        tenantSubdomain: tenantSubdomain,
      );

      if (!result.success) {
        state = state.copyWith(
          isLoading: false,
          errorMessage: result.message ?? 'Giriş başarısız.',
        );
        return false;
      }

      // If multiple tenants returned: trigger selection screen
      if (result.multipleTenants &&
          result.tenants != null &&
          result.tenants!.isNotEmpty) {
        state = state.copyWith(
          isLoading: false,
          multipleTenants: true,
          availableTenants: result.tenants!,
        );
        return false;
      }

      // Single tenant authenticated
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: true,
        user: result.user,
        currentTenant: result.tenant,
        multipleTenants: false,
        availableTenants: [],
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'Giriş hatası: $e',
      );
      return false;
    }
  }

  Future<bool> selectTenant(TenantModel tenant) async {
    final identifier = state.savedIdentifier;
    final password = state.savedPassword;

    if (identifier == null || password == null) {
      state = state.copyWith(errorMessage: 'Oturum bilgisi bulunamadı.');
      return false;
    }

    return await login(
      identifier: identifier,
      password: password,
      tenantSubdomain: tenant.subdomain,
    );
  }

  Future<bool> register({
    required String tenantSubdomain,
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    String? phoneNumber,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      final repo = ref.read(authRepositoryProvider);
      final result = await repo.register(
        tenantSubdomain: tenantSubdomain,
        firstName: firstName,
        lastName: lastName,
        email: email,
        password: password,
        phoneNumber: phoneNumber,
      );

      if (!result.success) {
        state = state.copyWith(
          isLoading: false,
          errorMessage: result.message ?? 'Kayıt başarısız.',
        );
        return false;
      }

      state = state.copyWith(
        isLoading: false,
        isAuthenticated: true,
        user: result.user,
        currentTenant: result.tenant,
      );
      return true;
    } catch (e) {
      state = state.copyWith(isLoading: false, errorMessage: 'Kayıt hatası: $e');
      return false;
    }
  }

  Future<void> logout() async {
    final repo = ref.read(authRepositoryProvider);
    await repo.logout();
    state = const AuthState();
  }

  void switchTenant(TenantModel newTenant) async {
    final storage = ref.read(storageServiceProvider);
    await storage.saveTenant(newTenant);
    state = state.copyWith(currentTenant: newTenant);
  }
}

final authProvider =
    NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

