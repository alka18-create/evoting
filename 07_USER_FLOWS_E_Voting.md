# User Flows — Sistem E-Voting Sekolah

**Dokumen:** 07 — User Flows  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD`, `02_ERD`, `03_SECURITY_Threat_Model`, `04_ARCHITECTURE`, `05_API_SPEC`, `06_UI_UX_SPEC`

---

# 1. Tujuan

Dokumen ini mendefinisikan alur pengguna end-to-end aplikasi e-voting sekolah.

Flow utama:

```text
Admin
  ↓
Buat Pemilihan
  ↓
Kelola Kandidat
  ↓
Import Pemilih
  ↓
Generate Credential
  ↓
Validasi Kesiapan
  ↓
Buka Pemilihan
  ↓
Pemilih Login
  ↓
Pilih Kandidat
  ↓
Konfirmasi
  ↓
Suara Disimpan
  ↓
Pemilihan Ditutup
  ↓
Hasil Dihitung
  ↓
Hasil Ditampilkan
  ↓
Export / Print
```

Prinsip utama:

> Tidak boleh ada flow UI yang memungkinkan sistem menghubungkan identitas pemilih dengan kandidat yang dipilih.

---

# 2. Actors

| Actor | Deskripsi |
|---|---|
| SUPER_ADMIN | Administrator tertinggi sistem |
| ADMIN | Pengelola pemilihan |
| OPERATOR | Membantu operasional |
| VOTER | Siswa/pemilih |
| SYSTEM | Proses otomatis aplikasi |

---

# 3. State Machine Election

```text
DRAFT
  │
  ├── schedule ──→ SCHEDULED
  │                  │
  │                  └── start time ──→ OPEN
  │
  └── open ───────→ OPEN
                       │
                       └── close ──→ CLOSED
                                      │
                                      └── archive ──→ ARCHIVED
```

Invalid transition:

```text
CLOSED → OPEN
ARCHIVED → OPEN
ARCHIVED → CLOSED
```

Untuk MVP, reopening election tidak diperbolehkan.

---

# 4. Flow A — Admin Login

## Trigger

Admin membuka:

```text
/admin/login
```

## Flow

```text
Open Login
   ↓
Input email + password
   ↓
Submit
   ↓
Validate credentials
   ↓
[Valid?]
 ├─ No → Generic error + rate limit
 └─ Yes
      ↓
Regenerate session
      ↓
Load role/permission
      ↓
Dashboard
```

## Security Checkpoint

- Password tidak disimpan plaintext.
- Login rate limited.
- Generic authentication error.
- Session fixation protection.
- Audit successful/failed login sesuai kebijakan.

---

# 5. Flow B — Create Election

## Actor

```text
ADMIN
```

## Flow

```text
Dashboard
   ↓
Pemilihan
   ↓
Buat Pemilihan
   ↓
Isi form
   ↓
Validasi
   ↓
Create Election
   ↓
Status = DRAFT
   ↓
Election Detail
```

## Validation

```text
name required
start < end
date/time valid
```

## Security Checkpoint

- Authorization server-side.
- User tidak dapat menetapkan `status` arbitrary.
- Audit log `ELECTION_CREATED`.

---

# 6. Flow C — Configure Election

Setelah election dibuat:

```text
DRAFT
```

Admin harus menyelesaikan checklist:

```text
✓ Election information
✓ Candidates
✓ Voters
✓ Credentials
```

Sistem menghitung readiness.

---

# 7. Election Readiness

Contoh:

```text
Kesiapan Pemilihan

[✓] Informasi pemilihan
[✓] Minimal 1 kandidat
[✓] Pemilih tersedia
[✓] Credential tersedia
[✓] Jadwal valid

Status:
SIAP DIBUKA
```

Jika belum siap:

```text
Belum siap dibuka
```

Tampilkan item yang belum lengkap.

---

# 8. Flow D — Add Candidate

## Actor

```text
ADMIN
```

## Flow

```text
Election Detail
   ↓
