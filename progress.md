# Progress E-Voting - 24 Agustus 2026

## Yang Dikerjakan Hari Ini

### 1. Phase 2 - Administrasi (Lanjutan)
- **Export Excel** (`maatwebsite/excel` ^3.1)
  - `app/Exports/ElectionResultExport.php` — hasil pemilihan
  - `app/Exports/VoterListExport.php` — daftar pemilih
  - Route: `admin.results.export-excel`, `admin.voters.export`

- **Export PDF** (`barryvdh/laravel-dompdf` ^3.0)
  - `resources/views/admin/results/pdf/show.blade.php` — template PDF
  - Route: `admin.results.export-pdf`

- **Dashboard Enhanced**
  - Stats cards: total pemilihan, aktif, pemilih, suara
  - Partisipasi: sudah/belum memilih, % partisipasi
  - Chart.js: donut (status pemilihan), bar (partisipasi per kelas)
  - `app/Livewire/Admin/DashboardStats.php`

### 2. Phase 3 - Fitur Lanjutan

#### QR Code
- Package: `simplesoftwareio/simple-qrcode` ^4.2
- QR berisi JSON: `{student_id, token, election_id}`
- Ditambahkan ke:
  - `resources/views/admin/tokens/print-card.blade.php` (single)
  - `resources/views/admin/tokens/print-cards.blade.php` (bulk)

#### QR Scanner
- **File baru:**
  - `app/Http/Controllers/Admin/ScanController.php`
  - `resources/views/admin/backups/scan.blade.php`
- Library: `html5-qrcode` (CDN)
- Fitur: enumerate kamera, dropdown pilih kamera, error handling detail
- Route: `/admin/scan`

#### Realtime Monitoring
- **File baru:**
  - `app/Events/VoteCasted.php` — broadcast event
  - `app/Livewire/Admin/RealtimeMonitor.php` — component realtime
  - `resources/views/livewire/admin/realtime-monitor.blade.php` — UI
- Cara kerja:
  - `wire:poll.3s` — refresh setiap 3 detik (fallback)
  - Echo listener → `Livewire.dispatch('vote-casted')` — refresh instan
  - Toast notification saat suara masuk
- VotingController: broadcast `VoteCasted` setelah voting

#### Backup Database
- Package: `spatie/laravel-backup` ^9.0
- **File baru:**
  - `app/Http/Controllers/Admin/BackupController.php`
  - `resources/views/admin/backups/index.blade.php`
  - `config/backup.php`
- Backup menggunakan PHP pure (pg_dump tidak tersedia di container)
- Export semua tabel ke SQL INSERT statements
- Route: `/admin/backups` (SuperAdmin only)

### 3. UI/UX Improvements

#### Sidebar Fixed + Toggle
- Sidebar `fixed top-0 left-0 h-full` — tetap saat scroll
- Tombol toggle di sidebar header + top bar
- Animasi transisi 0.3s
- State tersimpan di localStorage

#### Voting Page Redesign
- Header gradient ungu dengan branding E-Voting
- Identitas pemilih (nama + kelas) di pojok kanan atas
- Kandidat card besar dengan foto penuh
- Visi + Misi kandidat dengan icon dan label berwarna
- Modal konfirmasi modern dengan backdrop blur

#### Voted Page Redesign
- Header gradient oranye dengan icon jam
- 3 informasi (aman, rahasia, hasil) dengan icon
- Button gradient ungu dengan icon arrow

### 4. Bug Fixes
- PHP version requirement: 8.4 → 8.2 (diseuaikan dengan system)
- Pest: 4.x → 3.x
- PHPUnit: 12 → 11
- Redis → File cache (`CACHE_STORE=file`)
- DomPDF import alias
- `candidates.number` → `candidates.candidate_number`
- Spatie backup: tambah key `notifiable` di config
- Livewire `#[On('vote.casted')]` → `#[On('vote-casted')]` (browser events pakai dash)

## File yang Diubah/Dibuat Hari Ini

