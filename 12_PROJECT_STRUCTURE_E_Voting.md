# Project Structure — Sistem E-Voting Sekolah

**Dokumen:** 12 — Project Structure  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD` s/d `11_CODING_STANDARDS`

---

# 1. Tujuan

Dokumen ini mendefinisikan struktur repository dan organisasi kode Laravel secara konkret.

Tujuan utama:

- memisahkan domain
- menjaga business logic tetap terorganisir
- mencegah controller menjadi terlalu besar
- memisahkan voting-critical code
- memudahkan testing
- memudahkan code review
- menjaga dependency antar-module tetap jelas

Prinsip:

```text
HTTP Layer
    ↓
Application Layer
    ↓
Domain Layer
    ↓
Infrastructure Layer
```

---

# 2. Recommended Repository Structure

```text
e-voting/
├── app/
├── bootstrap/
├── config/
├── database/
├── docs/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── docker/
├── .github/
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── phpunit.xml
├── pint.json
├── phpstan.neon
├── docker-compose.yml
├── Dockerfile
├── .env.example
├── .gitignore
└── README.md
```

---

# 3. Application Directory

Recommended:

```text
app/
├── Actions/
├── Console/
├── Domain/
├── Enums/
├── Exceptions/
├── Http/
├── Jobs/
├── Listeners/
├── Models/
├── Notifications/
├── Policies/
├── Providers/
├── Rules/
├── Services/
└── Support/
```

Tidak semua folder harus digunakan jika belum diperlukan.

---

# 4. Domain Structure

Domain merupakan bagian terpenting dari struktur aplikasi.

```text
app/Domain/
├── Elections/
├── Candidates/
├── Voters/
├── Credentials/
├── Voting/
├── Results/
├── Auditing/
└── Exports/
```

Setiap domain memiliki tanggung jawab yang jelas.

---

# 5. Elections Domain

```text
app/Domain/Elections/
├── Actions/
│   ├── CreateElection.php
│   ├── UpdateElection.php
│   ├── ScheduleElection.php
│   ├── OpenElection.php
│   ├── CloseElection.php
│   └── ArchiveElection.php
├── Data/
│   └── ElectionData.php
├── Enums/
│   └── ElectionStatus.php
├── Exceptions/
│   ├── InvalidElectionState.php
│   └── ElectionNotReady.php
├── Services/
│   └── ElectionStateManager.php
└── Policies/
    └── ElectionPolicy.php
```

Jika Model tetap diletakkan di `app/Models`, domain tidak perlu menggandakan Model.

---

# 6. Candidates Domain

```text
app/Domain/Candidates/
├── Actions/
│   ├── CreateCandidate.php
│   ├── UpdateCandidate.php
│   ├── DeleteCandidate.php
│   └── ReorderCandidates.php
├── Data/
│   └── CandidateData.php
├── Exceptions/
│   └── DuplicateCandidateNumber.php
└── Services/
    └── CandidateService.php
```

Candidate harus selalu divalidasi terhadap election yang bersangkutan.

---

# 7. Voters Domain

```text
app/Domain/Voters/
├── Actions/
│   ├── CreateVoter.php
│   ├── UpdateVoter.php
│   ├── DeactivateVoter.php
│   └── ImportVoters.php
├── Data/
│   ├── VoterData.php
│   └── VoterImportData.php
├── Jobs/
│   └── ProcessVoterImport.php
├── Services/
│   └── VoterImportService.php
└── Exceptions/
    └── InvalidVoterImport.php
```

---

# 8. Credentials Domain

```text
app/Domain/Credentials/
├── Actions/
│   ├── GenerateCredential.php
│   ├── RevokeCredential.php
│   └── VerifyCredential.php
├── Services/
│   ├── CredentialGenerator.php
│   └── CredentialVerifier.php
├── Data/
│   └── CredentialData.php
└── Exceptions/
    ├── InvalidCredential.php
    └── CredentialAlreadyUsed.php