Kandidat
   ↓
Tambah Kandidat
   ↓
Isi:
- Nomor
- Nama
- Foto
- Visi
- Misi
   ↓
Validate
   ↓
Save
   ↓
Candidate list
```

## Validation

- Nomor unik per election.
- Nama wajib.
- Foto sesuai policy.
- Election harus DRAFT/SCHEDULED.
- Tidak boleh dimodifikasi saat OPEN.

---

# 9. Flow E — Edit Candidate

```text
Candidate List
   ↓
Edit
   ↓
Update
```

Allowed:

```text
DRAFT
SCHEDULED
```

Not allowed:

```text
OPEN
CLOSED
ARCHIVED
```

Jika election sudah OPEN:

```text
Edit disabled
```

Server tetap melakukan validation walaupun tombol UI disembunyikan.

---

# 10. Flow F — Import Voters

## Actor

```text
ADMIN
OPERATOR
```

## Flow

```text
Voter Management
   ↓
Import
   ↓
Upload CSV/XLSX
   ↓
Parse file
   ↓
Validate rows
   ↓
Show preview
   ↓
[Confirm?]
 ├─ No → Cancel
 └─ Yes
      ↓
Queue import
      ↓
Process
      ↓
Generate result
      ↓
Show summary
```

---

# 11. Import Validation

Per row:

```text
Student ID
Name
Class
```

Check:

```text
Required fields
Format
Duplicate
Existing student
Maximum row count
```

---

# 12. Import Result

Contoh:

```text
Import selesai

Total       842
Berhasil    840
Gagal         2
Duplicate     0
```

Error report:

```text
Row 125
Student ID kosong
```

---

# 13. Flow G — Generate Credentials

## Actor

```text
ADMIN
OPERATOR
```

## Flow

```text
Voter Management
   ↓
Credential
   ↓
Generate
   ↓
Validate voter list
   ↓
Generate random credential
   ↓
Hash credential
   ↓
Store hash
   ↓
Issue credential
   ↓
Optional QR
```

---

# 14. Credential Security

Credential:

```text
plaintext
```

hanya tersedia saat issuance.

Database:

```text
credential_hash
```

Jangan menyimpan plaintext.

Jangan menampilkan credential melalui endpoint GET setelah issuance.

---

# 15. Flow H — Generate QR

Optional.

```text
Credential
   ↓
Generate QR
   ↓
Encode random credential/reference
   ↓
Create QR image
   ↓
Print / distribute
```

QR tidak boleh berisi:

```text
student_id
name
class
candidate_id
```

---

# 16. Flow I — Schedule Election

## Actor

```text
ADMIN
```

## Flow

```text
Election DRAFT
   ↓
Set start/end
   ↓
Schedule
   ↓
Validate readiness
   ↓
Status = SCHEDULED
```

Sistem dapat otomatis membuka election ketika waktu mulai tercapai jika fitur scheduler diaktifkan.

---

# 17. Flow J — Open Election

## Manual Open

```text
Election Detail
   ↓
Buka Voting
   ↓
Show readiness checklist
   ↓
Confirm
   ↓
Server validation
   ↓
Transaction
   ↓
Status = OPEN
   ↓
Audit log
```

---

# 18. Open Election Preconditions

Semua harus valid:

```text
Election exists
Election state allows OPEN
At least one candidate
At least one voter
Valid voting window
Credential system ready
```

Jika gagal:

```text
Election belum siap dibuka.
```

Tampilkan reason yang aman.

---

# 19. Flow K — Automatic Open

Jika scheduled opening digunakan:

```text
Scheduler
   ↓
Find SCHEDULED elections
   ↓
Check start time
   ↓
Validate readiness
   ↓
OPEN
   ↓
Audit event
```

Jika readiness gagal:

```text
Election tetap SCHEDULED
```

dan sistem membuat operational alert.

---

# 20. Flow L — Voter Access

## Actor

```text
VOTER
```

## Flow

```text
/vote
   ↓
