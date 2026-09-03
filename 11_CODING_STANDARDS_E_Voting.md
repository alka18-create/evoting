# Coding Standards — Sistem E-Voting Sekolah

**Dokumen:** 11 — Coding Standards  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD` s/d `10_IMPLEMENTATION_ROADMAP`

---

# 1. Tujuan

Dokumen ini menetapkan standar penulisan kode untuk memastikan aplikasi e-voting:

- konsisten
- mudah dipelihara
- mudah direview
- aman
- dapat diuji
- mudah dikembangkan
- meminimalkan bug pada voting-critical code

Prinsip utama:

```text
Readable > Clever
Explicit > Implicit
Small > Monolithic
Tested > Assumed
Secure by Default
```

---

# 2. Technology Baseline

Standar ini ditujukan untuk:

```text
PHP
Laravel
PostgreSQL
Redis
Blade
Livewire
Tailwind CSS
Pest / PHPUnit
Playwright
Docker
```

Versi aktual harus mengikuti versi yang masih mendapatkan security updates.

---

# 3. General Principles

## 3.1 Keep Code Simple

Hindari:

```php
if ($a && $b && !$c && ($d || $e)) {
    // ...
}
```

Jika logic kompleks, pecah menjadi method atau domain action yang memiliki nama jelas.

---

## 3.2 Explicit Business Rules

Business rule penting harus terlihat jelas.

Contoh:

```php
if ($election->isNotOpen()) {
    throw new ElectionNotOpenException();
}
```

lebih baik daripada:

```php
if ($election->status !== 2) {
    // ...
}
```

---

# 4. PHP Standard

Gunakan:

```text
PSR-12
```

dan Laravel coding conventions.

Prefer:

```php
declare(strict_types=1);
```

untuk file yang sesuai dengan project convention.

---

# 5. Type Safety

Gunakan type declaration:

```php
public function calculateResult(Election $election): Result
{
    // ...
}
```

Hindari:

```php
public function calculateResult($election)
{
}
```

Gunakan nullable type jika memang valid:

```php
public function findCandidate(int $id): ?Candidate
{
}
```

---

# 6. Return Types

Setiap public method harus memiliki return type jika memungkinkan.

Contoh:

```php
public function execute(): Ballot
{
}
```

Hindari:

```php
public function execute()
{
}
```

---

# 7. Naming Convention

## Classes

```text
PascalCase
```

Contoh:

```text
ElectionService
CastVote
GenerateCredential
ElectionPolicy
```

---

## Methods

```text
camelCase
```

Contoh:

```php
castVote()
calculateResult()
verifyCredential()
```

---

## Variables

```text
camelCase
```

Contoh:

```php
$voter
$election
$candidate
```

---

## Constants

```php
UPPER_SNAKE_CASE
```

Contoh:

```php
const MAX_IMPORT_ROWS = 5000;
```

---

# 8. Boolean Naming

Gunakan nama yang jelas:

```php
$isOpen
$hasVoted
$canVote
$isActive
```

Hindari:

```php
$status
$flag
$value
```

untuk boolean.

---

# 9. Database Naming

Gunakan:

```text
snake_case
```

Contoh:

```text
student_id
election_id
candidate_id
created_at
updated_at
```

---

# 10. Table Naming

Gunakan plural:

```text
users
elections
candidates
voters
ballots
audit_logs
```

Ikuti Laravel conventions kecuali ERD menentukan nama berbeda.

---

# 11. Primary Keys

Gunakan standar yang konsisten di seluruh project.

Contoh:

```text
bigint
```

atau:

```text
UUID
```

Pilihan final harus mengikuti `02_ERD`.

Jangan mencampur strategi ID tanpa alasan yang jelas.

---

# 12. Foreign Keys

Gunakan foreign key database.

Contoh:

```text
election_id → elections.id
candidate_id → candidates.id
```

Jangan hanya mengandalkan validation Laravel.

---

# 13. Database Constraints

Business invariants penting harus diperkuat database.

Contoh:

```text
unique(voter_id, election_id)
unique(election_id, candidate_number)
```

Constraint final harus mengikuti ERD.

---

# 14. Model Standards

Laravel Models harus fokus pada:

```text
relationships
casts
scopes
simple domain behavior
```

Hindari menempatkan seluruh business logic di Model.

---

# 15. Fat Model Anti-Pattern

Hindari:

```php
class Election extends Model
{
    public function vote(...)
    {
        // hundreds of lines
    }
}
```

Lebih baik:

```text
CastVote
ElectionStateManager
ResultCalculator
```

---

# 16. Controllers

Controller harus tipis.

Ideal:

```php
public function store(CastVoteRequest $request)
{
    $ballot = $this->castVote->execute(
        $request->user(),
        $request->candidate_id
    );

    return response()->json(...);
}
```

Controller tidak boleh berisi seluruh voting transaction.

---

# 17. Form Requests

Gunakan Form Request untuk validation.

Contoh:

```text
StoreElectionRequest
UpdateElectionRequest
StoreCandidateRequest
ImportVoterRequest
CastVoteRequest
```

---

# 18. Validation

Validasi harus dilakukan pada boundary.

```text
HTTP Request
    ↓
