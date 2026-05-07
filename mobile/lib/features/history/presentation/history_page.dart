import 'package:flutter/material.dart';

import '../../../app/app_controller.dart';

class HistoryPage extends StatelessWidget {
  const HistoryPage({super.key, required this.controller});

  final AppController controller;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Hari Ini'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: controller.loadHistory,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: AnimatedBuilder(
        animation: controller,
        builder: (context, _) {
          if (controller.isLoading && controller.history.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }
          if (controller.history.isEmpty) {
            return const Center(child: Text('Riwayat aksi hari ini kosong.'));
          }
          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: controller.history.length,
            separatorBuilder: (context, index) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final event = controller.history[index];
              return ListTile(
                leading: const Icon(Icons.receipt_long),
                title: Text(
                  '${event.eventType.toUpperCase()} - ${event.ticketNo}',
                ),
                subtitle: Text(
                  '${event.serviceName}\n${event.calledAt}${event.notes == null ? '' : '\n${event.notes}'}',
                ),
                isThreeLine: true,
              );
            },
          );
        },
      ),
    );
  }
}
