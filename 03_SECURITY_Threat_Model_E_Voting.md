# Security & Threat Model — Sistem E-Voting Sekolah

**Dokumen:** 03 — Security & Threat Model  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `PRD_E_Voting_Sekolah.md` dan `02_ERD_Database_Design_E_Voting.md`  
**Platform:** Laravel + PostgreSQL + Livewire + Redis + Reverb

---

## 1. Tujuan Dokumen

Dokumen ini mendefinisikan model ancaman, prinsip keamanan, kontrol keamanan, serta batasan keamanan sistem e-voting.

Prioritas keamanan:

1. Mencegah double voting.
2. Menjaga kerahasiaan pilihan pemilih.
3. Menjaga integritas ballot.
4. Mencegah manipulasi hasil.
5. Mencegah credential digunakan ulang.
6. Membatasi hak akses admin/operator.
7. Menyediakan audit trail administratif.
8. Mengurangi risiko korelasi identitas pemilih dengan pilihan.
9. Melindungi data pribadi pemilih.
10. Menyediakan mekanisme recovery tanpa merusak integritas hasil.

> **Catatan:** Sistem ini ditujukan untuk pemilihan internal sekolah/organisasi. Sistem pemilu publik dengan tuntutan legal/kriptopgrafis yang lebih tinggi memerlukan threat model, verifiability, dan protokol kriptografi yang jauh lebih formal.

---

# 2. Security Goals

Sistem harus memenuhi lima tujuan keamanan utama.

## 2.1 Eligibility

Hanya pemilih yang terdaftar dan aktif yang boleh memberikan suara.

## 2.2 Uniqueness

Satu pemilih hanya dapat menggunakan hak pilih satu kali untuk satu election.

## 2.3 Confidentiality

Sistem tidak boleh menyediakan hubungan langsung:

```text
voter → candidate
```

## 2.4 Integrity

Ballot yang sudah masuk tidak boleh diubah atau dihapus melalui operasi aplikasi normal.

## 2.5 Auditability

Aktivitas administratif penting harus dapat ditelusuri tanpa mengungkap pilihan individual.

---

# 3. Security Boundaries

```text
                    INTERNET
                       │
                       ▼
                 ┌───────────┐
                 │   Nginx   │
                 └─────┬─────┘
                       │
                       ▼
                ┌──────────────┐
                │   Laravel    │
                │ Application  │
                └──────┬───────┘
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
     PostgreSQL      Redis       Reverb
          │
          ▼
     Sensitive Data
```

Boundary utama:

1. Internet → Web Application.
2. Browser → Laravel.
3. Laravel → Database.
4. Laravel → Redis.
5. Admin → Administrative functions.
6. Operator → Verification functions.
7. Backup → Storage.

---

# 4. Assets

| Asset | Sensitivitas | Integrity | Confidentiality |
|---|---|---|---|
| Data pemilih | Tinggi | Tinggi | Tinggi |
| Credential hash | Sangat tinggi | Sangat tinggi | Sangat tinggi |
| Ballots | Sangat tinggi | Sangat tinggi | Tinggi |
| Candidate data | Sedang | Tinggi | Rendah |
| Election configuration | Tinggi | Sangat tinggi | Sedang |
| Audit logs | Tinggi | Sangat tinggi | Tinggi |
| Admin accounts | Sangat tinggi | Sangat tinggi | Sangat tinggi |
| Backup database | Sangat tinggi | Sangat tinggi | Sangat tinggi |
| Application secrets | Sangat tinggi | Sangat tinggi | Sangat tinggi |

---

# 5. Trust Model

## 5.1 Pemilih

Pemilih dianggap:

- Tidak dipercaya sepenuhnya.
- Dapat mencoba login berulang.
- Dapat mencoba menggunakan credential orang lain.
- Dapat mencoba replay request.
- Dapat membuka developer tools.
- Dapat mengirim request manual ke endpoint.

Pemilih **tidak boleh dipercaya hanya karena UI menyembunyikan tombol tertentu**.

Semua validasi harus dilakukan server-side.

---

## 5.2 Operator

Operator dipercaya secara terbatas.

Operator boleh:

- Memverifikasi pemilih.
- Membantu proses login/QR.
- Melihat status eligibility.

Operator tidak boleh:

- Melihat pilihan pemilih.
- Mengubah ballot.
- Membuka hasil sebelum waktunya.
- Mengubah kandidat setelah voting dibuka.

---

## 5.3 Admin Pemilihan

Admin memiliki akses lebih tinggi tetapi tetap harus dibatasi.

Admin boleh:

