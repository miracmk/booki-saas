import 'package:dio/dio.dart';
import '../constants/api_constants.dart';
import '../storage/storage_service.dart';

class ApiClient {
  final StorageService _storageService;
  late final Dio dio;

  ApiClient({required StorageService storageService})
      : _storageService = storageService {
    final baseUrl = _storageService.getBaseUrl() ?? ApiConstants.defaultProductionUrl;

    dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: ApiConstants.connectTimeout,
        receiveTimeout: ApiConstants.receiveTimeout,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    // Auth & Tenant Interceptor
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Token injection
          final token = await _storageService.getToken();
          if (token != null && token.isNotEmpty) {
            options.headers[ApiConstants.authorizationHeader] = 'Bearer $token';
          }

          // Tenant header injection
          final tenant = _storageService.getTenant();
          if (tenant != null && tenant.subdomain.isNotEmpty) {
            options.headers[ApiConstants.tenantHeader] = tenant.subdomain;
          }

          return handler.next(options);
        },
        onError: (DioException error, handler) {
          // Log or handle 401 unauthenticated
          return handler.next(error);
        },
      ),
    );
  }

  void updateBaseUrl(String newBaseUrl) {
    dio.options.baseUrl = newBaseUrl;
    _storageService.saveBaseUrl(newBaseUrl);
  }
}

