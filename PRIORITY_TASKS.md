# Priority Tasks — E-Voting Sekolah

**Tanggal:** 4 Sep 2026
**Sumber:** Audit `progress.md` + review kode (`VotingService`, `VoterLoginController`, `TokenController`, `ScanController`, `BackupController`, QR views, migrations, Threat Model `03_SECURITY_*`)
**Kesimpulan singkat:** Fungsional Phase 1–4 + UI kartu sudah baik untuk demo. **Belum siap production** sebelum P0 selesai.

> Referensi wajib: `01_PRD_E_Voting_Sekolah.md §6.5, §15`, `03_SECURITY_Threat_Model_E_Voting.md §8.1, §11, §12, §29, SEC-07, §62 Keputusan 2`.

---

## P0 — Critical (Blocking Production)

### P0-01 — Simpan token sebagai hash, bukan plaintext
- **Masalah:** `voter_eligibilities.token` (migrasi `000010`) dan `voting_event_voters.token` (`000014`) disimpan plain 6 digit. Migrasi `000010` malah drop tabel `credentials(credential_hash)` yang sudah benar. Backup SQL ikut membocorkan token.
- **Lokasi:**
  - `database/migrations/0001_01_01_000010_simplify_credentials_to_token.php`
  - `database/migrations/0001_01_01_000014_create_voting_event_voters_table.php`
  - `app/Domain/Voting/Services/VotingEventService.php:91-94,152-154`
  - `app/Http/Controllers/Admin/TokenController.php:76-77`
  - `app/Http/Controllers/Auth/VoterLoginController.php:56-65`
  - `app/Http/Controllers/Admin/ScanController.php:41-44,74-77`
- **Perbaikan:**
  1. Tambah kolom `token_hash CHAR(64)` + (opsional) pertahankan `token` sementara untuk masa transisi, lalu drop.
  2. Generate token kuat (lihat P0-06), simpan `hash('sha256', $token)` / HMAC dengan `APP_KEY`.
  3. Login/verify bandingkan hash, bukan plain. Tampilkan plain **hanya sekali** saat issue (flash `issued_tokens`), jangan disimpan di session lama / log.
  4. Update `BackupController` agar tidak pernah dump plain (setelah migrasi, kolom plain sudah hilang).
- **Acceptance:** `SELECT token FROM voting_event_voters` tidak ada plain; DB bocor ≠ token bocor (SEC-07); test login dengan token benar lolos, token salah ditolak.

### P0-02 — Fix path traversal + otorisasi Backup
- **Masalah:** `BackupController::download/destroy($filename)` concat langsung `storage/app/backups/$filename` — bisa `../../.env`. Semua `ADMIN/OPERATOR` bisa akses, padahal harus SuperAdmin only.
- **Lokasi:** `app/Http/Controllers/Admin/BackupController.php:120-142`, `routes/web.php:108-112`
- **Perbaikan:**
  1. Sanitasi: `$filename = basename($filename)`, whitelist regex `/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/`, verifikasi `realpath()` masih di dalam `backupPath`.
  2. Tambah middleware/policy: `->middleware('role:SUPER_ADMIN')` atau `Gate::authorize('manageBackups')` di `index/create/download/destroy`.
  3. Tambah audit log `BACKUP_CREATED/DOWNLOADED/DELETED`.
- **Acceptance:** `GET /admin/backups/../../.env/download` → 404; OPERATOR → 403; setiap aksi tercatat di audit log.

### P0-03 — Backup Postgres-compatible + terproteksi
- **Masalah:** Dump pakai sintaks MySQL (`SET FOREIGN_KEY_CHECKS`, `utf8mb4`, `addslashes`) — gagal restore di Postgres. Tidak ada enkripsi, tidak pakai `spatie/laravel-backup` padahal sudah di `composer.json`.
- **Lokasi:** `app/Http/Controllers/Admin/BackupController.php:46-118`, `config/backup.php`
- **Perbaikan:**
  1. Ganti ke `pg_dump` bila tersedia, fallback ke generator Postgres: `SET session_replication_role='replica'`, `COPY`/`INSERT` dengan `pdo->quote()`, bukan `addslashes`.
  2. Enkripsi file (GPG/openssl dengan `BACKUP_PASSWORD`) + permission `0600`, simpan di disk `private`, bukan `local` publik.
  3. Tambah tombol `restore --dry-run` test + dokumentasikan retention (mis. 14 backup / 30 hari).
- **Acceptance:** Backup berhasil di-restore ke DB kosong di staging; file backup tidak readable oleh user lain; restore test tercatat.

