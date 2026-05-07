# MobileANTRIAN Blueprint Index

| Dokumen | Tujuan | Pembaca Utama |
|---|---|---|
| [01_BLUEPRINT_UTAMA.md](01_BLUEPRINT_UTAMA.md) | Blueprint bisnis, scope, requirement, workflow, peran, risiko, dan roadmap MobileANTRIAN | Product owner, analis, engineer lead |
| [02_API_DAN_DATABASE.md](02_API_DAN_DATABASE.md) | Kontrak API Laravel, pemetaan MySQL mANTRIAN web, aturan transaksi, payload, error, dan sinkronisasi | Backend/API engineer, Flutter engineer |
| [03_FLUTTER_UX_DAN_ARSITEKTUR.md](03_FLUTTER_UX_DAN_ARSITEKTUR.md) | Arsitektur Flutter/Dart, state management, screen inventory, UX operator loket, aksesibilitas, dan offline behavior | Flutter engineer, UI/UX, QA |
| [04_QA_SECURITY_DEVOPS.md](04_QA_SECURITY_DEVOPS.md) | Strategi pengujian, keamanan, operasional, observability, deployment, rollout, dan definition of done | QA, security, DevOps, release owner |

## Prinsip Utama

MobileANTRIAN adalah aplikasi Flutter/Dart khusus operator loket untuk memanggil antrian yang menggunakan data MySQL aplikasi mANTRIAN versi web. Aplikasi mobile direkomendasikan membaca dan menulis data melalui REST API Laravel mANTRIAN, bukan koneksi langsung dari perangkat mobile ke MySQL, agar transaksi nomor antrian, assignment operator, otorisasi, audit log, dan keamanan tetap konsisten.

## Basis Fakta dari Aplikasi Web Saat Ini

Blueprint ini disusun berdasarkan struktur mANTRIAN web di repository ini:

| Area | Fakta Teknis |
|---|---|
| Backend web | Laravel dengan PHP 8.3+ |
| Database | MySQL/MariaDB kompatibel Laravel |
| Domain utama | layanan, loket, assignment operator, tiket, panggilan, audit |
| Tabel inti | `users`, `services`, `counters`, `counter_services`, `counter_assignments`, `operating_hours`, `daily_sequences`, `tickets`, `queue_calls`, `settings`, `audit_logs` |
| Workflow operator web | lihat assignment aktif, lihat nomor aktif, lihat daftar waiting, panggil berikutnya, ulang panggil, skip, selesai |
| Status tiket | `waiting`, `called`, `serving`, `skipped`, `done`, `cancelled`, `expired`; implementasi saat ini memakai `serving` sebagai status aktif saat dipanggil |

## Asumsi dan Keputusan Terbuka

| ID | Item | Asumsi/Keputusan Sementara | Dampak Jika Berubah |
|---|---|---|---|
| AOD-001 | Bentuk integrasi database | Mobile memakai API Laravel yang mengakses MySQL web | Jika dipaksa direct MySQL dari mobile, risiko credential leak, audit tidak konsisten, dan transaksi lebih rapuh |
| AOD-002 | Target platform | Android prioritas MVP; iOS opsional fase berikutnya | Build, signing, dan device testing perlu disesuaikan |
| AOD-003 | Real-time | MVP memakai polling 3-5 detik; WebSocket/SSE menjadi enhancement | Latensi display/operator bisa lebih rendah jika WebSocket ditambahkan |
| AOD-004 | Auth mobile | Token API Laravel Sanctum atau mekanisme bearer token sejenis | Jika tetap session cookie web, mobile client perlu CSRF/cookie handling tambahan |
| AOD-005 | Mode offline | Aksi panggil/skip/done wajib online; offline hanya cache read-only terakhir | Mode offline penuh tidak aman untuk transaksi antrian |
