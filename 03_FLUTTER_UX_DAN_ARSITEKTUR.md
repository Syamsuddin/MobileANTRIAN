# MobileANTRIAN Flutter UX dan Arsitektur

## 1. Product UX Principle

MobileANTRIAN adalah tool kerja operator, bukan landing page. Tampilan harus padat, jelas, dan cepat dipindai. Layar utama harus langsung menunjukkan loket, nomor aktif, daftar menunggu, dan tombol aksi.

## 2. Target Platform

| Area | Target MVP |
|---|---|
| Framework | Flutter stable |
| Language | Dart |
| Platform | Android 10+ prioritas |
| Orientation | Portrait utama; tablet landscape didukung |
| Device | Ponsel internal atau tablet loket |
| Network | HTTPS ke Laravel API |
| Local storage | Secure storage untuk token, local cache read-only untuk state terakhir |

## 3. Suggested Flutter Architecture

```text
lib/
  main.dart
  app/
    mobile_antrian_app.dart
    router.dart
    theme.dart
  core/
    api/api_client.dart
    api/api_error.dart
    auth/secure_token_store.dart
    config/app_config.dart
    connectivity/connectivity_service.dart
    logging/request_id_interceptor.dart
  features/
    auth/
      data/auth_api.dart
      domain/auth_session.dart
      presentation/login_page.dart
      presentation/session_gate.dart
    operator_queue/
      data/operator_queue_api.dart
      domain/operator_state.dart
      domain/ticket.dart
      presentation/dashboard_page.dart
      presentation/widgets/
    history/
      data/history_api.dart
      presentation/history_page.dart
    diagnostics/
      presentation/diagnostics_page.dart
```

## 4. Recommended Packages

| Need | Package Type | Notes |
|---|---|---|
| HTTP client | `dio` or `http` | `dio` memudahkan interceptor request ID/token |
| State management | Riverpod/BLoC/provider | Pilih satu; Riverpod direkomendasikan untuk separation dan testability |
| Secure token | `flutter_secure_storage` | Token tidak boleh di shared preferences biasa |
| Connectivity | `connectivity_plus` | Untuk banner status koneksi, bukan sumber kebenaran mutlak |
| Routing | `go_router` | Session gate dan redirect login/dashboard |
| Date formatting | `intl` | Format waktu lokal |
| Testing | `flutter_test`, mocking HTTP | Unit dan widget tests |

## 5. Screen Inventory

| UI ID | Screen | Layout | Primary Components | States |
|---|---|---|---|---|
| UI-001 | Session Gate | Full screen loading | App logo/name sederhana, progress indicator | checking, token valid, token invalid, API error |
| UI-002 | Login | Form compact | Email, password, login button, error banner | idle, submitting, invalid, offline |
| UI-003 | Dashboard | Work surface | Header loket, active ticket panel, action bar, waiting list | loading, ready, empty assignment, offline, action pending |
| UI-004 | Skip Dialog | Modal bottom sheet/dialog | Ticket no, reason input, cancel, confirm skip | idle, submitting, validation error |
| UI-005 | Complete Dialog | Modal | Ticket no, notes input, cancel, confirm done | idle, submitting |
| UI-006 | History | List | Event filter today, event rows | loading, empty, error |
| UI-007 | Diagnostics | Settings-like | User, API, app version, last sync, logout | ready, API incompatible |

## 6. Dashboard Layout Specification

| Region | Content | Behavior |
|---|---|---|
| Top app bar | Loket name, connection indicator, history/settings icons | Tetap terlihat; refresh icon disabled saat request berjalan |
| Assignment strip | Counter code, location, services | Jika banyak layanan, tampilkan chips scroll horizontal |
| Active ticket panel | Ticket number besar, service name, status, duration | Jika kosong, tampilkan "Tidak ada nomor aktif" |
| Action bar | Panggil, Ulang, Skip, Selesai | `Panggil` aktif bila tidak ada active ticket; Ulang/Skip/Selesai aktif bila ada active ticket |
| Waiting list | 20 tiket pertama, waiting duration | Pull-to-refresh; list tidak boleh menggeser action bar secara tidak terkendali |
| Status banner | Offline, token expired, assignment changed | Muncul di bawah app bar |

## 7. Interaction Rules

| ID | Rule | Acceptance Criteria |
|---|---|---|
| UX-001 | Tombol aksi disable saat request berjalan | Double tap tidak mengirim request kedua dari UI |
| UX-002 | POST action mengirim `Idempotency-Key` | Key unik per tap; dipertahankan saat retry request yang sama |
| UX-003 | Setelah aksi sukses, app memakai state dari response | Tidak perlu menunggu polling berikutnya |
| UX-004 | Setelah error 409/422, app refresh state | UI tidak mempertahankan active ticket yang sudah stale |
| UX-005 | Offline banner tidak menyembunyikan tombol utama | Tombol write disabled atau menampilkan dialog retry |
| UX-006 | Skip butuh konfirmasi | Mencegah salah tekan destructive action |
| UX-007 | Done butuh konfirmasi ringan | Mencegah layanan selesai tidak sengaja |