### P0-04 — Pesan error generik anti-enumeration
- **Masalah:** `VoterLoginController` bedakan `NIS tidak ditemukan` vs `Token tidak valid` → attacker bisa enumerasi NIS + brute force token.
- **Lokasi:** `app/Http/Controllers/Auth/VoterLoginController.php:49-65`
- **Perbaikan:** Samakan semua kegagalan login voter jadi satu pesan: `Kredensial tidak valid atau tidak dapat digunakan.` Pertahankan pesan detail hanya di log server (tanpa token plain).
- **Acceptance:** Response NIS salah vs token salah identik (status + body); tidak ada `student_id` valid yang bisa dibedakan dari timing/error.

### P0-05 — Lengkapi authorization server-side
- **Masalah:** `ResultController`, `ScanController`, `BackupController` tanpa `Gate::authorize` — hanya andalkan prefix `role:SUPER_ADMIN,ADMIN,OPERATOR`.
- **Lokasi:**
  - `app/Http/Controllers/Admin/ResultController.php`
  - `app/Http/Controllers/Admin/ScanController.php:15-20`
  - `app/Http/Controllers/Admin/BackupController.php`
- **Perbaikan:** Tambah `Gate::authorize('viewResult', $election)`, `Gate::authorize('verifyVoter')`, `Gate::authorize('manageBackups')` + Policies (`ResultPolicy` sudah ada — dipakai). Tutup IDOR: pastikan `election_id/voting_event_id` dari QR diauthorize terhadap admin scope.
- **Acceptance:** OPERATOR akses backup → 403; voter akses `/admin/*` → 403/redirect; test `AuthorizationTest` hijau.