```

Credential plaintext tidak boleh disimpan.

---

# 9. Voting Domain

Voting adalah domain paling sensitif.

```text
app/Domain/Voting/
├── Actions/
│   ├── StartVotingSession.php
│   ├── VerifyEligibility.php
│   └── CastVote.php
├── Data/
│   ├── CastVoteData.php
│   └── VotingSessionData.php
├── Exceptions/
│   ├── AlreadyVoted.php
│   ├── ElectionNotOpen.php
│   ├── InvalidCandidate.php
│   └── VoterNotEligible.php
├── Services/
│   ├── VotingService.php
│   ├── BallotIntegrityService.php
│   └── VotingEligibilityService.php
└── Rules/
    └── CandidateBelongsToElection.php
```

---

# 10. Voting Critical Boundary

Kode berikut dianggap voting-critical:

```text
CastVote
VerifyEligibility
VotingEligibilityService
BallotIntegrityService
```

Dependency harus seminimal mungkin.

Ideal:

```text
CastVote
 ├── Election
 ├── Eligibility
 ├── Candidate
 └── Ballot
```

Hindari:

```text
CastVote
 ├── Email
 ├── PDF
 ├── Notification
 ├── Analytics
 └── External API
```

---

# 11. Results Domain

```text
app/Domain/Results/
├── Actions/
│   ├── CalculateResults.php
│   └── PublishResults.php
├── Data/
│   └── ElectionResultData.php
├── Services/
│   └── ResultCalculator.php
├── Exceptions/
│   └── ResultsNotAvailable.php
└── Queries/
    └── ElectionResultQuery.php
```

Result harus mengambil data ballot secara aggregate.

---

# 12. Auditing Domain

```text
app/Domain/Auditing/
├── Actions/
│   └── RecordAuditEvent.php
├── Data/
│   └── AuditEventData.php
├── Enums/
│   └── AuditAction.php
└── Services/
    └── AuditLogger.php
```

Audit tidak boleh menyimpan hubungan:

```text
voter → candidate
```

---

# 13. Exports Domain

```text
app/Domain/Exports/
├── Actions/
│   ├── GenerateElectionExport.php
│   └── GenerateResultPdf.php
├── Jobs/
│   └── GenerateElectionExportJob.php
├── Services/
│   ├── ExcelExportService.php
│   └── PdfExportService.php
└── Data/
    └── ExportData.php
```

Export harus menggunakan aggregate result.

---

# 14. Models

Untuk MVP, model dapat berada di:

```text
app/Models/
```

Contoh:

```text
app/Models/
├── User.php
├── Election.php
├── Candidate.php
├── Voter.php
├── VoterEligibility.php
├── Credential.php
├── Ballot.php
└── AuditLog.php
```

Model berisi:

```text
relationships
casts
scopes
simple invariants
```

Business workflow tetap di Domain Actions.

---

# 15. Enums

Central enum sederhana dapat diletakkan:

```text
app/Enums/
```

Contoh:

```text
UserRole.php
```

Namun enum yang sangat spesifik terhadap domain lebih baik berada di:

```text
app/Domain/Elections/Enums/
```

Contoh:

```text
ElectionStatus.php
```

---

# 16. HTTP Layer

```text
app/Http/
├── Controllers/
├── Middleware/
├── Requests/
└── Resources/
```

---

# 17. Controllers

Pisahkan controller berdasarkan area.

```text
app/Http/Controllers/
├── Admin/
│   ├── DashboardController.php
│   ├── ElectionController.php
│   ├── CandidateController.php
│   ├── VoterController.php
│   ├── CredentialController.php
│   ├── ResultController.php
│   ├── ExportController.php
│   └── AuditLogController.php
│
├── Auth/
│   ├── LoginController.php
│   └── LogoutController.php
│
└── Voter/
    ├── VotingController.php
    └── VotingSessionController.php
```

---

# 18. Controller Responsibility

Controller hanya bertugas:

```text
receive request
 ↓
authorize
 ↓
validate
 ↓
call action
 ↓
