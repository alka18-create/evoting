# UI/UX Specification — Sistem E-Voting Sekolah

**Dokumen:** 06 — UI/UX Specification  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD`, `02_ERD`, `03_SECURITY_Threat_Model`, `04_ARCHITECTURE`, `05_API_SPEC`

---

# 1. Tujuan

Dokumen ini mendefinisikan struktur antarmuka dan pengalaman pengguna aplikasi e-voting sekolah.

Target utama:

1. Admin dapat mengelola pemilihan dengan mudah.
2. Operator dapat membantu proses operasional tanpa memperoleh akses berlebihan.
3. Pemilih dapat memberikan satu suara dengan proses sederhana.
4. Sistem meminimalkan kesalahan pemilih.
5. UI tidak membocorkan hubungan identitas pemilih dengan pilihan suara.
6. Dashboard hasil mudah dipahami.
7. UI nyaman digunakan pada desktop, tablet, dan smartphone.

---

# 2. Prinsip UX

## 2.1 Simple

Pemilih tidak boleh menghadapi terlalu banyak langkah.

Flow utama:

```text
Login
  ↓
Verifikasi
  ↓
Lihat kandidat
  ↓
Pilih kandidat
  ↓
Konfirmasi
  ↓
Selesai
```

---

## 2.2 Clear

Setiap halaman harus memiliki:

- Judul yang jelas.
- Status yang jelas.
- Primary action yang jelas.
- Pesan error yang mudah dipahami.

---

## 2.3 Safe by Default

UI tidak boleh menyediakan pilihan yang dapat menyebabkan:

- Double vote.
- Perubahan vote.
- Voting pada election tertutup.
- Manipulasi status voter.
- Akses data yang tidak sesuai role.

---

## 2.4 Privacy by Design

Jangan menampilkan:

```text
Siswa A memilih Kandidat 01
```

di dashboard, audit log, monitoring, maupun halaman admin.

---

# 3. User Roles

| Role | UI Utama |
|---|---|
| SUPER_ADMIN | Semua fitur administrasi |
| ADMIN | Dashboard, election, candidate, voter, result |
| OPERATOR | Voter, credential, monitoring |
| VOTER | Voting flow |

---

# 4. Design System

Framework UI yang direkomendasikan:

```text
Tailwind CSS
```

Komponen dapat menggunakan:

```text
shadcn/ui
```

atau komponen Blade/Livewire yang konsisten.

---

# 5. Visual Style

Karakter:

```text
Modern
Clean
Trustworthy
School-friendly
Minimal
Accessible
```

Hindari:

- Dashboard terlalu padat.
- Animasi berlebihan.
- Warna status yang ambigu.
- Tombol destructive yang mudah salah klik.

---

# 6. Color Semantics

Warna tidak boleh menjadi satu-satunya indikator status.

Gunakan kombinasi:

```text
Color + Icon + Text
```

Contoh:

```text
● OPEN
```

bukan hanya lingkaran hijau tanpa teks.

Status:

| Status | Makna |
|---|---|
| Draft | Belum aktif |
| Scheduled | Terjadwal |
| Open | Sedang voting |
| Closed | Voting selesai |
| Archived | Diarsipkan |

---

# 7. Typography

Gunakan font UI yang mudah dibaca.

Rekomendasi:

```text
Inter
```

Hierarchy:

```text
H1 → halaman
H2 → section
H3 → card
Body → informasi
Caption → metadata
```

Ukuran teks minimum untuk informasi penting harus nyaman dibaca pada layar mobile.

---

# 8. Layout Admin

Struktur:

```text
┌─────────────────────────────────────────┐
│ Header                                  │
├──────────────┬──────────────────────────┤
│ Sidebar      │ Main Content             │
│              │                          │
│ Dashboard    │                          │
│ Elections    │                          │
│ Candidates   │                          │
│ Voters       │                          │
│ Monitoring   │                          │
│ Results      │                          │
│ Audit Logs   │                          │
│ Settings     │                          │
└──────────────┴──────────────────────────┘
```

Pada mobile:

```text
Header
  ↓
Content
  ↓