## 8. State Model

```dart
enum TicketStatus { waiting, called, serving, skipped, done, cancelled, expired }

class OperatorState {
  final Assignment? assignment;
  final Ticket? activeTicket;
  final List<Ticket> waiting;
  final QueueSummary summary;
  final DateTime serverTime;
  final bool isStale;
}
```

| State | Meaning | UI |
|---|---|---|
| `Unauthenticated` | Token tidak ada/invalid | Login |
| `LoadingSession` | Cek token dan meta | Splash |
| `NoAssignment` | Operator valid tapi belum ditugaskan | Empty state tanpa aksi queue |
| `ReadyNoActive` | Ada assignment, tidak ada tiket aktif | Tombol `Panggil` aktif |
| `ReadyWithActive` | Ada tiket `serving/called` | Tombol `Ulang`, `Skip`, `Selesai` aktif |
| `OfflineStale` | Data terakhir dari cache | Read-only, retry |
| `ActionPending` | POST sedang berjalan | Disable action, show progress |

## 9. Local Cache and Offline Behavior

| Data | Cache | TTL | Write Offline |
|---|---|---:|---:|
| Token | Secure storage | Sampai logout/revoke | No |
| User profile | Encrypted/simple local cache | 24 jam | No |
| Last operator state | Local storage | 5 menit atau sampai app refresh | No |
| History today | Optional cache | 10 menit | No |

Aksi `call-next`, `recall`, `skip`, dan `done` tidak boleh masuk antrean offline karena dapat bentrok dengan operator web atau device lain. Saat offline, app hanya menampilkan state terakhir sebagai referensi dan meminta operator kembali online.

## 10. Accessibility and Usability Requirements

| ID | Requirement | Target |
|---|---|---|
| A11Y-001 | Touch target tombol aksi | Minimum 48dp |
| A11Y-002 | Kontras teks utama | WCAG 2.1 AA |
| A11Y-003 | Nomor tiket aktif | Sangat besar dan terbaca dari jarak kerja loket |
| A11Y-004 | Error message | Bahasa Indonesia jelas, tidak teknis kecuali request ID untuk support |
| A11Y-005 | Loading | Setiap request >300ms menampilkan progress |
| A11Y-006 | Dynamic text | Mendukung font scale tanpa overflow pada tombol utama |

## 11. Mobile Acceptance Criteria

| Requirement | Test ID | Scenario | Expected Result |
|---|---|---|---|
| FR-001 | MT-001 | Login operator aktif | Dashboard terbuka |
| FR-001 | MT-002 | Login user admin | Ditolak role tidak sesuai |
| FR-005 | MT-003 | Operator tanpa assignment | Empty state tampil, tombol call tidak ada |
| FR-010 | MT-004 | Tap Panggil saat ada waiting | Active ticket berubah menjadi nomor FIFO |
| FR-012 | MT-005 | Tap Panggil saat active exists | Error active ticket exists, state tetap |
| FR-013 | MT-006 | Tap Ulang | Event recall tercatat, ticket tetap active |
| FR-014 | MT-007 | Tap Skip dan confirm | Ticket menjadi skipped, active kosong |
| FR-016 | MT-008 | Tap Selesai dan confirm | Ticket menjadi done, active kosong |
| FR-018 | MT-009 | Matikan jaringan | Banner offline tampil, app tidak crash |
| FR-002 | MT-010 | Logout | Token hilang, kembali ke login |

## 12. UI Copy Standard

| Situation | Copy |
|---|---|
| No assignment | `Akun Anda belum memiliki loket aktif. Hubungi admin.` |
| Queue empty | `Belum ada antrian menunggu.` |
| Active ticket exists | `Selesaikan atau skip nomor aktif sebelum memanggil berikutnya.` |
| Network timeout | `Koneksi ke server terputus. Coba lagi.` |
| Token expired | `Sesi berakhir. Silakan login kembali.` |
| Skip confirm | `Lewati nomor ini?` |
| Done confirm | `Selesaikan layanan nomor ini?` |

## 13. Flutter Implementation Phasing

| Phase | Deliverable | Exit Criteria |
|---|---|---|
| F1 | App shell, theme, routing, config | Login route dan dashboard placeholder berjalan |
| F2 | Auth integration | Login/logout/token restore lulus test |
| F3 | Operator state | Dashboard menampilkan assignment, active, waiting |
| F4 | Queue actions | Call/recall/skip/done terhubung API dan menangani error |
| F5 | History/diagnostics | Riwayat hari ini dan info support tersedia |
| F6 | Hardening | Widget tests, Android release build, UAT fixes |
