class ApiConstants {
  // Varsayılan API URL'i (Üretim ortamı veya Android emülatör / yerel geliştirme)
  static const String defaultProductionUrl = 'https://bookiapp.kibusiness.co';
  static const String defaultEmulatorUrl = 'http://10.0.2.2';
  static const String defaultLocalhostUrl = 'http://127.0.0.1';

  // API Yolları
  static const String loginEndpoint = '/api/v1/auth/login';
  static const String registerEndpoint = '/api/v1/auth/register';
  static const String meEndpoint = '/api/v1/auth/me';
  static const String tenantsEndpoint = '/api/v1/auth/tenants';

  static const String servicesEndpoint = '/api/v1/services';
  static const String providersEndpoint = '/api/v1/providers';
  static const String availabilitiesEndpoint = '/api/v1/availabilities';
  static const String appointmentsEndpoint = '/api/v1/appointments';
  static const String customersEndpoint = '/api/v1/customers';
  static const String settingsEndpoint = '/api/v1/settings';

  // Header isimleri
  static const String tenantHeader = 'X-Tenant-Subdomain';
  static const String authorizationHeader = 'Authorization';

  // Zaman aşımı süreleri
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 15);
}

