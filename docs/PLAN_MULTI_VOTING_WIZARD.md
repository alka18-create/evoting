# Plan — Multi-Voting Wizard Batch (Opsi C Revisi)

> 1 Token = 1 Event = N Organisasi. Wizard: OSIS → Pramuka → PMR → Rohis → Review → 1x Submit Atomik.
> Mengacu: `02_ERD`, `04_ARCHITECTURE`, `15_VOTING_ENGINE_SPEC`, `progress.md`, dan jawaban 4 poin user (semua siswa eligible, 1 token, periode bisa sama/beda, publish terpisah).

## 0. Ringkasan Keputusan

* Semua siswa boleh pilih semua organisasi (tidak ada filter keanggotaan per org di v1).
* 1 token 6 digit per `voting_event` (bukan per election). QR baru isi `{student_id, token, voting_event_id}` (`print-card.blade.php:25` sebelumnya `election_id`).
* Periode bisa serentak atau beda: `voting_events.starts_at/ends_at` = default, `elections.starts_at/ends_at` nullable override (jika null inherit event).
* Publish terpisah per organisasi = `elections.status` tetap `DRAFT→SCHEDULED→OPEN→CLOSED→ARCHIVED` per election (`ElectionStatus.php:10`).
* Monitoring per organisasi simpel (kartu + bar chart).
* Wizard batch: pilihan disimpan di session server (encrypted), baru `DB::transaction()` di submit akhir (all-or-nothing). Jika tutup browser sebelum submit = 0 suara.

## 1. ERD & Migrasi

### 1.1 Tabel Baru

**`organizations`**
`id, name, slug unique, logo_path nullable, description nullable, timestamps`

**`voting_events`**
`id, name, slug unique, description nullable, status(20) default DRAFT, starts_at timestamptz nullable, ends_at nullable, created_by FK users, opened_at nullable, closed_at nullable, timestampsTz` + CHECK `status IN (DRAFT,SCHEDULED,OPEN,CLOSED,ARCHIVED)` + CHECK `ends_at > starts_at`

**`voting_event_voters` (token event)**
`id, voting_event_id FK restrict, voter_id FK restrict, token(6) nullable, created_at timestamptz, updated_at` + UNIQUE `(voting_event_id, voter_id)` + INDEX `(voting_event_id, token)`

### 1.2 Ubah Tabel Lama

**`elections`**: tambah `voting_event_id FK nullable restrict`, `organization_id FK nullable restrict`, index keduanya. Nullable untuk backward-compat legacy. Jika `starts_at/ends_at IS NULL` → inherit dari `voting_events`.

**`voter_eligibilities`**: `token` jadi nullable deprecated (tidak dipakai lagi untuk event baru, tetap ada untuk legacy). Tetap jadi penanda `ELIGIBLE/VOTED` per election.

**`voting_event_voters` vs `voter_eligibilities`**: keduanya diisi saat assign. `voting_event_voters` = pintu masuk login, `voter_eligibilities` = guard anti double-vote per organisasi.

### 1.3 Migrasi Legacy

* Seed `organizations: Umum` + `voting_events: Legacy Event` untuk elections lama yang `voting_event_id IS NULL`.
* Tidak hapus kolom `voter_eligibilities.token` (soft-deprecate).

## 2. Model & Domain

* `app/Models/Organization.php`, `VotingEvent.php`, `VotingEventVoter.php`
* `app/Domain/Elections/Enums/VotingEventStatus.php` (mirror `ElectionStatus.php`)
* `Election.php` tambah `belongsTo votingEvent, organization`, `votingEvent()` + helper `effectiveStartsAt()/effectiveEndsAt()` (fallback ke event).
* `VotingEvent.php` hasMany `elections`, `votingEventVoters`, `voters` via pivot.
* Factory/Seeder: `OrganizationFactory`, `VotingEventFactory`, `VotingEventVoterFactory`, update `DevelopmentSeeder`.

## 3. Service Layer (Prinsip `04_ARCHITECTURE:64` & `VotingService.php:22`)