- Mengelola election.
- Mengelola kandidat.
- Mengelola pemilih.
- Membuka/menutup election.
- Melihat hasil setelah voting ditutup.

Admin tidak boleh melalui UI normal:

- Mengubah ballot.
- Menghapus ballot.
- Menghubungkan voter dengan ballot.
- Melihat pilihan individual.

---

## 5.4 Super Admin

Super Admin memiliki hak sistem tertinggi.

Namun hak tersebut tetap harus diaudit.

Aktivitas sensitif harus masuk audit log.

---

# 6. Threat Actors

| Threat Actor | Kemampuan |
|---|---|
| Pemilih nakal | Manipulasi request, replay, brute force |
| Pemilih yang kehilangan token | Token misuse |
| Operator nakal | Penyalahgunaan akses |
| Admin nakal | Penyalahgunaan privilege |
| Attacker internet | Credential stuffing, scanning, exploit |
| Attacker database | Membaca/manipulasi database |
| Attacker server | Mengakses filesystem/application secrets |
| Attacker jaringan | Interception jika HTTPS tidak digunakan |
| Insider | Penyalahgunaan log/backup |
| Attacker backup | Membaca backup database |

---

# 7. Security Assumptions

Sistem mengasumsikan:

1. Server production dikontrol organisasi.
2. HTTPS aktif.
3. OS server dipatch.
4. Database tidak diekspos langsung ke internet.
5. Application secret tidak masuk repository.
6. Backup dilindungi.
7. Admin memiliki akun individual.
8. Password admin tidak dibagikan.
9. Perangkat pemilih berada di lingkungan yang relatif terkontrol.
10. Sistem operasi dan browser pengguna tidak sepenuhnya dapat dipercaya.

---

# 8. Threat Model

## 8.1 Credential Theft

### Ancaman

Token/QR milik siswa dicuri.

### Risiko

Attacker dapat mencoba memberikan suara menggunakan token tersebut.

### Mitigasi

- Token random.
- Token tidak berdasarkan student ID.
- Token disimpan sebagai hash.
- Token hanya sekali pakai.
- Token dapat memiliki expiry.
- Setelah digunakan status menjadi `USED`.
- Rate limiting.
- Monitoring penggunaan credential.
- Operator tidak melihat token plaintext setelah provisioning jika tidak diperlukan.

### Residual risk

Jika attacker memperoleh credential yang belum digunakan dan dapat melewati kontrol tambahan, credential dapat disalahgunakan.

Mitigasi tambahan dapat berupa:

- PIN kedua.
- Verifikasi identitas.
- QR + PIN.
- Device/session binding dengan hati-hati.

---

# 9. Double Voting

### Ancaman

Pemilih mengirim dua request voting secara bersamaan.

Contoh:

```text
Request A → Vote Candidate 01
Request B → Vote Candidate 02
```

### Risiko

Dua ballot dibuat.

### Mitigasi

Gunakan database transaction + row lock.

```text
BEGIN

SELECT credential
FOR UPDATE

IF status != UNUSED
    REJECT

CREATE BALLOT

UPDATE credential
SET status = USED

UPDATE voter
SET voting_status = VOTED

COMMIT
```

Salah satu request harus gagal.

---

# 10. Replay Attack

### Ancaman

Request voting yang sudah pernah berhasil direplay.

### Mitigasi

- Credential one-time.
- Status `USED`.
- Server-side authorization.
- CSRF protection untuk browser flow.
- Validasi election status.
- Jangan percaya parameter client-side.
- Idempotency protection untuk endpoint voting jika diperlukan.

---

# 11. Credential Brute Force

### Ancaman

Attacker mencoba:

```text
000001
000002
000003
...
```

### Mitigasi

- Credential memiliki entropy tinggi.
- Token tidak sequential.
- Rate limiting.
- Account/session throttling.
- Monitoring failed attempts.
- Jangan memberikan pesan error yang terlalu informatif.

Contoh pesan:

```text
Credential tidak valid atau tidak dapat digunakan.
```

bukan:

```text
Credential valid tetapi sudah digunakan.
```

untuk endpoint publik yang rawan enumeration.

---

# 12. Student ID Enumeration

### Ancaman

Attacker mencoba mengetahui daftar siswa melalui endpoint.

### Mitigasi

- Jangan expose daftar voters melalui API publik.
- Gunakan authorization.
- Gunakan generic error message.
- Jangan gunakan sequential student ID sebagai credential.
- Rate limiting.

---

# 13. Anonymous Ballot Correlation

Ini merupakan ancaman paling penting.