Input Token/PIN
   ↓
Submit
   ↓
Validate credential
   ↓
Find eligible election
   ↓
Check election OPEN
   ↓
Check credential unused
   ↓
Create voting session
   ↓
Display candidates
```

---

# 21. Voter Authentication Security

Jangan mengirim ke browser:

```text
student_id
name
class
credential database ID
```

jika tidak diperlukan.

Voting session hanya membawa context minimum.

---

# 22. Invalid Credential Flow

```text
Input Credential
   ↓
Validate
   ↓
Invalid
   ↓
Generic error
```

Pesan:

```text
Token tidak dapat digunakan.
Silakan periksa kembali atau hubungi panitia.
```

Jangan membedakan secara detail:

```text
token tidak ditemukan
token sudah digunakan
token salah election
```

jika hal tersebut memungkinkan credential enumeration.

---

# 23. Flow M — Candidate Selection

```text
Voting Session
   ↓
Candidate List
   ↓
Review candidate
   ↓
Select ONE candidate
   ↓
Selection state
   ↓
Lanjutkan
```

UI menggunakan radio/select-one behavior.

---

# 24. Candidate Validation

Client:

```text
candidate_id
```

Server:

```text
candidate belongs to active election
candidate is valid
election is OPEN
session is valid
```

Jika tidak valid:

```text
Pilihan tidak valid.
```

---

# 25. Flow N — Candidate Detail

```text
Candidate Card
   ↓
Lihat detail
   ↓
Vision + Mission
   ↓
Back
```

Tidak membuat perubahan voting.

Optional:

```text
Pilih Kandidat Ini
```

langsung mengubah selection state, tetapi belum menyimpan suara.

---

# 26. Flow O — Confirmation

Setelah kandidat dipilih:

```text
Candidate selected
   ↓
Lanjutkan
   ↓
Confirmation screen
```

Tampilkan:

```text
Anda memilih:

01
Ahmad

Pilihan tidak dapat diubah setelah dikonfirmasi.
```

Actions:

```text
Kembali
Kirim Suara
```

---

# 27. Flow P — Cast Vote

Ini adalah flow paling kritis.

```text
Confirm
   ↓
POST /voting/cast
   ↓
Authenticate voting session
   ↓
Validate election OPEN
   ↓
Lock voter credential/eligibility record
   ↓
Check unused
   ↓
Validate candidate
   ↓
BEGIN TRANSACTION
   ↓
Create anonymous ballot
   ↓
Mark eligibility USED/VOTED
   ↓
COMMIT
   ↓
Invalidate voting session
   ↓
Emit aggregate progress event
   ↓
Success
```

---

# 28. Anonymous Ballot Boundary

Secara konseptual:

```text
Voter Eligibility
      │
      │  "boleh memilih?"
      ↓
Voting Transaction
      │
      ├── mark voter as voted
      │
      └── create ballot
              │
              └── candidate_id
```

Database dan application layer harus menghindari struktur query/API yang membuat:

```text
voter → candidate
```

mudah ditelusuri.

---

# 29. Vote Transaction

Atomic:

```text
BEGIN

lock eligibility

validate OPEN

validate unused

validate candidate

create ballot

mark eligibility voted

COMMIT
```

Jika salah satu gagal:

```text
ROLLBACK
```

Tidak boleh terjadi:

```text
ballot created
BUT voter remains NOT_VOTED
```

atau sebaliknya.

---

# 30. Double Vote Flow

Scenario:

```text
Browser A ──┐
            ├── POST /voting/cast
Browser B ──┘
```

Server:

```text
Request A → lock → success
Request B → waits → detects used → reject
```

Expected:

```text
1 successful ballot
1 rejected request
```

Database constraint tetap menjadi defense-in-depth.

---

# 31. Double Submit UX

Saat submit:

```text
Mengirim suara...
```

Button disabled.

Namun:

> Disabled button bukan security control.

Backend harus tetap menangani duplicate request.

---

# 32. Network Retry Flow

Jika client kehilangan response:

```text
POST /voting/cast
       ↓