### Baru
```
app/Exports/ElectionResultExport.php
app/Exports/VoterListExport.php
app/Events/VoteCasted.php
app/Http/Controllers/Admin/BackupController.php
app/Http/Controllers/Admin/ScanController.php
app/Livewire/Admin/RealtimeMonitor.php
resources/views/admin/backups/index.blade.php
resources/views/admin/backups/scan.blade.php
resources/views/admin/results/pdf/show.blade.php
resources/views/livewire/admin/realtime-monitor.blade.php
config/backup.php
INSTALL_PHASE3.md
```

### Diubah
```
app/Http/Controllers/Admin/ElectionController.php
app/Http/Controllers/Admin/ResultController.php (tambah exportExcel, exportPdf)
app/Http/Controllers/Admin/VoterController.php (tambah export)
app/Http/Controllers/Auth/VoterLoginController.php
app/Http/Controllers/Voter/VotingController.php (tambah broadcast)
app/Livewire/Admin/DashboardStats.php (tambah #[On])
app/Models/Voter.php (public)
resources/views/admin/dashboard.blade.php (charts + realtime)
resources/views/admin/results/show.blade.php (tambah export buttons)
resources/views/admin/voters/index.blade.php (tambah export button)
resources/views/admin/tokens/print-card.blade.php (tambah QR)
resources/views/admin/tokens/print-cards.blade.php (tambah QR)
resources/views/components/layouts/admin.blade.php (sidebar fixed + toggle)
resources/views/voter/index.blade.php (redesign)
resources/views/voter/voted.blade.php (redesign)
routes/web.php (tambah routes backup, scan, export)
composer.json (tambah packages)
```

## Phase 4 — Multi-Voting Wizard Batch (Opsi C) — 2 Sep 2026
- **Schema** — `organizations`, `voting_events`, `voting_event_voters`, alter `elections` (+voting_event_id, +organization_id, effectiveStartsAt/EndsAt)
- **1 Token untuk semua** — `VotingEventService::assignAllActiveVoters` + `generateTokens` (retry unique), `VotingEventTokenController` per event
- **Wizard Batch** — `WizardController::step/storeStep/review/submit` + `VotingService::castVotesBatch` atomik all-or-nothing, session `wizard.selections`, publish per election `CLOSED` tetap
- **Auth Event** — `VoterLoginController` login `voting_event_id`, `VoterGuard` clear event session, view `voter-login` event dropdown, routes `vote.wizard.*`
- **Admin** — `OrganizationController`, `VotingEventController` (schedule/open/close/archive), `ElectionController` support event/org, views + sidebar Event/Organisasi, token print `voting_event_id`
- **QR/Scan** — `ScanController` support `voting_event_id` + legacy `election_id`, scan view JS dual payload
- **Monitoring per Organisasi** — `RealtimeMonitor` `perOrganization` cards + filter `votingEventId`, `voting-events/show` embed live monitor
- **Results** — filter `voting_event_id` & `organization_id`, badge Event/Organisasi
- **Legacy** — migrasi `000016` seed Umum + Legacy Event, Pest `WizardBatchTest`

## Redesign Kartu Pemilih (Single & Bulk) — 2 Sep 2026
- **Desain Modern Pixel-Perfect**:
  - Header: Deep Royal Blue Gradient (`#102a9c` → `#1e40af` → `#2563eb`), ikon kotak suara + checkmark, kicker KARTU PEMILIH, judul event/pemilihan dinamis, subjudul instansi & tahun, badge `✔ SEKALI PAKAI` + teks panduan.
  - Body:
    - Avatar bulat inisial nama (`#e0e7ff` / `#3730a3`), nama pemilih, kelas & NIS.
    - 2 Data Box: NIS dengan ikon ID Card & KELAS dengan ikon Toga.
    - Banner TOKEN PEMILIH: Ikon gembok + 6 kotak digit putih terpisah tebal font monospace.
    - Panel QR Code: Ikon scanner `[ ⛶ ] SCAN UNTUK VERIFIKASI`, QR Code ukuran besar & tajam, footer panduan scanner.
  - Footer: Status kartu **AKTIF** (perisai hijau), 3 langkah cara penggunaan (`1 Scan QR › 2 Token › 3 Pilih`), dan catatan keamanan kerahasiaan token.
  - Print CSS: Dukungan `-webkit-print-color-adjust: exact`, page breaks otomatis 4 kartu per lembar A4 (bulk) dan presisi single card.

