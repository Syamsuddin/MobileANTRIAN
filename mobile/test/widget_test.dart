import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_antrian/app/app_controller.dart';
import 'package:mobile_antrian/core/api/api_client.dart';
import 'package:mobile_antrian/core/auth/token_store.dart';
import 'package:mobile_antrian/features/auth/presentation/login_page.dart';
import 'package:mobile_antrian/features/operator_queue/domain/operator_models.dart';

class MemoryTokenStore implements TokenStore {
  String? token;
  String installation = 'test-installation';

  @override
  Future<void> clearToken() async {
    token = null;
  }

  @override
  Future<String> installationId() async => installation;

  @override
  Future<String?> readToken() async => token;

  @override
  Future<void> saveToken(String token) async {
    this.token = token;
  }
}

void main() {
  test(
    'OperatorState parses assignment, active ticket, waiting, and summary',
    () {
      final state = OperatorState.fromJson({
        'assignment': {
          'id': 5,
          'counter': {
            'id': 1,
            'code': 'LK-01',
            'name': 'Loket 1',
            'location': 'Ruang Pelayanan',
          },
          'services': [
            {
              'id': 1,
              'code': 'ADM',
              'name': 'Administrasi',
              'prefix': 'A',
              'color': '#2563eb',
            },
          ],
        },
        'active_ticket': {
          'id': 20,
          'ticket_no': 'A007',
          'service_name': 'Administrasi',
          'status': 'serving',
          'duration_seconds': 180,
        },
        'waiting': [
          {
            'id': 21,
            'ticket_no': 'A008',
            'service_name': 'Administrasi',
            'status': 'waiting',
            'waiting_seconds': 132,
          },
        ],
        'summary': {'waiting_total': 8, 'served_today': 12, 'skipped_today': 1},
      });

      expect(state.assignment?.counter.code, 'LK-01');
      expect(state.activeTicket?.ticketNo, 'A007');
      expect(state.waiting.single.ticketNo, 'A008');
      expect(state.summary.waitingTotal, 8);
    },
  );

  testWidgets('LoginPage renders operator login form', (tester) async {
    final controller = AppController(
      apiClient: ApiClient(baseUrl: 'http://localhost/api/mobile/v1'),
      tokenStore: MemoryTokenStore(),
    );

    await tester.pumpWidget(
      MaterialApp(home: LoginPage(controller: controller)),
    );

    expect(find.text('MobileANTRIAN'), findsOneWidget);
    expect(find.text('Console operator loket'), findsOneWidget);
    expect(find.byType(TextFormField), findsNWidgets(2));
    expect(find.text('Login'), findsOneWidget);

    controller.dispose();
  });
}