Server successfully commits
       ↓
Network failure
       ↓
Client does not receive response
       ↓
Client retries with same Idempotency-Key
       ↓
Server returns previous result
```

Tujuan:

```text
Tidak membuat vote kedua.
```

---

# 33. Vote Success Flow

```text
Transaction committed
   ↓
Session invalidated
   ↓
Confirmation generated
   ↓
Progress aggregate updated
   ↓
Success page
```

Success page:

```text
Suara berhasil disimpan.

Kode konfirmasi:
XXXX-XXXX
```

Confirmation tidak mengandung:

```text
voter identity
candidate ID
candidate name
```

---

# 34. Vote Failure Flow

Jika transaction gagal:

```text
ROLLBACK
   ↓
No vote stored
   ↓
Generic error
```

User dapat retry jika aman.

---

# 35. Election Closed During Voting

Scenario:

```text
Voter opens session
       ↓
Admin closes election
       ↓
Voter submits
```

Server:

```text
check election state
       ↓
CLOSED
       ↓
reject vote
```

Response:

```text
Voting telah ditutup.
```

Tidak boleh membuat ballot.

---

# 36. Voting Session Expiry

```text
Voting session created
       ↓
Idle
       ↓
Session expires
       ↓
Candidate page
       ↓
Request
       ↓
Reject
```

UI:

```text
Sesi voting telah berakhir.
Silakan login kembali.
```

---

# 37. Flow Q — Live Monitoring

## Actor

```text
ADMIN
OPERATOR
```

## Flow

```text
Open Monitoring
   ↓
Load aggregate
   ↓
Subscribe realtime channel
   ↓
Vote accepted
   ↓
Aggregate event
   ↓
Dashboard updates
```

Data:

```text
total
voted
remaining
percentage
```

Tidak ada:

```text
candidate result
```

selama OPEN.

---

# 38. Monitoring Disconnect

```text
Realtime connection lost
       ↓
Show "Disconnected"
       ↓
Keep last known data
       ↓
Allow refresh
       ↓
Reconnect
```

UI harus memberi tahu bahwa data mungkin stale.

---

# 39. Flow R — Close Election

## Actor

```text
ADMIN
```

Flow:

```text
Monitoring
   ↓
Tutup Voting
   ↓
Confirmation
   ↓
Server authorization
   ↓
Validate state
   ↓
Transaction
   ↓
Status = CLOSED
   ↓
Audit log
   ↓
Results available
```

---

# 40. Close Confirmation

Text:

```text
Tutup voting?

Setelah voting ditutup, pemilih tidak dapat memberikan suara lagi.

Tindakan ini tidak dapat dibatalkan pada MVP.
```

Actions:

```text
Batal
Tutup Voting
```

---

# 41. Close Preconditions

```text
Election = OPEN
```

Tidak perlu menunggu semua voter memilih.

Jika:

```text
671 / 842
```

election tetap dapat ditutup sesuai jadwal/keputusan admin.

---

# 42. Flow S — Result Calculation

Setelah CLOSED:

```text
Election CLOSED
   ↓
Result service
   ↓
Count ballots
   ↓
Group by candidate
   ↓
Calculate percentage
   ↓
Calculate participation
   ↓
Persist/cache aggregate if needed
   ↓
Results available
```

Result calculation tidak boleh mengubah ballot.

---

# 43. Result Integrity

Result harus dapat diverifikasi dari:

```text
ballot count
+
election configuration
```

Invariant:

```text
SUM(candidate_votes) == total_valid_ballots
```

dan:

```text
total_valid_ballots <= total_eligible_voters
```

---

# 44. Flow T — View Results

```text
Election Detail
   ↓
Hasil
   ↓
Check CLOSED
   ↓