Bottom/Drawer Navigation
```

---

# 9. Admin Navigation

Menu:

```text
Dashboard
Pemilihan
Pemilih
Monitoring
Hasil
Audit Log
Pengaturan
```

Menu ditampilkan berdasarkan permission.

---

# 10. Admin Dashboard

Route:

```text
/admin
```

## Content

Card:

```text
Total Pemilihan
Pemilihan Aktif
Pemilihan Selesai
Total Pemilih
```

Active election card:

```text
PEMILIHAN KETUA OSIM 2026

Status: OPEN

842 Pemilih
671 Sudah memilih
171 Belum memilih

79,69%
```

CTA:

```text
Lihat Monitoring
```

---

# 11. Dashboard States

## No Election

```text
Belum ada pemilihan.

[ Buat Pemilihan ]
```

## Active Election

Tampilkan election aktif.

## Multiple Elections

Tampilkan daftar election dengan status.

---

# 12. Election List

Route:

```text
/admin/elections
```

Table:

| Nama | Status | Periode | Pemilih | Action |
|---|---|---|---:|---|
| Ketua OSIM 2026 | OPEN | 1 Sep | 842 | Detail |
| Ketua OSIS 2025 | CLOSED | 2 Sep 2025 | 790 | Hasil |

Action:

```text
Detail
Edit
Open
Close
Archive
```

Action hanya muncul jika state memungkinkan.

---

# 13. Create Election

Route:

```text
/admin/elections/create
```

Form:

```text
Nama Pemilihan
Deskripsi
Tanggal Mulai
Jam Mulai
Tanggal Selesai
Jam Selesai
```

CTA:

```text
Simpan Draft
```

Validation:

- Nama wajib.
- Start < End.
- Waktu valid.
- Tidak konflik dengan aturan election.

---

# 14. Election Detail

Route:

```text
/admin/elections/{id}
```

Header:

```text
Pemilihan Ketua OSIM 2026
[ OPEN ]
```

Tabs:

```text
Overview
Kandidat
Pemilih
Monitoring
Hasil
Export
```

---

# 15. Election Overview

Tampilkan:

```text
Status
Periode
Jumlah kandidat
Jumlah pemilih
Jumlah sudah memilih
Partisipasi
```

Untuk OPEN:

```text
[ Monitoring ]
[ Tutup Voting ]
```

Untuk DRAFT:

```text
[ Edit ]
[ Kelola Kandidat ]
[ Kelola Pemilih ]
[ Buka Voting ]
```

---

# 16. Election State UI

## DRAFT

Primary:

```text
Persiapkan Pemilihan
```

Checklist:

```text
✓ Informasi election
✓ Kandidat
✓ Pemilih
✓ Credential
```

Jika belum lengkap:

```text
Belum siap dibuka
```

---

## SCHEDULED

Tampilkan:

```text
Voting dimulai:
1 September 2026
07:00 WIB
```

---

## OPEN

Tampilkan:

```text
Voting sedang berlangsung
```

Action:

```text
Monitoring
Tutup Voting
```

---

## CLOSED

Tampilkan:

```text
Voting telah selesai
```

Action:

```text
Lihat Hasil
Export
```

---

# 17. Candidate Management

Route:

```text
/admin/elections/{id}/candidates
```

Card kandidat:

```text
┌────────────────────────────┐
│ Foto                       │
│                            │
│ 01                         │
│ Ahmad                      │
│                            │
│ Visi                       │
│ ...                        │
│                            │
│ [Edit] [Hapus]             │
└────────────────────────────┘
```

Pada election OPEN:

```text
Edit
Hapus
```

dinonaktifkan.

---

# 18. Candidate Form

Field:

```text
Nomor Urut
Nama
Foto
Visi
Misi
```

Misi dapat berupa list:

```text
+ Tambah Misi
```

Validation:

- Nomor unik dalam election.
- Nama wajib.
- Foto sesuai ukuran/type.
- XSS-safe text handling.

---

# 19. Candidate Photo

Recommendation:

```text
JPEG / PNG / WebP
```

Batasi:

```text
Maximum file size
Maximum dimensions
```

UI menampilkan preview sebelum upload.

---

# 20. Voter Management

Route:

```text
/admin/elections/{id}/voters
```

Top:

```text
842 Total
671 Sudah memilih
171 Belum memilih
```

Actions:

```text
[ Import Excel ]
[ Import CSV ]
[ Generate Credential ]
```

---

# 21. Voter Table

Columns:

```text
No
Student ID
Nama
Kelas
Status
```

Status:

```text
BELUM MEMILIH
SUDAH MEMILIH
```

Jangan tampilkan:

```text
Kandidat yang dipilih
Ballot ID
```

---

# 22. Import Voter UI

Flow:

```text
Upload File
    ↓
