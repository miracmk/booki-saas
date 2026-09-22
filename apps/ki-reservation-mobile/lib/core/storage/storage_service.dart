import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../data/models/user_model.dart';
import '../../data/models/tenant_model.dart';

class StorageService {
  final FlutterSecureStorage _secureStorage;
  final SharedPreferences _prefs;

  static const String _keyToken = 'auth_token';
  static const String _keyUser = 'auth_user';
  static const String _keyTenant = 'auth_tenant';
  static const String _keyBaseUrl = 'app_base_url';

  StorageService({
    FlutterSecureStorage? secureStorage,
    required this._prefs,
  }) : _secureStorage = secureStorage ?? const FlutterSecureStorage();


  static Future<StorageService> init() async {
    final prefs = await SharedPreferences.getInstance();
    return StorageService(prefs: prefs);
  }

  // Token
  Future<void> saveToken(String token) async {
    await _secureStorage.write(key: _keyToken, value: token);
  }

  Future<String?> getToken() async {
    return await _secureStorage.read(key: _keyToken);
  }

  Future<void> deleteToken() async {
    await _secureStorage.delete(key: _keyToken);
  }

  // User
  Future<void> saveUser(UserModel user) async {
    await _prefs.setString(_keyUser, jsonEncode(user.toJson()));
  }

  UserModel? getUser() {
    final raw = _prefs.getString(_keyUser);
    if (raw == null || raw.isEmpty) return null;
    try {
      return UserModel.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  Future<void> deleteUser() async {
    await _prefs.remove(_keyUser);
  }

  // Tenant
  Future<void> saveTenant(TenantModel tenant) async {
    await _prefs.setString(_keyTenant, jsonEncode(tenant.toJson()));
  }

  TenantModel? getTenant() {
    final raw = _prefs.getString(_keyTenant);
    if (raw == null || raw.isEmpty) return null;
    try {
      return TenantModel.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  Future<void> deleteTenant() async {
    await _prefs.remove(_keyTenant);
  }

  // Custom Base URL (Local / Emülatör / Canlı)
  Future<void> saveBaseUrl(String url) async {
    await _prefs.setString(_keyBaseUrl, url);
  }

  String? getBaseUrl() {
    return _prefs.getString(_keyBaseUrl);
  }

  // Clear all session
  Future<void> clearAll() async {
    await deleteToken();
    await deleteUser();
    await deleteTenant();
  }

  // Last Business / Tenant Code
  static const String _keyLastBusinessCode = 'last_business_code';
  static const String _keyLastBusinessName = 'last_business_name';

  Future<void> saveLastBusinessCode(String code, {String? name}) async {
    await _prefs.setString(_keyLastBusinessCode, code);
    if (name != null) {
      await _prefs.setString(_keyLastBusinessName, name);
    }
  }

  String? getLastBusinessCode() {
    return _prefs.getString(_keyLastBusinessCode);
  }

  String? getLastBusinessName() {
    return _prefs.getString(_keyLastBusinessName);
  }

  Future<void> clearLastBusinessCode() async {
    await _prefs.remove(_keyLastBusinessCode);
    await _prefs.remove(_keyLastBusinessName);
  }
}