### Ancaman

Walaupun `ballots` tidak memiliki `voter_id`, sistem dapat secara tidak sengaja membuat korelasi melalui:

- Timestamp.
- Request ID.
- IP.
- Session ID.
- Application log.
- Web server log.
- Queue metadata.
- Debug log.
- Database audit.
- Monitoring tools.

Contoh buruk:

```text
20:01:01
Student 12345 authenticated

20:01:01
Ballot Candidate 02 created
```

Jika kedua event dapat dikorelasikan, anonimitas dapat berkurang.

### Mitigasi

- Jangan log pilihan kandidat bersama identitas pemilih.
- Jangan menyimpan `voter_id` pada ballot.
- Jangan menyimpan `credential_id` pada ballot.
- Minimalkan correlation identifiers.
- Pisahkan authorization flow dan ballot creation flow secara logis.
- Review access log sebelum production.
- Nonaktifkan debug logging production.
- Jangan mengirim candidate selection ke analytics pihak ketiga.
- Lindungi backup.
- Batasi akses database.

---

# 14. Admin Membaca Pilihan Pemilih

### Ancaman

Admin mencoba mengetahui pilihan individual.

### Mitigasi database

`ballots` tidak memiliki:

```text
voter_id
student_id
credential_id
```

### Mitigasi application

Tidak menyediakan endpoint:

```text
GET /admin/voters/{id}/vote
```

Tidak menyediakan UI:

```text
Student → Candidate
```

### Prinsip

Admin dapat melihat:

```text
Student A → VOTED
```

Admin dapat melihat:

```text
Candidate 02 → 314 votes
```

Admin tidak dapat melihat melalui sistem:

```text
Student A → Candidate 02
```

---

# 15. Malicious Admin / Database Administrator

Ini merupakan batasan penting.

Jika satu pihak memiliki:

- akses database,
- akses application logs,
- akses server,
- akses backup,

maka secara teori mereka dapat mencoba melakukan correlation analysis.

Karena itu, sistem MVP tidak boleh mengklaim:

> "Anonimitas matematis/kriptografis sempurna."

Sistem harus menyatakan:

> "Database aplikasi tidak menyimpan hubungan langsung antara identitas pemilih dan pilihan kandidat."

Untuk tingkat keamanan lebih tinggi, diperlukan desain cryptographic voting protocol dan pemisahan trust domain.

---

# 16. Ballot Tampering

### Ancaman

Attacker/admin mengubah:

```text
candidate_id
```

pada ballot.

### Mitigasi

- Database access restricted.
- Ballot immutable pada application layer.
- Tidak ada update/delete endpoint.
- Audit database access jika tersedia.
- Database role separation.
- Backup.
- Hash/integrity mechanism.
- Result reconciliation.

---

# 17. Ballot Deletion

### Ancaman

Ballot dihapus sebelum penghitungan.

### Mitigasi

- Tidak ada delete route.
- Foreign key policy tidak boleh menyebabkan cascade delete ballot.
- Database permissions.
- Election tidak boleh hard delete setelah menerima suara.
- Backup.
- Audit.

---

# 18. Candidate Manipulation

### Ancaman

Admin mengubah kandidat setelah voting dimulai.

### Mitigasi

Ketika:

```text
election.status = OPEN
```

maka:

```text
candidate.name      → LOCK
candidate.number    → LOCK
candidate.vision    → LOCK
candidate.mission   → LOCK
candidate.photo     → LOCK
```

Perubahan hanya dapat dilakukan sebelum voting dibuka.

---

# 19. Election Status Manipulation

### Ancaman

Attacker mengubah status election.

Contoh:

```text
CLOSED → OPEN
```

### Mitigasi

- Role-based authorization.
- State transition validation.
- Audit log.
- Hanya role tertentu yang boleh melakukan transition.
- Tidak menerima `status` mentah dari client tanpa validasi.

Valid transition:

```text
DRAFT → SCHEDULED
SCHEDULED → OPEN
OPEN → CLOSED
CLOSED → ARCHIVED
```

Transition yang tidak valid harus ditolak.

---

# 20. Result Premature Disclosure

### Ancaman

Hasil sementara diketahui sebelum voting ditutup.

### Risiko

Dapat memengaruhi pemilih berikutnya.

### Mitigasi

Selama:

```text
election.status = OPEN
```

endpoint hasil harus menolak.

Admin dashboard hanya menampilkan:

```text
total voters
voted
not voted
participation
```

Setelah:

```text
election.status = CLOSED
```

barulah hasil kandidat tersedia.

---