Load aggregate results
   ↓
Display chart
   ↓
Display table
   ↓
Display participation
```

Jika OPEN:

```text
Hasil belum tersedia.
```

---

# 45. Flow U — Export Results

```text
Results
   ↓
Export
   ↓
Select format
   ├── XLSX
   ├── PDF
   └── CSV
   ↓
Authorization
   ↓
Check CLOSED
   ↓
Generate
   ↓
Download
```

Jika file besar:

```text
Queue
  ↓
Processing
  ↓
Completed
  ↓
Download
```

---

# 46. Flow V — Print Results

```text
Results
   ↓
Print
   ↓
Print stylesheet
   ↓
Hide navigation/actions
   ↓
Print final result
```

Tidak mencetak:

```text
voter identity → candidate relationship
```

---

# 47. Flow W — Audit Log

Events:

```text
Admin login
Election created
Candidate changed
Voter imported
Credential issued
Election opened
Election closed
Export generated
Unauthorized access
```

Flow:

```text
Action
   ↓
Domain operation
   ↓
Audit event
   ↓
Audit storage
```

Audit logging tidak boleh mengubah voting transaction outcome.

---

# 48. Audit Privacy

Audit tidak boleh mencatat:

```text
Siswa A memilih Ahmad
```

Jika perlu mencatat vote event:

```text
VOTE_ACCEPTED
election_id
timestamp
```

dengan metadata minimum dan tanpa candidate identity.

---

# 49. Flow X — Operator Assistance

Operator dapat:

```text
Import voter
Generate/issue credential
Monitor participation
Help voter access
```

Operator tidak dapat:

```text
View ballot mapping
Change result
Edit candidate during OPEN
Reopen CLOSED election
```

---

# 50. Assisted Voting Flow

Jika operator membantu:

```text
Voter arrives
   ↓
Operator verifies eligibility
   ↓
Voter receives/uses credential
   ↓
Voter interacts with voting UI
   ↓
Voter selects candidate
   ↓
Voter confirms
   ↓
Vote submitted
```

Operator sebaiknya tidak memilih kandidat atas nama voter.

Jika mode TPS mengizinkan bantuan khusus, proses harus memiliki aturan operasional dan audit yang jelas.

---

# 51. Flow Y — Voter Uses QR

```text
Scan QR
   ↓
Open voting page
   ↓
Token/reference extracted
   ↓
Server validates
   ↓
Voting session
   ↓
Candidate selection
```

QR tidak langsung melakukan:

```text
automatic vote
```

QR hanya membantu authentication/entry.

---

# 52. Flow Z — Credential Regeneration

Ini adalah operasi sensitif.

```text
Admin
   ↓
Credential Management
   ↓
Regenerate
   ↓
Confirmation
   ↓
Check election state
   ↓
Revoke previous credential
   ↓
Generate new credential
   ↓
Audit
```

Untuk election OPEN, regeneration massal sebaiknya:

```text
disabled
```

atau membutuhkan emergency procedure yang sangat terbatas.

---

# 53. Unauthorized Access Flow

Contoh:

```text
OPERATOR
   ↓
GET /admin/audit-logs
```

Server:

```text
Authorization check
   ↓
DENY
   ↓
403
```

UI:

```text
Anda tidak memiliki izin untuk mengakses halaman ini.
```

---

# 54. IDOR Attack Flow

Contoh:

```text
Operator memiliki election 10

Request:
GET /elections/11/voters
```

Server:

```text
Authenticate
   ↓
Authorize election scope
   ↓
Deny if not permitted
```

Tidak cukup hanya:

```text
Voter::find()
```

---

# 55. Flow AA — Election Archive

```text
CLOSED
   ↓
Archive
   ↓
Confirmation
   ↓
Authorization
   ↓
Archive
   ↓
Audit
```

Archived election:

```text
Read-only
```

Tidak dapat:

```text
vote
edit candidate
edit voter
open
```

---

# 56. Flow AB — Disaster / Incident Handling

Jika terjadi masalah kritis selama voting:

```text
Detect incident
   ↓
