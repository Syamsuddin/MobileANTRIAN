import 'dart:async';

import 'package:flutter/foundation.dart';

import '../core/api/api_client.dart';
import '../core/auth/token_store.dart';
import '../features/operator_queue/domain/operator_models.dart';

enum SessionStatus { checking, unauthenticated, authenticated }

class AppController extends ChangeNotifier {
  AppController({required ApiClient apiClient, required TokenStore tokenStore})
    : _apiClient = apiClient,
      _tokenStore = tokenStore;

  final ApiClient _apiClient;
  final TokenStore _tokenStore;
  Timer? _poller;

  SessionStatus sessionStatus = SessionStatus.checking;
  OperatorUser? user;
  OperatorState? state;
  List<HistoryEvent> history = [];
  bool isLoading = false;
  bool isActionPending = false;
  bool isOffline = false;
  String? errorMessage;
  String? lastSync;

  String get apiBaseUrl => _apiClient.baseUrl;

  Future<void> bootstrap() async {
    sessionStatus = SessionStatus.checking;
    notifyListeners();

    final token = await _tokenStore.readToken();
    if (token == null) {
      sessionStatus = SessionStatus.unauthenticated;
      notifyListeners();
      return;
    }

    _apiClient.token = token;
    try {
      final data = await _apiClient.get('/me');
      user = OperatorUser.fromJson(
        (data['user'] as Map).cast<String, dynamic>(),
      );
      await refreshState();
      sessionStatus = SessionStatus.authenticated;
      _startPolling();
    } on ApiException catch (exception) {
      await _tokenStore.clearToken();
      errorMessage = exception.message;
      sessionStatus = SessionStatus.unauthenticated;
    } catch (_) {
      isOffline = true;
      sessionStatus = SessionStatus.authenticated;
    }
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      final installationId = await _tokenStore.installationId();
      final data = await _apiClient.post(
        '/auth/login',
        body: {
          'email': email,
          'password': password,
          'device': {
            'installation_id': installationId,
            'platform': 'android',
            'app_version': '1.0.0',
            'device_name': 'MobileANTRIAN',
          },
        },
      );
      final token = data['token']?.toString();
      if (token == null || token.isEmpty) {
        throw ApiException('SERVER_ERROR', 'Token login tidak tersedia.', 500);
      }

      await _tokenStore.saveToken(token);
      _apiClient.token = token;
      user = OperatorUser.fromJson(
        (data['user'] as Map).cast<String, dynamic>(),
      );
      await refreshState();
      sessionStatus = SessionStatus.authenticated;
      _startPolling();
    } on ApiException catch (exception) {
      errorMessage = exception.message;
    } catch (_) {
      errorMessage = 'Koneksi ke server terputus. Coba lagi.';
      isOffline = true;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    try {
      if (_apiClient.token != null) {
        await _apiClient.post('/auth/logout');
      }
    } catch (_) {
      // Local logout must still proceed if the server is unreachable.
    }

    _poller?.cancel();
    await _tokenStore.clearToken();
    _apiClient.token = null;
    user = null;
    state = null;
    history = [];
    isLoading = false;
    isActionPending = false;
    isOffline = false;
    errorMessage = null;
    lastSync = null;
    sessionStatus = SessionStatus.unauthenticated;
    notifyListeners();
  }

  Future<void> refreshState() async {
    isLoading = state == null;
    errorMessage = null;
    notifyListeners();

    try {
      final data = await _apiClient.get('/operator/state');
      state = OperatorState.fromJson(data);
      isOffline = false;
      lastSync = DateTime.now().toLocal().toIso8601String();
    } on ApiException catch (exception) {
      errorMessage = exception.message;
      if (exception.statusCode == 401) {
        await logout();
      }
    } catch (_) {
      isOffline = true;
      errorMessage = 'Koneksi ke server terputus. Coba lagi.';
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> callNext() => _queueAction(
    () => _apiClient.post('/operator/queue/call-next', idempotencyKey: _key()),
  );

  Future<void> recall() {
    final ticket = state?.activeTicket;
    if (ticket == null) {
      return Future.value();
    }
    return _queueAction(
      () => _apiClient.post(
        '/operator/queue/${ticket.id}/recall',
        idempotencyKey: _key(),
      ),
    );
  }

  Future<void> skip(String? reason) {
    final ticket = state?.activeTicket;
    if (ticket == null) {
      return Future.value();
    }
    return _queueAction(
      () => _apiClient.post(
        '/operator/queue/${ticket.id}/skip',
        body: {'reason': reason},
        idempotencyKey: _key(),
      ),
    );
  }

  Future<void> done(String? notes) {
    final ticket = state?.activeTicket;
    if (ticket == null) {
      return Future.value();
    }
    return _queueAction(
      () => _apiClient.post(
        '/operator/queue/${ticket.id}/done',
        body: {'notes': notes},
        idempotencyKey: _key(),
      ),
    );
  }

  Future<void> loadHistory() async {
    isLoading = true;
    notifyListeners();
    try {
      final data = await _apiClient.get('/operator/history');
      history = ((data['events'] as List?) ?? const [])
          .map(
            (item) =>
                HistoryEvent.fromJson((item as Map).cast<String, dynamic>()),
          )
          .toList();
      isOffline = false;
    } on ApiException catch (exception) {
      errorMessage = exception.message;
    } catch (_) {
      isOffline = true;
      errorMessage = 'Koneksi ke server terputus. Coba lagi.';
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> _queueAction(
    Future<Map<String, dynamic>> Function() action,
  ) async {
    isActionPending = true;
    errorMessage = null;
    notifyListeners();

    try {
      final data = await action();
      state = OperatorState.fromJson(data);
      isOffline = false;
      lastSync = DateTime.now().toLocal().toIso8601String();
    } on ApiException catch (exception) {
      errorMessage = exception.message;
      if (exception.statusCode == 409 || exception.statusCode == 422) {
        await refreshState();
      }
    } catch (_) {
      isOffline = true;
      errorMessage = 'Koneksi ke server terputus. Coba lagi.';
    } finally {
      isActionPending = false;
      notifyListeners();
    }
  }

  void _startPolling() {
    _poller?.cancel();
    _poller = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!isActionPending && sessionStatus == SessionStatus.authenticated) {
        refreshState();
      }
    });
  }

  String _key() => 'tap-${DateTime.now().microsecondsSinceEpoch}';

  @override
  void dispose() {
    _poller?.cancel();
    super.dispose();
  }
}
