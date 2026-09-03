# System Architecture — Sistem E-Voting Sekolah

**Dokumen:** 04 — System Architecture  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:**  
- `PRD_E_Voting_Sekolah.md`
- `02_ERD_Database_Design_E_Voting.md`
- `03_SECURITY_Threat_Model_E_Voting.md`

**Target stack:**
- Laravel
- PHP
- PostgreSQL
- Livewire
- Redis
- Laravel Reverb
- Nginx
- Queue Worker
- Object/File Storage

---

# 1. Tujuan

Dokumen ini mendefinisikan arsitektur teknis aplikasi e-voting sekolah.

Fokus utama:

1. Memisahkan domain pemilihan dari authentication.
2. Memusatkan proses voting pada `VotingService`.
3. Menjaga anonymous ballot.
4. Menjamin atomicity proses voting.
5. Membatasi akses berdasarkan role dan election.
6. Memisahkan operational data dari ballot data.
7. Mendukung dashboard realtime tanpa membocorkan pilihan pemilih.
8. Memudahkan testing dan maintenance.

---

# 2. Architectural Principles

## 2.1 Server-side authority

Browser tidak pernah menjadi sumber kebenaran untuk:

- Election status.
- Voter eligibility.
- Credential validity.
- Candidate validity.
- Vote uniqueness.
- Result availability.

Semua harus diverifikasi server-side.

---

## 2.2 Single voting entry point

Semua proses pemberian suara harus melalui:

```text
VotingService
```

Controller/Livewire component tidak boleh mengimplementasikan transaksi voting secara langsung.

---

## 2.3 Anonymous ballot by design

Arsitektur tidak boleh membuat hubungan:

```text
Voter → Ballot
```

Ballot hanya berhubungan dengan:

```text
Election → Candidate
```

---

## 2.4 Transactional voting

Proses:

```text
validate
lock credential
create ballot
mark credential used
mark voter voted
commit
```

harus dilakukan sebagai satu database transaction.

---

## 2.5 Least privilege

Setiap role hanya memperoleh fungsi yang diperlukan.

```text
SUPER_ADMIN
    ↓
ADMIN
    ↓
OPERATOR
    ↓
VOTER
```

Role lebih rendah tidak boleh mengakses fungsi role lebih tinggi.

---

# 3. High-Level Architecture

```text
                         INTERNET
                            │
                            ▼
                    ┌────────────────┐
                    │     Nginx      │
                    │ TLS / Reverse  │
                    │     Proxy      │
                    └───────┬────────┘
                            │
                            ▼
                 ┌──────────────────────┐
                 │       Laravel        │
                 │      Application     │
                 ├──────────────────────┤
                 │ Web / Livewire       │
                 │ Authentication       │
                 │ Authorization         │
                 │ Domain Services      │
                 │ Validation           │
                 └──────────┬───────────┘
                            │
          ┌─────────────────┼─────────────────┐
          │                 │                 │
          ▼                 ▼                 ▼
    ┌───────────┐     ┌───────────┐    ┌────────────┐
    │PostgreSQL │     │   Redis   │    │   Reverb   │
    │  Primary  │     │Cache/Queue│    │ WebSocket  │
    └───────────┘     └─────┬─────┘    └────────────┘
                            │
                            ▼
                      Queue Worker
```

---

# 4. Application Layers

Aplikasi menggunakan pendekatan layered architecture.

```text
Presentation
     ↓
Application
     ↓
Domain
     ↓
Infrastructure
     ↓
Database / External Services
```

---

# 5. Presentation Layer

Berisi antarmuka pengguna.

Teknologi:

```text
Laravel Blade
Livewire
Tailwind CSS
Alpine.js
```

Contoh:

```text
Admin Dashboard
Election Management
Candidate Management
Voter Import
Voting Page
Result Dashboard
```

Presentation layer tidak boleh mengandung business logic voting yang kompleks.

---

# 6. Application Layer

Application layer mengorkestrasi use case.

Contoh service:

```text
ElectionService
CandidateService
VoterService
CredentialService
VotingService
ResultService
AuditService
```

Contoh:

```text
VotingController / Voting Livewire Component
                  │
                  ▼
            VotingService
                  │
          ┌───────┼────────┐
          ▼       ▼        ▼
      Credential Voter  Candidate
          │       │        │
          └───────┼────────┘
                  ▼
              Ballot
```

---

# 7. Domain Services

## 7.1 ElectionService

Tanggung jawab:

- Create election.
- Update draft election.
- Schedule election.
- Open election.
- Close election.
- Archive election.
- Validate state transition.

Tidak boleh menghitung suara secara langsung.

---

## 7.2 CandidateService

Tanggung jawab:

- Create candidate.
- Update candidate sebelum election OPEN.
- Reorder candidate jika diperbolehkan.
- Upload candidate photo.
- Lock candidate ketika election OPEN.

---

## 7.3 VoterService

Tanggung jawab:

- Import voter.
- Validate student ID.
- Detect duplicates.
- Assign voter group.
- Check eligibility.
- Manage voter status.

Tidak boleh mengetahui pilihan kandidat.

---

## 7.4 CredentialService

Tanggung jawab:

- Generate credential.
- Hash credential.
- Validate credential.
- Revoke credential.
- Mark credential as used.

CredentialService tidak boleh membuat ballot.

---

## 7.5 VotingService

Ini adalah service paling kritis.

Tanggung jawab:

- Validate election.
- Validate credential.
- Lock credential.
- Validate candidate.
- Create anonymous ballot.
- Mark credential used.
- Mark voter voted.
- Commit transaction.
- Return generic confirmation.

VotingService tidak boleh menulis data:

```text
voter_id + candidate_id
```

ke log atau tabel audit.

---

## 7.6 ResultService

Tanggung jawab:

- Verify election CLOSED.
- Count ballots.
- Calculate percentage.
- Generate statistics.
- Prepare export data.

ResultService tidak boleh dipanggil untuk menampilkan hasil sebelum election CLOSED.

---

## 7.7 AuditService

Tanggung jawab:

- Record administrative events.
- Record security events.
- Store actor.
- Store election context.
- Store action metadata.

AuditService harus memiliki guardrail agar developer tidak secara tidak sengaja mencatat vote choice.

---

# 8. Repository / Data Access

Untuk MVP, Laravel Eloquent dapat digunakan langsung pada service layer dengan query yang terstruktur.

Contoh:

```text
VotingService
    ↓
Eloquent Model
    ↓
PostgreSQL
```

Repository abstraction tidak wajib dibuat untuk setiap model.

Repository dapat digunakan jika:

- Query sangat kompleks.
- Ada kebutuhan multiple data sources.
- Domain query semakin besar.
- Testing memerlukan abstraction tertentu.

Prioritas MVP adalah menghindari abstraction berlebihan.

---

# 9. Authentication Architecture

Authentication dipisahkan dari authorization.

```text
Authentication
    ↓
"Siapa user ini?"

Authorization
    ↓
"Apa yang boleh dilakukan user ini?"
```

Contoh:

```text
User authenticated
      │
      ▼
Role / Permission
      │
      ▼
Election access
      │
      ▼
Action allowed
```

---

# 10. Admin Authentication

Admin login menggunakan:

```text
email
password
```

Recommended:

- Password hashing Laravel.
- Session regeneration.
- Rate limiting.
- Secure cookie.
- HttpOnly.
- SameSite.
- HTTPS.
- Optional MFA.

---

# 11. Voter Authentication

Voter menggunakan credential/token sesuai workflow sekolah.

Contoh:

```text
QR / Token
    ↓
Credential validation
    ↓
Voter eligibility
    ↓
Voting session
```

Token tidak boleh berupa:

```text
student_id
class + number
tanggal lahir
```

Token harus random dan memiliki entropy cukup.

---

# 12. Voting Session

Setelah credential berhasil diverifikasi:

```text
Credential
    ↓
Authenticated Voting Session
```

Session menyimpan informasi minimum yang diperlukan.

Hindari menyimpan:

```text
candidate_id selected
```

sebelum ballot final.

Jika session menyimpan voter reference untuk authorization, jangan memasukkan reference tersebut ke ballot.

---

# 13. Voting Flow

```text
                    VOTER
                      │
                      ▼
                Login / QR
                      │
                      ▼
             Validate Credential
                      │
                      ▼
             Validate Election OPEN
                      │
                      ▼
             Load Candidates
                      │
                      ▼
                Select One
                      │
                      ▼
                Confirmation
                      │
                      ▼
              POST / VOTE
                      │
                      ▼
             ┌────────────────┐
             │ VotingService  │
             └───────┬────────┘
                     │
                 BEGIN TX
                     │
                     ▼
              Lock Credential
                     │
                     ▼
              Validate Candidate
                     │
                     ▼
              Create Anonymous
                  Ballot
                     │
                     ▼
              Mark Credential
                   USED
                     │
                     ▼
              Mark Voter VOTED
                     │
                     ▼
                  COMMIT
                     │
                     ▼
                Confirmation
```