Freeze affected operation if required
   ↓
Preserve logs
   ↓
Do not modify ballots manually
   ↓
Investigate
   ↓
Document incident
   ↓
Resume/terminate according to election procedure
```

Jangan langsung melakukan:

```text
DELETE ballot
UPDATE vote
RESET voter status
```

tanpa prosedur forensik dan audit.

---

# 57. Flow AC — Database Failure During Vote

```text
Voter submits
   ↓
DB transaction begins
   ↓
DB failure
   ↓
ROLLBACK
   ↓
No partial vote
```

Client:

```text
Vote belum dapat dikonfirmasi.
Silakan coba kembali.
```

Jika commit sudah terjadi tetapi response hilang:

```text
Idempotency-Key
```

digunakan untuk retry aman.

---

# 58. Flow AD — Redis Failure

Jika Redis digunakan untuk:

```text
cache
realtime
queue
```

Redis failure tidak boleh menyebabkan:

```text
vote dianggap berhasil
```

Source of truth:

```text
PostgreSQL
```

Voting tetap bergantung pada database transaction.

Realtime dapat mengalami fallback:

```text
manual refresh
```

---

# 59. Flow AE — Queue Failure

Untuk:

```text
Import
Export
Notification
```

gunakan queue retry.

Jika gagal:

```text
Job failed
   ↓
Retry policy
   ↓
Dead-letter/failed jobs
   ↓
Admin notification
```

Voting transaction tidak boleh bergantung pada queue untuk menentukan apakah vote berhasil.

---

# 60. Flow AF — Election Day Operational Checklist

Sebelum OPEN:

```text
[ ] Election configured
[ ] Candidate final
[ ] Voter list final
[ ] Credential generated
[ ] Credential distribution ready
[ ] Start/end time verified
[ ] Database backup verified
[ ] Monitoring available
[ ] Realtime tested
[ ] Admin account tested
[ ] Network tested
[ ] Test vote completed in staging
```

---

# 61. Flow AG — Test Vote

Sebelum production:

```text
Create test election
   ↓
Create test voters
   ↓
Generate test credentials
   ↓
Open election
   ↓
Cast test votes
   ↓
Verify one-vote constraint
   ↓
Close
   ↓
Verify results
   ↓
Verify export
```

Jangan menggunakan data voter production untuk test.

---

# 62. Flow AH — End of Election

```text
Close election
   ↓
Verify final participation
   ↓
Calculate results
   ↓
Verify result invariant
   ↓
Generate export
   ↓
Print if needed
   ↓
Archive election
   ↓
Retain audit/logs according to policy
```

---

# 63. Flow AI — Complete End-to-End

```text
                    ADMIN
                      │
                      ▼
              Create Election
                      │
                      ▼
              Add Candidates
                      │
                      ▼
               Import Voters
                      │
                      ▼
            Generate Credentials
                      │
                      ▼
             Readiness Check
                      │
                      ▼
                Open Election
                      │
          ┌───────────┴───────────┐
          │                       │
          ▼                       ▼
       VOTER                   MONITOR
          │                       │
          ▼                       ▼
   Credential Login        Participation
          │                       │
          ▼                       │
    Candidate List                │
          │                       │
          ▼                       │
   Select One Candidate            │
          │                       │
          ▼                       │
      Confirmation                │
          │                       │
          ▼                       │
       Cast Vote                  │
          │                       │
          ├───────────┐           │
          ▼           │           │
 Anonymous Ballot     │           │
          │           │           │
          ▼           │           │
 Mark Eligibility     │           │
          │           │           │
          └───────────┴───────────┘
                      │
                      ▼
                Close Election
                      │
                      ▼
               Calculate Results
                      │
                      ▼
                View Results
                      │
             ┌────────┴────────┐
             ▼                 ▼
          Export              Print