# 21. Result Manipulation

### Ancaman

Jumlah hasil diubah.

### Mitigasi

Jangan menyimpan hasil manual sebagai source of truth.

Source of truth:

```text
BALLOTS
```

Hasil dihitung:

```sql
COUNT(*) GROUP BY candidate_id
```

Hasil export harus dibuat dari query yang sama.

Jika diperlukan performa, materialized result harus memiliki mekanisme reconciliation.

---

# 22. SQL Injection

### Ancaman

Attacker memasukkan SQL melalui form.

### Mitigasi

Laravel Eloquent / Query Builder dengan parameter binding.

Jangan:

```php
DB::statement("SELECT ... {$input}");
```

Gunakan parameter binding.

Validasi semua input.

---

# 23. XSS

### Ancaman

Admin memasukkan script melalui:

- Nama kandidat.
- Visi.
- Misi.
- Deskripsi election.

### Mitigasi

- Output escaping.
- Validasi input.
- Sanitization jika rich text diperbolehkan.
- Content Security Policy.
- Jangan menggunakan raw HTML tanpa alasan.

---

# 24. CSRF

### Ancaman

Attacker membuat website yang mengirim request atas nama session admin/pemilih.

### Mitigasi

Gunakan CSRF protection Laravel untuk state-changing browser requests.

Voting confirmation harus memiliki CSRF protection.

---

# 25. Session Hijacking

### Ancaman

Session cookie dicuri.

### Mitigasi

- HTTPS.
- Secure cookie.
- HttpOnly.
- SameSite.
- Session regeneration setelah login.
- Session timeout.
- Logout invalidation.
- Jangan menyimpan credential voting di localStorage jika tidak diperlukan.

---

# 26. Brute Force Login

### Ancaman

Attacker mencoba password berulang kali.

### Mitigasi

- Rate limiting.
- Throttling.
- Password hashing.
- Strong password policy admin.
- MFA untuk admin jika tersedia.
- Monitoring failed login.

Untuk pemilih, mekanisme rate limiting harus mempertimbangkan skenario banyak siswa login bersamaan agar tidak memblokir legitimate traffic.

---

# 27. Privilege Escalation

### Ancaman

Operator mencoba mengakses fungsi admin.

Contoh:

```text
/operator/elections/10
```

diubah menjadi:

```text
/admin/elections/10
```

### Mitigasi

Authorization harus server-side.

Gunakan:

- Laravel Gates.
- Policies.
- Middleware.
- Role/permission checks.

Jangan mengandalkan URL hiding.

---

# 28. IDOR

### Ancaman

Pemilih mengubah:

```text
/elections/10
```

menjadi:

```text
/elections/11
```

dan memperoleh akses ke election lain.

### Mitigasi

Setiap resource harus di-authorize terhadap:

- Current user.
- Election.
- Role.
- Voting eligibility.

---

# 29. QR Code Theft

### Ancaman

Screenshot QR dikirim kepada orang lain.

### Mitigasi

- QR berisi credential random.
- Credential one-time.
- Expiration.
- Tambahkan verifikasi kedua jika threat model membutuhkan.
- Jangan menaruh data pribadi plaintext dalam QR.

Contoh yang tidak disarankan:

```text
QR = student_id=12345
```

Contoh lebih baik:

```text
QR = random credential
```

---

# 30. Lost Credential

### Ancaman

Siswa kehilangan PIN/QR.

### Requirement

Credential dapat:

```text
REVOKED
```

kemudian admin dapat menerbitkan credential pengganti sebelum digunakan.

Jika credential sudah `USED`, tidak boleh diterbitkan credential baru untuk memberikan suara kedua.

---

# 31. Concurrent Requests

### Ancaman

Dua browser/device menggunakan credential yang sama secara bersamaan.

### Mitigasi

Database row locking:

```sql
SELECT *
FROM voting_credentials
WHERE token_hash = ?
FOR UPDATE;
```

Kemudian:

```text
IF status != UNUSED
    reject
```

---

# 32. Denial of Service

### Ancaman

Attacker mengirim request dalam jumlah besar.

### Mitigasi

- Reverse proxy.
- Rate limiting.
- Connection limits.
- Request size limits.
- Queue untuk pekerjaan berat.
- Caching untuk halaman read-only.
- Monitoring.
- Infrastruktur scaling jika diperlukan.

Untuk election day, siapkan kapasitas lebih tinggi dari jumlah request rata-rata.

---

# 33. Realtime Security

Laravel Reverb digunakan untuk monitoring.

Channel harus memiliki authorization.

Contoh:

