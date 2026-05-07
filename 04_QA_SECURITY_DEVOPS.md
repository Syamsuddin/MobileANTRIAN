# MobileANTRIAN QA, Security, dan DevOps Blueprint

## 1. Quality Strategy

Kualitas MobileANTRIAN harus dibuktikan di dua sisi: API Laravel yang menjaga integritas transaksi MySQL, dan aplikasi Flutter yang menjaga UX operator tetap cepat serta tidak mengirim aksi ganda. Karena mobile dan web dapat mengakses workflow yang sama, test integrasi harus mencakup interaksi lintas client.

## 2. Test Strategy

| Test Type | Scope | Owner | Tooling |
|---|---|---|---|
| Unit Backend | Action/service Laravel mobile API | Backend | PHPUnit |
| Feature/API Backend | Auth, state, call, recall, skip, done, error | Backend/QA | Laravel HTTP tests |
| Concurrency Test | Double call dari web/mobile | Backend | PHPUnit parallel simulation / DB transaction tests |
| Unit Flutter | Model parsing, repository, state notifier | Flutter | `flutter_test` |
| Widget Flutter | Login form, dashboard states, dialogs | Flutter/QA | `flutter_test` |
| Integration/E2E | Device/emulator ke API staging | QA | Flutter integration test/manual UAT |
| Security Test | Auth, token, authorization, rate limit | Security | OWASP checklist, manual API testing |
| Performance Test | API latency call-next | Backend/DevOps | k6/JMeter/simple load script |
| UAT | Operator loket nyata | Product/QA | UAT script |

## 3. Critical Test Matrix

| Requirement ID | Test ID | Test Type | Scenario | Expected Result |
|---|---|---|---|---|
| FR-001 | TC-001 | API | Operator aktif login | Token issued, audit login |
| FR-001 | TC-002 | API | User inactive login | `403 USER_INACTIVE` |
| FR-001 | TC-003 | API | Admin login mobile | `403 ROLE_NOT_ALLOWED` |
| FR-004 | TC-004 | API | Operator assigned fetch state | Counter/services returned |
| FR-005 | TC-005 | API/UI | Operator no assignment | Empty state and no action |
| FR-010 | TC-006 | API | Call next with waiting | Ticket `serving`, queue call `call` |
| FR-010 | TC-007 | API | Call next from unassigned counter | `403 ASSIGNMENT_REQUIRED` |
| FR-012 | TC-008 | API | Call next while active exists | `409 ACTIVE_TICKET_EXISTS` |
| FR-013 | TC-009 | API | Recall active ticket | New queue call `recall`, status unchanged |
| FR-014 | TC-010 | API | Skip active ticket | Status `skipped`, event `skip`, audit |
| FR-016 | TC-011 | API | Complete active ticket | Status `done`, event `done`, audit |
| NFR-004 | TC-012 | Concurrency | Two devices tap call same time | Only one ticket becomes active |
| NFR-006 | TC-013 | Security | Missing token on write endpoint | `401` |
| NFR-007 | TC-014 | Security | Device metadata contains sensitive raw ID | Request rejected or sanitized |
| UX-001 | TC-015 | Flutter Widget | Double tap action button | Only one repository call |
| UX-004 | TC-016 | Flutter Integration | Backend returns 409 | UI refreshes state |

## 4. UAT Script for Operator

| Step | Action | Expected Result |
|---|---|---|
| UAT-001 | Login dengan akun operator aktif | Dashboard loket tampil |
| UAT-002 | Pastikan loket dan layanan sesuai assignment web | Nama loket dan layanan benar |
| UAT-003 | Buat tiket dari kiosk/web | Tiket muncul di waiting mobile |
| UAT-004 | Tap `Panggil` | Nomor aktif berubah dan display web ikut berubah |
| UAT-005 | Tap `Ulang` | Display/web menerima panggilan ulang |
| UAT-006 | Tap `Skip` dengan alasan | Nomor aktif hilang, history mencatat skip |
| UAT-007 | Panggil tiket berikutnya lalu tap `Selesai` | Status done di laporan web |
| UAT-008 | Cabut assignment dari web saat mobile aktif | Mobile menampilkan assignment tidak tersedia setelah refresh |
| UAT-009 | Putuskan jaringan device | Banner offline tampil dan aksi write tidak berjalan |
| UAT-010 | Logout | Session selesai dan token tidak bisa dipakai lagi |

## 5. Security Requirements