---

# 14. Detailed Voting Transaction

Pseudo-code:

```php
DB::transaction(function () {

    $credential = lockCredential();

    validateCredential($credential);

    validateElectionOpen();

    validateCandidate();

    createAnonymousBallot();

    markCredentialUsed();

    markVoterVoted();

});
```

Jika salah satu langkah gagal:

```text
ROLLBACK
```

Tidak boleh terjadi kondisi:

```text
credential = USED
ballot = tidak ada
```

atau:

```text
ballot = ada
credential = UNUSED
```

setelah transaction selesai.

---

# 15. Database Transaction Isolation

Default PostgreSQL transaction isolation dapat digunakan untuk sebagian besar flow.

Untuk credential:

```text
SELECT ... FOR UPDATE
```

digunakan untuk memastikan concurrent request tidak menggunakan credential yang sama.

Jika sistem berkembang dan contention meningkat, isolation/locking strategy harus diuji dengan concurrency test.

---

# 16. Anonymous Ballot Architecture

Data authorization:

```text
voters
    │
    ▼
voting_credentials
```

Data vote:

```text
ballots
    │
    ▼
candidates
```

Keduanya hanya berbagi:

```text
election_id
```

secara konseptual, bukan hubungan individual.

```text
VOTER
 │
 └── CREDENTIAL
       │
       X
       │
       │ no ballot relationship
       │
       ▼
     BALLOT
       │
       ▼
   CANDIDATE
```

---

# 17. Ballot Creation

Ballot dibuat dengan:

```text
election_id
candidate_id
ballot_hash
created_at
```

Tidak dengan:

```text
voter_id
student_id
credential_id
```

Ballot setelah dibuat dianggap immutable.

---

# 18. Ballot Hash

`ballot_hash` dibuat dari random nonce atau random identifier yang tidak berasal dari voter identity.

Contoh konseptual:

```text
random_bytes
     ↓
hash
     ↓
ballot_hash
```

Jangan membuat:

```text
hash(student_id + candidate_id)
```

karena dapat menciptakan correlation signal.

---

# 19. Result Architecture

Hasil dihitung dari ballot:

```text
Ballots
   ↓
COUNT(*)
GROUP BY candidate_id
   ↓
Result DTO
   ↓
Dashboard / Export
```

ResultService harus melakukan:

```text
if election.status != CLOSED
    reject
```

---

# 20. Participation Architecture

Partisipasi dihitung dari:

```text
voters
```

bukan dari ballot.

```text
Voters
  │
  ├── NOT_VOTED
  └── VOTED
        │
        ▼
Participation %
```

Formula:

```text
VOTED / TOTAL_VOTERS × 100
```

---

# 21. Realtime Architecture

Laravel Reverb digunakan untuk dashboard monitoring.

Flow:

```text
VotingService
     │
     ▼
VoteAccepted Event
     │
     ▼
Queue / Event
     │
     ▼
Reverb
     │
     ▼
Admin Dashboard
```

Broadcast hanya data agregat:

```text
voted_count
remaining_count
participation
```

Jangan broadcast:

```text
voter_id
student_id
candidate_id selected
credential
token
```

---

# 22. Realtime Event

Contoh event:

```text
VotingProgressUpdated
```

Payload:

```json
{
  "election_id": 10,
  "voted_count": 671,
  "eligible_count": 842,
  "participation_percentage": 79.69
}
```

Tidak ada:

```text
voter_id
candidate_id
```

---

# 23. Queue Architecture

Queue digunakan untuk pekerjaan yang tidak perlu memblokir request utama.

Contoh:

```text
Excel import
PDF export
Large CSV export
Email notification
Report generation
Audit aggregation
```

Jangan memindahkan core ballot transaction ke asynchronous queue jika hal tersebut membuat confirmation voting tidak atomic.

Voting acceptance harus tetap memiliki transaksi database yang jelas.

---

# 24. Redis Usage

Redis digunakan untuk:

```text
Cache
Queue
Rate limiting
Session (optional)
Temporary locks (only where appropriate)
```

Jangan menggunakan Redis sebagai source of truth untuk:

```text
ballots
voter voting status
credential status
```

Source of truth tetap PostgreSQL.

---

# 25. Rate Limiting

