# Implementation Roadmap — Sistem E-Voting Sekolah

**Dokumen:** 10 — Implementation Roadmap  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD` s/d `09_DEPLOYMENT`

---

# 1. Tujuan

Dokumen ini mengubah seluruh spesifikasi produk, database, security, architecture, API, UI/UX, user flow, testing, dan deployment menjadi urutan pekerjaan implementasi yang dapat langsung digunakan oleh developer.

Prinsip:

```text
Design
  ↓
Foundation
  ↓
Security
  ↓
Core Election
  ↓
Voting Engine
  ↓
Results
  ↓
Testing
  ↓
Deployment
  ↓
Go-Live
```

---

# 2. Recommended Technology Stack

Baseline implementasi:

```text
Backend
└── Laravel

Language
└── PHP

Database
└── PostgreSQL

Cache / Queue
└── Redis

Frontend
├── Blade
├── Livewire
└── Tailwind CSS

Testing
├── Pest / PHPUnit
└── Playwright

Web Server
└── Nginx

Runtime
└── PHP-FPM

Deployment
└── Docker

Version Control
└── Git
```

Versi dependency harus menggunakan versi yang masih mendapatkan security updates pada saat project dimulai.

---

# 3. Implementation Principles

## 3.1 Security First

Security-critical functionality harus dibangun sebelum UI polish.

Prioritas:

```text
Authentication
Authorization
Election isolation
Credential security
Voting transaction
Anonymous ballot
Audit
```

---

## 3.2 Database Constraints Are Security Controls

Jangan hanya mengandalkan application code.

Gunakan:

```text
foreign key
unique constraint
check constraint
transaction
row locking
```

untuk invariant penting.

---

## 3.3 Voting Engine Must Be Small

Kode yang bertanggung jawab terhadap ballot harus seminimal mungkin.

Ideal:

```text
authenticate
   ↓
verify eligibility
   ↓
verify election
   ↓
verify candidate
   ↓
create ballot
   ↓
mark eligibility voted
   ↓
commit
```

Jangan mencampurkan:

```text
email
notification
analytics
file upload
export
```

ke dalam critical vote transaction.

---

# 4. Repository Structure

Recommended:

```text
app/
├── Actions/
├── Console/
├── Domain/
│   ├── Elections/
│   ├── Candidates/
│   ├── Voters/
│   ├── Credentials/
│   ├── Voting/
│   ├── Results/
│   └── Audit/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Services/
└── Support/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── views/
└── js/

routes/
├── web.php
└── api.php