| ID | Area | Requirement | Verification |
|---|---|---|---|
| SEC-001 | Transport | Semua API mobile wajib HTTPS di non-local environment | Config review, network test |
| SEC-002 | Token | Token disimpan di secure storage | Code review |
| SEC-003 | Token | Logout revoke token server-side | API test |
| SEC-004 | Authorization | Setiap endpoint operator validasi `role=operator`, `is_active=true`, dan assignment | Feature test |
| SEC-005 | DB Credential | App mobile tidak memiliki credential MySQL | APK inspection/config review |
| SEC-006 | Rate Limit | Login dan write action punya rate limit | API test |
| SEC-007 | Audit | Semua write action mobile mencatat actor, action, source, request ID, device pseudonymous ID | Audit test |
| SEC-008 | Input Validation | `reason` dan `notes` max 255 dan disanitasi output | Validation test |
| SEC-009 | Secrets | API base URL dan non-secret config boleh di app; secret tidak boleh hard-coded | Code review |
| SEC-010 | Privacy | Tidak menyimpan IMEI, phone number, kontak, lokasi GPS tanpa kebutuhan legal | Privacy review |

## 6. OWASP-Oriented Threats and Controls

| Threat | Risk | Control |
|---|---|---|
| Stolen token | Orang lain dapat menjalankan loket | Secure storage, revoke token, short token lifetime optional, audit device |
| Broken authorization | Operator memanggil loket lain | Server-side assignment check on every request |
| Replay/double submit | Event call/skip ganda | Idempotency key, disabled UI, backend transaction |
| Direct DB exposure | Credential bocor dan bypass audit | API-only architecture |
| API abuse | Login brute force atau spam call | Throttle, lockout, monitoring |
| Stale state | Operator bertindak pada tiket yang sudah berubah | Backend guard, refresh after error |

## 7. DevOps and Environments

| Environment | Purpose | URL/Distribution | Data |
|---|---|---|---|
| Local | Developer Flutter dan API | Local Laravel server/emulator | Seed data |
| Staging | QA/UAT internal | HTTPS staging API + APK internal | Masked/sample data |
| Production | Operasional loket | HTTPS production API + signed APK/managed distribution | Live MySQL |

## 8. CI/CD Expectations

| Pipeline | Checks |
|---|---|
| Laravel API | Composer install, Pint, PHPUnit, migration test |
| Flutter | `flutter analyze`, `flutter test`, build debug/release artifact |
| Security | Dependency audit, secret scan, API auth tests |
| Release | Version bump, changelog, signed build, smoke test against staging |

## 9. Observability

| Signal | Backend | Mobile |
|---|---|---|
| Request ID | Generate/propagate `X-Request-ID` | Generate if absent, display in support diagnostics |
| Logs | Auth, queue actions, errors, 409 conflicts | Local recent error log optional, no sensitive data |
| Metrics | API latency, error rate, call-next count, queue action count | Crash-free sessions, API error count |
| Audit | `audit_logs` with source mobile | Include app version/platform/installation ID |
| Alerts | High 5xx, DB down, latency high | Crash spike if crash reporting exists |

## 10. Backup and Recovery

| Area | Requirement |
|---|---|
| Database | Ikuti backup MySQL mANTRIAN web; minimal backup harian dan restore drill berkala |
| Mobile config | API base URL documented; app can be reinstalled without data loss |
| Token compromise | Admin/technical operator dapat revoke token user/device |
| API rollback | Endpoint mobile v1 tidak dihapus saat mobile lama masih aktif |

## 11. Rollout Plan

| Phase | Activity | Exit Criteria |
|---|---|---|
| R1 | Internal technical smoke test | Login, state, call, recall, skip, done berhasil di staging |
| R2 | Pilot satu loket | Operator pilot menyelesaikan satu hari layanan tanpa data conflict |
| R3 | Expand beberapa loket | Semua operator pilot terlatih, support runbook siap |
| R4 | Production standard | APK signed, monitoring aktif, rollback plan tersedia |

## 12. Support Runbook

| Symptom | Likely Cause | Action |
|---|---|---|
| Operator tidak melihat loket | Tidak ada assignment aktif | Admin cek `counter_assignments` di web |
| Tombol panggil ditolak | Ada active ticket | Selesaikan atau skip tiket aktif |
| Antrian tidak muncul | Layanan loket tidak sesuai atau tiket tanggal berbeda | Cek `counter_services`, `tickets.ticket_date` |
| App terus login ulang | Token revoked/expired | Login ulang; cek waktu device dan API |
| Display tidak berubah | Display polling/web issue | Cek `/api/display/state` dan queue_calls terbaru |
| Error server | Backend/DB issue | Gunakan request ID untuk cek log Laravel |

## 13. Release Definition of Done

| ID | Criteria |
|---|---|
| RDOD-001 | API mobile v1 deployed di staging dan production |
| RDOD-002 | Semua test kritis pada matrix lulus |
| RDOD-003 | APK release signed dan versioned |
| RDOD-004 | UAT minimal satu hari operasional lulus |
| RDOD-005 | Audit log source mobile terverifikasi |
| RDOD-006 | Dokumentasi install, rollback, dan support tersedia |
| RDOD-007 | Tidak ada credential database di aplikasi mobile |
