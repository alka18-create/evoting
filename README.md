# E-Voting Sekolah

Sistem E-Voting Sekolah — modular monolith Laravel 12 + PostgreSQL + Redis + Laravel Reverb + Livewire + Tailwind CSS.

> Repo ini juga berisi 15 dokumen spesifikasi (01–15) yang menjadi sumber kebenaran desain, dan `IMPLEMENTATION_PLAN.md` sebagai kontrol progres pembangunan.

## Perangkat Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 12 (PHP 8.2 — dikunci di Dockerfile & CI) |
| Database | PostgreSQL 17 |
| Cache / Queue / Realtime | Redis 7 + Laravel Reverb |
| Frontend | Blade + Livewire 4 + Tailwind CSS 4 + Vite |
| Testing | Pest, PHPStan (static analysis), Pint (code style), Playwright (E2E) |
| Web Server | Nginx + PHP-FPM |
| Deployment | Docker Compose |

## Prasyarat

- Docker + Docker Compose (atau PHP 8.2 + Composer + PostgreSQL + Redis lokal)

## Fitur Multi-Voting (Wizard Batch)

* **1 Token = 1 Event = N Organisasi** — siswa login sekali dengan `NIS + Token + Event`, lalu wizard `OSIS → Pramuka → PMR → Rohis → Review → 1x Submit atomik` (all-or-nothing). Token baru 8 char alfanumerik + expiry; legacy 6 digit tetap diterima selama transisi. Detail: `docs/PLAN_MULTI_VOTING_WIZARD.md`.
* **Organisasi & Event** — kelola via `/admin/organizations` & `/admin/voting-events` (periode event default, election `starts_at/ends_at` nullable = inherit event). Publish hasil tetap terpisah per `elections.status` (`CLOSED`/`ARCHIVED`).
* **Monitoring per Organisasi** — di Dashboard & di detail Event (`/admin/voting-events/{id}`) tampil kartu per organisasi (eligible/sudah/belum/%) secara realtime via channel private ber-auth + alert anomali.
* **QR 1 Token (v2 opaque)** — print bulk per event (`/admin/voting-events/{id}/tokens`), payload `{v:2, eid, t}` tanpa NIS plain (legacy `{student_id, token, ...}` tetap terverifikasi). Scan di `/admin/scan` support keduanya + resolve-by-token.

## Menjalankan dengan Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
# legacy elections otomatis di-assign ke Event "Legacy" & Organisasi "Umum" (migrasi 000016)
docker compose exec app php artisan db:seed --class=DevelopmentSeeder
# Buat event serentak contoh:
# 1. Buat 4 Organisasi (OSIS, Pramuka, PMR, Rohis) di /admin/organizations
# 2. Buat Event "Serentak 2026" (OPEN) di /admin/voting-events
# 3. Buat 4 Pemilihan link ke Event + Organisasi masing-masing (OPEN/SCHEDULED)
# 4. Assign Semua Siswa Aktif + Generate Token per Event
npm install
npm run build
```

Health check:

```bash
curl http://localhost/health
curl http://localhost/api/v1/health
```

## Menjalankan secara lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer setup   # install deps + key + migrate + npm
```

## Tooling & Commands

```bash
composer lint          # Pint check (read-only)
composer lint:fix      # Pint perbaiki otomatis
composer analyse       # PHPStan level 8
composer test          # Pest (Unit + Feature + Security + Integration)
npm run dev            # Vite dev server
npm run build          # Build produksi frontend
```

## Struktur Proyek

Aplikasi modular monolith; lihat `12_PROJECT_STRUCTURE_E_Voting.md` untuk detail lengkap.

```text
app/
├── Domain/
│   ├── Elections/      # CRUD, state machine
│   ├── Candidates/     # kandidat
│   ├── Voters/         # pemilih, import CSV
│   ├── Credentials/    # kredensial voting (hash, tidak plaintext)
│   ├── Voting/         # VotingService, CastVote (voting-critical)
│   ├── Results/        # perhitungan hasil
│   ├── Auditing/       # audit log
│   └── Exports/        # export hasil
├── Http/               # Controllers, Middleware, Requests
├── Models/
├── Policies/
└── Support/
```

## Aturan Keamanan Utama

- Tabel `ballots` **TIDAK** menyimpan `voter_id` / `student_id` / `credential_id` — ballot anonim.
- Voting hanya boleh lewat `CastVote` (single entry point) dalam satu transaction atomik.
- PostgreSQL adalah satu-satunya source of truth; Redis hanya cache.
- Credential tidak pernah disimpan dalam plaintext — hanya hash.

Detail: `03_SECURITY_Threat_Model`, `15_VOTING_ENGINE_SPEC`.

## Batasan Keamanan & Risiko Residual (P3-03)

Sistem ini untuk **pemilihan internal sekolah/organisasi**, bukan pemilu publik.
Jangan klaim anonimitas kriptografis sempurna — yang dijamin:

- DB aplikasi tidak menyimpan hubungan langsung `voter → candidate`
  (`ballots` tanpa `voter_id`; token disimpan sebagai hash + ciphertext
  dekripsi-terbatas; audit `VOTE_CAST*` tanpa IP/UA).
- Hasil hanya terbuka setelah `CLOSED/ARCHIVED`; kandidat dikunci setelah `OPEN`.

Risiko yang tetap ada (Threat Model §15, §61):

1. Admin/DBA penuh secara teori bisa mencoba korelasi via timestamp, log
   server, backup, atau akses filesystem — mitigasi: least-privilege,
   backup SuperAdmin-only + `0600`, retensi (`audit:prune`), review log.
2. Perangkat pemilih terinfeksi malware / QR difoto orang lain — mitigasi:
   QR v2 opaque tanpa NIS, token sekali pakai + expiry, rotasi bila bocor.
3. Kredensial dicuri sebelum dipakai — mitigasi: entropy 8 char,
   rate-limit, `VOTER_LOGIN_FAILED` + alert anomali dashboard, MFA admin.
4. Gangguan ketersediaan (DoS/lonjak infrastruktur) — mitigasi: import
   dibatasi 5000 baris, rate-limit global, backup Postgres teruji restore.

Insiden: ikuti DETECT → CONTAIN → INVESTIGATE → PRESERVE EVIDENCE →
ASSESS → RECOVER → AUDIT → POST-MORTEM (§55), jangan hapus log mentah
saat investigasi.

## Dokumentasi

Dokumen spesifikasi (01–15) berada di root proyek mengikuti convention project. Lihat `docs/` untuk materi tambahan (ADR, architecture, security, operations, development).