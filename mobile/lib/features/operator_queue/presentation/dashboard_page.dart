import 'package:flutter/material.dart';

import '../../../app/app_controller.dart';
import '../../diagnostics/presentation/diagnostics_page.dart';
import '../../history/presentation/history_page.dart';
import '../domain/operator_models.dart';

class DashboardPage extends StatelessWidget {
  const DashboardPage({super.key, required this.controller});

  final AppController controller;

  @override
  Widget build(BuildContext context) {
    final state = controller.state;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard Loket'),
        actions: [
          IconButton(
            tooltip: 'Riwayat',
            onPressed: () {
              controller.loadHistory();
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => HistoryPage(controller: controller),
                ),
              );
            },
            icon: const Icon(Icons.history),
          ),
          IconButton(
            tooltip: 'Diagnostics',
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => DiagnosticsPage(controller: controller),
                ),
              );
            },
            icon: const Icon(Icons.settings),
          ),
          IconButton(
            tooltip: 'Refresh',
            onPressed: controller.isLoading ? null : controller.refreshState,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: controller.refreshState,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (controller.isOffline)
              const _StatusBanner(
                text: 'Koneksi ke server terputus. Data terakhir read-only.',
              ),
            if (controller.errorMessage != null)
              _StatusBanner(text: controller.errorMessage!, isError: true),
            if (controller.isLoading && state == null)
              const Padding(
                padding: EdgeInsets.all(32),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (state?.assignment == null)
              const _NoAssignment()
            else ...[
              _AssignmentHeader(
                assignment: state!.assignment!,
                summary: state.summary,
              ),
              const SizedBox(height: 12),
              _ActiveTicketPanel(ticket: state.activeTicket),
              const SizedBox(height: 12),
              _ActionBar(controller: controller, state: state),
              const SizedBox(height: 12),
              _WaitingList(waiting: state.waiting),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusBanner extends StatelessWidget {
  const _StatusBanner({required this.text, this.isError = false});

  final String text;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isError ? const Color(0xFFFEE2E2) : const Color(0xFFFEF3C7),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(text),
    );
  }
}

class _NoAssignment extends StatelessWidget {
  const _NoAssignment();

  @override
  Widget build(BuildContext context) {
    return const Card(
      child: Padding(
        padding: EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.assignment_late, size: 32),
            SizedBox(height: 12),
            Text('Akun Anda belum memiliki loket aktif. Hubungi admin.'),
          ],
        ),
      ),
    );
  }
}

class _AssignmentHeader extends StatelessWidget {
  const _AssignmentHeader({required this.assignment, required this.summary});

  final Assignment assignment;
  final QueueSummary summary;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${assignment.counter.code} - ${assignment.counter.name}',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 4),
            Text(assignment.counter.location),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: assignment.services
                  .map((service) => Chip(label: Text(service.name)))
                  .toList(),
            ),
            const Divider(height: 22),
            Row(
              children: [
                _SummaryItem(
                  label: 'Menunggu',
                  value: summary.waitingTotal.toString(),
                ),
                _SummaryItem(
                  label: 'Selesai',
                  value: summary.servedToday.toString(),
                ),
                _SummaryItem(
                  label: 'Skip',
                  value: summary.skippedToday.toString(),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _SummaryItem extends StatelessWidget {
  const _SummaryItem({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(value, style: Theme.of(context).textTheme.titleLarge),
          Text(label, style: Theme.of(context).textTheme.bodySmall),
        ],
      ),
    );
  }
}

class _ActiveTicketPanel extends StatelessWidget {
  const _ActiveTicketPanel({required this.ticket});

  final Ticket? ticket;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Nomor Aktif', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            if (ticket == null)
              const Text('Tidak ada nomor aktif')
            else ...[
              Text(
                ticket!.ticketNo,
                style: Theme.of(context).textTheme.displayMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              Text('${ticket!.serviceName} - ${ticket!.status}'),
            ],
          ],
        ),
      ),
    );
  }
}

class _ActionBar extends StatelessWidget {
  const _ActionBar({required this.controller, required this.state});

  final AppController controller;
  final OperatorState state;

  @override
  Widget build(BuildContext context) {
    final hasActive = state.activeTicket != null;
    final disabled = controller.isActionPending || controller.isOffline;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            ElevatedButton.icon(
              onPressed: !hasActive && !disabled ? controller.callNext : null,
              icon: const Icon(Icons.campaign),
              label: const Text('Panggil'),
            ),
            OutlinedButton.icon(
              onPressed: hasActive && !disabled ? controller.recall : null,
              icon: const Icon(Icons.replay),
              label: const Text('Ulang'),
            ),
            OutlinedButton.icon(
              onPressed: hasActive && !disabled
                  ? () => _showSkipDialog(context)
                  : null,
              icon: const Icon(Icons.skip_next),
              label: const Text('Skip'),
            ),
            FilledButton.icon(
              onPressed: hasActive && !disabled
                  ? () => _showDoneDialog(context)
                  : null,
              icon: const Icon(Icons.check_circle),
              label: const Text('Selesai'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showSkipDialog(BuildContext context) async {
    final reason = await _NotesDialog.show(
      context,
      title: 'Lewati nomor ini?',
      label: 'Alasan opsional',
    );
    if (reason != null) {
      await controller.skip(reason);
    }
  }

  Future<void> _showDoneDialog(BuildContext context) async {
    final notes = await _NotesDialog.show(
      context,
      title: 'Selesaikan layanan nomor ini?',
      label: 'Catatan opsional',
    );
    if (notes != null) {
      await controller.done(notes);
    }
  }
}

class _NotesDialog extends StatefulWidget {
  const _NotesDialog({required this.title, required this.label});

  final String title;
  final String label;

  static Future<String?> show(
    BuildContext context, {
    required String title,
    required String label,
  }) {
    return showDialog<String>(
      context: context,
      builder: (_) => _NotesDialog(title: title, label: label),
    );
  }

  @override
  State<_NotesDialog> createState() => _NotesDialogState();
}

class _NotesDialogState extends State<_NotesDialog> {
  final controller = TextEditingController();

  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(widget.title),
      content: TextField(
        controller: controller,
        maxLength: 255,
        decoration: InputDecoration(labelText: widget.label),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Batal'),
        ),
        FilledButton(
          onPressed: () => Navigator.pop(context, controller.text.trim()),
          child: const Text('Konfirmasi'),
        ),
      ],
    );
  }
}

class _WaitingList extends StatelessWidget {
  const _WaitingList({required this.waiting});

  final List<Ticket> waiting;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Antrian Menunggu',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            if (waiting.isEmpty)
              const Text('Belum ada antrian menunggu.')
            else
              ...waiting.map(
                (ticket) => ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.confirmation_number),
                  title: Text(ticket.ticketNo),
                  subtitle: Text(ticket.serviceName),
                  trailing: Text('${ticket.waitingSeconds ?? 0}s'),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