```

---

# 64. Critical Security Checkpoints

| Checkpoint | Security Requirement |
|---|---|
| Login | Authentication + rate limit |
| Election creation | Authorization |
| Candidate change | State restriction |
| Voter import | Validation |
| Credential generation | Secure random + hash |
| Voting session | Eligibility validation |
| Candidate selection | Election scoping |
| Cast vote | Transaction + lock + constraint |
| Retry | Idempotency |
| Monitoring | Aggregate only |
| Close | Authorization + state transition |
| Results | CLOSED only |
| Export | Authorization + CLOSED |
| Audit | No voter → candidate mapping |

---

# 65. Privacy Boundary

Informasi yang boleh diketahui admin:

```text
Total voters
Voted count
Not voted count
Participation
```

Setelah CLOSED:

```text
Candidate vote totals
```

Informasi yang tidak boleh tersedia melalui aplikasi:

```text
Voter A → Candidate 01
Voter B → Candidate 03
```

---

# 66. Flow Invariants

### FLOW-01

Satu credential hanya menghasilkan satu successful vote.

### FLOW-02

Satu voter eligibility hanya dapat berubah ke `VOTED` satu kali.

### FLOW-03

Vote hanya dapat dibuat ketika election `OPEN`.

### FLOW-04

Candidate harus berasal dari election yang sedang divoting.

### FLOW-05

Ballot tidak dapat diubah melalui UI normal.

### FLOW-06

Result tidak tersedia selama election `OPEN`.

### FLOW-07

Admin/operator tidak mendapatkan voter → candidate mapping.

### FLOW-08

Retry request tidak membuat duplicate vote.

### FLOW-09

Closing election menghentikan voting baru.

### FLOW-10

Archived election bersifat read-only.

---

# 67. Error Handling Matrix

| Scenario | Expected Behavior |
|---|---|
| Invalid admin login | Generic error |
| No permission | 403 |
| Election not found | 404 |
| Invalid state transition | 409 |
| Invalid candidate | 422 |
| Invalid credential | Generic voting error |
| Credential already used | Generic voting error |
| Session expired | Re-authentication |
| Election closed | Reject vote |
| Concurrent vote | Max one success |
| Network retry | Idempotent |
| DB transaction failure | Rollback |
| Result before close | Reject |
| Export before close | Reject |

---

# 68. UX Copy Principles

Gunakan bahasa:

```text
Singkat
Jelas
Tidak menyalahkan pengguna
Tidak membocorkan security detail
```

Contoh:

Buruk:

```text
Credential hash tidak cocok dengan record voter.
```

Baik:

```text
Token tidak dapat digunakan.
```

---

# 69. Operational Roles Boundary

## Admin

```text
Configure
Open
Close
View results
Export
```

## Operator

```text
Import
Credential operation
Monitor
Assist
```

## Voter

```text
Authenticate
View candidates
Select
Confirm
```

Tidak ada role yang dapat:

```text
edit individual ballot
```

---

# 70. Definition of Done

User flow dianggap siap implementasi jika:

- [ ] Semua actor memiliki flow.
- [ ] Happy path sudah didefinisikan.
- [ ] Error path sudah didefinisikan.
- [ ] Security checkpoint sudah didefinisikan.
- [ ] Election state transition jelas.
- [ ] Voting transaction jelas.
- [ ] Double vote flow jelas.
- [ ] Retry/idempotency jelas.
- [ ] Monitoring privacy jelas.
- [ ] Result visibility jelas.
- [ ] Export flow jelas.
- [ ] Incident flow dasar tersedia.

---

# 71. Next Document

Dokumen berikutnya:

```text
08_TEST_PLAN.md
```

Fokus:

- Unit test.
- Feature test.
- Integration test.
- API test.
- Security test.
- Authorization test.
- Double-voting/concurrency test.
- Anonymous ballot privacy test.
- Load test.
- E2E test.
- UAT.
- Election-day operational test.