tests/
├── Unit/
├── Feature/
├── Security/
└── E2E/
```

Struktur dapat disederhanakan jika project kecil, tetapi domain voting sebaiknya tetap terisolasi.

---

# 5. Git Repository Setup

Tasks:

```text
[ ] Create repository
[ ] Add README
[ ] Add .gitignore
[ ] Add LICENSE
[ ] Add contribution rules if needed
[ ] Configure branch protection
[ ] Configure CI
```

Branches:

```text
main
develop
feature/*
fix/*
```

---

# 6. Phase 0 — Project Foundation

## Goal

Membuat project Laravel yang dapat dijalankan secara lokal dan staging.

Tasks:

```text
[ ] Create Laravel project
[ ] Configure PHP
[ ] Configure Composer
[ ] Configure PostgreSQL
[ ] Configure Redis
[ ] Configure Docker
[ ] Configure Nginx
[ ] Configure environment files
[ ] Configure logging
[ ] Configure queue
```

Deliverable:

```text
Laravel application boots successfully.
```

---

# 7. Phase 0 Acceptance

```text
docker compose up
```

harus menghasilkan:

```text
Application OK
PostgreSQL OK
Redis OK
Queue OK
```

Health check:

```text
/health
```

harus memberikan status sehat tanpa membocorkan secret.

---

# 8. Phase 1 — Database Foundation

Implement:

```text
users
elections
candidates
voters
voter_eligibilities
credentials
ballots
audit_logs
```

Nama/tabel final harus mengikuti `02_ERD / Database Design`.

Tasks:

```text
[ ] migrations
[ ] indexes
[ ] foreign keys
[ ] unique constraints
[ ] factories
[ ] seeders
```

---

# 9. Phase 1 Acceptance

Automated test:

```text
[ ] migrations run cleanly
[ ] fresh database works
[ ] rollback works where supported
[ ] factories generate valid data
[ ] FK constraints verified
[ ] unique constraints verified
```

---

# 10. Phase 2 — Authentication

Implement:

```text
Admin login
Operator login
Voter authentication
Logout
Session management
Password/PIN verification
```

Security:

```text
session regeneration
secure cookies
rate limiting
credential hashing
```

---

# 11. Authentication Roles

Implement RBAC:

```text
SUPER_ADMIN
ADMIN
OPERATOR
VOTER
```

Exact role names must follow PRD/API design if already specified differently.

---

# 12. Phase 2 Acceptance

Tests:

```text
[ ] valid login
[ ] invalid login
[ ] logout
[ ] expired session
[ ] role authorization
[ ] rate limit
[ ] IDOR
[ ] session security
```

---

# 13. Phase 3 — Authorization & Policies

Implement Laravel Policies/Gates for:

```text
Election
Candidate
Voter
Credential
Result
Export
Audit
```

Example:

```text
Admin → manage election
Operator → operational election actions
Voter → own voting session
```

No controller should rely solely on UI hiding.

---

# 14. Phase 3 Acceptance

Attempt:

```text
Voter → Admin endpoint
Operator → restricted admin endpoint
Admin → unrelated election
```

Expected:

```text
403/404 according to security policy.
```

---

# 15. Phase 4 — Election Management

Implement:

```text
create election
edit election
schedule election
open election
close election
archive election
```

State machine:

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

Invalid transitions must be rejected.

---

# 16. Election Configuration

Fields should include only what is defined by PRD/ERD, such as:

```text
name
description
start_at
end_at
status
settings
```

Do not add unnecessary configuration to MVP.

---

# 17. Phase 4 Acceptance

Tests:

```text
[ ] valid state transition
[ ] invalid transition rejected
[ ] election cannot open without required data
[ ] closed election cannot accept votes
[ ] archive behavior verified
```

---

# 18. Phase 5 — Candidate Management

Implement:

```text
create candidate
edit candidate
delete/deactivate candidate
candidate ordering
candidate photo
vision
mission
```

Rules:

```text
candidate belongs to election
candidate number unique within election
```

---

# 19. Candidate Upload

Implement:

```text
image validation
size limits
safe filenames
private/controlled storage
```

Do not store executable files.

---

# 20. Phase 5 Acceptance

```text
[ ] candidate CRUD
[ ] duplicate number rejected
[ ] cross-election candidate rejected
[ ] upload validation
[ ] XSS-safe rendering
```

---

# 21. Phase 6 — Voter Management

Implement:

```text
create voter
edit voter
deactivate voter
view eligibility
class/group assignment
bulk import
```

Important:

```text
voter identity
```

must remain separate from:

```text
ballot choice
```

---

# 22. Voter Import

Supported initial format:

```text
CSV
XLSX
```

Pipeline:

```text
Upload
 ↓
Validate file
 ↓
Parse
 ↓
Validate rows
 ↓
Preview
 ↓
Confirm import
 ↓
Commit
```

Large import should run asynchronously if necessary.

---

# 23. Import Validation

Validate:

```text
student ID
name
class
duplicate
required fields
format
```

Security:

```text
formula injection protection
file size limit
MIME validation
malicious filename protection
```

---

# 24. Phase 6 Acceptance

```text
[ ] valid import
[ ] duplicate detection
[ ] invalid row reporting
[ ] large file handling
[ ] safe spreadsheet handling
[ ] rollback on failed import
```

---

# 25. Phase 7 — Credential System

Implement:

```text
credential generation
credential hashing
credential verification
credential status
credential expiry if required
single-use protection
```

Credential should not be stored in plaintext.

---

# 26. Credential Delivery

Possible modes:

```text
manual print
QR code
secure distribution
```

MVP can begin with:

```text
unique voter credential/PIN
```

QR can be added after core voting is stable.

---

# 27. QR Code Strategy

If QR is implemented:

```text
QR identifies a login/credential mechanism
```

not the vote choice.

QR must still require:

```text
authentication
single-use protection
expiry where appropriate
```

---

# 28. Phase 7 Acceptance

```text
[ ] unique credentials
[ ] no plaintext credential persistence
[ ] invalid credential rejected
[ ] used credential rejected
[ ] brute-force protection
[ ] QR cannot be reused
```

---

# 29. Phase 8 — Voting Session

Implement:

```text
voter authentication
election selection
eligibility verification
candidate retrieval
confirmation
vote submission
success state
```

---

# 30. Voting UI

Flow:

```text
Login
 ↓
Election
 ↓
Candidate List
 ↓
Select One
 ↓
Confirmation
 ↓
Submit
 ↓
Success
```

Never submit vote merely because a candidate card was clicked.

Require explicit confirmation.

---

# 31. Phase 9 — Voting Engine

This is the most critical implementation phase.

Implement a dedicated application service/action:

```text
CastVote
```

Responsibilities:

```text
validate session
validate election
validate eligibility
validate candidate
execute transaction
create ballot
mark voter voted
return result
```

---

# 32. Vote Transaction

Conceptual:

```text
BEGIN

lock voter eligibility

verify status = NOT_VOTED

verify election = OPEN

verify candidate belongs to election

create ballot

mark eligibility = VOTED

COMMIT
```

If any critical operation fails:

```text
ROLLBACK
```

---

# 33. Preventing Double Vote

Use multiple defenses:

```text
application validation
database unique constraint
transaction
row lock / appropriate concurrency control
idempotency key
```

Do not depend on only one defense.

---

# 34. Concurrent Vote Handling

Scenario:

```text
Request A ─┐
Request B ─┼→ same voter
Request C ─┘
```

Expected:

```text
exactly one successful vote
```

The others must fail safely.

---

# 35. Ballot Privacy Boundary

Normal application flow must never produce:

```text
voter_id → candidate_id
```

as an accessible relationship.

Ballot storage should contain only the data required by the ballot model defined in `02_ERD`.

---

# 36. Vote Hash / Integrity

If defined in ERD/security model:

```text
ballot_hash
```

must be generated deterministically according to the documented integrity design.

Do not invent a hash scheme during implementation without updating:

```text
02_ERD
03_SECURITY_Threat_Model
```

---

# 37. Phase 9 Acceptance

Mandatory:

```text
[ ] one voter = max one valid ballot
[ ] concurrent requests safe
[ ] duplicate request safe
[ ] invalid candidate rejected
[ ] closed election rejected
[ ] transaction rollback works
[ ] privacy boundary verified
```

---

# 38. Phase 10 — Monitoring

Implement:

```text
eligible count
voted count
remaining count
participation
```

During OPEN:

```text
no candidate result totals
```

unless explicitly required by election policy.

Recommended:

```text
candidate result hidden until CLOSED
```

---

# 39. Realtime Monitoring

Optional MVP enhancement:

```text
Redis
WebSocket / broadcasting
```

Events should contain aggregate information only.

Example:

```text
voter participation updated
```

Not:

```text
Voter A selected Candidate B
```

---

# 40. Phase 11 — Result Calculation

After election CLOSED:

```text
calculate valid ballots
group by candidate
calculate percentages
calculate participation
```

Invariant:

```text
sum(candidate votes)
=
valid ballots
```

---

# 41. Result Lock

Before CLOSED:

```text
candidate totals hidden
```

After CLOSED:

```text
candidate totals available
```

Result endpoint must enforce this server-side.

---

# 42. Tie Handling

If tie occurs:

```text
show tie
```

Do not automatically determine winner unless election rules explicitly define the tie-break process.

---

# 43. Phase 12 — Export

Implement:

```text
CSV/XLSX
PDF
print view
```

MVP recommendation:

```text
XLSX
PDF
```

Export should contain:

```text
aggregate result
participation
candidate information
```

Never export:

```text
voter → candidate mapping
```

---

# 44. Export Security

Rules:

```text
only authorized role
election CLOSED
private storage
short-lived download
audit event
```

---

# 45. Phase 13 — Audit Log

Implement audit events:

```text
LOGIN
LOGOUT
ELECTION_CREATED
ELECTION_UPDATED
ELECTION_OPENED
ELECTION_CLOSED
CANDIDATE_CREATED
CANDIDATE_UPDATED
VOTER_IMPORTED
CREDENTIAL_ISSUED
EXPORT_CREATED
```

Vote audit event must not reveal candidate choice.

---

# 46. Phase 14 — Admin Dashboard

Dashboard:

```text
total elections
active election
eligible voters
voted
remaining
participation
```

After close:

```text
candidate results
charts
statistics
```

---

# 47. Phase 15 — UI/UX Implementation

Implement according to:

```text
06_UI_UX_SPEC.md
```

Priorities:

```text
mobile first for voter
desktop/tablet for admin
clear confirmation
clear error states
accessible controls
```

---

# 48. Voter UX Requirements

Voter must immediately understand:

```text
where to vote
who the candidates are
which candidate is selected
what happens after confirmation
whether vote succeeded
```

Avoid:

```text
ambiguous buttons
multiple submit buttons
hidden errors
```

---

# 49. Admin UX Requirements

Admin must easily see:

```text
election state
voter participation
candidate configuration
errors
system status
```

Dangerous actions require confirmation:

```text
open
close
archive
regenerate credentials
bulk delete
```

---

# 50. Phase 16 — Automated Testing

Implement tests continuously, not after all features.

Minimum test order:

```text
Unit
 ↓
Feature
 ↓
Security
 ↓
Concurrency
 ↓
E2E
```

---

# 51. Test Priority

P0:

```text
authentication
authorization
election state
single vote
double vote
concurrency
anonymous ballot
result integrity
```

P1:

```text
import
export
realtime
accessibility
performance
```

---

# 52. Test Factories

Create:

```text
UserFactory
ElectionFactory
CandidateFactory
VoterFactory
EligibilityFactory
CredentialFactory
BallotFactory
```

Factories must support test states:

```text
open election
closed election
eligible voter
voted voter
unused credential
used credential
```

---

# 53. Security Test Implementation

Implement:

```text
[ ] IDOR
[ ] RBAC
[ ] CSRF
[ ] XSS
[ ] rate limiting
[ ] session security
[ ] file upload
[ ] import
[ ] privacy
[ ] credential brute force
```

---

# 54. Concurrency Test Implementation

Test with:

```text
parallel HTTP requests
```

or concurrent application jobs.

Critical assertion:

```text
ballot count remains 1
```

for a single voter.

---

# 55. Phase 17 — Performance Testing

Baseline:

```text
100 concurrent voters
500 concurrent voters
1000 concurrent voters
```

Measure:

```text
p50
p95
p99
error rate
DB locks
DB connections
CPU
RAM
```

Initial target:

```text
p95 vote response < 1 second
```

Final target depends on infrastructure.

---

# 56. Phase 18 — Staging

Staging must reproduce:

```text
application
database
redis
queue
storage
TLS
monitoring
```

Use synthetic voters.

Never use real voter credentials in staging unless explicitly authorized and protected.

---

# 57. Staging Acceptance

Run:

```text
full migration
seed
import
credential generation
open
vote
double vote
concurrent vote
close
result
export
backup
restore
```

---

# 58. Phase 19 — Security Review

Before production:

```text
SAST
dependency scan
DAST
authorization review
privacy review
secret scan
configuration review
```

Critical findings must be resolved.

---

# 59. Phase 20 — Production Deployment

Follow:

```text
09_DEPLOYMENT.md
```

Sequence:

```text
backup
 ↓
deploy
 ↓
migration
 ↓
cache
 ↓
queue restart
 ↓
health
 ↓
smoke test
 ↓
monitor
```

---

# 60. Phase 21 — Pre-Go-Live

At least one complete rehearsal:

```text
842 synthetic voters
3 candidates
671 simulated votes
```

Verify:

```text
participation
results
privacy
performance
backup
restore
```

---

# 61. Phase 22 — Election-Day Readiness

Checklist:

```text
[ ] server healthy
[ ] database healthy
[ ] Redis healthy
[ ] queue healthy
[ ] backup verified
[ ] TLS valid
[ ] credentials ready
[ ] candidate data final
[ ] voter count final
[ ] monitoring active
[ ] support contact ready
[ ] deployment freeze active
```

---

# 62. Election Opening

Procedure:

```text
Final verification
 ↓
Backup
 ↓
Open election
 ↓
Smoke test
 ↓
Monitor
```

Do not perform feature deployment after opening unless emergency procedure is activated.

---

# 63. Election Monitoring

Monitor:

```text
votes/minute
vote failures
authentication failures
database latency
database locks
CPU
RAM
disk
queue
```

Alert on abnormal behavior.

---

# 64. Incident Handling

If critical issue occurs:

```text
Detect
 ↓
Freeze risky operations
 ↓
Preserve evidence
 ↓
Assess integrity
 ↓
Recover or rollback
 ↓
Verify
 ↓
Resume/close according to policy
```

Never manually modify ballots as an ad-hoc fix.

---

# 65. Election Closing

Procedure:

```text
Close election
 ↓
Verify no new votes accepted
 ↓
Calculate results
 ↓
Verify invariants
 ↓
Generate export
 ↓
Backup
```

---

# 66. Post-Election

Tasks:

```text
[ ] result verification
[ ] export verification
[ ] backup
[ ] archive
[ ] audit review
[ ] incident review
[ ] performance review
```

---

# 67. Milestone Plan

## Milestone M0 — Foundation

Deliver:

```text
Laravel
Docker
PostgreSQL
Redis
CI
Health check
```

---

## Milestone M1 — Identity

Deliver:

```text
Authentication
RBAC
Users
Voters
Credentials
```

---

## Milestone M2 — Election

Deliver:

```text
Election CRUD
Candidate CRUD
State machine
```

---

## Milestone M3 — Voting

Deliver:

```text
Eligibility
Voting session
CastVote
Anonymous ballot
Concurrency protection
```

---

## Milestone M4 — Results

Deliver:

```text
Close election
Result calculation
Dashboard
Export
```

---

## Milestone M5 — Security

Deliver:

```text
Security tests
Privacy review
Audit
Rate limiting
Hardening
```

---

## Milestone M6 — Production

Deliver:

```text
Deployment
Backup
Monitoring
Restore
Load test
Go-live rehearsal
```

---

# 68. Suggested Sprint Breakdown

## Sprint 1

```text
Project setup
Docker
CI
Database foundation
Authentication
```

## Sprint 2

```text
RBAC
Election
Candidate
```

## Sprint 3

```text
Voter
Import
Credential
```

## Sprint 4

```text
Voting session
Voting engine
Concurrency
Privacy
```

## Sprint 5

```text
Monitoring
Results
Export
Audit
```

## Sprint 6

```text
Security
E2E
Performance
Accessibility
```

## Sprint 7

```text
Deployment
Backup
Restore
Rehearsal
Go-live
```

Sprint duration should be adapted to team size and project scope.

---

# 69. Dependency Graph

```text
Foundation
   ↓
Database
   ↓
Authentication
   ↓
RBAC
   ↓
Election
   ├── Candidate
   └── Voter
         ↓
     Credential
         ↓
    Voting Session
         ↓
     Voting Engine
         ↓
       Results
         ↓
       Export
```

Parallel:

```text
Testing
Security
UI/UX
Documentation
Deployment
```

can proceed continuously.

---

# 70. Critical Path

The critical path is:

```text
Database
 ↓
Authentication
 ↓
Authorization
 ↓
Election
 ↓
Eligibility
 ↓
Credential
 ↓
Voting Engine
 ↓
Privacy Verification
 ↓
Result
 ↓
E2E
 ↓
Production
```

---

# 71. MVP Scope

MVP should contain:

```text
[CORE]
Authentication
RBAC
Election
Candidate
Voter
Credential
Voting
Anonymous ballot
Result
Export
Audit
```

Infrastructure:

```text
Docker
PostgreSQL
Redis
Nginx
HTTPS
Backup
Monitoring
```

---

# 72. Post-MVP Features

Defer until core is stable:

```text
QR enhancement
advanced realtime
multi-election analytics
advanced reporting
mobile app
SSO
external identity provider
multi-school tenancy
advanced notification
```

---

# 73. Features That Must Not Delay MVP

Do not delay core launch for:

```text
animations
complex dashboard
advanced charting
dark mode
native mobile app
AI features
```

---

# 74. Definition of Ready

A task is ready for development if:

```text
requirement clear
acceptance criteria clear
database impact known
security impact known
API impact known
UI impact known
test strategy known
```

---

# 75. Definition of Done

A feature is done if:

```text
[ ] implemented
[ ] code reviewed
[ ] automated tests
[ ] security reviewed
[ ] UI tested
[ ] API tested
[ ] documentation updated
[ ] staging verified
```

---

# 76. Coding Standards

Recommended:

```text
PSR-12
Laravel conventions
strict validation
typed properties/return types where appropriate
small services/actions
dependency injection
```

Avoid:

```text
fat controllers
duplicated business rules
raw SQL without reason
business logic inside Blade
```

---

# 77. Pull Request Rules

Every PR should include:

```text
Summary
Changes
Tests
Security impact
Database impact
Screenshots if UI
Migration notes
```

Voting-critical PRs require additional review.

---

# 78. Voting-Critical Code Review

Mandatory review for:

```text
CastVote
Eligibility
Credential verification
Ballot creation
Election state transition
Result calculation
Authorization policies
```

Reviewer must explicitly verify:

```text
privacy
concurrency
transaction boundaries
authorization
```

---

# 79. Migration Rules

Migration harus:

```text
reversible where practical
tested
indexed
non-destructive by default
```

For critical tables:

```text
ballots
eligibilities
credentials
```

changes require special review.

---

# 80. API Implementation Order

Implement API in this order:

```text
/auth
/elections
/candidates
/voters
/credentials
/voting
/results
/exports
/audit
```

Exact endpoint paths must follow `05_API_SPEC.md`.

---

# 81. UI Implementation Order

Admin:

```text
Login
 ↓
Dashboard
 ↓
Election
 ↓
Candidates
 ↓
Voters
 ↓
Credentials
 ↓
Monitoring
 ↓
Results
 ↓
Export
```

Voter:

```text
Login
 ↓
Election
 ↓
Candidates
 ↓
Confirmation
 ↓
Success
```

---

# 82. Observability Implementation Order

Implement early:

```text
structured logging
request ID
health endpoint
error tracking
metrics
```

Do not wait until production incident to add observability.

---

# 83. Security Implementation Order

Security controls should be implemented alongside features:

```text
Auth
→ RBAC
→ validation
→ rate limit
→ CSRF
→ audit
→ privacy boundary
→ security headers
→ secret management
```

---

# 84. Release Candidate

Before `v1.0.0`:

```text
all P0 tests pass
all critical security findings resolved
load test pass
restore test pass
E2E pass
privacy review pass
deployment rehearsal pass
```

---

# 85. Go-Live Gate

Production launch requires explicit sign-off for:

```text
Product
Technical
Security
Operations
Election Owner
```

At minimum, the responsible organization must confirm:

```text
voter data final
candidate data final
schedule final
credentials ready
backup verified
incident plan ready
```

---

# 86. Post-Go-Live Monitoring

First period after launch:

```text
high monitoring
```

Observe:

```text
authentication
voting
errors
latency
database
queue
infrastructure
```

Do not deploy non-essential changes.

---

# 87. Project Success Criteria

The implementation is successful when:

```text
100% eligible voters can authenticate
```

and:

```text
one voter cannot cast more than one valid ballot
```

and:

```text
candidate totals equal valid ballots
```

and:

```text
normal administrative workflows cannot reveal voter choices
```

and:

```text
system can recover from infrastructure failure
```

---

# 88. Final Implementation Checklist

## Foundation

- [ ] Repository
- [ ] Laravel
- [ ] Docker
- [ ] CI
- [ ] PostgreSQL
- [ ] Redis

## Security

- [ ] Authentication
- [ ] RBAC
- [ ] Rate limit
- [ ] CSRF
- [ ] Secure session
- [ ] Secret management

## Core

- [ ] Election
- [ ] Candidate
- [ ] Voter
- [ ] Credential
- [ ] Voting
- [ ] Anonymous ballot

## Results

- [ ] Close
- [ ] Calculate
- [ ] Dashboard
- [ ] Export

## Reliability

- [ ] Audit
- [ ] Backup
- [ ] Restore
- [ ] Monitoring
- [ ] Alerts
- [ ] Incident procedure

## Quality

- [ ] Unit
- [ ] Feature
- [ ] Security
- [ ] Concurrency
- [ ] E2E
- [ ] Performance
- [ ] Accessibility
- [ ] UAT

## Production

- [ ] Staging
- [ ] Load test
- [ ] Deployment rehearsal
- [ ] Go-live checklist
- [ ] Change freeze

---

# 89. Recommended Next Documents

Setelah roadmap, dokumentasi teknis dapat dilanjutkan ke:

```text
11_CODING_STANDARDS.md
12_PROJECT_STRUCTURE.md
13_DATABASE_MIGRATIONS.md
14_AUTH_RBAC_SPEC.md
15_VOTING_ENGINE_SPEC.md
16_AUDIT_LOG_SPEC.md
17_DEVOPS_CI_CD.md
18_OPERATIONS_RUNBOOK.md
19_GO_LIVE_CHECKLIST.md
```

Dokumen yang paling penting untuk implementasi berikutnya adalah:

```text
15_VOTING_ENGINE_SPEC.md
```

karena bagian tersebut mendefinisikan secara lebih detail transaksi `CastVote`, concurrency control, idempotency, anonymous ballot boundary, dan invariants yang menjadi inti keamanan aplikasi e-voting.
