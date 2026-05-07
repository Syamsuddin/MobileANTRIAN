# MobileANTRIAN

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](backend/composer.json)
[![Flutter](https://img.shields.io/badge/Flutter-3.x-02569B?logo=flutter&logoColor=white)](mobile/pubspec.yaml)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](backend/composer.json)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

MobileANTRIAN adalah aplikasi antrean berbasis **Laravel API** dan **Flutter mobile app** untuk operator loket. Aplikasi ini menangani login operator, assignment loket, pemanggilan nomor antrean, recall, skip, penyelesaian layanan, riwayat aksi operator, dan audit aktivitas.

Repository ini berisi backend API, aplikasi mobile, blueprint produk, rancangan database, panduan UX, serta catatan QA/security/devops.

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Struktur Repository](#struktur-repository)
- [Prasyarat](#prasyarat)
- [Instalasi Backend](#instalasi-backend)
- [Menjalankan Mobile App](#menjalankan-mobile-app)
- [Akun Demo Lokal](#akun-demo-lokal)
- [API Mobile](#api-mobile)
- [Pengujian](#pengujian)
- [Build dan Deployment](#build-dan-deployment)
- [Dokumentasi Proyek](#dokumentasi-proyek)
- [Troubleshooting](#troubleshooting)
- [Lisensi](#lisensi)

## Fitur Utama

| Area | Deskripsi |
|---|---|
| Autentikasi operator | Login mobile khusus operator aktif dengan bearer token. |
| Assignment loket | Operator hanya dapat bekerja pada loket aktif yang ter-assign. |
| Pemanggilan antrean | Ambil tiket waiting berikutnya secara FIFO berdasarkan layanan loket. |
| Recall | Ulangi panggilan nomor aktif. |
| Skip | Lewati tiket aktif dengan alasan. |
| Done | Selesaikan tiket aktif dengan catatan opsional. |
| Dashboard operator | Menampilkan tiket aktif, daftar waiting, summary harian, dan status koneksi. |
| History | Riwayat aksi operator per tanggal dengan filter tervalidasi. |
| Audit log | Aktivitas penting dicatat untuk kebutuhan pelacakan operasional. |

## Teknologi

| Komponen | Stack |
|---|---|
| Backend | Laravel 13.x, PHP 8.3+, Eloquent ORM, PHPUnit |
| Mobile | Flutter, Dart, Material UI, `http`, `flutter_secure_storage` |
| Database default lokal | SQLite |
| Database produksi | MySQL/MariaDB atau database lain yang kompatibel Laravel |
| Frontend asset backend | Vite, Tailwind CSS |

## Struktur Repository

```text
MobileANTRIAN/
|-- backend/                    # Laravel API untuk mobile operator
|-- mobile/                     # Flutter app operator loket
|-- 00_INDEX.md                 # Indeks blueprint dan dokumen teknis
|-- 01_BLUEPRINT_UTAMA.md       # Requirement, scope, workflow, dan roadmap
|-- 02_API_DAN_DATABASE.md      # Kontrak API dan rancangan database
|-- 03_FLUTTER_UX_DAN_ARSITEKTUR.md
|-- 04_QA_SECURITY_DEVOPS.md
|-- IMPLEMENTATION_PLAN.md
|-- IMPLEMENTATION_TRACE.md
|-- LICENSE
`-- README.md
```

## Prasyarat

Pastikan tool berikut tersedia di mesin lokal:

| Tool | Versi minimum | Keterangan |
|---|---:|---|
| PHP | 8.3 | Runtime Laravel |
| Composer | 2.x | Dependency manager PHP |
| Node.js dan npm | 20.x direkomendasikan | Build asset Laravel/Vite |
| Flutter SDK | 3.x | Build dan run mobile app |
| SQLite | Opsional | Default database lokal Laravel |

## Instalasi Backend

Jalankan dari root repository:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Backend akan berjalan di:

```text
http://127.0.0.1:8000
```

Endpoint base API mobile:

```text
http://127.0.0.1:8000/api/mobile/v1
```

Untuk menjalankan asset development backend:

```bash
cd backend
npm install
npm run dev
```

## Menjalankan Mobile App

Jalankan dari root repository:

```bash
cd mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/mobile/v1
```

Gunakan base URL sesuai target device:

| Target | API_BASE_URL |
|---|---|
| Android emulator | `http://10.0.2.2:8000/api/mobile/v1` |
| iOS simulator | `http://127.0.0.1:8000/api/mobile/v1` |
| Perangkat fisik | `http://<LAN_IP_BACKEND>:8000/api/mobile/v1` |

## Akun Demo Lokal

Seeder backend membuat data demo berikut untuk pengembangan lokal:

| Role | Email | Password | Catatan |
|---|---|---|---|
| Operator | `operator@example.test` | `password` | Memiliki assignment ke `LK-01`. |
| Admin | `admin@example.test` | `password` | Tidak diizinkan login ke mobile app. |

Data demo juga membuat layanan `ADM`, loket `LK-01`, dan beberapa tiket waiting untuk tanggal berjalan.

## API Mobile

Semua endpoint berada di prefix:

```text
/api/mobile/v1
```

| Method | Endpoint | Auth | Fungsi |
|---|---|---|---|
| `GET` | `/meta` | Tidak | Informasi versi API dan waktu server. |
| `POST` | `/auth/login` | Tidak | Login operator dan penerbitan token. |
| `POST` | `/auth/logout` | Bearer | Revoke token aktif. |
| `GET` | `/me` | Bearer | Profil operator dan assignment. |
| `GET` | `/operator/state` | Bearer | Dashboard state operator. |
| `GET` | `/operator/history` | Bearer | Riwayat aksi operator. |
| `POST` | `/operator/queue/call-next` | Bearer | Panggil tiket waiting berikutnya. |
| `POST` | `/operator/queue/{ticket}/recall` | Bearer | Recall tiket aktif. |
| `POST` | `/operator/queue/{ticket}/skip` | Bearer | Skip tiket aktif. |
| `POST` | `/operator/queue/{ticket}/done` | Bearer | Selesaikan tiket aktif. |

Format response menggunakan envelope konsisten:

```json
{
  "success": true,
  "request_id": "req-example",
  "server_time": "2026-05-07T10:00:00+08:00",
  "data": {}
}
```

## Pengujian

Backend:

```bash
cd backend
php artisan test
```

Mobile:

```bash
cd mobile
flutter analyze
flutter test
```

Validasi yang terakhir dijalankan pada repository ini:

| Area | Command | Status |
|---|---|---|
| Backend | `php artisan test` | Lulus |
| Mobile | `flutter analyze` | Lulus |
| Mobile | `flutter test` | Lulus |

## Build dan Deployment

Backend production checklist:

```bash
cd backend
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Flutter Android release build:

```bash
cd mobile
flutter build apk --release --dart-define=API_BASE_URL=https://example.com/api/mobile/v1
```

Catatan deployment:

- Gunakan HTTPS untuk API production.
- Jangan menyimpan credential database di aplikasi mobile.
- Aksi antrean harus tetap melalui API backend agar transaksi, audit, dan authorization konsisten.
- Sesuaikan `APP_URL`, database, cache, queue, dan logging di `.env` backend.

## Dokumentasi Proyek

| Dokumen | Isi |
|---|---|
| [00_INDEX.md](00_INDEX.md) | Indeks blueprint dan prinsip utama proyek. |
| [01_BLUEPRINT_UTAMA.md](01_BLUEPRINT_UTAMA.md) | Scope bisnis, requirement, workflow, risiko, dan roadmap. |
| [02_API_DAN_DATABASE.md](02_API_DAN_DATABASE.md) | Kontrak API, database, transaksi, dan error model. |
| [03_FLUTTER_UX_DAN_ARSITEKTUR.md](03_FLUTTER_UX_DAN_ARSITEKTUR.md) | Arsitektur Flutter, state management, UI, dan UX operator. |
| [04_QA_SECURITY_DEVOPS.md](04_QA_SECURITY_DEVOPS.md) | Test strategy, security checklist, deployment, dan operasional. |
| [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) | Rencana implementasi teknis. |
| [IMPLEMENTATION_TRACE.md](IMPLEMENTATION_TRACE.md) | Jejak implementasi fitur. |

## Troubleshooting

| Masalah | Penyebab umum | Solusi |
|---|---|---|
| Mobile tidak bisa konek ke API | Base URL salah untuk target device | Gunakan `10.0.2.2` untuk Android emulator atau IP LAN backend untuk perangkat fisik. |
| Login operator ditolak | User bukan role `operator` atau `is_active=false` | Gunakan akun seed operator atau periksa data `users`. |
| Dashboard tidak punya assignment | Operator belum memiliki assignment loket aktif | Periksa `counter_assignments`, `counters.is_active`, dan layanan loket. |
| Tombol call-next mengembalikan queue empty | Tidak ada tiket waiting untuk tanggal berjalan dan layanan loket | Seed ulang data atau buat tiket baru dengan status `waiting`. |
| `php artisan migrate` gagal di SQLite | File database belum ada | Jalankan `touch backend/database/database.sqlite` dari root repo. |
| Flutter memakai API lama | `API_BASE_URL` tidak dikirim saat build/run | Jalankan ulang dengan `--dart-define=API_BASE_URL=...`. |

## Kontribusi

1. Buat branch dari `main`.
2. Jalankan test backend dan mobile sebelum membuka pull request.
3. Sertakan ringkasan perubahan, risiko, dan hasil pengujian.
4. Jangan commit file `.env`, credential, database lokal, build output, atau token.

## Lisensi

Project ini menggunakan lisensi [MIT](LICENSE).