Preview
    ↓
Validation
    ↓
Confirm Import
    ↓
Processing
    ↓
Result
```

---

# 23. Import Preview

Tampilkan:

```text
Total rows: 842
Valid: 840
Invalid: 2
Duplicate: 0
```

Error example:

```text
Row 125
Student ID kosong
```

CTA:

```text
[ Batalkan ]
[ Import Data Valid ]
```

---

# 24. Import Result

Success:

```text
Import berhasil.

840 data berhasil
2 data gagal
```

CTA:

```text
Download Error Report
Kembali ke Pemilih
```

---

# 25. Credential Management

Route:

```text
/admin/elections/{id}/credentials
```

Tampilkan:

```text
Total credential
Sudah diterbitkan
Belum diterbitkan
Digunakan
```

Action:

```text
Generate Credential
Generate QR
Export Credential
```

---

# 26. Credential Security UX

Jangan menyediakan:

```text
Lihat semua PIN
```

Jika credential ditampilkan setelah generation:

```text
Credential hanya ditampilkan pada proses issuance.
Setelah meninggalkan halaman, credential tidak dapat ditampilkan kembali.
```

---

# 27. Monitoring Dashboard

Route:

```text
/admin/elections/{id}/monitoring
```

Header:

```text
PEMILIHAN KETUA OSIM 2026
LIVE MONITORING
```

Stats:

```text
TOTAL PEMILIH
842

SUDAH MEMILIH
671

BELUM MEMILIH
171

PARTISIPASI
79,69%
```

---

# 28. Monitoring Privacy

Selama election OPEN:

Jangan tampilkan:

```text
hasil kandidat
peringkat kandidat
suara kandidat
```

Monitoring hanya:

```text
Participation
```

---

# 29. Realtime Monitoring

Jika realtime aktif:

```text
● LIVE
```

Ketika update:

```text
671 → 672
```

Gunakan animasi ringan.

Jika connection lost:

```text
○ LIVE DISCONNECTED

Data terakhir diperbarui:
20:14:32
```

---

# 30. Group Monitoring

Jika mode kelas/TPS digunakan:

```text
XII IPA 1     31 / 40
XII IPA 2     34 / 42
XII IPS 1     28 / 38
```

Gunakan progress bar + angka.

Jangan menampilkan informasi pilihan kandidat.

---

# 31. Voting Login Page

Route:

```text
/vote
```

Tampilan sangat sederhana.

```text
PEMILIHAN KETUA OSIM 2026

Masukkan Token / PIN

[________________]

[ Mulai Voting ]
```

Optional:

```text
[ Scan QR Code ]
```

---

# 32. Voting Login UX

Pesan:

```text
Token hanya dapat digunakan satu kali.
```

Jangan tampilkan:

```text
Token milik siswa siapa
```

Jika gagal:

```text
Token tidak dapat digunakan.
Silakan periksa kembali atau hubungi panitia.
```

Jangan membedakan secara detail:

```text
token tidak ada
token sudah digunakan
token bukan untuk election ini
```

jika perbedaan tersebut membantu enumeration.

---

# 33. Voting Session

Setelah credential valid:

```text
PEMILIHAN KETUA OSIM 2026

Silakan pilih satu kandidat.
```

Tampilkan hanya kandidat election aktif.

---

# 34. Candidate Voting Card

Contoh:

```text
┌──────────────────────────────┐
│           FOTO               │
│                              │
│            01                │
│          Ahmad               │
│                              │
│ [ Lihat Visi & Misi ]        │
│                              │
│       ○ Pilih Kandidat       │
└──────────────────────────────┘
```

Card dapat dipilih dengan:

```text
click
keyboard
touch
```

---

# 35. Candidate Detail

Modal/page:

```text
Kandidat 01
Ahmad

VISI
...

MISI
1. ...
2. ...
3. ...

