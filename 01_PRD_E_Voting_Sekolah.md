# PRD — Sistem E-Voting Sekolah

**Versi:** 1.0  
**Status:** Draft  
**Platform:** Web Application  
**Target:** Sekolah/Institusi Pendidikan  
**Stack:** Laravel + PostgreSQL + Livewire + Tailwind CSS + Redis + Reverb + Chart.js

---

## 1. Ringkasan Produk

Sistem E-Voting adalah aplikasi berbasis web untuk menyelenggarakan pemilihan secara elektronik, mulai dari pembuatan pemilihan, pengelolaan kandidat dan pemilih, proses pemberian suara, monitoring partisipasi, hingga penghitungan dan publikasi hasil.

Sistem dirancang agar:

- Satu pemilih hanya dapat memberikan satu suara.
- Identitas pemilih tidak tersimpan bersama pilihan kandidat.
- Suara tidak dapat diubah setelah dikonfirmasi.
- Hasil kandidat tidak ditampilkan sebelum pemilihan ditutup.
- Aktivitas administratif dapat diaudit.
- Hasil dapat diekspor dan dicetak.

---

## 2. Tujuan Produk

### Tujuan utama

1. Menyediakan sistem pemilihan elektronik yang mudah digunakan siswa.
2. Memudahkan admin mengelola pemilihan.
3. Mencegah pemilih memberikan suara lebih dari satu kali.
4. Menjaga kerahasiaan pilihan pemilih.
5. Menghasilkan penghitungan suara secara otomatis.
6. Menyediakan monitoring partisipasi secara realtime.
7. Menyediakan audit trail untuk aktivitas administratif.

### Non-goals

Versi pertama tidak ditujukan untuk:

- Pemilu pemerintahan.
- Pemilihan dengan persyaratan legal tingkat negara.
- Sistem yang menjamin anonimitas kriptografis tingkat pemilu nasional.
- Integrasi dengan sistem kependudukan pemerintah.

Target awal adalah pemilihan internal sekolah/organisasi.

---

## 3. Aktor Sistem

### 3.1 Super Admin

- Mengelola administrator.
- Mengelola konfigurasi sistem.
- Melihat seluruh pemilihan.
- Mengakses audit log.
- Melakukan backup/maintenance.

### 3.2 Admin Pemilihan

- Membuat pemilihan.
- Mengatur periode voting.
- Mengelola kandidat.
- Mengelola pemilih.
- Import pemilih.
- Generate credential.
- Membuka/menutup pemilihan.
- Monitoring partisipasi.
- Melihat hasil setelah voting ditutup.
- Export hasil.

### 3.3 Operator TPS/Kelas

Role opsional.

- Membantu proses verifikasi pemilih.
- Scan QR.
- Melihat status pemilih.
- Membantu pemilih masuk ke sistem.

Operator tidak boleh melihat pilihan kandidat pemilih.

### 3.4 Pemilih

- Login/verifikasi.
- Melihat informasi pemilihan.
- Melihat kandidat.
- Memilih satu kandidat.
- Konfirmasi suara.
- Mendapatkan bukti bahwa suara berhasil diterima.

Pemilih tidak dapat mengubah suara setelah konfirmasi, memberikan suara kedua, atau melihat hasil sebelum voting ditutup.

---

## 4. Alur Utama

```text
Admin membuat pemilihan
        ↓
Admin memasukkan kandidat
        ↓
Admin import daftar pemilih
        ↓
Sistem membuat credential
        ↓
Admin membuka voting
        ↓
Pemilih melakukan verifikasi
        ↓
Sistem mengecek hak memilih
        ↓
Pemilih melihat kandidat
        ↓
Pemilih memilih satu kandidat
        ↓
Konfirmasi
        ↓
Sistem membuat anonymous ballot
        ↓
Credential ditandai USED
        ↓
Pemilih mendapatkan konfirmasi
        ↓
Voting ditutup
        ↓
Sistem menghitung hasil
        ↓
Admin melihat hasil
        ↓
Export / Print
```

---

## 5. Status Pemilihan

```text
DRAFT
  ↓
SCHEDULED
  ↓
OPEN
  ↓
CLOSED
  ↓
ARCHIVED
```

### DRAFT

Admin masih melakukan konfigurasi. Voting belum dapat dilakukan.

### SCHEDULED

Pemilihan sudah disiapkan dan memiliki jadwal pembukaan.

### OPEN

Pemilih dapat memberikan suara.

### CLOSED