Rate limit diterapkan pada:

### Admin login

Ketat.

### Voter credential validation

Cukup ketat untuk mencegah brute force.

### Voting endpoint

Mencegah abuse tetapi tidak mengganggu legitimate election traffic.

### API/public endpoint

Berdasarkan endpoint dan risiko.

Rate limit harus diuji dengan simulasi election-day traffic.

---

# 26. Authorization Architecture

Gunakan:

```text
Middleware
+
Policies
+
Gates
```

Contoh:

```text
ElectionPolicy
CandidatePolicy
VoterPolicy
ResultPolicy
AuditLogPolicy
```

---

# 27. Role Matrix

| Capability | Super Admin | Admin | Operator | Voter |
|---|---:|---:|---:|---:|
| Manage system users | ✓ | - | - | - |
| Create election | ✓ | ✓ | - | - |
| Edit election | ✓ | ✓ | - | - |
| Open election | ✓ | ✓ | - | - |
| Close election | ✓ | ✓ | - | - |
| Manage candidates | ✓ | ✓ | - | - |
| Import voters | ✓ | ✓ | ✓ | - |
| Generate credentials | ✓ | ✓ | ✓ | - |
| Assist voter | ✓ | ✓ | ✓ | - |
| Vote | - | - | - | ✓ |
| View participation | ✓ | ✓ | ✓ | - |
| View results | ✓ | ✓ | - | - |
| Export results | ✓ | ✓ | - | - |
| View audit logs | ✓ | ✓ | limited | - |

Permission detail dapat berkembang menjadi permission-based authorization jika role menjadi lebih kompleks.

---

# 28. Module Structure

Rekomendasi struktur domain:

```text
app/
├── Actions/
├── Console/
├── Domain/
│   ├── Elections/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Policies/
│   │   └── DTOs/
│   │
│   ├── Candidates/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Policies/
│   │
│   ├── Voters/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── DTOs/
│   │
│   ├── Credentials/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── DTOs/
│   │
│   ├── Voting/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Events/
│   │   └── Exceptions/
│   │
│   └── Results/
│       ├── Services/
│       ├── DTOs/
│       └── Queries/
│
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
├── Livewire/
├── Models/
├── Policies/
├── Jobs/
├── Events/
├── Listeners/
└── Support/
```

---

# 29. Domain Model Placement

Untuk MVP Laravel, model dapat ditempatkan secara bertahap.

Sederhana:

```text
app/Models/
```

Jika domain semakin besar:

```text
app/Domain/Voting/Models/
```

Rekomendasi awal:

```text
app/Models/
```

dengan service/domain organization di:

```text
app/Domain/
```

Hal ini mengurangi kompleksitas awal.

---

# 30. Recommended Models

```text
User
Election
ElectionAdmin
VoterGroup
Voter
VotingCredential
Candidate
Ballot
AuditLog
```

---

# 31. Recommended Services

```text
ElectionService
CandidateService
VoterService
CredentialService
VotingService
ResultService
AuditService
```

---

# 32. Recommended DTOs

DTO membantu menghindari passing array bebas di seluruh aplikasi.

Contoh:

```text
CreateElectionData
CreateCandidateData
ImportVoterData
CreateCredentialData
CastVoteData
ElectionResultData
ParticipationStatsData
```

Untuk `CastVoteData`, jangan memasukkan field yang tidak diperlukan.

Contoh:

```text
electionId
candidateId
credential
```

Jangan:

```text
voterId
candidateId
```

jika voter dapat diperoleh secara aman dari credential/session.

---

# 33. Request Validation

Gunakan Laravel Form Request untuk input HTTP.

Contoh:

```text
CreateElectionRequest
UpdateElectionRequest
CreateCandidateRequest
ImportVoterRequest
CastVoteRequest
```

Validasi input sebelum service dipanggil.

Namun business rules tetap berada pada domain/application service.

---

# 34. Controller Responsibility

Controller harus tipis.

Contoh:

```php
public function store(CastVoteRequest $request)
{
    $result = $this->votingService->cast(
        $request->validated()
    );

    return response()->json($result);
}
```

Controller tidak boleh:

```text
create ballot
update credential
update voter
```

secara manual.

---

# 35. Livewire Responsibility

Livewire component bertanggung jawab atas:

- State UI.
- Form interaction.
- Validation presentation.
- Calling service.
- Redirect/notification.

Tidak bertanggung jawab atas:

- Database transaction.
- Ballot creation.
- Credential locking.
- Vote counting logic.

---

# 36. API Boundary

Walaupun aplikasi menggunakan Livewire, domain service tetap dirancang agar dapat digunakan oleh API.

Contoh:

```text
POST /voting/cast
```

atau internal Livewire action:

```text
VotingService::cast()
```

Keduanya harus menggunakan business logic yang sama.

---

# 37. API Design Principle

API response tidak boleh membocorkan informasi internal.

Contoh sukses:

```json
{
  "success": true,
  "message": "Suara berhasil disimpan.",
  "confirmation": "..."
}
```

Contoh error:

```json
{
  "success": false,
  "message": "Voting tidak dapat diproses."
}
```

Hindari error seperti:

```text
Student 12345 belongs to election 10 but credential belongs to election 11.
```

karena terlalu banyak informasi.

---

# 38. Confirmation Reference

Setelah voting berhasil, sistem dapat memberikan reference:

```text
Vote successfully recorded.
Confirmation: ABCD-1234
```

Reference ini bukan:

```text
voter ID
credential ID
candidate ID
```

dan tidak boleh dapat digunakan untuk mengambil pilihan kandidat.

Tujuan reference:

- Memberikan feedback kepada pemilih.
- Membantu verifikasi bahwa request berhasil.
- Tidak membuktikan kandidat yang dipilih.

---

# 39. File Storage Architecture

Candidate photos:

```text
Storage
  └── candidates/
       └── {election-id}/
```

Jangan gunakan:

```text
student_id
```

sebagai nama file.

Contoh:

```text
candidates/10/random-uuid.webp
```

---

# 40. Export Architecture

Export tidak boleh mengubah source data.

Flow:

```text
Admin
  ↓
ResultService
  ↓
Result DTO
  ↓
Export Job
  ↓
CSV / XLSX / PDF
```

Export hanya tersedia setelah election CLOSED.

---

# 41. Dashboard Architecture

## Admin dashboard

Menampilkan:

```text
Total elections
Active election
Total voters
Voted
Not voted
Participation
Candidates
Results
```

## Saat voting OPEN

Jangan tampilkan:

```text
Candidate vote count
```

## Setelah CLOSED

Tampilkan:

```text
Candidate
Vote count
Percentage
Ranking
```

---

# 42. Monitoring Dashboard

Monitoring realtime menggunakan aggregate data.

```text
Voters
   ↓
voting_status
   ↓
Aggregate
   ↓
Reverb
   ↓
Dashboard
```

Contoh:

```text
Total: 842
Voted: 671
Remaining: 171
Participation: 79.69%
```

---

# 43. Error Handling

Gunakan domain exceptions.

Contoh:

```text
ElectionNotOpenException
InvalidCredentialException
CredentialAlreadyUsedException
CandidateUnavailableException
ElectionClosedException
UnauthorizedElectionAccessException
```

Global exception handler mengubah exception menjadi response yang aman.

---

# 44. Error Logging

Error internal dapat dicatat untuk debugging, tetapi jangan memasukkan:

```text
password
token plaintext
candidate choice + voter identity
```

Production logging:

```text
INFO
WARNING
ERROR
CRITICAL
```

Debug mode harus:

```text
APP_DEBUG=false
```

---

# 45. Security Logging Separation

Audit log:

```text
business/security events
```

Application log:

```text
technical errors
```

Contoh:

```text
Audit:
ADMIN_CLOSED_ELECTION

Application:
SQL connection timeout
```

Keduanya memiliki tujuan berbeda.

---

# 46. Deployment Architecture

Recommended production:

```text
                 Internet
                    │
                    ▼
               Load / Nginx
                    │
          ┌─────────┴─────────┐
          ▼                   ▼
    Laravel App A       Laravel App B
          │                   │
          └─────────┬─────────┘
                    │
          ┌─────────┼─────────┐
          ▼         ▼         ▼
     PostgreSQL   Redis    Reverb
          │
          ▼
       Backup
```

Untuk deployment sekolah kecil, satu application server dapat digunakan pada MVP dengan database dan Redis yang terisolasi.

---

# 47. Production Server Separation

Minimum:

```text
Nginx
Laravel
Queue Worker
PostgreSQL
Redis
Reverb
```

Idealnya PostgreSQL tidak exposed langsung ke internet.

---

# 48. Process Architecture

Server menjalankan:

```text
Nginx
PHP-FPM
Queue Worker
Reverb
Scheduler
```