[ Tutup ]
[ Pilih Kandidat Ini ]
```

---

# 36. Selection State

Candidate terpilih:

```text
✓ Terpilih
```

Pastikan:

```text
aria-selected=true
```

jika menggunakan selectable component.

---

# 37. Single Choice

Voting menggunakan:

```text
Radio behavior
```

Bukan checkbox.

Hanya satu kandidat dapat dipilih.

---

# 38. Confirm Vote

Setelah pilih:

```text
[ Lanjutkan ]
```

Tampilkan confirmation:

```text
Konfirmasi Pilihan

Anda memilih:

01
Ahmad

Pilihan tidak dapat diubah setelah dikonfirmasi.

[ Kembali ]
[ Ya, Kirim Suara ]
```

Button destructive/important action harus jelas.

---

# 39. Final Submit UX

Saat submit:

```text
Mengirim suara...
```

Disable:

```text
Back
Submit
Refresh-sensitive action
```

Tujuan:

```text
mencegah double submit
```

Namun backend tetap wajib mencegah double vote.

---

# 40. Voting Success

```text
✓ SUARA BERHASIL DISIMPAN

Terima kasih telah menggunakan hak suara Anda.

Kode konfirmasi:
8F4A-2C91

Silakan simpan kode ini jika diperlukan.
```

Kode konfirmasi tidak boleh mengungkap pilihan kandidat.

CTA:

```text
Selesai
```

---

# 41. Voting Error States

## Session Expired

```text
Sesi voting telah berakhir.

Silakan login kembali.
```

## Election Closed

```text
Voting telah ditutup.
```

## Network Error

```text
Koneksi terganggu.

Jangan mengulangi proses secara berulang.
Silakan periksa koneksi dan coba lagi.
```

UI harus menggunakan idempotency untuk retry yang aman.

---

# 42. Prevent Back Navigation

Setelah successful vote:

- Voting session invalidated.
- Back navigation tidak boleh memungkinkan vote baru.
- Candidate selection tidak boleh dapat dikirim ulang sebagai vote kedua.

---

# 43. Result Dashboard

Route:

```text
/admin/elections/{id}/results
```

Hanya setelah CLOSED.

Header:

```text
HASIL PEMILIHAN
Pemilihan Ketua OSIM 2026
```

---

# 44. Result Summary

Cards:

```text
Total Pemilih
842

Total Suara
671

Tidak Memilih
171

Partisipasi
79,69%
```

---

# 45. Result Chart

Gunakan:

```text
Horizontal Bar Chart
```

Contoh:

```text
01 Ahmad     ███████████████ 42%
02 Budi      ███████████     31%
03 Citra     █████████       27%
```

Chart harus memiliki data table/accessible representation.

---

# 46. Result Table

| No | Kandidat | Suara | Persentase |
|---:|---|---:|---:|
| 1 | Ahmad | 282 | 42,03% |
| 2 | Budi | 208 | 31,00% |
| 3 | Citra | 181 | 26,97% |

---

# 47. Winner

Jika aturan election menentukan satu pemenang:

```text
PEMENANG

01
Ahmad

282 suara
42,03%
```

Jika seri:

```text
HASIL SERI

