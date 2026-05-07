import 'package:flutter/material.dart';

import '../../../app/app_controller.dart';

class DiagnosticsPage extends StatelessWidget {
  const DiagnosticsPage({super.key, required this.controller});

  final AppController controller;

  @override
  Widget build(BuildContext context) {
    final user = controller.user;
    return Scaffold(
      appBar: AppBar(title: const Text('Diagnostics')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.person),
                  title: Text(user?.name ?? '-'),
                  subtitle: Text(user?.email ?? '-'),
                ),
                ListTile(
                  leading: const Icon(Icons.link),
                  title: const Text('API Base URL'),
                  subtitle: Text(controller.apiBaseUrl),
                ),
                ListTile(
                  leading: const Icon(Icons.sync),
                  title: const Text('Last Sync'),
                  subtitle: Text(controller.lastSync ?? '-'),
                ),
                ListTile(
                  leading: Icon(
                    controller.isOffline ? Icons.cloud_off : Icons.cloud_done,
                  ),
                  title: Text(controller.isOffline ? 'Offline' : 'Online'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: () async {
              await controller.logout();

              if (context.mounted) {
                Navigator.of(context).popUntil((route) => route.isFirst);
              }
            },
            icon: const Icon(Icons.logout),
            label: const Text('Logout'),
          ),
        ],
      ),
    );
  }
}