Contoh:

```text
systemd / Supervisor
        │
        ├── php-fpm
        ├── queue:work
        └── reverb:start
```

Scheduler menjalankan:

```text
php artisan schedule:run
```

---

# 49. Scheduler

Scheduler dapat digunakan untuk:

- Election state automation.
- Credential expiration.
- Cleanup temporary files.
- Report generation.
- Health checks.

Namun opening/closing election yang bersifat critical harus tetap memiliki state validation di service.

Scheduler bukan satu-satunya security control.

---

# 50. Health Checks

Endpoint internal:

```text
/health
```

dapat memeriksa:

```text
Application
Database
Redis
Queue
```

Jangan menampilkan detail infrastructure kepada public.

Contoh public response:

```json
{
  "status": "ok"
}
```

---

# 51. Observability

Monitoring minimal:

```text
CPU
Memory
Disk
Database connections
Redis health
Queue size
HTTP error rate
Response latency
Voting success rate
```

Monitoring tidak boleh menangkap:

```text
candidate selected
voter identity
credential
```

---

# 52. Database Connection Policy

Application menggunakan connection pool/configuration yang sesuai deployment.

Jangan membuat koneksi database baru pada setiap operasi manual.

Semua query harus melalui Laravel database layer.

---

# 53. Caching

Cache aman untuk:

```text
Candidate display
Election metadata
Static configuration
```

Jangan cache:

```text
credential plaintext
voter-specific secret
ballot choice
```

Jika cache voter-specific digunakan, TTL harus pendek dan key harus aman.

---

# 54. Data Flow — Admin

```text
Admin
  ↓
Authentication
  ↓
Authorization
  ↓
Livewire / Controller
  ↓
Service
  ↓
Model
  ↓
PostgreSQL
  ↓
AuditService
```

---

# 55. Data Flow — Voter

```text
Voter
  ↓
Credential
  ↓
Authentication / Voting Session
  ↓
Candidate List
  ↓
Selection
  ↓
Confirmation
  ↓
VotingService
  ↓
Transaction
  ├── Lock credential
  ├── Create ballot
  ├── Mark credential used
  └── Mark voter voted
  ↓
Commit
  ↓
Confirmation
```

---

# 56. Data Flow — Result

```text
Admin
  ↓
Authorization
  ↓
ResultService
  ↓
Check election = CLOSED
  ↓
Query ballots
  ↓
GROUP BY candidate
  ↓
Result DTO
  ├── Dashboard
  ├── XLSX
  └── PDF
```

---

# 57. Data Flow — Realtime Participation

```text
Successful vote
      ↓
VotingService
      ↓
Transaction committed
      ↓
VotingProgressUpdated
      ↓
Reverb
      ↓
Admin dashboard
```

Event sebaiknya dipublish setelah transaction berhasil commit.

---

# 58. Queue Safety

Queue jobs harus:

- Idempotent jika memungkinkan.
- Tidak menyimpan credential plaintext.
- Tidak menyimpan candidate choice bersama voter identity.
- Memiliki retry policy.
- Memiliki failed job handling.

---

# 59. Testing Architecture

Testing dibagi:

```text
Unit Tests
Feature Tests
Integration Tests
Security Tests
Concurrency Tests
Browser Tests
```

---

# 60. Unit Tests

Test:

```text
Election state transition
Credential hashing
Result calculation
Participation calculation
Candidate validation
DTO validation
```

---

# 61. Feature Tests

Test:

```text
Admin login
Create election
Import voters
Create candidates
Generate credentials
Open election
Cast vote
Close election
View results
Export result
```

---

# 62. Security Tests

Test:

```text
Unauthorized admin access
IDOR
Credential brute force
Replay
Double vote
CSRF
XSS
SQL injection
Result-before-close
Candidate modification after open
Ballot update/delete
```

---

# 63. Concurrency Test

Scenario:

```text
Credential X
    │
    ├── Request A
    └── Request B
```

Expected:

```text
Request A = SUCCESS
Request B = REJECTED
```

Database harus memiliki:

```text
1 ballot
1 USED credential
1 VOTED voter
```

---

# 64. Anonymous Voting Test

Automated test harus memastikan schema/application tidak menghasilkan mapping:

```text
voter → candidate
```

Test dapat memeriksa:

```text
ballots table columns
audit logs
application logs
events
broadcast payloads
```

---

# 65. Architecture Invariants

### ARCH-01

Semua voting masuk melalui `VotingService`.