transform response
```

Bukan:

```text
SQL
transaction
complex business logic
```

---

# 19. Form Requests

```text
app/Http/Requests/
├── Auth/
├── Admin/
│   ├── Elections/
│   ├── Candidates/
│   ├── Voters/
│   ├── Credentials/
│   └── Exports/
└── Voter/
    └── Voting/
```

Contoh:

```text
StoreElectionRequest.php
UpdateElectionRequest.php
StoreCandidateRequest.php
ImportVoterRequest.php
CastVoteRequest.php
```

---

# 20. API Resources

Jika menggunakan JSON API:

```text
app/Http/Resources/
├── ElectionResource.php
├── CandidateResource.php
├── VoterResource.php
├── ElectionResultResource.php
└── AuditLogResource.php
```

Resource tidak boleh mengembalikan field sensitif yang tidak diperlukan.

---

# 21. Policies

```text
app/Policies/
├── ElectionPolicy.php
├── CandidatePolicy.php
├── VoterPolicy.php
├── CredentialPolicy.php
├── ResultPolicy.php
└── ExportPolicy.php
```

Jika policy sangat domain-specific, dapat ditempatkan dekat domain.

---

# 22. Jobs

Global asynchronous jobs:

```text
app/Jobs/
```

Contoh:

```text
ProcessVoterImport.php
GenerateElectionExport.php
```

Tetapi job yang sangat domain-specific dapat ditempatkan di:

```text
app/Domain/Voters/Jobs/
app/Domain/Exports/Jobs/
```

Pilih satu convention dan konsisten.

---

# 23. Queue Rule

Queue tidak boleh menjadi bagian dari authoritative voting commit.

Tidak boleh:

```text
CastVote
 ↓
dispatch vote job
 ↓
job creates ballot
```

untuk core voting.

Vote harus committed synchronously dalam transaction.

---

# 24. Services

```text
app/Services/
```

Gunakan untuk service lintas domain.

Contoh:

```text
Clock
FileStorage
QrCode
```

Service yang hanya relevan pada satu domain lebih baik berada di:

```text
app/Domain/<Domain>/Services/
```

---

# 25. Exceptions

```text
app/Exceptions/
```

untuk exception application-wide.

Domain exception sebaiknya berada di domain masing-masing:

```text
app/Domain/Voting/Exceptions/
```

---

# 26. Support

```text
app/Support/
├── Hashing/
├── Security/
├── Pagination/
├── Response/
└── Helpers/
```

Hindari membuat:

```text
Support/Helpers.php
```

yang berisi ratusan fungsi tidak terkait.

---

# 27. Database Structure

```text
database/
├── factories/
├── migrations/
└── seeders/
```

---

# 28. Factories

```text
database/factories/
├── UserFactory.php
├── ElectionFactory.php
├── CandidateFactory.php
├── VoterFactory.php
├── VoterEligibilityFactory.php
├── CredentialFactory.php
└── BallotFactory.php
```

Factory harus menghasilkan data sintetis.

---

# 29. Seeders

```text
database/seeders/
├── DatabaseSeeder.php
├── DevelopmentSeeder.php
└── DemoElectionSeeder.php
```

Production tidak boleh menjalankan demo seeder.

---

# 30. Migrations

```text
database/migrations/
```

Migration harus mengikuti dependency:

```text
users
 ↓
elections
 ↓
candidates
voters
 ↓
eligibilities
 ↓
credentials
 ↓
ballots
 ↓