### P0-06 — Perkuat entropy + expiry token
- **Masalah:** Hardcode 6 digit numerik (`random_int(100000,999999)`) ≈ 20 bit, hanya 900rb kombinasi. `.env.example` janji `VOTING_CREDENTIAL_LENGTH=8 + ALPHABET` tapi tidak dipakai. Tanpa expiry.
- **Lokasi:** `TokenController.php:76`, `VotingEventService.php:177,185`, `.env.example:104-106`
- **Perbaikan:**
  1. Pakai config `VOTING_CREDENTIAL_LENGTH` (min 8) + alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` → ≥ 40 bit.
  2. Tambah kolom `expires_at` (mis. akhir event) + `last_used_at/revoked_at`; tolak token expired/revoked di login/scan.
  3. Tambah throttling global per-IP + lockout sementara setelah N gagal (lihat P1-03).
- **Acceptance:** Token baru format 8 char alfanumerik; token expired ditolak; brute force 6-digit lama tidak berlaku setelah migrasi.

---

## P1 — High (Wajib sebelum uji lapangan)

### P1-01 — Minimasi payload QR
- **Lokasi:** `resources/views/admin/voting-events/tokens/print-card.blade.php:664`, `print-cards.blade.php:934`, `resources/views/admin/tokens/print-*.blade.php`, `resources/views/admin/backups/scan.blade.php:213-225`
- **Perbaikan:** QR idealnya hanya `credential acak` (opaque), bukan `{student_id, token, event_id}` plain. Minimal: jangan tampilkan `student_id` plain di QR bila tidak perlu; tambah panduan rotasi jika QR bocor/foto tersebar. Update JS scan agar tetap backward-compatible (dual payload sudah ada — pertahankan).
- **Acceptance:** QR lama tetap terverifikasi selama transisi; QR baru tidak expose NIS plain.

### P1-02 — Hardening session, cookie, headers, HTTPS
- **Lokasi:** `.env.example:38-41`, `docker/nginx/*`, `app/Http/Middleware/*`
- **Perbaikan:** `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true` (prod), `SESSION_HTTP_ONLY=true`, `SAME_SITE=lax`, `SESSION_LIFETIME` ≤ 30 untuk voter; tambah headers `HSTS, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CSP` (uji agar tidak blok Livewire/Reverb); wajib HTTPS di prod (`09_DEPLOYMENT`).
- **Acceptance:** Scan header prod hijau; cookie voter `Secure+HttpOnly`.

### P1-03 — Rate-limit + monitoring brute force
- **Lokasi:** `app/Providers/AppServiceProvider.php:29-31`, `VoterLoginController`
- **Perbaikan:** Pertahankan `5/mnt per student_id+IP`, tambah limit global per-IP (mis. 30/mnt), backoff eksponensial, log `VOTER_LOGIN_FAILED` tanpa token, alert jika >100 gagal/5 mnt. Pertimbangkan allowlist IP TPS saat hari-H agar tidak blokir siswa sah.
- **Acceptance:** Test brute force 20x/mnt diblok; siswa sah 1 kelas (40 orang) tetap bisa login bergantian.

### P1-04 — Fix collision token legacy
- **Lokasi:** `TokenController.php:75-77` (tanpa cek unik/retry seperti `VotingEventService:174`)
- **Perbaikan:** Samakan dengan `generateUniqueToken()` + unique index `WHERE token IS NOT NULL` per election/event. Tambah handling `QueryException duplicate` → retry.
- **Acceptance:** Issue 1000 token massal tanpa duplikat.

### P1-05 — Audit log IP + retention tanpa korelasi
- **Lokasi:** `VotingService.php:75-83,180-185` (`$ip/$userAgent` dioper tapi tak disimpan), `AuditLogger`
- **Perbaikan:** Simpan IP ter-hash/pseudonim + `user_agent` truncated di audit admin saja, **jangan** di `ballots`; tetapkan retention `app log 30–90 hari, audit 1–3 tahun` (Threat Model §41); pastikan tidak ada `voter→candidate` di log.
- **Acceptance:** Audit `VOTE_CAST` ada konteks tanpa bocor pilihan; tidak ada `candidate_id` + identitas dalam 1 baris log.

### P1-06 — Selaraskan Dockerfile + secrets
- **Lokasi:** `Dockerfile: FROM php:8.4-fpm` vs `progress.md: downgrade 8.2`, `.env.example:31,109`
- **Perbaikan:** Kunci `php:8.2-fpm`, ganti default `DB_PASSWORD/superadmin` dengan generator acak di setup script, pastikan `.env` tidak ter-commit, tambah `composer audit` + `npm audit` di CI.
- **Acceptance:** `docker build` reproducible; `git secrets scan` bersih.

---

## P2 — Medium (Kualitas + performa)

- [ ] **P2-01 Wizard N+1:** `WizardController::getWizardData:34-37` query eligibility per election dalam loop → eager load sekali (`whereIn electionIds + voter_id`). Tambah index `(voter_id, election_id, status)` bila belum ada.
- [ ] **P2-02 Enum handling:** `ResultController:35` pakai `->value !== 'CLOSED'` rapuh → pakai `!== ElectionStatus::Closed && !== Archived` + test election `OPEN` tolak hasil (INV-05).
- [ ] **P2-03 CDN pinning:** `scan.blade.php:105` `unpkg html5-qrcode@2.3.8` tanpa SRI → vendor-kan lokal / tambah `integrity + crossorigin`, CSP `script-src` ketat.
- [ ] **P2-04 Upload & import:** verifikasi validasi MIME/ukuran foto kandidat, rename file, anti formula-injection (`=,+,-,@` prefix di export Excel) — Threat Model §36-37.
- [ ] **P2-05 Candidate locking:** pastikan edit/hapus kandidat ditolak saat `OPEN` (INV-06) — tambah test + policy check di `CandidateController`.
- [ ] **P2-06 Reverb auth:** pastikan channel monitoring `private-election.*` hanya untuk admin/operator, payload hanya `voted/eligible/rate` tanpa `candidate_id/voter` (§33).

---

## P3 — Enhancement (Setelah stabil)

- [ ] **P3-01** MFA untuk SUPER_ADMIN/ADMIN (TOTP).
- [ ] **P3-02** Anomaly detection: lonjakan vote, login gagal massal, dashboard alert.
- [ ] **P3-03** Dokumentasikan residual risk (korelasi timestamp/log, admin DB penuh) di README — jangan klaim anonimitas kriptografis.
- [ ] **P3-04** Update `progress.md` + `09_DEPLOYMENT`: checklist hari-H (§56), penutup (§57), incident response (§55).
- [ ] **P3-05** E2E print test: 8 kartu A4 portrait, QR 31mm ter-scan HP low-end.

---

## Definisi Selesai (DoD)

- [ ] Semua P0 merged + test hijau (`VotingServiceTest`, `WizardBatchTest`, `AuthorizationTest`, test concurrent double-vote).
- [ ] Tidak ada token plain di DB/backup/log/QR baru.
- [ ] Backup bisa restore di staging + terenkripsi + SuperAdmin only + tanpa traversal.
- [ ] Pen-test checklist §53-54 lolos (auth, IDOR, XSS, CSRF, rate-limit, headers, secrets scan).
- [ ] `progress.md` diupdate tanggal + status tiap phase.

## Urutan eksekusi saran

```
P0-01 + P0-06 (skema token) → P0-04 (error generik) → P0-05 (gates)
  → P0-02 + P0-03 (backup) → P1-01..P1-06 → P2 → P3
```
