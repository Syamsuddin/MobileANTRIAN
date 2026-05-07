import 'package:flutter/material.dart';

import '../core/api/api_client.dart';
import '../core/auth/token_store.dart';
import '../core/config/app_config.dart';
import '../features/auth/presentation/login_page.dart';
import '../features/operator_queue/presentation/dashboard_page.dart';
import 'app_controller.dart';
import 'theme.dart';

class MobileAntrianApp extends StatefulWidget {
  const MobileAntrianApp({super.key, AppController? controller})
    : _controller = controller;

  final AppController? _controller;

  @override
  State<MobileAntrianApp> createState() => _MobileAntrianAppState();
}

class _MobileAntrianAppState extends State<MobileAntrianApp> {
  late final AppController controller;

  @override
  void initState() {
    super.initState();
    final config = AppConfig.fromEnvironment();
    controller =
        widget._controller ??
        AppController(
          apiClient: ApiClient(baseUrl: config.apiBaseUrl),
          tokenStore: SecureTokenStore(),
        );
    controller.bootstrap();
  }

  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: controller,
      builder: (context, _) {
        return MaterialApp(
          title: 'MobileANTRIAN',
          debugShowCheckedModeBanner: false,
          theme: buildMobileAntrianTheme(),
          home: switch (controller.sessionStatus) {
            SessionStatus.checking => const _SessionGate(),
            SessionStatus.unauthenticated => LoginPage(controller: controller),
            SessionStatus.authenticated => DashboardPage(
              controller: controller,
            ),
          },
        );
      },
    );
  }
}

class _SessionGate extends StatelessWidget {
  const _SessionGate();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: SizedBox(
          width: 40,
          height: 40,
          child: CircularProgressIndicator(),
        ),
      ),
    );
  }
}