* `VotingService` tetap, tambah `castVotesBatch(int votingEventId, int voterId, array selections [electionId=>candidateId], ip, ua): array<Ballot>` — `DB::transaction` + `lockForUpdate` semua `voter_eligibilities` terkait + re-check `ELIGIBLE` + `ElectionStatus::Open` (pakai effective date) + `candidate.election_id == electionId` + create N `Ballot` (tanpa voter_id, anonim) + update N `VOTED` + `AuditLogger::log` per ballot + return ballots.
* `VotingEventService` (baru): `assignAllActiveVoters(event)`, `generateTokens(event)` (loop `random_int` cek unique per event), `open/close` state machine.
* `VotingEventVoter` token generate 6 digit `VOTING_CREDENTIAL_*` sama seperti lama.

## 4. Fase Pembangunan

### Fase 1 — Schema & Model (1-2 hari)
**Goal:** DB siap tanpa break existing.
- [ ] Migrasi `organizations`, `voting_events`, `voting_event_voters`, alter `elections`
- [ ] Model + Enum + Factory + `effectiveStartsAt/EndsAt`
- [ ] Seeder legacy
**Verif:** `php artisan migrate:fresh --seed`, `Election->effective*` unit test
**File:** `database/migrations/0001_01_01_000012_*`, `app/Models/*`, `app/Domain/Elections/Enums/*`

### Fase 2 — Token Event & Assign (1 hari)
**Goal:** 1 token untuk semua.
- [ ] `VotingEventService::generateTokens` + Admin UI bulk generate
- [ ] Assign semua voter aktif ke event → insert `voting_event_voters` + `voter_eligibilities` per election dalam event
- [ ] Deprecate `voter_eligibilities.token` (nullable)
**Verif:** Buat event dengan 4 elections, assign 10 voter → cek 10 token di `voting_event_voters`, 40 baris `voter_eligibilities`
**File:** `app/Domain/Voting/Services/VotingEventService.php`, `app/Http/Controllers/Admin/TokenController.php` (refactor)

### Fase 3 — Auth Voter Baru (1 hari)
**Goal:** Login pakai event.
- [ ] `VoterLoginController:showLoginForm` list `VotingEvent::where status OPEN` (bukan `Election`)
- [ ] `VoterLoginController:login` validasi `voting_event_id`, cari `VotingEventVoter` by `token`, `Auth::guard('voter')->loginById`, session `voting_event_id` + `voter_id` (hapus `eligibility_id/election_id` tunggal)
- [ ] `VoterGuard` clear event keys on logout
- [ ] Throttle `vote-login` tetap
**Verif:** Login dengan token event sukses, token election lama ditolak
**File:** `app/Http/Controllers/Auth/VoterLoginController.php`, `app/Guards/VoterGuard.php`, `resources/views/auth/voter-login.blade.php`, `routes/web.php:104`

### Fase 4 — Wizard Voting Batch (2 hari) — INTI
**Goal:** OSIS→Pramuka→PMR→Rohis→Review→1x Submit.
- [ ] `VotingController@index` redirect ke `wizard.step` jika belum `submit`
- [ ] `WizardController` (baru) `step($n)`, `storeStep(Request)` simpan `session('wizard.selections', [electionId=>candidateId])`, validasi `candidate belongsTo election in event`, progress indicator
- [ ] `WizardController@review` tampil 4 kartu pilihan (foto, visi/misi) + tombol Kembali/Ubah
- [ ] `WizardController@submit` → `VotingService::castVotesBatch` → `Auth::guard('voter')->logout + invalidate` → `vote.confirmation?hashes[]=...`
- [ ] `VotingController@vote` lama di-deprecate (atau jadi wrapper ke batch untuk single-election event)
- [ ] Middleware `auth:voter` + `EnsureVoterHasEventAccess`
**Verif:** Pest: wizard full 4 pilihan sukses, back ubah pilihan, submit tanpa pilih 1 org → 422, double submit → `ELIGIBLE` check gagal, tutup browser sebelum submit → 0 ballot
**File:** `app/Http/Controllers/Voter/WizardController.php`, `app/Domain/Voting/Services/VotingService.php`, `resources/views/voter/wizard/*`, `routes/web.php`

