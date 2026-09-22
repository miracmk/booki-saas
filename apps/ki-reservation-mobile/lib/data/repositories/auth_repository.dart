import 'package:dio/dio.dart';
import '../../core/constants/api_constants.dart';
import '../../core/network/api_client.dart';
import '../../core/storage/storage_service.dart';
import '../models/user_model.dart';
import '../models/tenant_model.dart';

class AuthResult {
  final bool success;
  final bool multipleTenants;
  final String? message;
  final String? token;
  final UserModel? user;
  final TenantModel? tenant;
  final List<TenantModel>? tenants;

  const AuthResult({
    required this.success,
    this.multipleTenants = false,
    this.message,
    this.token,
    this.user,
    this.tenant,
    this.tenants,
  });
}

class AuthRepository {
  final ApiClient _apiClient;
  final StorageService _storageService;

  AuthRepository({
    required ApiClient apiClient,
    required StorageService storageService,
  })  : _apiClient = apiClient,
        _storageService = storageService;

  Future<AuthResult> login({
    required String identifier,
    required String password,
    String? tenantSubdomain,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        ApiConstants.loginEndpoint,
        data: {
          'identifier': identifier,
          'password': password,
          if (tenantSubdomain != null && tenantSubdomain.isNotEmpty)
            'tenant_subdomain': tenantSubdomain,
        },
      );

      final data = response.data as Map<String, dynamic>;

      if (data['success'] == true) {
        // Multi-tenant check: user belongs to multiple businesses
        if (data['multiple_tenants'] == true && data['tenants'] is List) {
          final list = (data['tenants'] as List)
              .map((t) => TenantModel.fromJson(t as Map<String, dynamic>))
              .toList();
          return AuthResult(
            success: true,
            multipleTenants: true,
            message: data['message']?.toString(),
            tenants: list,
          );
        }

        // Direct single login success
        final token = data['token']?.toString();
        final user = data['user'] != null ? UserModel.fromJson(data['user']) : null;
        final tenant = data['tenant'] != null ? TenantModel.fromJson(data['tenant']) : null;

        if (token != null && user != null) {
          await _storageService.saveToken(token);
          await _storageService.saveUser(user);
          if (tenant != null) {
            await _storageService.saveTenant(tenant);
          }
        }

        return AuthResult(
          success: true,
          token: token,
          user: user,
          tenant: tenant,
        );
      }

      return AuthResult(
        success: false,
        message: data['message']?.toString() ?? 'Giriş başarısız.',
      );
    } on DioException catch (e) {
      final errorMsg = e.response?.data is Map
          ? (e.response?.data['message']?.toString() ?? 'Giriş hatası.')
          : (e.message ?? 'Sunucu bağlantı hatası.');
      return AuthResult(success: false, message: errorMsg);
    } catch (e) {
      return AuthResult(success: false, message: 'Beklenmeyen hata: $e');
    }
  }

  Future<AuthResult> register({
    required String tenantSubdomain,
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    String? phoneNumber,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        ApiConstants.registerEndpoint,
        data: {
          'tenant_subdomain': tenantSubdomain,
          'first_name': firstName,
          'last_name': lastName,
          'email': email,
          'password': password,
          if (phoneNumber != null) 'phone_number': phoneNumber,
        },
      );

      final data = response.data as Map<String, dynamic>;

      if (data['success'] == true) {
        final token = data['token']?.toString();
        final user = data['user'] != null ? UserModel.fromJson(data['user']) : null;
        final tenant = data['tenant'] != null ? TenantModel.fromJson(data['tenant']) : null;

        if (token != null && user != null) {
          await _storageService.saveToken(token);
          await _storageService.saveUser(user);
          if (tenant != null) {
            await _storageService.saveTenant(tenant);
          }
        }

        return AuthResult(success: true, token: token, user: user, tenant: tenant);
      }

      return AuthResult(
        success: false,
        message: data['message']?.toString() ?? 'Kayıt başarısız.',
      );
    } on DioException catch (e) {
      final errorMsg = e.response?.data is Map
          ? (e.response?.data['message']?.toString() ?? 'Kayıt hatası.')
          : (e.message ?? 'Sunucu bağlantı hatası.');
      return AuthResult(success: false, message: errorMsg);
    } catch (e) {
      return AuthResult(success: false, message: 'Beklenmeyen hata: $e');
    }
  }

  Future<UserModel?> getMe() async {
    try {
      final response = await _apiClient.dio.get(ApiConstants.meEndpoint);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['user'] != null) {
        final user = UserModel.fromJson(data['user']);
        await _storageService.saveUser(user);
        if (data['tenant'] != null) {
          await _storageService.saveTenant(TenantModel.fromJson(data['tenant']));
        }
        return user;
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  Future<List<TenantModel>> getTenants({String? query}) async {
    try {
      final response = await _apiClient.dio.get(
        ApiConstants.tenantsEndpoint,
        queryParameters: query != null && query.isNotEmpty ? {'q': query} : null,
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['tenants'] is List) {
        return (data['tenants'] as List)
            .map((t) => TenantModel.fromJson(t as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  Future<void> logout() async {
    await _storageService.clearAll();
  }
}