```text
private-election.{electionId}.monitoring
```

Hanya admin/operator yang berwenang yang boleh subscribe.

Jangan broadcast:

```text
student_id
candidate_id selected by voter
credential
token
```

Broadcast yang aman:

```text
voted_count
eligible_count
participation_percentage
```

---

# 34. Redis Security

Redis dapat berisi:

- Cache.
- Session.
- Queue.
- Temporary data.

Requirement:

- Redis tidak boleh exposed ke internet.
- Password/auth jika deployment membutuhkan.
- Network isolation.
- Jangan menyimpan credential plaintext.
- Jangan memasukkan candidate selection ke cache yang dapat diakses publik.

---

# 35. Application Secrets

Secret tidak boleh masuk Git.

Contoh:

```text
APP_KEY
DB_PASSWORD
REDIS_PASSWORD
MAIL_PASSWORD
REVERB_APP_SECRET
```

Gunakan:

```text
.env
```

atau secret manager.

Repository hanya menyimpan:

```text
.env.example
```

tanpa secret production.

---

# 36. File Upload Security

Candidate photo merupakan file upload.

Mitigasi:

- Validasi MIME type.
- Validasi extension.
- Batas ukuran file.
- Rename file.
- Jangan menggunakan filename asli sebagai path.
- Simpan di storage yang sesuai.
- Jangan mengizinkan executable upload.
- Gunakan image processing jika diperlukan.

---

# 37. Import Excel/CSV Security

Import pemilih harus divalidasi.

Validasi:

- Header.
- Student ID.
- Nama.
- Kelas.
- Duplikasi.
- Encoding.
- Jumlah row.
- Ukuran file.

Hindari formula injection ketika data nantinya diekspor kembali ke Excel.

---

# 38. Audit Logging

Audit log harus mencatat:

```text
who
what
when
which election
result
```

Contoh:

```text
ADMIN
ELECTION_OPENED
Election #10
2026-08-20 08:00
```

Audit log tidak boleh mencatat:

```text
Voter #123 → Candidate #2
```

---

# 39. Logging Policy

### Boleh dicatat

```text
Admin login
Admin logout
Election created
Election opened
Election closed
Candidate created
Voters imported
Credential revoked
Result exported
System error
```

### Tidak boleh dicatat

```text
token plaintext
password
candidate selected by voter
voter_id + candidate_id
credential + candidate_id
```

### IP Address

IP address merupakan data sensitif dari perspektif privacy dan dapat menjadi correlation signal.

Jika disimpan:

- Tentukan retention.
- Batasi akses.
- Jangan gunakan untuk menghubungkan voter dengan ballot.
- Pertimbangkan anonymization/pseudonymization sesuai kebutuhan organisasi.

---

# 40. Privacy Requirements

Data pemilih harus memiliki:

- Purpose limitation.
- Access control.
- Retention policy.
- Backup protection.
- Deletion/archival policy sesuai kebijakan organisasi.

Data yang tidak diperlukan untuk voting tidak boleh dikumpulkan.

---

# 41. Data Retention

Rekomendasi:

### Active election

Simpan seluruh data yang dibutuhkan.

### Setelah election

Data ballot dan hasil dipertahankan sesuai kebijakan organisasi.

### Credential

Credential yang sudah digunakan tidak diperlukan sebagai authentication aktif dan dapat masuk retention/archival policy.

### Logs

Retention harus ditentukan berdasarkan kebutuhan audit dan privacy.

Contoh kebijakan awal:

```text
Application logs     → 30–90 hari
Audit logs           → 1–3 tahun
Election records     → sesuai kebijakan sekolah
Backups              → sesuai backup policy
```

Angka tersebut merupakan rekomendasi awal dan harus disesuaikan dengan kebijakan organisasi/hukum yang berlaku.

---

# 42. Backup Security

Backup harus:

- Dienkripsi.
- Memiliki akses terbatas.
- Tidak public.
- Memiliki retention policy.
- Memiliki restore test.
- Tidak dikirim ke perangkat pribadi admin.

Backup database dapat berisi:

```text
VOTERS
CREDENTIAL HASHES
BALLOTS
AUDIT LOGS
```

Karena itu backup harus diperlakukan sebagai data sangat sensitif.

---

# 43. Database Security

Production database:

- Tidak public.
- Hanya dapat diakses application server.
- Menggunakan dedicated database user.
- Password kuat.
- Least privilege.
- Backup terenkripsi.
- Monitoring access.

Jika memungkinkan, gunakan role database terpisah untuk:

```text
application runtime
migration/deployment
read-only reporting
```