Voting dihentikan. Tidak ada suara baru yang dapat masuk.

### ARCHIVED

Pemilihan telah selesai dan menjadi arsip.

---

## 6. Modul Produk

### 6.1 Dashboard

Menampilkan:

- Total pemilihan.
- Pemilihan aktif.
- Total pemilih.
- Sudah memilih.
- Belum memilih.
- Persentase partisipasi.
- Status sistem.

Untuk pemilihan aktif, dashboard menampilkan statistik partisipasi tetapi tidak menampilkan perolehan kandidat.

### 6.2 Manajemen Pemilihan

Admin dapat:

- Membuat pemilihan.
- Mengubah informasi pemilihan.
- Menentukan tanggal mulai.
- Menentukan tanggal selesai.
- Mengatur status.
- Membuka voting.
- Menutup voting.
- Mengarsipkan pemilihan.

Data:

- Nama
- Deskripsi
- Tanggal mulai
- Tanggal selesai
- Status
- Jenis pemilihan
- Logo/foto

### 6.3 Manajemen Kandidat

Admin dapat:

- Tambah kandidat.
- Edit kandidat.
- Hapus kandidat sebelum voting dibuka.
- Upload foto.
- Menentukan nomor urut.
- Mengisi nama.
- Mengisi visi.
- Mengisi misi.

Setelah voting dibuka, data kandidat dikunci.

### 6.4 Manajemen Pemilih

Admin dapat:

- Tambah pemilih manual.
- Import CSV.
- Import Excel.
- Edit data pemilih sebelum voting.
- Menonaktifkan pemilih.
- Generate credential.
- Export daftar pemilih.

Data:

- Student ID
- Nama
- Kelas
- Status
- Voting status

### 6.5 Credential / Token Voting

Setiap pemilih memperoleh credential unik.

Credential digunakan untuk membuktikan bahwa pemilih berhak memberikan satu suara.

Sistem harus:

- Menghasilkan credential secara random.
- Tidak menggunakan ID siswa sebagai token.
- Menyimpan hash credential.
- Memiliki status USED/UNUSED.
- Mencegah credential digunakan dua kali.
- Dapat memiliki expiration time.

QR Code dapat menjadi representasi credential tersebut.

---

## 7. Proses Voting

### 7.1 Verifikasi

Pemilih memasukkan token/PIN atau menggunakan QR Code.

### 7.2 Validasi

Sistem mengecek:

- Credential valid.
- Voting sedang OPEN.
- Credential belum digunakan.
- Pemilih masih aktif.

Jika gagal, voting tidak dapat dilanjutkan.

### 7.3 Pemilihan Kandidat

Pemilih hanya dapat memilih satu kandidat.

### 7.4 Konfirmasi

Sebelum menyimpan suara, sistem menampilkan kandidat yang dipilih dan peringatan bahwa suara tidak dapat diubah setelah dikonfirmasi.

---

## 8. Anonymous Ballot

Ini merupakan requirement keamanan utama.

Sistem tidak boleh menggunakan struktur langsung:

```text
voter_id
candidate_id
```

sebagai hubungan suara.

Struktur konseptual:

```text
VOTERS
-------------------
id
election_id
student_id
name
class
credential_hash
status
used_at
```

dan:

```text
BALLOTS
-------------------
id
election_id
candidate_id
ballot_hash
created_at
```

`BALLOTS` tidak mempunyai `voter_id`.

Tujuannya agar database tidak secara langsung menyimpan:

```text
Siswa A → Kandidat 02
```

---

## 9. Atomic Voting Transaction

Proses voting harus dilakukan sebagai transaksi database.

```text
BEGIN TRANSACTION

1. Validasi credential
2. Lock credential
3. Pastikan belum digunakan
4. Buat anonymous ballot
5. Tandai credential sebagai USED

COMMIT
```

Jika salah satu proses gagal:

```text
ROLLBACK
```

Tujuannya mencegah:

- Suara tersimpan tetapi status belum memilih.
- Status sudah memilih tetapi suara tidak tersimpan.
- Double voting akibat request bersamaan.

---

## 10. Monitoring Realtime

Admin dapat melihat:

```text
Total Pemilih       842
Sudah Memilih       671
Belum Memilih       171
Partisipasi         79,69%
```

Data dapat diperbarui realtime menggunakan Laravel Reverb.

Yang boleh ditampilkan selama voting:

- Total pemilih.
- Sudah memilih.
- Belum memilih.
- Partisipasi per kelas.

Yang tidak boleh ditampilkan:

