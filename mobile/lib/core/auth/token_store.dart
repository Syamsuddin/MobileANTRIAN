import 'package:flutter_secure_storage/flutter_secure_storage.dart';

abstract class TokenStore {
  Future<String?> readToken();
  Future<void> saveToken(String token);
  Future<void> clearToken();
  Future<String> installationId();
}

class SecureTokenStore implements TokenStore {
  SecureTokenStore({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'mobile_antrian_token';
  static const _installationKey = 'mobile_antrian_installation_id';

  @override
  Future<String?> readToken() => _storage.read(key: _tokenKey);

  @override
  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  @override
  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  @override
  Future<String> installationId() async {
    final existing = await _storage.read(key: _installationKey);
    if (existing != null) {
      return existing;
    }

    final generated = 'inst-${DateTime.now().microsecondsSinceEpoch}';
    await _storage.write(key: _installationKey, value: generated);
    return generated;
  }
}