Validation
    ↓
Authorization
    ↓
Domain logic
```

Jangan menganggap frontend validation cukup.

---

# 19. Authorization

Authorization harus terjadi server-side.

Gunakan:

```text
Policies
Gates
middleware
```

Contoh:

```php
$this->authorize('update', $election);
```

Jangan hanya menyembunyikan tombol di UI.

---

# 20. Service / Action Classes

Gunakan Action/Service untuk business operation penting.

Contoh:

```text
CreateElection
OpenElection
CloseElection
ImportVoters
GenerateCredentials
CastVote
CalculateResults
GenerateExport
```

---

# 21. Single Responsibility

Satu Action sebaiknya memiliki satu tanggung jawab utama.

Buruk:

```text
ElectionService
├── create
├── open
├── close
├── import voters
├── generate PDF
└── calculate result
```

Lebih baik:

```text
CreateElection
OpenElection
CloseElection
ImportVoters
GenerateResultExport
CalculateResults
```

---

# 22. Voting-Critical Code

Kode berikut dianggap critical:

```text
CastVote
VerifyEligibility
CreateBallot
MarkVoterVoted
ElectionStateTransition
CalculateResults
CredentialVerification
```

Perubahan harus mendapatkan review tambahan.

---

# 23. CastVote Rule

`CastVote` harus:

```text
small
transactional
deterministic
testable
```

Tidak boleh melakukan pekerjaan yang tidak diperlukan untuk commit vote.

---

# 24. Transaction Boundary

Voting transaction harus jelas.

Contoh konseptual:

```php
DB::transaction(function () {
    // verify
    // lock
    // create ballot
    // mark voted
});
```

Jangan membuat sebagian proses voting di luar transaction jika dapat menyebabkan inconsistent state.

---

# 25. Locking

Gunakan row-level locking atau mekanisme concurrency yang sesuai untuk eligibility.

Contoh konseptual:

```php
$eligibility = VoterEligibility::query()
    ->where(...)
    ->lockForUpdate()
    ->firstOrFail();