- Kandidat dengan suara terbanyak sementara.
- Jumlah suara kandidat.
- Pilihan individu.

---

## 11. Mode Kelas / TPS

Pemilihan dapat dikelompokkan berdasarkan:

- Kelas.
- Jurusan.
- Angkatan.
- TPS.
- Kelompok.

Contoh:

```text
Kelas 7A       38/40
Kelas 7B       35/40
Kelas 8A       42/45
Kelas 8B       40/45
```

Operator dapat membantu proses verifikasi tanpa dapat melihat pilihan suara.

---

## 12. Hasil Voting

Hasil kandidat hanya dapat ditampilkan setelah status menjadi `CLOSED`.

Contoh:

```text
HASIL PEMILIHAN

01 Ahmad Fauzi      354 suara
02 Budi Santoso     261 suara
03 Citra Lestari    227 suara

Total suara         842
```

Grafik:

- Bar chart.
- Pie/donut chart.
- Persentase.
- Total suara sah.

---

## 13. Export

### Excel

- Rekap kandidat.
- Jumlah suara.
- Statistik partisipasi.
- Rekap per kelas.

### PDF

- Nama pemilihan.
- Periode.
- Daftar kandidat.
- Hasil.
- Grafik.
- Total pemilih.
- Total suara.
- Persentase partisipasi.

Hasil export harus memiliki timestamp dan identitas pemilihan.

---

## 14. Audit Log

Audit log mencatat aktivitas penting.

Contoh:

```text
20 Aug 2026 08:00
Admin membuka pemilihan

20 Aug 2026 08:10
Admin mengimpor 842 pemilih

20 Aug 2026 08:30
Admin membuka voting

20 Aug 2026 15:00
Admin menutup voting

20 Aug 2026 15:05
Admin melihat hasil

20 Aug 2026 15:10
Admin mengekspor hasil PDF
```

Audit log tidak boleh mencatat hubungan pemilih dengan kandidat.

Contoh yang dilarang:

```text
Student A memilih Candidate 02
```

---

## 15. Requirement Keamanan

### Authentication

- Password menggunakan hashing.
- Session aman.
- Session timeout.
- Login rate limiting.
- Role-based access control.

### Voting

- Credential random.
- Credential hanya sekali pakai.
- Double voting protection.
- Database transaction.
- Anonymous ballot.
- CSRF protection.
- HTTPS wajib pada production.

### Authorization

Admin tidak otomatis dapat melihat pilihan individual.

Operator hanya memiliki akses yang diperlukan.

---

## 16. Database Entitas Utama

```text
USERS
 │
 ├── ADMINS
 │
 └── OPERATORS

ELECTIONS
 │
 ├── CANDIDATES
 │
 ├── VOTERS
 │
 └── BALLOTS

AUDIT_LOGS
```

Relasi penting:

```text
ELECTION
 ├── candidates
 ├── voters
 └── ballots
```

Tidak ada relasi langsung:

```text
VOTER ───────> BALLOT
```

---

## 17. Non-Functional Requirements

### Performance

Target awal:

- Halaman umum < 2 detik pada kondisi normal.
- Proses voting < 3 detik setelah konfirmasi.
- Mendukung minimal 1.000 pemilih per pemilihan.
- Sistem mampu menangani request voting bersamaan tanpa double voting.

### Availability

- Backup database otomatis.
- Monitoring server.
- Error logging.
- Recovery procedure.

### Security

- HTTPS.
- Secure headers.
- Input validation.
- SQL injection protection.
- XSS protection.
- CSRF protection.
- Rate limiting.

### Auditability

Aktivitas administratif penting harus dapat ditelusuri tanpa mengorbankan kerahasiaan pilihan.

---

## 18. MVP

### Phase 1 — Core

- [ ] Login admin
- [ ] Login/token pemilih
- [ ] Manajemen pemilihan
- [ ] Manajemen kandidat
- [ ] Manajemen pemilih
- [ ] Voting satu kandidat
- [ ] Konfirmasi voting
- [ ] Anonymous ballot
- [ ] Double voting protection
- [ ] Buka/tutup voting
- [ ] Hasil setelah voting ditutup

### Phase 2 — Administration

- [ ] Import Excel/CSV
- [ ] Export Excel
- [ ] Export PDF
- [ ] Dashboard statistik
- [ ] Audit log
- [ ] Manajemen role

### Phase 3 — Advanced