### Fase 5 — Admin CRUD Organisasi & Event (1-2 hari)
**Goal:** Kelola paket serentak.
- [ ] CRUD `organizations` (logo upload `FILESYSTEM_DISK=local`)
- [ ] CRUD `voting_events` + state machine + tambah elections ke event (pilih `organization_id`)
- [ ] `elections` form tambah `organization_id` + `voting_event_id` + override tanggal
- [ ] Publish terpisah: tombol `CLOSED/ARCHIVED` per election tetap
**Verif:** Buat Event "Serentak 2026" + 4 elections beda org, periode override 1 election beda jam
**File:** `app/Http/Controllers/Admin/OrganizationController.php`, `VotingEventController.php`, `ElectionController.php`, `resources/views/admin/*`, `routes/web.php`

### Fase 6 — QR & Scan (1 hari)
- [ ] `print-card.blade.php` & `print-cards.blade.php:25` QR payload ganti `voting_event_id`
- [ ] `ScanController.php:12` + `resources/views/admin/backups/scan.blade.php` parse event token, tampil daftar 4 elections status per voter
- [ ] Bulk print kartu event (bukan per election)
**File:** `resources/views/admin/tokens/*`, `app/Http/Controllers/Admin/ScanController.php`, `app/Http/Controllers/Admin/TokenController.php`

### Fase 7 — Monitoring Per Organisasi (1 hari)
- [ ] `RealtimeMonitor.php` + `RealtimeMonitor.blade.php` kartu per org: `eligible/sudah/belum/%` + bar chart `partisipasi per organisasi` (reuse `DashboardStats.php:19` Chart.js)
- [ ] `VoteCasted.php` broadcast tetap per ballot, monitor agregat per `voting_event_id`
- [ ] Dashboard admin event: donut status elections + bar per org
**File:** `app/Livewire/Admin/RealtimeMonitor.php`, `app/Events/VoteCasted.php`, `app/Livewire/Admin/DashboardStats.php`, `resources/views/admin/dashboard.blade.php`

### Fase 8 — Export & Hasil (0.5 hari)
- [ ] `ElectionResultExport.php` & `VoterListExport.php` filter `voting_event_id` + `organization_id`
- [ ] `ResultController:exportExcel/exportPdf` tambah param event/org
- [ ] Halaman `admin/results/show` tampil per election, tetap `CLOSED` baru bisa export
**File:** `app/Exports/*`, `app/Http/Controllers/Admin/ResultController.php`, `resources/views/admin/results/*`

### Fase 9 — Migrasi Legacy & Testing (1 hari)
- [ ] Pest: `castVotesBatch` atomic rollback jika 1 election sudah `VOTED`, anonimitas `ballots` tanpa `voter_id`, concurrent lock, token unik per event, publish terpisah
- [ ] `php artisan migrate --force` + `db:seed` di Docker, cek `docker compose logs app`
**File:** `tests/Feature/Voting/*`, `tests/Unit/*`

### Fase 10 — Docker & Docs (0.5 hari)
- [ ] `docker/nginx/default.conf` tidak berubah, `docker-compose.yml` cek `DB_HOST=postgres`
- [ ] Update `README.md`, `progress.md`, `INSTALL_PHASE3.md` cara buat event serentak
- [ ] `APP_URL` tetap `http://localhost:8888`

## 5. Risiko & Mitigasi

* **Token collision 6 digit** → loop `random_int` + cek unique `voting_event_id+token`, max retry 10.
* **Wizard abandon** → tidak ada ballot, voter bisa login lagi (by design all-or-nothing). Alternatif future: simpan draft di `voting_event_voters.metadata` jika butuh resume.
* **Anonimitas** → `Ballot` tetap tanpa `voter_id`, `audit_logs` hanya `election_id` (ikut `VotingService.php:22`).
* **Backward-compat** → semua FK nullable, election lama tanpa event tetap bisa vote via route legacy `/vote/login` (fallback).

## 6. Urutan Eksekusi Disarankan

Fase 1 → 2 → 3 → 4 (inti, demo wizard) → 5 → 6 → 7 → 8 → 9 → 10. Tiap fase commit terpisah, bisa di-review tanpa menunggu semua selesai.

---
*Siap dieksekusi per fase. Konfirmasi Fase 1 dimulai?*