```

Implementasi final harus mengikuti `15_VOTING_ENGINE_SPEC.md`.

---

# 26. Double Vote Prevention

Jangan hanya:

```php
if ($voter->hasVoted()) {
    return;
}
```

Karena race condition dapat terjadi.

Gunakan kombinasi:

```text
transaction
+
locking
+
database constraint
+
idempotency
```

---

# 27. Idempotency

Request voting dapat dikirim ulang karena:

```text
network retry
double click
browser retry
timeout
```

Gunakan mekanisme idempotency sesuai API design.

Idempotency key tidak boleh menjadi pengganti database transaction.

---

# 28. Error Handling

Gunakan domain exception yang jelas.

Contoh:

```text
ElectionNotOpenException
AlreadyVotedException
InvalidCandidateException
UnauthorizedVoterException
```

Hindari:

```php
throw new Exception('Something went wrong');
```

untuk business error yang dapat diidentifikasi.

---

# 29. API Error Response

Format error harus konsisten.

Contoh:

```json
{
  "message": "You have already voted.",
  "code": "ALREADY_VOTED"
}
```

Jangan membocorkan detail internal database.

---

# 30. Logging

Log:

```text
error
warning
security event
performance
request ID
```

Jangan log:

```text
password
PIN plaintext
session token
authorization header
private ballot information
```

---

# 31. Privacy Rule

Kode normal tidak boleh menghasilkan log seperti:

```text
student_001 voted for candidate_03
```

Audit/logging harus mempertahankan privacy boundary.

---

# 32. Ballot Data

Kode yang menangani ballot harus memisahkan:

```text
voter identity
```

dari:

```text
vote choice
```

Jangan menambahkan relationship convenience yang melanggar privacy design.

---

# 33. Query Standards

Gunakan Eloquent/query builder dengan parameter binding.

Hindari:

```php
DB::select("SELECT * FROM voters WHERE id = {$id}");
```

Gunakan:

```php
Voter::query()
    ->whereKey($id)
    ->first();