- **File yang Diperbarui**:
  - `resources/views/admin/voting-events/tokens/print-card.blade.php` (Single Card Event)
  - `resources/views/admin/voting-events/tokens/print-cards.blade.php` (Bulk Cards Event)
  - `resources/views/admin/tokens/print-card.blade.php` (Single Card Pemilihan)
  - `resources/views/admin/tokens/print-cards.blade.php` (Bulk Cards Pemilihan)

## Perbaikan Cetak Kartu Pemilih (Bulk & PDF Print Engine) — 2 Sep 2026

### Print Layout A4 Portrait 8 Kartu (2×4)
- **Orientasi**: A4 portrait, margin `5mm 5mm 4mm 5mm`
- **Grid Layout (Print Engine Compatible)**: Menggunakan `display: flex; flex-wrap: wrap; justify-content: space-between;`
- **Dimensi Kartu**: `97.5mm lebar × 67mm tinggi`
- **Kapasitas**: **8 kartu per lembar A4** (2 kolom × 4 baris)
- **Page Break**: `nth-child(8n)` — pemisahan halaman otomatis tiap 8 kartu

### Ukuran Elemen & Tipografi Cetak
| Elemen | Ukuran Print |
|--------|--------------|
| Header Title | 8px (Multi-line wrap, tanpa pemotongan `...`) |
| Header Kicker & Subtitle | 5.5px |
| Badge SEKALI PAKAI | 5.5px pill badge hijau |
| Nama Pemilih | **10px - 10.5px** (Bold, kontras tajam) |
| Meta Kelas • NIS | 6.8px - 7px bold |
| Box NIS & KELAS | Padding 1.2mm × 1.8mm, Label 5.2px, Value **8px** |
| Panel Token Pemilih | Gradient biru, 6 kotak digit putih (10.5mm × 14.5mm, font 9px JetBrains Mono) |
| Panel QR Code (Kanan) | QR Code **24mm × 24mm**, header 5.8px, subtext 4.8px, hint box 4.5px |
| Kotak Status & Cara Pakai | Terletak di bawah panel token dalam kolom kiri (100% lebar sejajar) |
| Langkah Cara Pakai | **Susun ke bawah (vertikal)**: ❶ Scan QR Code › ❷ Masukkan Token › ❸ Pilih Suara |
| Security Note | 4.8px (Peringatan kerahasiaan token) |

### Perbaikan Teknis & Bug Fixes
- `resources/views/admin/voting-events/tokens/print-cards.blade.php`:
  - **Penataan Kotak Status & Cara Pakai**: Dipindahkan ke dalam `body-left` sehingga lebarnya sejajar persis dengan kotak token pemilih dan NIS/Kelas.
  - **Langkah Cara Pakai Vertikal**: Mengubah susunan 3 langkah cara pakai dari horizontal menjadi vertikal berurutan dengan badge nomor bulat biru.
  - **Fix Bug Penumpukan Kartu (Card Overlap/Nesting)**: Memperbaiki tag penutup `</div>` untuk `.card-wrapper` yang hilang sebelum `@endforeach`, serta mengganti CSS grid cetak dengan `flex-wrap` yang kompatibel dengan browser print engine.
  - **Eliminasi Space Kosong PDF Print (3 Sep 2026)**:
    - Menyelaraskan proporsi elemen cetak agar mengisi penuh kartu 67mm secara presisi (tinggi kolom kiri dan kanan seimbang).
    - Memperbesar QR Code di PDF dari `24mm` menjadi **`31mm × 31mm`** yang tajam, kontras, dan mudah dipindai kamera.
    - Menyesuaikan font size dan padding (`body-left` & `body-right` menggunakan `justify-content: space-between !important;` dengan padding proporsional).
    - Nama pemilih diperjelas ke **11px Bold**, digit box token **12mm × 16.5mm**, dan catatan keamanan merapat rapi ke batas bawah kartu tanpa menyisakan ruang kosong (space kosong) sama sekali.
  - **Fix Teks "TOKEN PEMILIH" Menabrak Garis Pembatas (3 Sep 2026)**:
    - Menambahkan `flex-shrink: 0` pada `.token-info` agar tidak terkompresi oleh kotak digit token.
    - Menyesuaikan proporsi ikon gembok (`22px`), tipografi judul (`8.5px` dengan `letter-spacing: 0.3px`), margin pembatas, dan lebar digit box (`18px × 25px` di layar, `10.5px × 15px` di print) sehingga teks memiliki jarak aman (clearance) dan tidak menabrak garis putus-putus.