- [ ] QR Code
- [ ] Mode kelas/TPS
- [ ] Realtime monitoring
- [ ] Redis
- [ ] Laravel Reverb
- [ ] Backup otomatis
- [ ] Notification

---

## 19. Acceptance Criteria

### AC-01 — Satu suara

**Given** pemilih belum memilih  
**When** pemilih mengonfirmasi kandidat  
**Then** satu ballot dibuat dan credential menjadi `USED`.

### AC-02 — Double voting

**Given** credential sudah `USED`  
**When** credential digunakan kembali  
**Then** sistem menolak voting.

### AC-03 — Kerahasiaan

**Given** ballot telah dibuat  
**Then** record ballot tidak memiliki `voter_id`.

### AC-04 — Hasil

**Given** voting masih `OPEN`  
**When** admin membuka dashboard  
**Then** hasil kandidat tidak ditampilkan.

### AC-05 — Voting ditutup

**Given** election berstatus `CLOSED`  
**When** pemilih mencoba memberikan suara  
**Then** sistem menolak transaksi.

### AC-06 — Atomicity

**Given** proses penyimpanan ballot gagal  
**Then** status credential tidak boleh berubah menjadi `USED`.

### AC-07 — Audit

**Given** admin melakukan aktivitas penting  
**Then** sistem mencatat aktivitas tersebut dalam audit log tanpa menyimpan hubungan pemilih → kandidat.

---

## 20. Definition of Done MVP

MVP dianggap selesai apabila:

- Pemilihan dapat dibuat.
- Kandidat dapat ditambahkan.
- Pemilih dapat diimport.
- Credential dapat dibuat.
- Pemilih dapat melakukan voting.
- Satu credential tidak dapat digunakan dua kali.
- Suara tidak memiliki hubungan langsung dengan identitas pemilih.
- Voting dapat dibuka dan ditutup.
- Hasil hanya muncul setelah voting ditutup.
- Hasil dapat dihitung secara akurat.
- Aktivitas admin tercatat.
- Test untuk concurrent/double voting berhasil.
- Backup database dapat dilakukan.
- Aplikasi dapat dijalankan melalui HTTPS pada environment production.

---

## 21. Technology Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel |
| Database | PostgreSQL |
| Frontend | Livewire + Tailwind CSS |
| Realtime | Laravel Reverb |
| Cache/Queue | Redis |
| Chart | Chart.js |
| Import | Laravel Excel |
| PDF | DomPDF |
| Web Server | Nginx |
| Runtime | PHP-FPM |
| Deployment | Linux / Docker |

---

## 22. Arsitektur Pengembangan

Urutan pengerjaan yang direkomendasikan:

```text
Database & Security Architecture
              ↓
Authentication
              ↓
Election Management
              ↓
Candidate Management
              ↓
Voter & Credential
              ↓
Anonymous Voting
              ↓
Result
              ↓
Admin Dashboard
              ↓
Import / Export
              ↓
QR Code
              ↓
Realtime Monitoring
              ↓
Security Hardening
              ↓
Production Deployment
```

Prinsip utama:

> Security architecture dan anonymous voting harus dirancang sebelum UI dan fitur tambahan dikembangkan.

---

## 23. Catatan Keamanan dan Batasan

Pemisahan `VOTERS` dan `BALLOTS` mengurangi hubungan langsung antara identitas dan pilihan, tetapi **tidak dengan sendirinya memberikan anonimitas sempurna**.

Implementasi production perlu memperhatikan kemungkinan korelasi melalui:

- Timestamp.
- Application logs.
- Web server logs.
- IP address.
- Session identifiers.
- Queue/job metadata.
- Backup database.
- Admin access.
- Monitoring/observability.

Karena itu, sistem harus dirancang agar data operasional tidak secara tidak sengaja menciptakan kembali hubungan identitas → pilihan.

Untuk pemilihan yang membutuhkan tingkat kerahasiaan, verifiability, atau keamanan setara sistem pemilu publik, diperlukan desain kriptografi dan threat model yang jauh lebih formal daripada MVP aplikasi sekolah.

---

## 24. Rekomendasi Implementasi

Stack final yang direkomendasikan:

**Laravel + PostgreSQL + Livewire + Tailwind CSS + Redis + Reverb + Chart.js**

Prioritas pertama adalah:

1. Integritas database.
2. Anonymous ballot.
3. Pencegahan double voting.
4. Authentication dan authorization.
5. Transactional voting.
6. Audit log tanpa membocorkan pilihan.
7. Baru kemudian UI, QR, realtime, dan fitur tambahan.
