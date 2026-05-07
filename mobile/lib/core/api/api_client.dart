import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiException implements Exception {
  ApiException(
    this.code,
    this.message,
    this.statusCode, [
    this.details = const {},
  ]);

  final String code;
  final String message;
  final int statusCode;
  final Map<String, dynamic> details;

  @override
  String toString() => '$code: $message';
}

class ApiClient {
  ApiClient({required this.baseUrl, http.Client? httpClient})
    : _httpClient = httpClient ?? http.Client();

  final String baseUrl;
  final http.Client _httpClient;
  String? token;

  Future<Map<String, dynamic>> get(String path, {Map<String, String>? query}) {
    final uri = _uri(path, query);
    return _send(() => _httpClient.get(uri, headers: _headers()));
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
    String? idempotencyKey,
  }) {
    final headers = _headers();
    if (idempotencyKey != null) {
      headers['Idempotency-Key'] = idempotencyKey;
    }

    return _send(
      () => _httpClient.post(
        uri(path),
        headers: headers,
        body: jsonEncode(body ?? <String, dynamic>{}),
      ),
    );
  }

  Uri uri(String path) => _uri(path, null);

  Uri _uri(String path, Map<String, String>? query) {
    final normalizedBase = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;
    final normalizedPath = path.startsWith('/') ? path : '/$path';
    return Uri.parse(
      '$normalizedBase$normalizedPath',
    ).replace(queryParameters: query);
  }

  Map<String, String> _headers() {
    final requestId = 'req-${DateTime.now().microsecondsSinceEpoch}';
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Request-ID': requestId,
      'X-App-Version': '1.0.0',
      'X-Platform': 'android',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> _send(
    Future<http.Response> Function() send,
  ) async {
    final response = await send().timeout(const Duration(seconds: 12));
    final decoded = jsonDecode(response.body) as Map<String, dynamic>;
    if (decoded['success'] == true) {
      return (decoded['data'] as Map?)?.cast<String, dynamic>() ??
          <String, dynamic>{};
    }

    final error =
        (decoded['error'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
    throw ApiException(
      error['code']?.toString() ?? 'SERVER_ERROR',
      error['message']?.toString() ?? 'Terjadi kesalahan server.',
      response.statusCode,
      (error['details'] as Map?)?.cast<String, dynamic>() ??
          <String, dynamic>{},
    );
  }
}