- `resources/views/admin/tokens/print-cards.blade.php`:
  - Menerapkan seluruh penyelarasan tata letak, proporsi seimbang, eliminasi space kosong, dan perbaikan divider token yang sama persis untuk pemilihan standalone.

## Aplikasi Sudah Lengkap

| Phase | Fitur | Status |
|-------|-------|--------|
| Phase 1 | Core (auth, election, candidates, voting, results) | ✅ |
| Phase 2 | Export Excel/PDF, dashboard charts, role management | ✅ |
| Phase 3 | QR Code, QR Scanner, Realtime Monitoring, Backup | ✅ |
| Phase 4 | Multi-Voting Wizard Batch (1 Token → N Organisasi, per-org monitoring) | ✅ |
| UI/UX | Redesign Kartu Pemilih Presisi Gambar Referensi (Single & Bulk) | ✅ |
| P0 Hardening | Token hash+HMAC, entropy 8 char+expiry, error generik, gates, backup SuperAdmin+traversal+Postgres | ✅ 4 Sep 2026 |
| P1 | QR v2 opaque, headers/CSP, rate-limit ganda, unique index+retry, audit tanpa IP ballot+prune, PHP 8.2+secrets | ✅ 4 Sep 2026 |
| P2 | Wizard N+1, enum hasil, QR vendor lokal+SRI, sanitasi Excel/upload/import, kunci kandidat, Reverb private | ✅ 4 Sep 2026 |
| P3 | MFA TOTP, anomali dashboard, risiko residual, E2E print bulk | ✅ 4 Sep 2026 (MFA/anomali/test di bawah) |

## P3 — 4 Sep 2026

- **MFA TOTP admin** — `users.two_factor_*` (secret+recovery terenkripsi), `App\Support\Totp` tanpa dep baru, setup di `/admin/profile/mfa` (QR otpauth + 8 recovery codes sekali tampil), challenge `/mfa/challenge` + middleware `mfa` di grup admin.
- **Anomali dashboard** — `AnomalyService` (lonjakan ballot 5 mnt, `VOTER_LOGIN_FAILED` massal, `MFA_FAILED`) + ambang `config/monitoring.php`, widget alert di `/admin`.
- **Audit prune** — `php artisan audit:prune [--dry-run]`, default retensi 1095 hari (`AUDIT_RETENTION_DAYS`).
- **E2E print bulk (P3-05)** — `tests/Feature/VotingEventPrintBulkTest.php`: issue 8 token via service, GET print-bulk, assert 8 kartu + QR v2 tanpa NIS + digit per kartu.
- **Verifikasi suite 4 Sep 2026** — `php artisan test`: **91 passed (301 assertions)**. Termasuk perbaikan `TokenSystemTest` (alur wizard-event + error generik + expiry), `ElectionPolicy` (create/update/open/close Admin+; Operator view-only), dan route kembali print-bulk legacy.

## Checklist Hari-H (ringkas, detail §56 Threat Model)

- [ ] Backup DB + uji restore staging; catat hash backup.
- [ ] Verifikasi konfigurasi event/election/kandidat/voter/token-exp; kunci kandidat (DRAFT→SCHEDULED→OPEN).
- [ ] HTTPS aktif + `SESSION_SECURE_COOKIE=true`; headers hijau; Reverb auth OK.
- [ ] Rate-limit aktif; allowlist IP TPS bila perlu; dashboard anomali dipantau.
- [ ] Kartu cetak v2 ter-scan HP low-end; QR legacy tetap didukung transisi.
- [ ] Akun admin MFA aktif; password default diganti; `.env` tidak ter-commit.
- [ ] Penutup: LOCK election → tolak vote baru → backup → rekonsiliasi ballot vs partisipasi → publish hasil → audit closing.

