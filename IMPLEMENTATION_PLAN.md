# MobileANTRIAN Implementation Plan

## 1. Approved Blueprint Source

| Source | Purpose |
|---|---|
| `00_INDEX.md` | Index dan keputusan utama lintas dokumen |
| `01_BLUEPRINT_UTAMA.md` | Scope bisnis, requirement, workflow, role, risiko, roadmap |
| `02_API_DAN_DATABASE.md` | Kontrak API, database mapping, transaksi, error, payload |
| `03_FLUTTER_UX_DAN_ARSITEKTUR.md` | Arsitektur Flutter, UX, screen inventory, state, offline behavior |
| `04_QA_SECURITY_DEVOPS.md` | QA, security, DevOps, rollout, release definition of done |

Status approval: user menyetujui executive summary Step 2 pada sesi orchestrator ini.

## 2. Implementation Scope

MVP dibangun sebagai full stack workspace baru karena root folder saat ini hanya berisi dokumen blueprint dan belum berisi kode Laravel/Flutter.

| Area | Scope MVP |
|---|---|
| Backend | Laravel API project di `backend/` dengan endpoint `/api/mobile/v1` |
| Database | MySQL/MariaDB schema kompatibel blueprint mANTRIAN; SQLite test/dev fallback bila diperlukan |
| Mobile | Flutter app di `mobile/` untuk operator loket |
| Auth | Bearer token API; Sanctum diprioritaskan bila Laravel scaffold tersedia |
| Queue | Assignment, state, call next, recall, skip, done, history |
| Security | Role operator, active user, assignment authorization, no mobile DB credential, audit metadata |
| Testing | Backend feature tests dan Flutter unit/widget tests sesuai risiko |
| Docs/Trace | `IMPLEMENTATION_TRACE.md` diperbarui setelah build |

Out of scope MVP tetap mengikuti blueprint: pengambilan tiket pengunjung dari mobile, admin CRUD mobile, display TV mobile, direct MySQL dari mobile, offline write, push notification publik, dan multi-assignment advanced.

## 3. Stack and Project Conventions

| Layer | Convention |
|---|---|
| Workspace | Root berisi dokumen delivery dan dua aplikasi: `backend/`, `mobile/` |
| Backend language | PHP/Laravel sesuai blueprint |
| Backend style | Thin controllers, FormRequest validation, service/action layer untuk queue transaction, API resources untuk response |
| Mobile language | Flutter/Dart |
| Mobile style | Layered architecture: `app`, `core`, `features/*/data`, `features/*/domain`, `features/*/presentation` |
| API response | Canonical envelope: `success`, `request_id`, `server_time`, `data` atau `error` |
| IDs | Gunakan requirement IDs dari blueprint dalam test names, comments penting, dan trace artifact |

## 4. Module Sequence

| Order | Module | Blueprint IDs | Deliverable |
|---:|---|---|---|
| 1 | Workspace bootstrap | CN-001, NFR-010, NFR-012 | Laravel backend dan Flutter mobile scaffold |
| 2 | Database/domain | FR-004-FR-017, BR-002-BR-007 | Migrations/models/relations untuk queue domain |
| 3 | Backend auth/API foundation | FR-001-FR-003, API-001-API-004 | Token auth, meta, login, logout, me |
| 4 | Backend operator state | FR-004-FR-009, API-005 | Assignment, active ticket, waiting, summary |
| 5 | Backend queue actions | FR-010-FR-016, API-006-API-009, TX-001-TX-006 | Transactional call/recall/skip/done |
| 6 | Backend history/audit | FR-017, FR-019, API-010, SEC-007 | History endpoint dan audit logs |
| 7 | Flutter app shell/auth | UI-001, UI-002, FR-001-FR-003 | Session gate, login, secure token, routing |
| 8 | Flutter dashboard/actions | UI-003-UI-005, FR-004-FR-018 | Dashboard, dialogs, refresh, offline/error states |
| 9 | Flutter history/diagnostics | UI-006, UI-007, FR-017, FR-020 | History page, diagnostics/settings |
| 10 | Tests and hardening | DOD-001-DOD-006, TC-001-TC-016 | Backend/Flutter tests, analyze/build checks |
| 11 | Trace and gate handoff | All | `IMPLEMENTATION_TRACE.md` ready for QA/security/review |

## 5. Database and Migration Plan

Create backend migrations for the blueprint domain when an existing mANTRIAN schema is not present locally.

| Table | Key Implementation Notes |
|---|---|
| `users` | Laravel users plus `role`, `is_active`, `last_login_at` |
| `services` | `code`, `name`, `prefix`, `color`, `is_active`, `sort_order` |
| `counters` | `code`, `name`, `location`, `is_active` |
| `counter_services` | many-to-many counter-service mapping |
| `counter_assignments` | `user_id`, `counter_id`, `start_at`, `end_at`, `is_active`; indexes for active lookup |
| `daily_sequences` | optional seed/support for ticket number generation compatibility |
| `tickets` | `ticket_no`, `service_id`, `ticket_date`, `status`, call/skip/complete timestamps |
| `queue_calls` | append-only `call`, `recall`, `skip`, `done` events with operator/counter/ticket |
| `audit_logs` | append-only actor/action/entity/metadata/request ID |
| `settings` | API/app compatibility metadata if needed by `/meta` |

Critical constraints:

- Queue write actions must use DB transactions.
- Active ticket uniqueness is enforced in service logic and supported by indexes.
- Waiting FIFO query orders by `created_at`, then `id`.
- Notes/reason fields max 255 characters.
- Audit logs are append-only at application level.

## 6. Backend/API Plan

| API ID | Endpoint | Implementation |
|---|---|---|
| API-001 | `GET /api/mobile/v1/meta` | Public compatibility endpoint |
| API-002 | `POST /api/mobile/v1/auth/login` | Validate operator active, issue token, audit login |
| API-003 | `POST /api/mobile/v1/auth/logout` | Revoke current token, audit logout |
| API-004 | `GET /api/mobile/v1/me` | Return user and active assignment |
| API-005 | `GET /api/mobile/v1/operator/state` | Aggregate assignment, active ticket, waiting, summary |
| API-006 | `POST /api/mobile/v1/operator/queue/call-next` | Transactional FIFO call with idempotency guard |
| API-007 | `POST /api/mobile/v1/operator/queue/{ticket}/recall` | Validate active ticket and append recall event |
| API-008 | `POST /api/mobile/v1/operator/queue/{ticket}/skip` | Validate active ticket, status `skipped`, append event |
| API-009 | `POST /api/mobile/v1/operator/queue/{ticket}/done` | Validate active ticket, status `done`, append event |
| API-010 | `GET /api/mobile/v1/operator/history` | Today/history events for operator |

Backend components:

- `Api\Mobile\AuthController`
- `Api\Mobile\MetaController`
- `Api\Mobile\OperatorStateController`
- `Api\Mobile\OperatorQueueController`
- `Api\Mobile\OperatorHistoryController`
- `Services\Mobile\OperatorStateService`
- `Services\Queue\CallNextTicketAction`
- `Services\Queue\RecallTicketAction`
- `Services\Queue\SkipTicketAction`
- `Services\Queue\CompleteTicketAction`
- `Services\Audit\AuditLogger`
- API resources for user, assignment, ticket, operator state, history.

## 7. Frontend/UI Plan

Flutter pages:

| UI ID | Screen | Implementation |
|---|---|---|
| UI-001 | Session Gate | Check token and `/meta`/`me`, route to login/dashboard |
| UI-002 | Login | Email/password form, error banner, loading, offline handling |
| UI-003 | Dashboard Loket | Counter header, services, active ticket, action bar, waiting list |
| UI-004 | Skip Dialog | Confirm skip, optional reason, max 255 |
| UI-005 | Complete Dialog | Confirm done, optional notes, max 255 |
| UI-006 | History | Today event list with refresh |
| UI-007 | Diagnostics | User, API base URL, app version, last sync, logout |

Mobile components/services:

- `ApiClient` with bearer token, request ID, timeout, canonical error parsing.
- Secure token store using Flutter secure storage.
- Repository layer for auth/operator queue/history.
- State controllers for session and dashboard.
- Polling while foreground, default 5 seconds.
- Idempotency key per write action.
- Offline/stale banner and read-only last state behavior.

## 8. Test Plan Summary

Backend tests:

- Auth success/invalid/inactive/role denied.
- Protected endpoint unauthorized.
- Assignment required and assignment ownership.
- State endpoint with no assignment, no active, active ticket, waiting list.
- Call next success, queue empty, active exists, unassigned counter.
- Recall/skip/done active ticket validation.
- Audit log source `mobile`, request ID, device metadata.
- Idempotency/double submit behavior.

Flutter tests:

- Model parsing for success/error envelopes.
- Login form validation and error display.
- Session routing for authenticated/unauthenticated states.
- Dashboard states: no assignment, no active, active, queue empty, offline.
- Action buttons disable during pending request.
- Skip/done dialogs validation.
- 409/422 API error refresh behavior.

Verification commands will depend on installed local tooling and generated scaffolds, but target commands are:

```bash
cd backend && composer test
cd backend && php artisan test
cd mobile && flutter analyze
cd mobile && flutter test
```

## 9. Risks and Dependencies

| Risk/Dependency | Impact | Mitigation |
|---|---|---|
| Existing mANTRIAN Laravel source is not in this root | Cannot literally reuse existing action classes | Implement equivalent services and keep contracts compatible |
| Flutter SDK may not be installed locally | Mobile scaffold/build may be blocked | Detect tooling before build and report blocker |
| Composer/Laravel installer may not be available | Backend scaffold may require fallback | Use Composer `create-project` if available; otherwise document blocker |
| MySQL not configured locally | Full DB integration may be blocked | Use `.env.example`; tests can use SQLite where compatible |
| Signed Android release requires keystore | Release APK signing may be blocked | Provide debug build and release runbook until keystore is supplied |
| Production API URL/credentials unknown | Deployment cannot be final | Keep env-driven config and document required values |

## 10. Definition of Done

Implementation phase is done when:

- Backend and mobile project scaffolds exist and are runnable.
- API endpoints match `02_API_DAN_DATABASE.md`.
- Flutter screens match `03_FLUTTER_UX_DAN_ARSITEKTUR.md`.
- Core queue workflows run end-to-end in local/dev mode.
- Backend tests cover critical API and authorization paths.
- Flutter tests cover critical UI/state behavior where tooling permits.
- No mobile code contains MySQL credentials or direct SQL access.
- `IMPLEMENTATION_TRACE.md` maps blueprint IDs to code and verification.
- Remaining blockers are documented for QA/security/review gates.