Application runtime sebaiknya tidak memiliki privilege superuser.

---

# 44. Ballot Immutability

Setelah ballot dibuat:

```text
CREATE → immutable
```

Tidak boleh:

```text
UPDATE candidate_id
DELETE ballot
```

melalui aplikasi normal.

Jika terjadi kesalahan:

- Jangan edit ballot.
- Jangan hapus ballot.
- Investigasi melalui audit process.
- Gunakan corrective procedure yang dapat diaudit jika memang dibutuhkan.

---

# 45. Election Locking

Ketika election menjadi `OPEN`:

```text
LOCK:
- candidates
- voter eligibility
- election configuration tertentu
```

Perubahan administratif besar harus ditolak.

Ketika election menjadi `CLOSED`:

```text
LOCK:
- voting
- candidates
- voters
- credentials
- ballots
```

Kemudian hasil dihitung.

---

# 46. Security State Machine

```text
DRAFT
 │
 │ configure
 ▼
SCHEDULED
 │
 │ open
 ▼
OPEN
 │
 │ close
 ▼
CLOSED
 │
 │ archive
 ▼
ARCHIVED
```

Tidak boleh:

```text
CLOSED → OPEN
```

untuk MVP.

Jika organisasi membutuhkan reopening, harus dibuat prosedur khusus dengan:

- Super Admin authorization.
- Reason.
- Audit log.
- Approval.
- Security review.

---

# 47. Voting Service Security Contract

Semua voting harus melalui satu service utama:

```text
VotingService
```

Service bertanggung jawab atas:

1. Validasi election.
2. Validasi credential.
3. Lock credential.
4. Validasi voter.
5. Validasi candidate.
6. Membuat ballot.
7. Menandai credential USED.
8. Menandai voter VOTED.
9. Commit transaction.
10. Menghasilkan confirmation reference.

Jangan menyebarkan logic voting ke banyak controller.

---

# 48. Pseudocode Aman

```php
DB::transaction(function () use ($token, $candidateId, $electionId) {

    $credential = VotingCredential::query()
        ->where('election_id', $electionId)
        ->where('token_hash', hashToken($token))
        ->lockForUpdate()
        ->first();

    if (!$credential) {
        throw InvalidCredentialException::class;
    }

    if ($credential->status !== 'UNUSED') {
        throw CredentialAlreadyUsedException::class;
    }

    $election = Election::query()
        ->whereKey($electionId)
        ->where('status', 'OPEN')
        ->firstOrFail();

    $candidate = Candidate::query()
        ->whereKey($candidateId)
        ->where('election_id', $election->id)
        ->where('status', 'ACTIVE')
        ->firstOrFail();

    Ballot::create([
        'election_id' => $election->id,
        'candidate_id' => $candidate->id,
        'ballot_hash' => createBallotHash(),
    ]);

    $credential->update([
        'status' => 'USED',
        'used_at' => now(),
    ]);

    $credential->voter()->update([
        'voting_status' => 'VOTED',
    ]);
});
```

> Implementasi final harus ditinjau kembali ketika migration dan model Laravel dibuat. Contoh ini adalah ilustrasi security contract, bukan source code production final.

---

# 49. Security Headers

Production sebaiknya menggunakan:

```text
Strict-Transport-Security
Content-Security-Policy
X-Content-Type-Options
Referrer-Policy
Permissions-Policy
```

Konfigurasi CSP harus diuji agar tidak memblokir Livewire/Reverb secara tidak sengaja.

---

# 50. HTTPS

Production wajib menggunakan HTTPS.

Tanpa HTTPS, attacker pada jaringan dapat mencoba:

- Session theft.
- Token interception.
- Request manipulation.
- Credential interception.

HTTP biasa tidak boleh digunakan untuk voting production.

---

# 51. Password Policy

Admin:

- Password panjang.
- Tidak boleh menggunakan password umum.
- Rate limiting.
- MFA direkomendasikan.
- Session timeout.

Pemilih:

Jika menggunakan password, gunakan password hashing.

Jika menggunakan PIN/token, gunakan entropy yang cukup dan mekanisme rate limiting.

---

# 52. MFA

MFA sangat direkomendasikan untuk:

```text
SUPER_ADMIN
ADMIN
```

MFA tidak harus digunakan untuk siswa jika workflow sekolah mengandalkan QR/PIN, tetapi threat model harus mempertimbangkan risiko credential theft.

---

# 53. Security Testing

Sebelum production, wajib menguji:

### Authentication