### ARCH-02

Voting transaction menggunakan PostgreSQL transaction.

### ARCH-03

Credential dikunci sebelum digunakan.

### ARCH-04

Ballot tidak menyimpan voter identity.

### ARCH-05

ResultService menolak election OPEN.

### ARCH-06

Candidate mutation ditolak setelah election OPEN.

### ARCH-07

Reverb tidak broadcast vote choice.

### ARCH-08

Redis bukan source of truth untuk vote.

### ARCH-09

Controller tidak mengimplementasikan voting transaction.

### ARCH-10

Admin authorization selalu server-side.

---

# 66. Recommended Laravel Components

| Kebutuhan | Teknologi |
|---|---|
| Backend | Laravel |
| PHP | PHP 8.3+ |
| UI | Livewire |
| Styling | Tailwind CSS |
| Database | PostgreSQL |
| Cache | Redis |
| Queue | Laravel Queue + Redis |
| Realtime | Laravel Reverb |
| Web server | Nginx |
| Auth | Laravel authentication stack |
| Authorization | Policies/Gates |
| File storage | Laravel Filesystem |
| Testing | PHPUnit/Pest + Laravel testing |
| Browser test | Laravel Dusk / Playwright jika diperlukan |

Versi dependency harus dikunci pada saat implementasi berdasarkan versi Laravel/PHP yang dipilih.

---

# 67. Recommended Package Policy

Jangan menambahkan package hanya untuk fitur kecil yang dapat ditangani Laravel.

Setiap package harus dievaluasi:

```text
maintenance
security
license
community
compatibility
performance
```

Untuk fitur security-critical seperti voting, lebih baik menggunakan komponen framework yang matang daripada package yang tidak terawat.

---

# 68. Environment Separation

Minimal:

```text
local
testing
staging
production
```

Database harus berbeda.

```text
local      → dummy data
testing    → automated test DB
staging    → realistic dummy data
production → real election data
```

Jangan menyalin database production ke development tanpa sanitization.

---

# 69. CI/CD

Pipeline minimum:

```text
git push
   ↓
Install dependencies
   ↓
Static analysis
   ↓
Lint
   ↓
Unit tests
   ↓
Feature tests
   ↓
Security checks
   ↓
Build assets
   ↓
Deploy staging
   ↓
Smoke test
   ↓
Production deployment
```

Production deployment harus memiliki rollback strategy.

---

# 70. Deployment Security

Production:

```text
APP_ENV=production
APP_DEBUG=false
```

Pastikan:

- HTTPS.
- Firewall.
- Database private.
- Redis private.
- Secrets secure.
- File permissions correct.
- OS updated.
- PHP updated.
- Dependency audit.
- Backup enabled.

---

# 71. Configuration Ownership

| Configuration | Owner |
|---|---|
| Election configuration | Admin |
| Candidate configuration | Admin |
| Voter import | Admin/Operator |
| Credential policy | Admin |
| Rate limit | System |
| Database | System |
| Backup | System |
| Application secrets | System administrator |
| Security headers | System administrator |

---

# 72. Architecture Decision: Monolith

Untuk MVP sekolah, gunakan:

```text
Laravel Modular Monolith
```

bukan microservices.

Alasan:

- Lebih sederhana.
- Deployment lebih mudah.
- Transaction database lebih mudah.
- Development lebih cepat.
- Biaya lebih rendah.
- Cocok untuk skala sekolah.
- Lebih mudah diaudit.

Microservices dapat dipertimbangkan jika skala dan kebutuhan organisasi meningkat.

---

# 73. Architecture Decision: PostgreSQL as Source of Truth

PostgreSQL menjadi source of truth untuk:

```text
elections
voters
credentials
candidates
ballots
audit logs
```

Redis bukan source of truth.

---

# 74. Architecture Decision: Livewire

Livewire digunakan untuk:

```text
Admin dashboard
CRUD
Voting UI
Result dashboard
```

Keuntungan:

- Tidak perlu SPA kompleks.
- Integrasi Laravel mudah.
- Server-side logic.
- Cocok untuk CRUD/admin application.

Untuk voting page, state sensitif tetap diverifikasi server-side.

---

# 75. Architecture Decision: Reverb

Reverb digunakan hanya untuk:

```text
monitoring
participation
system notifications
```

Bukan untuk:

```text
ballot transport
vote storage
authorization
```

Vote tetap disimpan melalui HTTP/application transaction ke PostgreSQL.

---

