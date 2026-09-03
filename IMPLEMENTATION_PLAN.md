# Implementation Plan — E-Voting Sekolah

> Dokumen kontrol pembangunan. Centang (`- [x]`) setiap item yang sudah dikerjakan.
> Berdasarkan 15 dokumen spesifikasi di folder ini (01–15).

## Fase 0 — Fondasi Partisipasi
Partisipasi semua aktor menggunakan akun pasif yang diaktifkan oleh SUPER_ADMIN ketika sistem siap digunakan.

- [x] Menyiapkan akun awal untuk semua aktor
- [x] Aktivasi akun oleh SUPER_ADMIN tepat sebelum sistem dibuka
- [x] Audit log pencatatan aktivasi oleh SUPER_ADMIN
- [ ] Simulasi seluruh flows aktor menggunakan akun pasif
- [x] Verifikasi bahwa akun pasif tidak dapat digunakan sebelum diaktifkan

## Fase 1 — Setup Proyek & Tooling
- [x] Bootstrap project Laravel 12 (skeleton resmi versi stable)
- [x] Struktur repo mengikuti `12_PROJECT_STRUCTURE_E_Voting.md` (`app/Domain`, `app/Actions`, `app/Services`, dll.)
- [x] Setup Docker Compose: PostgreSQL, Redis, Laravel Reverb
- [x] Setup Dockerfile (PHP-FPM) + Nginx
- [x] `.env.example` lengkap + dokumentasi konfigurasi
- [x] Setup Pint (code style), PHPStan (static analysis), Pest (testing)
- [x] CI pipeline (`08_TEST_PLAN_E_Voting.md`): Pint → PHPStan → Pest → build
- [x] Struktur `docs/` (ADR, architecture, security, operations, development) + README + .gitignore + health endpoint

## Fase 2 — Database Migration
Referensi: `13_DATABASE_MIGRATIONS_E_Voting.md`

- [x] Migrasi `users` (role, status aktif/pasif) + CHECK constraint
- [x] Migrasi `elections` + state machine (`DRAFT → SCHEDULED → OPEN → CLOSED → ARCHIVED`) + CHECK constraints
- [x] Migrasi `candidates` + UNIQUE(election_id, candidate_number)
- [x] Migrasi `voters` + UNIQUE(student_id)
- [x] Migrasi `voter_eligibilities` (unique `voter_id` + `election_id`) + CHECK status
- [x] Migrasi `credentials` + partial unique index (one active per eligibility)
- [x] Migrasi `ballots` — **TANPA `voter_id` / tanpa FK ke voters** (prinsip anonimitas)
- [x] Migrasi `audit_logs` + JSONB metadata + indexes
- [x] Check/unique constraints (state transition validity, FK restrict, timestampTz)
- [x] Seeder (SuperAdminSeeder + DevelopmentSeeder + UserFactory states)

## Fase 3 — Auth & RBAC
Referensi: `14_AUTH_RBAC_SPEC_E_Voting.md`, `03_SECURITY_Threat_Model_E_Voting.md`

- [x] Login admin berbasis session (SUPER_ADMIN, ADMIN, OPERATOR)
- [x] Login voter berbasis Voting Credential → voting session
- [x] Middleware & Policies per role
- [x] Rate limiting pada endpoint login/voting
- [x] Proteksi session (regenerate, timeout)
- [x] Logging semua event ke `audit_logs`

## Fase 4 — Core Election Management
- [x] CRUD election + validasi status
- [x] Transisi state machine election (dengan rule invalid transition)
- [x] CRUD candidate
- [x] Import voter via CSV
- [x] Generate Voting Credential (token/PIN/QR) per voter
- [x] Flow "Open Election" → tidak boleh di-reopen setelah CLOSED

## Fase 5 — Voting Engine (paling kritis)
Referensi: `15_VOTING_ENGINE_SPEC_E_Voting.md`

- [x] `VotingService` sebagai single entry point voting
- [x] Atomic domain transaction: auth → resolve eligibility → verify → `BEGIN` → lock row → re-check eligible → create `ballots` (tanpa voter_id) → mark voted → `COMMIT`
- [x] Anti double-vote (unique constraint + re-check dalam transaksi ber-lock)
- [x] Consumption credential permanen setelah vote sukses
- [x] Invariant & rollback penuh jika ada kegagalan
- [x] Audit log setiap vote (tanpa membocorkan identitas pemilih)

## Fase 6 — Results & Monitoring
- [x] Tally hasil hanya setelah election `CLOSED`
- [x] Control visibility hasil per role/waktu
- [x] Export/print hasil
- [ ] Dashboard partisipasi realtime (Reverb)
- [x] Chart.js visualisasi hasil

## Fase 7 — UI/UX
Referensi: `06_UI_UX_SPEC_E_Voting.md`

- [x] Layout admin (Livewire + Blade + Tailwind)
- [x] Halaman login admin
- [x] Dashboard admin
- [x] UI kelola election & kandidat
- [x] UI import voter & credential management
- [x] Halaman voting voter: Login → Lihat kandidat → Pilih → Konfirmasi → Sukses
- [ ] UI hasil & monitoring
- [ ] Responsive design & security-check UX (pencegahan salah vote)

## Fase 8 — Testing
Referensi: `08_TEST_PLAN_E_Voting.md`

- [ ] Unit tests (domain services, state machine)
- [ ] Feature tests (CRUD, flows admin)
- [ ] **P0 security tests**: single vote, double vote, concurrent vote, anonymous privacy, election state, authorization, result integrity, credential security, transaction rollback, result visibility
- [ ] Load tests (simulasi high traffic)
- [ ] E2E Playwright (flow voter penuh)
- [ ] Gate: seluruh P0 lulus sebelum release

## Fase 9 — Deployment & Go-Live
Referensi: `09_DEPLOYMENT_E_Voting.md`

- [ ] Deployment staging (Nginx + PHP-FPM + queue worker)
- [ ] Observability (logging, error tracking, health endpoint)
- [ ] Backup & restore test PostgreSQL
- [ ] Deployment production
- [ ] Aktivasi akun aktor (Fase 0)
- [ ] Go-Live + monitoring pasca-release