```

---

# 34. Raw SQL

Raw SQL diperbolehkan jika:

```text
performance
database-specific feature
complex query
migration requirement
```

tetapi harus:

```text
parameterized
reviewed
tested
```

---

# 35. N+1 Prevention

Gunakan eager loading jika diperlukan:

```php
Election::with('candidates')->findOrFail($id);
```

Monitor query count pada critical pages.

---

# 36. Mass Assignment

Gunakan:

```text
fillable
guarded
validated DTO/input
```

Jangan langsung:

```php
Model::create($request->all());
```

---

# 37. DTOs

DTO dapat digunakan ketika payload kompleks.

Contoh:

```text
CreateElectionData
ImportVoterData
CastVoteData
```

DTO membantu memisahkan:

```text
HTTP input
```

dari:

```text
domain logic
```

---

# 38. Enums

Gunakan PHP Enum untuk state yang terbatas jika sesuai.

Contoh:

```php
enum ElectionStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';
}
```

Hindari magic string yang tersebar.

---

# 39. State Transition

Jangan mengubah state langsung dari banyak tempat:

```php
$election->status = 'open';
```

Gunakan domain operation:

```php
OpenElection::execute($election);
```

Ini menjaga transition rules tetap terpusat.

---

# 40. Time Handling

Gunakan timezone policy yang konsisten.

Rekomendasi:

```text
storage: UTC
display: Asia/Jakarta
```

Gunakan Carbon/Laravel date handling.

Hindari manipulasi timestamp manual dengan string.

---

# 41. Time-Sensitive Logic

Jangan menggunakan:

```php
if (now() > $election->end_at)
```

di banyak tempat dengan aturan berbeda.

Centralize:

```text
Election::isOpen()
ElectionAvailability
```

atau domain service.

---

# 42. File Upload

Upload harus:

```text
validated
size-limited
type-checked
renamed
stored outside executable paths
```

Jangan mempercayai extension filename saja.

---

# 43. Spreadsheet Import

Import harus mencegah formula injection.

Contoh dangerous values:

```text
=SUM(...)
=HYPERLINK(...)
+cmd
-cmd
@cmd
```

Sanitize/escape ketika data akan diekspor kembali ke spreadsheet.

---

# 44. Export

Export harus menggunakan data aggregate.

Jangan membuat export query yang melakukan:

```text
voter → candidate
```

relationship jika tidak diperlukan.

---

# 45. Blade Standards

Gunakan escaped output:

```blade
{{ $candidate->name }}
```

Hindari:

```blade
{!! $candidate->name !!}
```

kecuali HTML sudah benar-benar disanitasi dan memang diperlukan.

---

# 46. Livewire Standards

Livewire action:

```text
authorize
validate
execute domain action
return UI state
```

Jangan menaruh security decision hanya di frontend component.

---

# 47. Tailwind Standards

Gunakan utility classes secara konsisten.

Hindari class string yang sangat panjang dan berulang jika dapat diekstraksi menjadi component.

---

# 48. UI Components

Gunakan reusable components:

```text
Button
Input
Modal
Alert
CandidateCard
StatusBadge
DataTable
```

Voting UI harus sederhana dan stabil.

---

# 49. Accessibility

Gunakan:

```text
semantic HTML
labels
keyboard navigation
focus states
ARIA only when needed
sufficient contrast
```

Candidate selection harus dapat digunakan tanpa mouse.

---

# 50. Frontend Security

Jangan simpan:

```text
admin secret
database credential
private token
```

di JavaScript bundle.

Jangan menganggap data frontend trusted.

---

# 51. API Client

API client harus:

```text
handle timeout
handle 401
handle 403
handle validation errors
handle retry carefully
```

Voting request tidak boleh otomatis diulang secara unsafe.

---

# 52. Retry Policy

Retry otomatis boleh untuk:

```text
GET
safe idempotent operation
```

Voting POST harus mengikuti idempotency strategy.

Jangan blind retry:

```text
POST /vote
```

tanpa idempotency protection.

---

# 53. Queue Jobs

Queue jobs harus:

```text
small
retryable
observable
idempotent where possible
```

Contoh:

```text
ImportVotersJob
GenerateExportJob
SendCredentialJob
```

Jangan memindahkan critical vote commit ke queue.

---

# 54. Queue Failure

Failed jobs harus:

```text
logged
retryable
visible to operator
```

Critical failures harus menghasilkan alert.

---

# 55. Caching

Cache hanya untuk:

```text
non-critical read
configuration
aggregate monitoring
```

Jangan menggunakan cache sebagai source of truth untuk:

```text
has voted
ballot existence
final vote acceptance
```

---

# 56. Redis Standards

Redis key harus memiliki namespace.

Contoh:

```text
evoting:election:{id}:stats
evoting:rate-limit:{key}
```

Gunakan TTL untuk data sementara.

---

# 57. Cache Invalidation

Jika data berubah:

```text
invalidate/rebuild cache
```

Tetapi voting correctness tidak boleh bergantung pada cache invalidation.

---

# 58. Testing Standards

Setiap feature harus memiliki:

```text
happy path
validation failure
authorization failure
edge case
security case
```

Voting-critical feature harus memiliki concurrency test.

---

# 59. Unit Test

Unit test untuk:

```text
domain rules
calculators
state transitions
validators
value objects
```

Contoh:

```text
ElectionStatusTest
ResultCalculatorTest
CredentialGeneratorTest
```

---

# 60. Feature Test

Feature test untuk:

```text
HTTP endpoint
authentication
authorization
database behavior
```

---

# 61. Integration Test

Gunakan integration test untuk:

```text
database transaction
Redis
queue
storage
```

jika behavior tersebut penting terhadap feature.

---

# 62. E2E Test

E2E minimal:

```text
admin creates election
admin adds candidate
admin imports voters
voter logs in
voter votes
admin closes election
admin sees result
admin exports result
```

---

# 63. Security Test Naming

Security test harus eksplisit.

Contoh:

```text
it_rejects_voter_accessing_admin_endpoint
it_prevents_double_voting
it_prevents_cross_election_ballot
it_does_not_expose_ballot_choice
```

---

# 64. Test Data Privacy

Jangan menggunakan:

```text
real student data
real credentials
real production ballots
```

dalam automated tests.

Gunakan synthetic fixtures.

---

# 65. Assertions for Voting

Minimal:

```text
ballot count
voted status
candidate association
election state
result count
privacy boundary
```

---

# 66. Database Testing

Test:

```text
foreign key
unique constraint
transaction rollback
concurrency
```

Database constraint test wajib untuk invariant kritis.

---

# 67. Performance Standards

Hindari:

```text
N+1
unbounded query
large memory collection
full table scan
```

Gunakan pagination untuk admin data besar.

---

# 68. Pagination

Default:

```text
server-side pagination
```

untuk:

```text
voters
audit logs
elections
candidates
```

Jangan load ribuan row sekaligus tanpa alasan.

---

# 69. Bulk Operations

Bulk operation harus:

```text
validated
authorized
audited
rate-limited where appropriate
```

Contoh:

```text
bulk voter import
credential regeneration
export
```

---

# 70. Long-Running Operations

Jika operasi:

```text
import
large export
large report
```

dapat memakan waktu lama:

```text
queue it
```

Jangan membuat HTTP request menunggu terlalu lama.

---

# 71. Error Messages

Untuk user:

```text
clear
actionable
non-technical
```

Contoh:

```text
"Voting is closed."
```

bukan:

```text
"SQLSTATE[23505]: duplicate key..."
```

Technical detail hanya untuk internal logs.

---

# 72. Security Error Messages

Authentication harus menghindari account enumeration jika relevan.

Contoh:

```text
Invalid credentials.
```

daripada:

```text
Student ID exists but PIN is wrong.
```

---

# 73. Configuration

Jangan hard-code:

```text
URL
credentials
timeouts
API keys
environment-specific values
```

Gunakan:

```text
config/*.php
.env
secret management
```

---

# 74. Environment Separation

Environment:

```text
local
testing
staging
production
```

harus terpisah.

Jangan menggunakan production database dari local development.

---

# 75. Local Development

Developer harus dapat menjalankan:

```text
docker compose up
```

atau documented equivalent.

Seed:

```text
php artisan db:seed
```

harus membuat environment demo yang aman.

---

# 76. Seed Data

Seed data harus jelas:

```text
Admin demo
Election demo
Candidates demo
Synthetic voters
```

Gunakan password/PIN yang hanya berlaku untuk development.

---

# 77. Production Seed Rule

Jangan menjalankan demo seeder di production.

Production initialization harus menggunakan controlled process.

---

# 78. Dependency Management

Gunakan lockfiles.

Jalankan secara berkala:

```text
composer audit
npm audit
dependency scanner
```

Security vulnerabilities harus ditangani berdasarkan severity.

---

# 79. Package Selection

Sebelum menambahkan package:

```text
check maintenance
check security history
check license
check dependency size
check compatibility
```

Jangan menambahkan package untuk masalah yang dapat diselesaikan dengan Laravel core secara sederhana.

---

# 80. Comments

Comment menjelaskan:

```text
why
```

bukan:

```text
what
```

Buruk:

```php
// Set status to open
$election->status = 'open';
```

Lebih berguna:

```php
// Transition is centralized here to prevent opening an election
// without candidates and eligible voters.
```

---

# 81. TODO

Jangan meninggalkan:

```text
TODO
FIXME
HACK
```

tanpa issue/ticket yang jelas.

---

# 82. Dead Code

Jangan commit:

```text
unused method
unused import
commented-out implementation
debug dump
```

Sebelum merge:

```text
dd()
dump()
ray()
console.log()
```

harus dihapus kecuali memang diperlukan dalam test/debug tooling.

---

# 83. Exception Handling

Jangan:

```php
try {
    // ...
} catch (\Throwable $e) {
    // ignore
}
```

Silent failure tidak diperbolehkan untuk voting-critical code.

---

# 84. Transactions and External Services

Jangan menggabungkan external API call ke dalam critical vote transaction jika dapat dihindari.

Buruk:

```text
BEGIN
create ballot
call external service
mark voted
COMMIT
```

Lebih baik:

```text
BEGIN
create ballot
mark voted
COMMIT

then:
non-critical side effects
```

---

# 85. Event-Driven Side Effects

Gunakan event/listener untuk:

```text
audit notification
analytics
non-critical monitoring
```

tetapi jangan membuat vote commit bergantung pada listener non-critical.

---

# 86. Audit Event Rules

Audit harus:

```text
immutable where possible
timestamped
actor-aware
resource-aware
privacy-safe
```

---

# 87. Audit Event Example

Contoh:

```json
{
  "action": "ELECTION_CLOSED",
  "actor_id": "admin-id",
  "resource_type": "election",
  "resource_id": "election-id",
  "created_at": "..."
}
```

Jangan:

```json
{
  "voter_id": "...",
  "candidate_id": "..."
}
```

untuk audit voting.

---

# 88. Commit Message Convention

Rekomendasi:

```text
feat: add election management
fix: prevent duplicate voting
test: add concurrent voting coverage
security: harden credential verification
refactor: extract result calculator
docs: update deployment guide
```

---

# 89. Pull Request Size

Prefer PR kecil:

```text
one feature
one concern
```

Hindari PR yang mengubah:

```text
database
backend
frontend
deployment
```

sekaligus kecuali memang satu perubahan terintegrasi.

---

# 90. Code Review Checklist

Reviewer memeriksa:

```text
[ ] requirement
[ ] correctness
[ ] security
[ ] authorization
[ ] privacy
[ ] performance
[ ] test coverage
[ ] migration
[ ] logging
[ ] backward compatibility
```

Voting-critical:

```text
[ ] transaction boundary
[ ] concurrency
[ ] idempotency
[ ] database constraints
```

---

# 91. Static Analysis

Gunakan tool yang sesuai, misalnya:

```text
PHPStan
Laravel Pint
ESLint jika JS digunakan
```

Pipeline:

```text
lint
static analysis
tests
```

---

# 92. Code Formatting

Gunakan formatter otomatis.

Contoh:

```text
Laravel Pint
```

CI harus gagal jika code tidak memenuhi formatting standard.

---

# 93. CI Quality Gate

Minimal:

```text
[ ] formatting
[ ] static analysis
[ ] unit tests
[ ] feature tests
[ ] security checks
```

Untuk release:

```text
[ ] E2E
[ ] performance smoke
```

---

# 94. Branch Protection

`main` minimal:

```text
PR required
review required
CI required
no direct push
```

---

# 95. Secrets in Git

CI harus mendeteksi:

```text
API keys
passwords
private keys
database URLs
tokens
```

Jika secret pernah ter-commit:

```text
rotate secret
remove exposure
review history
```

Jangan hanya menghapus file dari commit terbaru.

---

# 96. Security Disclosure

Jika ditemukan security bug:

```text
do not discuss publicly before remediation
```

Gunakan internal security issue process.

---

# 97. Documentation Standards

Public code documentation:

```text
README
setup guide
environment variables
testing
deployment
```

Domain-critical behavior harus didokumentasikan di:

```text
docs/
```

atau ADR bila diperlukan.

---

# 98. Architecture Decision Records

Gunakan ADR untuk keputusan yang berdampak besar.

Contoh:

```text
ADR-001 PostgreSQL
ADR-002 Anonymous ballot architecture
ADR-003 Credential strategy
ADR-004 Redis usage
ADR-005 Voting concurrency strategy
```

---

# 99. Versioning

Application:

```text
Semantic Versioning
```

Contoh:

```text
1.0.0
1.0.1
1.1.0
2.0.0
```

API version:

```text
/api/v1
```

---

# 100. Release Notes

Setiap release harus mencatat:

```text
Features
Fixes
Security
Database changes
Breaking changes
Deployment notes
```

---

# 101. Backward Compatibility

API changes harus mempertimbangkan:

```text
existing clients
existing database
existing credentials
```

Breaking change harus memiliki:

```text
migration plan
```

---

# 102. Production Debugging

Production debugging harus menggunakan:

```text
logs
metrics
traces
request ID
```

Jangan mengaktifkan:

```text
APP_DEBUG=true
```

sebagai solusi debugging.

---

# 103. Performance Debugging

Gunakan:

```text
query profiler
APM
slow query log
application metrics
```

Jangan melakukan premature optimization tanpa measurement.

---

# 104. Database Query Review

Query pada voting-critical path harus diperiksa:

```text
index usage
lock behavior
transaction duration
row count
```

Target:

```text
short transaction
minimal locks
predictable query plan
```

---

# 105. Transaction Duration

Voting transaction harus sesingkat mungkin.

Di dalam transaction hanya lakukan pekerjaan yang benar-benar diperlukan untuk:

```text
validate authoritative state
create ballot
mark eligibility
```

---

# 106. Network Calls

Jangan melakukan network call ke:

```text
email provider
SMS provider
external API
object storage
```

di tengah voting transaction kecuali desain secara eksplisit membutuhkannya dan telah dianalisis.

---

# 107. Failure-Safe Design

Jika:

```text
Redis down
email down
realtime down
export service down
```

voting harus tetap aman jika komponen tersebut bukan bagian dari authoritative vote path.

---

# 108. Graceful Degradation

Contoh:

```text
Realtime down
→ dashboard refresh manual

Candidate image unavailable
→ placeholder

Export queue delayed
→ vote data tetap aman
```

---

# 109. No Silent Data Mutation

Jangan melakukan perubahan data voting tanpa:

```text
explicit operation
authorization
audit
```

---

# 110. Data Deletion

Ballot data harus mengikuti retention policy.

Jangan membuat:

```text
delete voter
```

secara otomatis menghapus atau mengubah ballot tanpa analisis privacy/integrity.

Foreign key strategy harus mengikuti ERD.

---

# 111. Soft Delete

Gunakan soft delete hanya jika memang diperlukan.

Untuk data voting-critical:

```text
do not casually soft-delete
```

karena dapat menyebabkan ambiguity antara:

```text
record exists
record hidden
record invalid
```

---

# 112. Data Integrity Invariants

Minimal invariant:

```text
valid ballot belongs to valid election
candidate belongs to same election
eligible voter belongs to election
one eligibility cannot produce more than one valid ballot
closed election accepts no new vote
```

Semua invariant harus memiliki test.

---

# 113. Result Integrity

Result calculation harus memenuhi:

```text
sum(candidate_votes)
=
valid_ballots
```

dan:

```text
valid_ballots
<=
eligible_voters
```

---

# 114. Security Priority

Jika ada trade-off:

```text
Security > Convenience
Integrity > Performance
Privacy > Analytics
Correctness > Feature count
```

---

# 115. Coding Standards for MVP

Developer tidak perlu mengimplementasikan semua pola enterprise.

MVP cukup menggunakan:

```text
Laravel conventions
Policies
Form Requests
Actions/Services
Enums
Transactions
Tests
```

Hindari overengineering.

---

# 116. Mandatory Rules

Rules berikut bersifat mandatory:

```text
1. No plaintext passwords/PINs.
2. No direct voter→candidate relationship outside approved ballot design.
3. No voting logic solely in controller.
4. No blind POST retry for vote.
5. No APP_DEBUG in production.
6. No secrets in Git.
7. No direct production DB access for normal developers.
8. No deployment of risky schema changes during OPEN election.
9. No manual ballot mutation as ad-hoc bug fix.
10. All P0 voting rules must have automated tests.
```

---

# 117. Final Coding Standard

Setiap developer harus dapat menjawab:

```text
Where is this rule enforced?
Where is it tested?
What happens under concurrency?
What happens when the request is retried?
What data is logged?
Can this reveal voter identity or vote choice?
What happens if the dependency fails?
```

Jika jawaban belum jelas, feature belum siap dianggap production-ready.

---

# 118. Next Document

Dokumen berikutnya yang direkomendasikan:

```text
12_PROJECT_STRUCTURE.md
```

Dokumen tersebut akan mendefinisikan struktur folder, namespace, module/domain boundary, naming file, dependency direction, dan lokasi setiap komponen Laravel secara konkret.
