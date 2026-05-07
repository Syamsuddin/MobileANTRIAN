class OperatorUser {
  const OperatorUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
  });

  factory OperatorUser.fromJson(Map<String, dynamic> json) => OperatorUser(
    id: json['id'] as int,
    name: json['name']?.toString() ?? '',
    email: json['email']?.toString() ?? '',
    role: json['role']?.toString() ?? '',
  );

  final int id;
  final String name;
  final String email;
  final String role;
}

class CounterInfo {
  const CounterInfo({
    required this.id,
    required this.code,
    required this.name,
    required this.location,
  });

  factory CounterInfo.fromJson(Map<String, dynamic> json) => CounterInfo(
    id: json['id'] as int,
    code: json['code']?.toString() ?? '',
    name: json['name']?.toString() ?? '',
    location: json['location']?.toString() ?? '',
  );

  final int id;
  final String code;
  final String name;
  final String location;
}

class ServiceInfo {
  const ServiceInfo({
    required this.id,
    required this.code,
    required this.name,
    required this.prefix,
    this.color,
  });

  factory ServiceInfo.fromJson(Map<String, dynamic> json) => ServiceInfo(
    id: json['id'] as int,
    code: json['code']?.toString() ?? '',
    name: json['name']?.toString() ?? '',
    prefix: json['prefix']?.toString() ?? '',
    color: json['color']?.toString(),
  );

  final int id;
  final String code;
  final String name;
  final String prefix;
  final String? color;
}

class Assignment {
  const Assignment({
    required this.id,
    required this.counter,
    required this.services,
  });

  factory Assignment.fromJson(Map<String, dynamic> json) => Assignment(
    id: json['id'] as int,
    counter: CounterInfo.fromJson(
      (json['counter'] as Map).cast<String, dynamic>(),
    ),
    services: ((json['services'] as List?) ?? const [])
        .map(
          (item) => ServiceInfo.fromJson((item as Map).cast<String, dynamic>()),
        )
        .toList(),
  );

  final int id;
  final CounterInfo counter;
  final List<ServiceInfo> services;
}

class Ticket {
  const Ticket({
    required this.id,
    required this.ticketNo,
    required this.serviceName,
    required this.status,
    this.calledAt,
    this.durationSeconds,
    this.waitingSeconds,
  });

  factory Ticket.fromJson(Map<String, dynamic> json) => Ticket(
    id: json['id'] as int,
    ticketNo: json['ticket_no']?.toString() ?? '',
    serviceName: json['service_name']?.toString() ?? '',
    status: json['status']?.toString() ?? 'waiting',
    calledAt: json['called_at']?.toString(),
    durationSeconds: json['duration_seconds'] as int?,
    waitingSeconds: json['waiting_seconds'] as int?,
  );

  final int id;
  final String ticketNo;
  final String serviceName;
  final String status;
  final String? calledAt;
  final int? durationSeconds;
  final int? waitingSeconds;
}

class QueueSummary {
  const QueueSummary({
    required this.waitingTotal,
    required this.servedToday,
    required this.skippedToday,
  });

  factory QueueSummary.fromJson(Map<String, dynamic> json) => QueueSummary(
    waitingTotal: json['waiting_total'] as int? ?? 0,
    servedToday: json['served_today'] as int? ?? 0,
    skippedToday: json['skipped_today'] as int? ?? 0,
  );

  final int waitingTotal;
  final int servedToday;
  final int skippedToday;
}

class OperatorState {
  const OperatorState({
    this.assignment,
    this.activeTicket,
    required this.waiting,
    required this.summary,
  });

  factory OperatorState.fromJson(Map<String, dynamic> json) => OperatorState(
    assignment: json['assignment'] == null
        ? null
        : Assignment.fromJson(
            (json['assignment'] as Map).cast<String, dynamic>(),
          ),
    activeTicket: json['active_ticket'] == null
        ? null
        : Ticket.fromJson(
            (json['active_ticket'] as Map).cast<String, dynamic>(),
          ),
    waiting: ((json['waiting'] as List?) ?? const [])
        .map((item) => Ticket.fromJson((item as Map).cast<String, dynamic>()))
        .toList(),
    summary: QueueSummary.fromJson(
      ((json['summary'] as Map?) ?? const {}).cast<String, dynamic>(),
    ),
  );

  final Assignment? assignment;
  final Ticket? activeTicket;
  final List<Ticket> waiting;
  final QueueSummary summary;
}

class HistoryEvent {
  const HistoryEvent({
    required this.eventType,
    required this.ticketNo,
    required this.serviceName,
    required this.calledAt,
    this.notes,
  });

  factory HistoryEvent.fromJson(Map<String, dynamic> json) => HistoryEvent(
    eventType: json['event_type']?.toString() ?? '',
    ticketNo: json['ticket_no']?.toString() ?? '',
    serviceName: json['service_name']?.toString() ?? '',
    calledAt: json['called_at']?.toString() ?? '',
    notes: json['notes']?.toString(),
  );

  final String eventType;
  final String ticketNo;
  final String serviceName;
  final String calledAt;
  final String? notes;
}