- [ ] Wrong password.
- [ ] Brute force.
- [ ] Session fixation.
- [ ] Logout.
- [ ] Session expiry.

### Authorization

- [ ] Operator → admin endpoint.
- [ ] Admin A → election milik Admin B.
- [ ] Voter → admin endpoint.
- [ ] Voter → election lain.

### Voting

- [ ] Double click.
- [ ] Double request.
- [ ] Concurrent requests.
- [ ] Replay request.
- [ ] Used credential.
- [ ] Revoked credential.
- [ ] Expired credential.
- [ ] Closed election.
- [ ] Invalid candidate.
- [ ] Candidate from another election.

### Privacy

- [ ] Search logs for voter/candidate correlation.
- [ ] Inspect database schema.
- [ ] Inspect API responses.
- [ ] Inspect browser storage.
- [ ] Inspect WebSocket payload.
- [ ] Inspect backup.

---

# 54. Penetration Testing Checklist

Sebelum deployment production:

- [ ] OWASP Top 10 review.
- [ ] Authentication testing.
- [ ] Authorization testing.
- [ ] SQL injection testing.
- [ ] XSS testing.
- [ ] CSRF testing.
- [ ] IDOR testing.
- [ ] Rate-limit testing.
- [ ] File upload testing.
- [ ] Session security testing.
- [ ] WebSocket authorization testing.
- [ ] Security headers testing.
- [ ] Dependency vulnerability scan.
- [ ] Secrets scan.
- [ ] Database exposure scan.

---

# 55. Incident Response

Jika terjadi insiden:

```text
DETECT
  ↓
CONTAIN
  ↓
INVESTIGATE
  ↓
PRESERVE EVIDENCE
  ↓
ASSESS IMPACT
  ↓
RECOVER
  ↓
AUDIT
  ↓
POST-INCIDENT REVIEW
```

Contoh insiden:

- Credential mass leak.
- Database compromise.
- Admin account compromise.
- Ballot integrity issue.
- Server compromise.
- Unauthorized election state change.

Jangan menghapus log atau database evidence secara sembarangan ketika investigasi berlangsung.

---

# 56. Election Day Security Checklist

Sebelum membuka voting:

- [ ] Backup database.
- [ ] Verify election configuration.
- [ ] Verify candidates.
- [ ] Verify voter count.
- [ ] Verify credential count.
- [ ] Verify HTTPS.
- [ ] Verify server time.
- [ ] Verify monitoring.
- [ ] Verify database backup.
- [ ] Verify no candidate result endpoint is publicly accessible.
- [ ] Verify admin accounts.
- [ ] Verify rate limits.
- [ ] Verify Reverb authorization.
- [ ] Verify logs do not expose vote choice.

---

# 57. Closing Election Checklist

Sebelum menutup:

- [ ] Pastikan waktu voting selesai.
- [ ] Lock election.
- [ ] Tolak voting baru.
- [ ] Verify transaction completion.
- [ ] Backup database.
- [ ] Calculate ballot count.
- [ ] Compare valid ballots with participation count.
- [ ] Generate result.
- [ ] Audit closing action.
- [ ] Publish result.

---

# 58. Security Invariants

Invariant adalah kondisi yang **tidak boleh pernah dilanggar**.

### INV-01

```text
A voter cannot cast more than one accepted vote
per election.
```

### INV-02

```text
A ballot cannot reference a voter.
```

### INV-03

```text
A used credential cannot be reused.
```

### INV-04

```text
A ballot cannot be modified after creation.
```

### INV-05

```text
Results cannot be accessed while election is OPEN.
```

### INV-06

```text
A candidate cannot be modified after election becomes OPEN.
```

### INV-07

```text
A normal admin action cannot reveal
voter → candidate mapping.
```

### INV-08

```text
A failed ballot transaction cannot mark
the voter/credential as successfully voted.
```

---

# 59. Security Priorities

Prioritas implementasi:

```text
P0 — Critical
- Anonymous ballot
- Double voting prevention
- Transaction/locking
- Authentication
- Authorization
- HTTPS
- Ballot immutability

P1 — High
- Credential hashing
- Rate limiting
- Audit log
- Session security
- Backup security
- Result locking

P2 — Medium
- MFA
- Security headers
- CSP
- Advanced monitoring
- Dependency scanning

P3 — Enhancement
- Advanced anomaly detection
- Cryptographic verification
- External security audit
```

---

# 60. Security Acceptance Criteria

## SEC-01 — Anonymous ballot

**Given** voter A memberikan suara kepada candidate B  
**Then** tidak ada record `ballots` yang menyimpan `voter_id`, `student_id`, atau `credential_id`.