Diperlukan mekanisme penentuan pemenang sesuai aturan pemilihan.
```

Jangan membuat tie-breaker otomatis tanpa aturan PRD.

---

# 48. Export UI

Button:

```text
[ Export Excel ]
[ Export PDF ]
[ Export CSV ]
```

Jika queued:

```text
Export sedang diproses.
```

Setelah selesai:

```text
[ Download ]
```

---

# 49. Audit Log UI

Route:

```text
/admin/audit-logs
```

Filter:

```text
Tanggal
Actor
Action
Election
```

Table:

```text
Timestamp
Actor
Action
Resource
```

Jangan tampilkan pilihan suara.

---

# 50. Settings UI

Route:

```text
/admin/settings
```

Minimal:

```text
Nama Sekolah
Logo
Timezone
Nama sistem
```

Sensitive configuration tidak dikelola melalui UI biasa.

---

# 51. Responsive Design

Breakpoints:

```text
Mobile
Tablet
Desktop
Large Desktop
```

Voting page harus:

```text
mobile-first
```

karena kemungkinan digunakan melalui smartphone.

---

# 52. Mobile Voting Layout

Pada mobile:

```text
┌──────────────────────┐
│ Logo                 │
│ Pemilihan            │
├──────────────────────┤
│ Kandidat 01          │
│ Foto                 │
│ Nama                 │
│ [Pilih]              │
├──────────────────────┤
│ Kandidat 02          │
│ Foto                 │
│ Nama                 │
│ [Pilih]              │
├──────────────────────┤
│                      │
│ [ Lanjutkan ]        │
└──────────────────────┘
```

---

# 53. Accessibility

Target:

```text
WCAG 2.1 AA
```

Minimal:

- Keyboard navigation.
- Focus indicator.
- Semantic HTML.
- Label form.
- Error association.
- Alt text.
- Sufficient contrast.
- Screen-reader friendly status.
- Tidak bergantung pada warna.
- Touch target cukup besar.

---

# 54. Keyboard Navigation

Admin dan voting:

```text
Tab
Shift + Tab
Enter
Space
Escape
```

harus berfungsi secara logis.

Voting candidate selection harus dapat dilakukan tanpa mouse.

---

# 55. Focus Management

Setelah modal dibuka:

```text
focus → modal
```

Setelah modal ditutup:

```text
focus → trigger
```

Setelah validation error:

```text
focus → first invalid field
```

---

# 56. Loading States

Setiap asynchronous action harus memiliki state:

```text
Idle
Loading
Success
Error
```

Contoh:

```text
[ Menyimpan... ]
```

bukan tombol tetap aktif.

---

# 57. Empty States

Contoh candidate:

```text
Belum ada kandidat.

Tambahkan kandidat untuk melanjutkan.

[ Tambah Kandidat ]
```

Contoh voter:

```text
Belum ada pemilih.

Import daftar siswa terlebih dahulu.
```

---

# 58. Destructive Action Confirmation

Untuk:

```text
Close Election
Delete Candidate
Archive Election
Regenerate Credential
```

gunakan confirmation dialog.

Contoh:

```text
Tutup voting?

Setelah ditutup, pemilih tidak dapat memberikan suara lagi.