# 76. Architecture Decision: Queue

Queue digunakan untuk pekerjaan berat:

```text
import
export
notifications
reports
```

Core voting tetap synchronous dan transactional.

---

# 77. Recommended Directory Structure

Contoh final:

```text
app/
├── Domain/
│   ├── Elections/
│   ├── Candidates/
│   ├── Voters/
│   ├── Credentials/
│   ├── Voting/
│   └── Results/
│
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
├── Livewire/
│   ├── Admin/
│   ├── Operator/
│   └── Voting/
│
├── Models/
├── Policies/
├── Jobs/
├── Events/
├── Listeners/
└── Support/
```

---

# 78. Suggested Voting Domain

```text
app/Domain/Voting/

├── Services/
│   └── VotingService.php
│
├── Events/
│   └── VoteAccepted.php
│
├── Exceptions/
│   ├── ElectionNotOpenException.php
│   ├── InvalidCredentialException.php
│   ├── CredentialAlreadyUsedException.php
│   └── CandidateUnavailableException.php
│
└── DTOs/
    └── CastVoteData.php
```

---

# 79. Suggested Election Domain

```text
app/Domain/Elections/

├── Services/
│   └── ElectionService.php
│
├── Exceptions/
│   └── InvalidElectionTransitionException.php
│
└── DTOs/
    └── CreateElectionData.php
```

---

# 80. Suggested Result Domain

```text
app/Domain/Results/

├── Services/
│   └── ResultService.php
│
├── Queries/
│   └── ElectionResultQuery.php
│
└── DTOs/
    └── ElectionResultData.php
```

---

# 81. Dependency Rule

Dependency flow:

```text
UI
 ↓
Application Service
 ↓
Domain Model / Query
 ↓
Infrastructure
```

Jangan:

```text
Model → Livewire
Model → Controller
Domain → Browser
```

Domain tidak boleh bergantung pada UI.

---

# 82. Anti-Corruption Rule

Jangan memasukkan data presentation ke domain.

Contoh buruk:

```text
VotingService(
    $buttonColor,
    $selectedCard,
    $browserTab
)
```

Service hanya menerima data bisnis:

```text
election
credential
candidate
```

---

# 83. Security-Critical Code Review

Kode berikut wajib mendapatkan review ekstra:

```text
VotingService
CredentialService
Authentication
Authorization
Election state transitions
ResultService
AuditService
Database migrations
Logging
Realtime events
```

---

# 84. Definition of Done — Architecture

Arsitektur dianggap siap untuk implementasi jika:

- [ ] Module boundaries jelas.
- [ ] VotingService ditentukan.
- [ ] Transaction flow ditentukan.
- [ ] Authorization model ditentukan.
- [ ] Anonymous ballot flow ditentukan.
- [ ] Realtime flow ditentukan.
- [ ] Queue boundary ditentukan.
- [ ] Database source of truth ditentukan.
- [ ] Production topology ditentukan.
- [ ] Testing strategy ditentukan.
- [ ] Security invariants sudah diterjemahkan menjadi architecture rules.

---

# 85. Kesimpulan

Arsitektur MVP menggunakan:

```text
Laravel Modular Monolith
        │
        ├── Livewire
        ├── Domain Services
        ├── PostgreSQL
        ├── Redis
        ├── Queue
        └── Reverb
```

Dengan voting flow:

```text
Voter
  ↓
Credential
  ↓
VotingService
  ↓
PostgreSQL Transaction
  ├── Lock Credential
  ├── Create Anonymous Ballot
  ├── Mark Credential Used
  └── Mark Voter Voted
  ↓
Commit
  ↓
Realtime Participation
```

Arsitektur ini menjaga kompleksitas tetap rendah untuk aplikasi sekolah, tetapi tetap memberikan fondasi yang kuat untuk keamanan, testing, dan pengembangan fitur berikutnya.

---

# 86. Dokumen Berikutnya

Setelah `04_ARCHITECTURE.md`, dokumen berikutnya yang direkomendasikan:

```text
05_API_SPEC.md
```

Dokumen tersebut akan mendefinisikan kontrak backend secara konkret:

- Endpoint authentication.
- Endpoint election.
- Endpoint candidate.
- Endpoint voter.
- Endpoint credential.
- Endpoint voting.
- Endpoint result.
- Endpoint export.
- Request schema.
- Response schema.
- Error response.
- Authorization.
- Rate limiting.
- Idempotency.
- HTTP status code.