## SEC-02 — Double voting

**Given** dua request menggunakan credential yang sama secara bersamaan  
**Then** maksimal satu request berhasil.

## SEC-03 — Failed transaction

**Given** ballot gagal dibuat  
**Then** credential tetap `UNUSED` dan voter tetap `NOT_VOTED`.

## SEC-04 — Result confidentiality

**Given** election status `OPEN`  
**When** user meminta result  
**Then** server menolak request.

## SEC-05 — Candidate locking

**Given** election status `OPEN`  
**When** admin mencoba mengubah candidate  
**Then** server menolak perubahan.

## SEC-06 — Authorization

**Given** operator mencoba membuka admin endpoint  
**Then** server mengembalikan unauthorized/forbidden response.

## SEC-07 — Token protection

**Given** database bocor  
**Then** database tidak berisi plaintext voting token.

## SEC-08 — Audit privacy

**Given** admin melakukan aktivitas voting  
**Then** audit log tidak membuat mapping voter → candidate.

## SEC-09 — Ballot immutability

**Given** ballot sudah dibuat  
**When** normal application user mencoba update/delete  
**Then** operasi ditolak.

## SEC-10 — Election closure

**Given** election status `CLOSED`  
**When** voter mencoba voting  
**Then** transaction ditolak.

---

# 61. Residual Risks

Walaupun kontrol di atas diterapkan, beberapa risiko tetap ada:

1. Perangkat pemilih dapat terinfeksi malware.
2. Admin dengan akses server penuh secara teoritis dapat memanipulasi sistem.
3. Database administrator dapat membaca data database.
4. Server compromise dapat memengaruhi integritas aplikasi.
5. Correlation analysis dapat dilakukan jika operational logs tidak dikelola dengan benar.
6. Credential yang dicuri sebelum digunakan dapat disalahgunakan.
7. Availability dapat terganggu oleh serangan atau kegagalan infrastruktur.
8. Human error tetap dapat terjadi.

Sistem harus mendokumentasikan risiko residual tersebut dan tidak mengklaim tingkat keamanan yang melebihi kemampuan desain.

---

# 62. Security Decision Record

### Keputusan 1

**Keputusan:** `ballots` tidak memiliki `voter_id`.

**Alasan:** Mengurangi kemungkinan database menyediakan hubungan langsung identitas → pilihan.

### Keputusan 2

**Keputusan:** Credential disimpan sebagai hash.

**Alasan:** Mengurangi dampak kebocoran database.

### Keputusan 3

**Keputusan:** Voting menggunakan transaction + row lock.

**Alasan:** Mencegah double voting akibat concurrent requests.

### Keputusan 4

**Keputusan:** Result hanya tersedia setelah `CLOSED`.

**Alasan:** Mencegah pengaruh hasil sementara terhadap pemilih berikutnya.

### Keputusan 5

**Keputusan:** Ballot immutable.

**Alasan:** Menjaga integritas suara setelah diterima.

### Keputusan 6

**Keputusan:** Audit log tidak mencatat candidate choice bersama voter identity.

**Alasan:** Mencegah audit system menjadi jalur kebocoran anonimitas.

---

# 63. Kesimpulan

Arsitektur keamanan sistem berpusat pada pemisahan:

```text
ELIGIBILITY
    │
    ├── voter
    └── voting credential

           X

ANONYMOUS VOTE
    │
    └── ballot → candidate
```

Sistem harus menjamin bahwa:

```text
Siapa yang memilih?
```

dan

```text
Memilih siapa?
```

merupakan dua informasi yang tidak memiliki hubungan langsung dalam database maupun application logs.

Untuk MVP sekolah, pendekatan ini memberikan dasar keamanan yang jauh lebih baik daripada model sederhana:

```text
voter_id → candidate_id
```

Namun, sistem tetap harus diposisikan sebagai aplikasi e-voting internal, bukan sistem pemilu publik dengan jaminan kriptografis formal.

---

# 64. Dokumen Berikutnya

Setelah `03_SECURITY.md`, dokumen berikutnya adalah:

```text
04_ARCHITECTURE.md
```

Dokumen tersebut akan mendefinisikan:

- Laravel application architecture.
- Module boundaries.
- Service layer.
- Repository/query strategy.
- Authentication architecture.
- VotingService.
- ElectionService.
- ResultService.
- AuditService.
- PostgreSQL access.
- Redis.
- Laravel Reverb.
- Queue.
- Storage.
- Deployment architecture.
- Request flow.
- Voting transaction flow.
- Struktur folder Laravel.