[ Batal ]
[ Ya, Tutup Voting ]
```

---

# 59. Critical Action Rules

Action:

```text
Open Election
Close Election
Generate Credentials
Regenerate Credentials
```

harus:

- Memiliki permission.
- Memiliki server-side validation.
- Memiliki audit log.
- Memiliki confirmation jika destructive/irreversible.

---

# 60. Toast / Notification

Gunakan toast untuk:

```text
Save successful
Import completed
Export ready
Monitoring connection
```

Jangan gunakan toast sebagai satu-satunya cara menampilkan error penting.

---

# 61. Error Message Style

Gunakan bahasa Indonesia yang jelas.

Buruk:

```text
SQLSTATE[23000]
```

Baik:

```text
Data tidak dapat disimpan karena terdapat data yang sama.
```

Detail teknis masuk log server.

---

# 62. Security UX Rules

UI tidak boleh:

- Menampilkan credential plaintext setelah issuance tanpa alasan.
- Menampilkan voter → candidate relationship.
- Menampilkan result sebelum election CLOSED.
- Menampilkan internal database ID jika tidak diperlukan.
- Mengandalkan hidden button sebagai authorization.
- Menganggap disable button sebagai double-vote protection.

---

# 63. Admin Permission UX

Jika user tidak memiliki permission:

Jangan hanya:

```text
hide button
```

Server tetap harus menolak request.

UI dapat menampilkan:

```text
Anda tidak memiliki izin untuk melakukan tindakan ini.
```

---

# 64. Session Timeout UX

Admin:

```text
Sesi akan berakhir dalam 2 menit.
[ Tetap Login ]
```

Voter:

```text
Sesi voting hampir berakhir.
```

Jangan tampilkan credential/token pada timeout modal.

---

# 65. Voting Timer

Timer boleh digunakan jika diperlukan.

Contoh:

```text
Sisa sesi: 04:32
```

Timer hanya indikator UX.

Backend tetap menjadi sumber kebenaran.

---

# 66. Browser Refresh

Pada halaman voting:

- Refresh tidak boleh menghasilkan vote baru.
- Session state tetap aman.
- Setelah successful vote, refresh tidak boleh membuat vote kedua.

---

# 67. Browser History

Setelah vote berhasil:

```text
history navigation
```

tidak boleh memberikan jalan untuk submit ulang.

---

# 68. Print UX

Result page dapat menyediakan:

```text
Print
```

Print layout harus menyembunyikan:

```text
navigation
buttons
interactive controls
```

dan hanya mencetak hasil final.

---

# 69. Dashboard Realtime Fallback

Jika realtime gagal:

```text
Realtime unavailable
[ Refresh Data ]
```

Jangan membuat dashboard menampilkan data lama tanpa indikator.

---

# 70. Data Freshness Indicator

Monitoring:

```text
● LIVE
Updated just now
```

atau:

```text
○ Last updated 15 seconds ago
```

---

# 71. Localization

Default:

```text
Bahasa Indonesia
```

Format:

```text
Tanggal: 1 September 2026
Waktu: 07:00 WIB
```

Timezone:

```text
Asia/Jakarta
```

---

# 72. Confirmation Copy

Gunakan wording yang tegas:

```text
Pilihan Anda akan dikirim sebagai suara final.
Setelah dikonfirmasi, pilihan tidak dapat diubah.
```

Hindari wording ambigu:

```text
Apakah Anda yakin?
```

tanpa menjelaskan konsekuensi.

---

# 73. Voting Completion

Setelah sukses:

```text
Voting session invalidated.
```

UI:

```text
Voting selesai.
```

Tidak ada:

```text
Vote again
Change vote
```

---

# 74. UI Routes

## Public / Voter

```text
/vote
/vote/session
/vote/candidates
/vote/confirm
/vote/success
```

## Admin

```text
/admin
/admin/elections
/admin/elections/create
/admin/elections/{id}
/admin/elections/{id}/candidates
/admin/elections/{id}/voters
/admin/elections/{id}/credentials
/admin/elections/{id}/monitoring
/admin/elections/{id}/results
/admin/audit-logs
/admin/settings
```

---

# 75. Component Inventory

## Layout

```text
AdminLayout
VotingLayout
AuthLayout
```

## Navigation

```text
Sidebar
Header
Breadcrumb
MobileNavigation
```

## Data

```text
DataTable
Pagination
FilterBar
SearchInput
```

## Election

```text
ElectionCard
ElectionStatusBadge
ElectionStateActions
ElectionChecklist
```

## Candidate

```text
CandidateCard
CandidateForm
CandidateDetail
CandidateSelector
```

## Voting

```text
VotingCredentialForm
VotingSessionHeader
CandidateSelector
VoteConfirmation
VoteSuccess
```

## Dashboard

```text
StatCard
ProgressCard
ParticipationChart
ResultChart
```

## Feedback

```text
Alert
Toast
Modal
ConfirmDialog
LoadingState
EmptyState
ErrorState
```

---

# 76. Suggested Frontend Architecture

Jika menggunakan Laravel + Livewire:

```text
Blade
  ↓
Livewire Components
  ↓
Actions / Services
  ↓