## Akses

| Halaman | URL |
|---------|-----|
| Admin Dashboard | `/admin` |
| Event Pemilihan | `/admin/voting-events` |
| Organisasi | `/admin/organizations` |
| Token Event | `/admin/voting-events/{id}/tokens` |
| Cetak Satuan Event | `/admin/voting-events/{id}/tokens/{voterId}/print` |
| Cetak Massal Event | `/admin/voting-events/{id}/tokens/print-bulk` |
| Voter Login (Wizard) | `/vote/login` → `/vote/wizard/step/1` |
| QR Scanner | `/admin/scan` |
| Backup | `/admin/backups` (SuperAdmin) |
| Hasil (filter Event/Org) | `/admin/results` |
| MFA setup | `/admin/profile/mfa` |
| MFA challenge | `/mfa/challenge` |

## Snapshot Progres — 4 Sep 2026 (sore)

- **Git**: repo diinisialisasi (`main`), commit `c455e49` — "P0-P3 hardening: token hash, QR v2, MFA, audit, suite 91 passed" (393 file, tree bersih). Ter-ignore: `.env`, `vendor/`, `node_modules/`, backup SQL, log, foto upload.
- **Ringkasan kerja hari ini**:
  - P0: token hash HMAC + entropy 8 char + expiry, error login generik, Gates backup/hasil/scan, backup anti-traversal + Postgres + SuperAdmin-only.
  - P1: QR v2 opaque, security headers/CSP, rate-limit ganda, unique index + retry, audit tanpa IP ballot + `audit:prune`, PHP 8.2 + secrets.
  - P2: wizard N+1, enum hasil, QR vendor lokal + SRI, sanitasi Excel/upload/import, kunci kandidat, Reverb private channel.
  - P3: MFA TOTP, alert anomali dashboard, risiko residual di README, checklist hari-H, E2E print bulk.
- **Suite: 91 passed (301 assertions)** — termasuk perbaikan `TokenSystemTest`, pengetatan `ElectionPolicy` (Operator view-only), dan route kembali print-bulk legacy.
- **Langkah berikut**: uji manual browser (login voter, scan QR v1/v2 di HP, enrol MFA, `npm run build`), lalu uji lapangan.

## P0 Lanjutan (audit) — 9 Sep 2026

- **C1**: `castVote()` wajib `voterId` + filter eligibility; controller legacy cek kepemilikan.
- **C2**: `VoterUserProvider` hanya voter aktif; kedua service tolak voter nonaktif.
- **`VotingGate` baru**: aturan event/election terpusat, dipakai login + voting + wizard.
- **Wizard**: re-validasi token/expiry/is_active/event per-request.
- **`audit:prune` terjadwal harian**; migrasi `000021` drop kolom `token` plaintext + hapus fallback; test regresi C1/C2.

## P1 Lanjutan (audit) — 9 Sep 2026

- Verifikasi: P1-01 (QR v2 opaque), headers/CSP, rate-limit ganda, retry+unique index, audit tanpa IP ballot, PHP 8.2 + CI audit — sudah ada di kode.
- **Baru**:
  - P1-03 lockout progresif: ≥10 gagal/IP+NIS dalam 15 mnt → 429 + audit `VOTER_LOGIN_LOCKED` (`VoterLoginController`, `config/auth.php`, env `VOTER_LOCKOUT_*`).
  - P1-02 idle-timeout sesi voter 30 mnt: middleware `voter.timeout` di grup vote (`config/session.php`, env `VOTER_SESSION_LIFETIME`).
  - Test: lockout 429 + timeout 31 mnt di `TokenSystemTest`.
- **Belum dijalankan di sini** (tanpa PHP): `composer lint`, `composer analyse`, `composer test`.