audit_logs
```

Urutan aktual harus mengikuti ERD.

---

# 31. Routes

```text
routes/
├── web.php
├── api.php
├── console.php
└── channels.php
```

---

# 32. Route Organization

Admin:

```text
/admin/*
```

Voter:

```text
/vote/*
```

API:

```text
/api/v1/*
```

Exact routes harus mengikuti `05_API_SPEC.md`.

---

# 33. Route Security

Routes harus menggunakan middleware sesuai kebutuhan:

```text
auth
role
verified
throttle
```

Jangan mengandalkan controller saja untuk boundary yang dapat ditegakkan middleware.

---

# 34. Resources / Views

```text
resources/
├── views/
│   ├── layouts/
│   ├── components/
│   ├── admin/
│   ├── auth/
│   └── voter/
└── css/
    └── app.css
```

---

# 35. Admin Views

```text
resources/views/admin/
├── dashboard.blade.php
├── elections/
├── candidates/
├── voters/
├── credentials/
├── results/
├── exports/
└── audit/
```

---

# 36. Voter Views

```text
resources/views/voter/
├── login.blade.php
├── elections/
├── voting/
│   ├── index.blade.php
│   ├── confirmation.blade.php
│   └── success.blade.php
└── errors/
```

---

# 37. Blade Components

```text
resources/views/components/
├── button.blade.php
├── input.blade.php
├── modal.blade.php
├── alert.blade.php
├── status-badge.blade.php
├── candidate-card.blade.php
└── pagination.blade.php
```

---

# 38. Livewire

Jika Livewire digunakan:

```text
app/Livewire/
├── Admin/
│   ├── Dashboard.php
│   ├── Elections/
│   ├── Candidates/
│   ├── Voters/
│   └── Results/
└── Voter/
    └── Voting.php
```

Livewire component tetap harus memanggil Domain Action.

---

# 39. Frontend JavaScript

Jika JavaScript diperlukan:

```text
resources/js/
├── app.js
├── bootstrap.js
├── components/
└── pages/
```

Jangan memindahkan business rule voting ke JavaScript.

---

# 40. CSS

```text
resources/css/
└── app.css
```

Tailwind configuration mengikuti versi framework yang digunakan.

---

# 41. Tests

```text
tests/
├── Unit/
├── Feature/
├── Security/
├── Integration/
└── E2E/
```

---

# 42. Unit Tests

Struktur mengikuti domain:

```text
tests/Unit/
├── Elections/
├── Credentials/
├── Voting/
├── Results/
└── Support/
```

Contoh:

```text
Voting/ResultCalculatorTest.php
Voting/VotingEligibilityTest.php
```

---

# 43. Feature Tests

```text
tests/Feature/
├── Auth/
├── Admin/
├── Voter/
├── Elections/
├── Candidates/
├── Voters/
├── Credentials/
├── Voting/
├── Results/
└── Exports/
```

---

# 44. Security Tests

```text
tests/Security/
├── Authentication/
├── Authorization/
├── IDOR/
├── Voting/
├── Privacy/
├── FileUpload/
└── RateLimiting/
```

---

# 45. Voting Security Tests

Minimal:

```text
PreventsDoubleVoteTest.php
ConcurrentVotingTest.php
CrossElectionVotingTest.php
BallotPrivacyTest.php
ClosedElectionVotingTest.php
InvalidCandidateTest.php
```

---

# 46. E2E Tests

```text
tests/E2E/
├── admin/
├── voter/
└── election/
```

Scenario utama:

```text
create election
 ↓
add candidates
 ↓
import voters
 ↓
login
 ↓
vote
 ↓
close election
 ↓
view result
```

---

# 47. Documentation

```text
docs/
├── architecture/
├── security/
├── api/
├── database/
├── operations/
├── adr/
└── development/
```

Dokumen proyek utama dapat tetap berada di root jika convention project mengharuskannya:

```text
01_PRD.md
02_ERD_DATABASE_DESIGN.md
...
```

---

# 48. ADR Structure

```text
docs/adr/
├── ADR-001-postgresql.md
├── ADR-002-anonymous-ballot.md
├── ADR-003-credential-strategy.md
├── ADR-004-redis.md
└── ADR-005-voting-concurrency.md
```

---

# 49. Docker Structure

```text
docker/
├── nginx/
│   └── default.conf
├── php/
│   └── php.ini
└── supervisor/
    └── worker.conf
```

Root:

```text
Dockerfile
docker-compose.yml
```

---

# 50. CI Structure

```text
.github/
└── workflows/
    ├── ci.yml
    ├── security.yml
    └── deploy.yml
```

CI minimal:

```text
lint
static analysis
tests
security scan
build
```

---

# 51. Configuration Structure

```text
config/
├── app.php
├── auth.php
├── database.php
├── filesystems.php
├── logging.php
├── queue.php
├── cache.php
├── services.php
└── evoting.php
```

---

# 52. Custom E-Voting Configuration

Gunakan:

```text
config/evoting.php
```

untuk configuration khusus aplikasi.

Contoh:

```php
return [
    'voting' => [
        'idempotency_ttl' => env('VOTING_IDEMPOTENCY_TTL', 3600),
    ],
];
```

Jangan menyimpan secret langsung di file configuration.

---

# 53. Dependency Direction

Dependency ideal:

```text
HTTP
 ↓
Application / Actions
 ↓
Domain
 ↓
Infrastructure
```

Infrastructure tidak boleh memaksa domain memahami detail framework jika dapat dihindari.

---

# 54. Domain Dependency Rule

Contoh:

```text
Voting
  ↓
Elections
  ↓
Candidates
```

Tetapi jangan membuat circular dependency:

```text
Voting → Results
Results → Voting
```

Result sebaiknya membaca authoritative ballot data tanpa membuat voting bergantung pada result.

---

# 55. Cross-Domain Communication

Gunakan:

```text
Action
Service
Domain Event
DTO
```

hindari akses langsung ke internal implementation domain lain.

---

# 56. Domain Events

Contoh event:

```text
ElectionOpened
ElectionClosed
VoterImported
CredentialIssued
VoteCast
```

Namun:

```text
VoteCast
```

harus membawa hanya data yang aman untuk consumer dan tidak boleh membocorkan pilihan kandidat kepada subsystem yang tidak membutuhkan informasi tersebut.

---

# 57. Event Rule

Event/listener tidak boleh menentukan apakah vote berhasil.

Authoritative state:

```text
database transaction
```

Event:

```text
side effect
```

---

# 58. Infrastructure Boundary

Infrastructure dapat mencakup:

```text
database
redis
filesystem
mail
external APIs
queue
```

Jika abstraction dibutuhkan:

```text
Contracts
Implementations
```

---

# 59. Contracts

Gunakan interface hanya ketika ada alasan jelas.

Contoh:

```text
app/Contracts/
├── CredentialGenerator.php
├── ResultExporter.php
└── Clock.php
```

Jangan membuat interface untuk setiap class secara otomatis.

---

# 60. Repositories

Repository pattern tidak wajib.

Gunakan repository hanya jika:

```text
query complexity tinggi
multiple data sources
domain abstraction benar-benar dibutuhkan
```

Untuk query sederhana:

```php
Election::query()
```

sudah cukup.

---

# 61. Query Objects

Gunakan query object untuk query kompleks.

Contoh:

```text
app/Domain/Results/Queries/ElectionResultQuery.php
```

---

# 62. Data Objects

DTO/Data objects digunakan untuk:

```text
validated input
complex command
result data
import rows
```

Jangan menjadikan DTO sebagai wrapper kosong untuk setiap Model.

---

# 63. Naming Files

File harus sesuai class:

```text
CastVote.php
CastVoteData.php
CastVoteRequest.php
CastVoteTest.php
```

Hindari:

```text
VotingStuff.php
Helper.php
Common.php
Utils.php
```

---

# 64. Naming Actions

Gunakan verb + object:

```text
CreateElection
OpenElection
CloseElection
ImportVoters
GenerateCredential
CastVote
CalculateResults
```

Hindari:

```text
ElectionManager
VotingHandler
ElectionProcessor
```

jika tanggung jawabnya terlalu luas.

---

# 65. Naming Services

Service digunakan untuk operasi yang benar-benar bersifat service.

Contoh:

```text
CredentialVerifier
ResultCalculator
ElectionStateManager
```

---

# 66. Naming Exceptions

Gunakan kondisi yang spesifik:

```text
ElectionNotOpen
AlreadyVoted
VoterNotEligible
InvalidCandidate
CredentialAlreadyUsed
```

---

# 67. Naming Tests

Nama test harus menjelaskan behavior:

```text
it_prevents_a_voter_from_voting_twice
it_rejects_vote_when_election_is_closed
it_rejects_candidate_from_another_election
```

---

# 68. Critical File Ownership

Voting-critical files harus memiliki ownership/reviewer yang jelas.

Contoh:

```text
CODEOWNERS

app/Domain/Voting/*
tests/Security/Voting/*
```

review wajib oleh maintainer yang memahami security voting.

---

# 69. Sensitive Files

Repository tidak boleh menyimpan:

```text
.env
production secrets
real voter export
real credentials
production database dump
private keys
```

Gunakan:

```text
.env.example
secret manager
```

---

# 70. Storage Structure

Application storage:

```text
storage/
├── app/
├── framework/
└── logs/
```

Private exports dan sensitive uploads tidak boleh ditempatkan langsung pada public web root.

---

# 71. Public Storage

Hanya data yang memang public:

```text
candidate public image
```

boleh disajikan melalui public storage jika sesuai policy.

Credential/export tidak boleh public.

---

# 72. Logging Structure

```text
storage/logs/
```

Production log aggregation sebaiknya menggunakan external centralized logging sesuai `09_DEPLOYMENT.md`.

---

# 73. Environment Variables

Contoh kategori:

```text
APP_*
DB_*
REDIS_*
QUEUE_*
FILESYSTEM_*
MAIL_*
VOTING_*
```

Secret tidak boleh dikomit.

---

# 74. Test Environment

Test harus menggunakan:

```text
separate database
```

dan tidak boleh terhubung ke production.

---

# 75. Local Demo Environment

Contoh:

```text
Election:
OSIM Demo 2026

Candidates:
01 Candidate A
02 Candidate B
03 Candidate C

Voters:
synthetic users
```

Semua data harus jelas sebagai demo.

---

# 76. Recommended Initial Tree

Versi awal repository:

```text
e-voting/
├── app/
│   ├── Domain/
│   │   ├── Elections/
│   │   ├── Candidates/
│   │   ├── Voters/
│   │   ├── Credentials/
│   │   ├── Voting/
│   │   ├── Results/
│   │   ├── Auditing/
│   │   └── Exports/
│   ├── Http/
│   ├── Models/
│   ├── Policies/
│   ├── Jobs/
│   ├── Exceptions/
│   └── Support/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   ├── views/
│   ├── css/
│   └── js/
│
├── routes/
│
├── tests/
│   ├── Unit/
│   ├── Feature/
│   ├── Security/
│   ├── Integration/
│   └── E2E/
│
├── docs/
│   ├── adr/
│   ├── architecture/
│   ├── security/
│   └── operations/
│
├── docker/
│   ├── nginx/
│   ├── php/
│   └── supervisor/
│
└── .github/
    └── workflows/
```

---

# 77. What Not to Do

Hindari struktur:

```text
app/
├── Helpers/
├── Managers/
├── Utils/
├── Common/
├── Misc/
└── Services/
    └── EverythingService.php
```

Masalah:

```text
unclear ownership
high coupling
difficult testing
business rules scattered
```

---

# 78. Avoid Premature Microservices

MVP sebaiknya:

```text
modular monolith
```

bukan:

```text
voting-service
user-service
result-service
credential-service
```

terpisah.

Alasan:

```text
simpler deployment
simpler transaction
lower operational complexity
easier privacy review
```

---

# 79. Modular Monolith Boundary

Target:

```text
Single Laravel application
        │
        ├── Elections
        ├── Candidates
        ├── Voters
        ├── Credentials
        ├── Voting
        ├── Results
        └── Auditing
```

Domain boundary tetap jelas walaupun deployment satu aplikasi.

---

# 80. Future Extraction

Jika suatu hari perlu scale:

```text
Modular Monolith
       ↓
Identify bottleneck
       ↓
Extract only if justified
```

Jangan membuat microservice hanya karena struktur terlihat lebih modern.

---

# 81. Recommended Dependency Rules

```text
Controllers
  → Actions

Actions
  → Domain Services / Models

Domain Services
  → Domain Models / Contracts

Infrastructure
  → external systems

Views
  → View Models / Resources
```

Views tidak boleh:

```text
write database
```

---

# 82. Voting Dependency Rule

Ideal:

```text
VoterController
      ↓
CastVote
      ↓
VotingEligibility
      ↓
Election/Candidate
      ↓
Ballot + Eligibility transaction
```

Bukan:

```text
VoterController
      ↓
20 queries
      ↓
manual status changes
      ↓
external API
```

---

# 83. Result Dependency Rule

```text
ResultController
      ↓
CalculateResults
      ↓
Ballot query
      ↓
aggregate
```

Result tidak boleh mengubah ballot.

---

# 84. Audit Dependency Rule

Audit:

```text
observe
```

bukan:

```text
control voting
```

Audit failure untuk non-critical event tidak boleh merusak vote transaction kecuali security policy secara eksplisit menentukan sebaliknya.

---

# 85. Import Dependency Rule

Import:

```text
upload
 ↓
validate
 ↓
parse
 ↓
preview
 ↓
commit
```

Jangan memasukkan import voter ke voting domain.

---

# 86. File Naming Consistency

Gunakan satu convention:

```text
PascalCase.php
```

untuk class files.

Blade:

```text
kebab-case.blade.php
```

atau convention Laravel yang dipilih project.

---

# 87. Documentation and Code Synchronization

Jika perubahan code mengubah:

```text
API
database
security
architecture
deployment
```

developer wajib memperbarui dokumen terkait.

---

# 88. Change Impact Matrix

| Perubahan | Dokumen yang mungkin perlu diperbarui |
|---|---|
| DB schema | ERD, migrations |
| Endpoint | API Spec |
| Auth | Security, API |
| Voting transaction | Security, Voting Engine |
| Deployment | Architecture, Deployment |
| UI flow | UI/UX, User Flows |
| Test requirement | Test Plan |
| Folder/module | Project Structure |
| Coding convention | Coding Standards |

---

# 89. Definition of a Clean Module

Module dianggap sehat jika:

```text
clear responsibility
limited public API
few dependencies
tests available
security boundary known
```

---

# 90. Final Architecture Shape

```text
                    ┌─────────────────┐
                    │      HTTP       │
                    │ Web / API       │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │   Application   │
                    │ Actions / DTO   │
                    └────────┬────────┘
                             │
                             ▼
             ┌─────────────────────────────┐
             │           Domain            │
             │                             │
             │ Elections   Candidates      │
             │ Voters      Credentials      │
             │ Voting      Results          │
             │ Auditing    Exports          │
             └─────────────┬───────────────┘
                           │
                           ▼
             ┌─────────────────────────────┐
             │       Infrastructure        │
             │                             │
             │ PostgreSQL  Redis  Storage  │
             │ Queue       Mail   External │
             └─────────────────────────────┘
```

---

# 91. Final Rules

Struktur project harus mempertahankan prinsip:

```text
1. Domain boundary jelas.
2. Controller tipis.
3. Business logic berada di Action/Domain.
4. Voting-critical code terisolasi.
5. Database tetap authoritative.
6. UI tidak dipercaya sebagai security boundary.
7. Async processing tidak menentukan keberhasilan vote.
8. Audit tidak boleh membocorkan pilihan.
9. Tests mengikuti domain.
10. Dokumentasi mengikuti perubahan architecture.
```

---

# 92. Next Document

Dokumen berikutnya yang direkomendasikan:

```text
13_DATABASE_MIGRATIONS.md
```

Dokumen tersebut akan menerjemahkan ERD menjadi migration plan Laravel secara konkret, termasuk:

```text
table creation order
columns
data types
indexes
foreign keys
unique constraints
check constraints
nullable rules
delete/update behavior
seed strategy
migration safety
```

Dokumen paling sensitif berikutnya tetap:

```text
15_VOTING_ENGINE_SPEC.md
```

karena akan menjadi blueprint implementasi `CastVote`.