Domain
```

Jangan menaruh business logic voting kompleks di Blade/JavaScript.

---

# 77. State Management

Untuk MVP, hindari global state kompleks.

Gunakan:

```text
Server-side state
+
Livewire component state
```

Untuk voting:

```text
Voting Session
Candidate Selection
Confirmation State
```

Client-side state hanya untuk UX.

---

# 78. Voting Security Boundary

Browser dapat mengirim:

```text
candidate_id
```

Browser tidak boleh menentukan:

```text
voter_id
election_id
voting_status
ballot_id
```

Server mengambil context dari authenticated voting session.

---

# 79. Anti-Tampering UX

Jika user mengubah request melalui browser:

```text
candidate_id = candidate dari election lain
```

server harus menolak.

UI bukan security boundary.

---

# 80. UX Acceptance Criteria

## Admin

- [ ] Admin dapat membuat election.
- [ ] Admin dapat melihat status election.
- [ ] Admin dapat mengelola kandidat.
- [ ] Admin dapat import voter.
- [ ] Admin dapat generate credential.
- [ ] Admin dapat monitoring partisipasi.
- [ ] Admin dapat close election.
- [ ] Admin dapat melihat hasil setelah close.
- [ ] Admin dapat export hasil.

## Operator

- [ ] Operator hanya melihat fitur yang diizinkan.
- [ ] Operator tidak dapat mengubah hasil.
- [ ] Operator tidak dapat melihat hubungan voter → candidate.

## Voter

- [ ] Voter dapat login.
- [ ] Voter hanya melihat election yang sesuai.
- [ ] Voter dapat memilih satu kandidat.
- [ ] Voter dapat melihat confirmation.
- [ ] Voter tidak dapat mengubah suara setelah submit.
- [ ] Voter mendapatkan confirmation setelah berhasil.

---

# 81. Usability Test Scenarios

Sebelum production, lakukan test:

### Scenario 1

Siswa login → memilih kandidat → submit.

Expected:

```text
Success
```

### Scenario 2

Siswa mencoba submit dua kali.

Expected:

```text
Hanya satu vote berhasil.
```

### Scenario 3

Credential sudah digunakan.

Expected:

```text
Voting ditolak.
```

### Scenario 4

Election ditutup saat voter mencoba vote.

Expected:

```text
Vote ditolak.
```

### Scenario 5

Admin mencoba melihat result saat OPEN.

Expected:

```text
Result tidak tersedia.
```

### Scenario 6

Operator mencoba mengakses fitur admin.

Expected:

```text
403
```

---

# 82. Performance UX

Target:

```text
Initial page load: < 2s
Candidate page interaction: < 300ms
Admin dashboard: < 2s
```

Target harus diuji pada perangkat dan jaringan yang realistis untuk sekolah.

---

# 83. Low-Bandwidth Consideration

Karena aplikasi dapat digunakan melalui jaringan sekolah/mobile:

- Optimalkan foto kandidat.
- Gunakan lazy loading.
- Hindari bundle JavaScript besar.
- Compress assets.
- Jangan autoplay video.
- Gunakan pagination untuk tabel besar.

---

# 84. Candidate Image Optimization

Sediakan:

```text
thumbnail
medium
```

Jangan mengirim foto original besar ke mobile jika tidak diperlukan.

---

# 85. Progressive Enhancement

Voting dasar harus tetap sederhana.

Jangan membuat keberhasilan voting bergantung pada animasi atau visual effect.

Primary requirement:

```text
candidate selection
→ confirmation
→ successful submission
```

---

# 86. Security-Critical UX Checklist

Sebelum release:

- [ ] Tidak ada voter → candidate mapping di UI.
- [ ] Result tersembunyi selama OPEN.
- [ ] Candidate hanya dapat dipilih sekali.
- [ ] Submit button disabled saat request berlangsung.
- [ ] Backend tetap melindungi double vote.
- [ ] Credential tidak tampil di URL.
- [ ] Credential tidak masuk browser localStorage jika tidak diperlukan.
- [ ] Tidak ada sensitive data di query string.
- [ ] Error message tidak memungkinkan enumeration.
- [ ] Admin UI mengikuti permission.
- [ ] Confirmation untuk final vote jelas.
- [ ] Successful vote mengakhiri voting session.

---

# 87. Definition of Done — UI/UX

UI dianggap siap implementasi jika:

- [ ] Semua route utama sudah didefinisikan.
- [ ] Semua role memiliki navigation yang jelas.
- [ ] Voting flow sudah lengkap.
- [ ] Error/loading/empty state tersedia.
- [ ] Responsive layout ditentukan.
- [ ] Accessibility requirement ditentukan.
- [ ] Security boundary jelas.
- [ ] Result visibility rule diterapkan.
- [ ] Destructive actions memiliki confirmation.
- [ ] UI tidak membocorkan voter → candidate relationship.

---

# 88. Next Document

Dokumen berikutnya:

```text
07_USER_FLOWS.md
```

Dokumen tersebut akan merinci flow operasional end-to-end dalam bentuk:

```text
Admin
  ↓
Create Election
  ↓
Add Candidates
  ↓
Import Voters
  ↓
Generate Credentials
  ↓
Open Election
  ↓
Voter Voting
  ↓
Close Election
  ↓
Calculate Results
  ↓
Export / Print
```

Flow juga akan mencakup exception path, error state, authorization boundary, dan security checkpoint pada setiap langkah.
