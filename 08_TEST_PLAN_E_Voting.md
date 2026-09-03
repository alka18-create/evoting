# Test Plan — Sistem E-Voting Sekolah

**Dokumen:** 08 — Test Plan  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD`, `02_ERD`, `03_SECURITY_Threat_Model`, `04_ARCHITECTURE`, `05_API_SPEC`, `06_UI_UX_SPEC`, `07_USER_FLOWS`

---

# 1. Tujuan

Test plan ini memastikan aplikasi e-voting:

1. Berfungsi sesuai requirement.
2. Hanya mengizinkan satu suara per pemilih.
3. Tidak membuka hubungan identitas pemilih dengan pilihan suara.
4. Menolak akses yang tidak berwenang.
5. Aman terhadap request duplikat dan concurrency.
6. Menampilkan hasil hanya setelah voting ditutup.
7. Memiliki integritas data voting.
8. Tetap konsisten ketika terjadi kegagalan sistem.
9. Siap digunakan pada hari pemilihan.

---

# 2. Testing Principles

Prioritas testing:

```text
Security
   ↓
Vote Integrity
   ↓
Privacy
   ↓
Authorization
   ↓
Functional Correctness
   ↓
Performance
   ↓
Usability
```

Testing security-critical tidak boleh digantikan hanya dengan UI testing.

---

# 3. Test Levels

```text
Unit Test
    ↓
Feature Test
    ↓
Integration Test
    ↓
API Test
    ↓
Security Test
    ↓
Concurrency Test
    ↓
E2E Test
    ↓
Load Test
    ↓
UAT
    ↓
Election-Day Test
```

---

# 4. Test Environments

## Local

Untuk:

```text
Unit
Feature
Basic integration
```

## Staging

Harus menyerupai production:

```text
Application
Database
Redis
Queue
Object storage
Realtime
```

## Production

Tidak digunakan untuk eksperimen destructive.

Production testing hanya:

```text
Smoke test
Health check
Non-destructive verification
```

---

# 5. Test Data

Gunakan dataset sintetis.

Contoh:

```text
Election:
TEST-OSIM-2026

Candidates:
01 Ahmad
02 Budi
03 Citra

Voters:
100 test voters

Credentials:
100 test credentials
```

Jangan menggunakan data siswa asli untuk automated test.

---

# 6. Test Database

Automated test harus:

```text
isolated database
```

Setiap test harus dapat:

```text
setup
execute
assert
cleanup
```

Jangan bergantung pada urutan test lain.

---

# 7. Test Naming Convention

Gunakan:

```text
test_<expected_behavior>
```

Contoh:

```text
test_voter_can_cast_one_vote
test_voter_cannot_cast_second_vote
test_result_is_hidden_while_election_is_open
```

Untuk Pest/PHPUnit:

```text
it('prevents a voter from casting a second vote')
```

---

# 8. Unit Test Scope

Unit test untuk:

```text
ElectionStateTransition
VoteEligibility
CredentialService
BallotService
ResultCalculator
ParticipationCalculator
PermissionService
ExportFormatter
```

---

# 9. Election State Unit Tests

### TEST-ELECTION-001

Input:

```text
DRAFT
```

Action:

```text
open
```

Expected:

```text
OPEN
```

---

### TEST-ELECTION-002

```text
DRAFT → SCHEDULED
```

Expected:

```text
allowed
```

---

### TEST-ELECTION-003

```text
CLOSED → OPEN
```

Expected:

```text
rejected
```

---

### TEST-ELECTION-004

```text
ARCHIVED → OPEN
```

Expected:

```text
rejected
```

---

# 10. Election Readiness Tests

Election dapat dibuka jika:

```text
candidate count >= 1
voter count >= 1
valid schedule
credential system ready
```

Test:

```text
missing candidate
missing voter
invalid schedule
```

Expected:

```text
OPEN rejected
```

---

# 11. Candidate Unit Tests

### TEST-CANDIDATE-001

Nomor kandidat unik.

Expected:

```text
success
```

### TEST-CANDIDATE-002

Nomor kandidat duplicate.

Expected:

```text
validation error
```

### TEST-CANDIDATE-003

Candidate dari election lain.

Expected:

```text
invalid
```

---

# 12. Credential Tests

Credential harus:

```text
cryptographically random
unique
hashed at rest
```

Test:

### TEST-CREDENTIAL-001

Generate 10,000 credentials.

Expected:

```text
no duplicate
```

### TEST-CREDENTIAL-002

Database inspection.

Expected:

```text
plaintext credential absent
```

### TEST-CREDENTIAL-003

Credential verification.

Expected:

```text
valid credential → success
invalid credential → failure
```

---

# 13. Voter Eligibility Tests

### TEST-VOTER-001

Voter eligible:

```text
NOT_VOTED
```

Expected:

```text
can vote
```

### TEST-VOTER-002

Voter already voted:

```text
VOTED
```

Expected:

```text
cannot vote
```

### TEST-VOTER-003

Voter belongs to different election.

Expected:

```text
cannot vote
```

---

# 14. Core Voting Test

### TEST-VOTE-001

Scenario:

```text
Open election
Eligible voter
Valid candidate
Valid credential
```

Action:

```text
cast vote
```

Expected:

```text
1 ballot created
eligibility marked VOTED
transaction committed
```

---

# 15. Single Vote Invariant

### TEST-VOTE-002

Action:

```text
same voter casts vote twice
```

Expected:

```text
first = success
second = rejected
```

Database:

```text
ballot count = 1
```

---

# 16. Different Voters

### TEST-VOTE-003

```text
Voter A → Candidate 01
Voter B → Candidate 02
```

Expected:

```text
2 ballots
2 voted eligibility records
```

---

# 17. Candidate Validation

### TEST-VOTE-004

Voter authenticated for:

```text
Election A
```

Attempts:

```text
Candidate from Election B
```

Expected:

```text
rejected
no ballot created
```

---

# 18. Election State Validation

### TEST-VOTE-005

Election:

```text
DRAFT
```

Attempt vote.

Expected:

```text
rejected
```

### TEST-VOTE-006

Election:

```text
CLOSED
```

Attempt vote.

Expected:

```text
rejected
```

---

# 19. Voting Session Tests

### TEST-SESSION-001

Valid credential:

```text
session created
```

### TEST-SESSION-002

Expired session:

```text
cast vote rejected
```

### TEST-SESSION-003

Invalid session:

```text
401/403 according to API policy
```

---

# 20. Idempotency Tests

### TEST-IDEMPOTENCY-001

Send:

```text
POST /voting/cast
Idempotency-Key: ABC
```

twice.

Expected:

```text
1 ballot
same logical result
```

---

### TEST-IDEMPOTENCY-002

Same key with different payload.

Expected:

```text
rejected
```

---

### TEST-IDEMPOTENCY-003

Retry after successful commit but lost response.

Expected:

```text
no duplicate ballot
```

---

# 21. Concurrency Tests

Concurrency is a mandatory security test.

Scenario:

```text
Request A ─┐
Request B ─┼→ same voter
Request C ─┘
```

Expected:

```text
exactly 1 success
remaining requests rejected
```

Database assertion:

```text
ballot count = 1
```

Eligibility assertion:

```text
voted = true
```

---

# 22. High Concurrency Voting

Test:

```text
1000 voters
```

simultaneously casting votes.

Expected:

```text
no duplicate votes
no orphan eligibility records
no orphan ballots
no negative participation
```

---

# 23. Transaction Rollback Test

Simulate failure after:

```text
ballot insert
```

but before:

```text
eligibility update
```

Expected:

```text
ballot rolled back
eligibility unchanged
```

---

# 24. Reverse Failure Test

Simulate failure after:

```text
eligibility update
```

but before:

```text
ballot commit
```

Expected:

```text
entire transaction rolled back
```

---

# 25. Anonymous Ballot Privacy Tests

This is a mandatory security test category.

Goal:

> Automated tests must verify that normal application data access cannot reconstruct voter → candidate mapping.

---

# 26. Privacy Test — API

### TEST-PRIVACY-001

Call voter endpoint.

Expected:

```text
No candidate_id
No ballot_id
No ballot_hash
```

---

# 27. Privacy Test — Admin Dashboard

Admin dashboard response must not contain:

```text
voter candidate relationship
```

Expected:

```text
aggregate participation only
```

---

# 28. Privacy Test — Monitoring

While election is OPEN:

Expected:

```text
voted count
remaining count
participation
```

Must not contain:

```text
candidate vote totals
voter → candidate
```

---

# 29. Privacy Test — Audit Log

Audit logs must not contain:

```text
student_id + candidate_id
```

in the same event for normal vote operations.

---

# 30. Privacy Test — Database Access Layer

Application repositories/services should not provide an ordinary method such as:

```text
getCandidateByVoter()
```

for normal administrative workflows.

Any privileged forensic capability, if ever required, must be separately controlled and outside normal election administration.

---

# 31. Result Visibility Tests

### TEST-RESULT-001

Election:

```text
OPEN
```

Request:

```text
GET /results
```

Expected:

```text
403/409 according to API contract
```

No candidate totals returned.

---

### TEST-RESULT-002

Election:

```text
CLOSED
```

Expected:

```text
results available
```

---

# 32. Result Integrity Tests

Given:

```text
Candidate A = 40
Candidate B = 35
Candidate C = 25
```

Expected:

```text
total = 100
```

Invariant:

```text
SUM(candidate votes) == total valid ballots
```

---

# 33. Participation Calculation

Given:

```text
eligible = 842
voted = 671
```

Expected:

```text
79.69%
```

Formula:

```text
participation =
voted / eligible * 100
```

Handle:

```text
eligible = 0
```

without division-by-zero.

---

# 34. Result Percentage Tests

Expected:

```text
candidate_votes / total_valid_ballots * 100
```

Rounding must be consistent.

Internal calculation should preserve sufficient precision; display rounding should occur at presentation level.

---

# 35. Tie Test

Input:

```text
Candidate A = 100
Candidate B = 100
```

Expected:

```text
tie
```

System must not invent a winner unless election rules explicitly define a tie-breaker.

---

# 36. Authorization Tests

Test each role:

```text
SUPER_ADMIN
ADMIN
OPERATOR
VOTER
```

against every protected endpoint.

Expected:

```text
allowed only when permission exists
```

---

# 37. IDOR Tests

Example:

```text
User has access to election 1
Request election 2
```

Expected:

```text
403/404
```

depending on API information-disclosure policy.

Test:

```text
candidate IDs
voter IDs
election IDs
export IDs
audit IDs
```

---

# 38. Role Boundary Tests

## OPERATOR

Must not:

```text
close election
change result
view audit logs if unauthorized
edit ballot
```

## VOTER

Must not:

```text
access admin
view other voters
view results
access audit logs
```

---

# 39. Authentication Tests

Test:

```text
valid login
invalid password
unknown account
locked/rate-limited account
expired session
logout
session regeneration
```

---

# 40. Rate Limit Tests

Test authentication and sensitive endpoints.

Example:

```text
100 failed login attempts
```

Expected:

```text
rate limiting triggered
```

Credential verification should also be protected against brute-force attempts.

---

# 41. CSRF Tests

For cookie/session authenticated web requests:

```text
missing CSRF token
invalid CSRF token
valid CSRF token
```

Expected:

```text
invalid requests rejected
valid request accepted
```

---

# 42. CORS Tests

Test:

```text
allowed origin
unknown origin
credentialed request
```

Expected according to configured policy.

Do not use:

```text
Access-Control-Allow-Origin: *
```

with credentialed requests.

---

# 43. Input Validation Tests

Test malicious/invalid input:

```text
empty
oversized
HTML
JavaScript
SQL-like strings
Unicode
unexpected types
negative numbers
invalid dates
```

Expected:

```text
validation error
safe rendering
```

---

# 44. XSS Tests

Test candidate fields:

```text
name
vision
mission
```

with payload-like strings.

Expected:

```text
escaped output
no script execution
```

Also test:

```text
audit fields
school settings
imported voter names
```

---

# 45. File Upload Security Tests

Test:

```text
valid image
invalid extension
oversized image
malicious filename
polyglot-like file
wrong MIME type
```

Expected:

```text
unsafe upload rejected
```

Store uploaded files outside executable application paths where appropriate.

---

# 46. Import Security Tests

Test CSV/XLSX:

```text
duplicate rows
huge row count
malformed file
formula-like cells
unexpected columns
empty required fields
invalid encoding
```

Expected:

```text
safe validation
controlled failure
```

Spreadsheet exports/imports must consider formula injection.

---

# 47. Export Security Tests

Verify:

```text
unauthorized user cannot export
OPEN election cannot export results
CLOSED election can export
export contains aggregate result only
```

---

# 48. Audit Log Tests

Every important operation should create expected audit event:

```text
LOGIN
ELECTION_CREATED
ELECTION_OPENED
ELECTION_CLOSED
CANDIDATE_CREATED
VOTER_IMPORTED
CREDENTIAL_ISSUED
EXPORT_CREATED
```

Audit record should contain:

```text
actor
action
resource
timestamp
request/correlation ID
```

without sensitive vote choice.

---

# 49. API Contract Tests

Verify:

```text
HTTP status
JSON schema
required fields
error format
pagination
authentication
authorization
```

For every endpoint in `05_API_SPEC.md`.

---

# 50. API Error Tests

Expected error categories:

```text
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
429 Too Many Requests
500 Internal Server Error
```

Do not expose stack traces.

---

# 51. API Pagination Tests

Test:

```text
page 1
middle page
last page
empty page
invalid page
large per_page
```

Expected:

```text
bounded page size
stable pagination
```

---

# 52. API Filtering Tests

For:

```text
voters
elections
audit logs
```

test:

```text
valid filter
invalid filter
unauthorized filter
combined filters
```

---

# 53. API Versioning Tests

Current:

```text
/api/v1
```

Verify:

```text
all endpoints consistently versioned
```

Breaking changes must not silently alter v1 behavior.

---

# 54. Database Integrity Tests

Verify:

```text
foreign keys
unique constraints
not null constraints
check constraints
indexes
```

Critical uniqueness:

```text
one eligibility per voter/election
one credential identity per expected scope
```

Exact constraint names/types must follow `02_ERD / Database Design`.

---

# 55. Ballot Integrity Tests

Verify:

```text
ballot belongs to election
candidate belongs to same election
ballot cannot be edited through normal workflow
ballot hash generated if specified
created_at immutable
```

---

# 56. Data Consistency Tests

After voting:

```text
eligibility = VOTED
ballot exists
```

After failed transaction:

```text
eligibility unchanged
ballot absent
```

After election close:

```text
new ballot impossible
```

---

# 57. Backup and Restore Test

Staging test:

```text
Create election
Cast test votes
Close election
Backup database
Restore backup
Verify:
- ballots
- voter eligibility
- results
- audit logs
```

Expected:

```text
data integrity preserved
```

---

# 58. Disaster Recovery Test

Simulate:

```text
application restart
database restart
queue restart
Redis restart
realtime disconnect
```

Expected:

```text
no duplicate vote
no corrupted election state
no lost committed ballot
```

---

# 59. Performance Test

Minimum scenarios:

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
database CPU
database connections
memory
queue latency
```

---

# 60. Voting Performance Target

Initial engineering target:

```text
p95 cast-vote response < 1 second
```

under the expected school election load.

Final target must be validated using actual infrastructure and concurrency assumptions.

---

# 61. Login Performance

Test simultaneous login:

```text
100
500
1000
```

Observe:

```text
authentication latency
rate limiter behavior
DB load
```

---

# 62. Database Load Test

Monitor:

```text
CPU
IOPS
connections
locks
deadlocks
query latency
slow queries
```

Special focus:

```text
vote transaction
eligibility locking
result aggregation
```

---

# 63. Concurrency / Deadlock Test

Run repeated concurrent voting tests.

Expected:

```text
no duplicate vote
acceptable lock wait
no persistent deadlock
```

If deadlocks occur:

```text
retry policy
transaction ordering
indexing
```

must be reviewed.

---

# 64. Queue Performance

Test:

```text
large voter import
large result export
```

Measure:

```text
queue wait
processing duration
failure/retry
memory
```

---

# 65. Realtime Test

Test:

```text
vote accepted
   ↓
aggregate event
   ↓
dashboard update
```

Expected:

```text
monitoring eventually consistent
no sensitive ballot information broadcast
```

---

# 66. Browser E2E Tests

Recommended:

```text
Playwright
```

Primary scenarios:

```text
Admin login
Create election
Add candidate
Import voter
Open election
Voter login
Vote
Close election
View result
Export result
```

---

# 67. E2E — Complete Election

Scenario:

```text
Create election
→ Add 3 candidates
→ Import 10 voters
→ Generate credentials
→ Open
→ 10 voters vote
→ Close
→ Results
```

Expected:

```text
10 valid ballots
10 voted voters
100% participation
result totals = 10
```

---

# 68. E2E — Partial Participation

```text
10 voters
6 vote
```

Expected:

```text
voted = 6
remaining = 4
participation = 60%
```

---

# 69. E2E — Double Vote

```text
Voter logs in
→ Vote
→ Attempt second vote
```

Expected:

```text
second vote impossible
```

---

# 70. E2E — Result Lock

```text
Open election
→ Admin tries results
```

Expected:

```text
no candidate totals
```

After close:

```text
results visible
```

---

# 71. Mobile E2E

Test at minimum:

```text
Android Chrome
iOS Safari
```

Flow:

```text
login
candidate selection
confirmation
submit
success
```

Verify:

```text
touch target
layout
scrolling
keyboard
network retry
```

---

# 72. Accessibility Tests

Automated:

```text
axe
Lighthouse accessibility
```

Manual:

```text
keyboard-only navigation
screen reader smoke test
focus order
focus trap
```

Target:

```text
WCAG 2.1 AA
```

---

# 73. Usability Tests

Recruit representative school users.

Test:

```text
student
operator
admin
```

Measure:

```text
task completion
time on task
errors
confusion points
```

Important voter task:

```text
Login → identify candidate → select → confirm → finish
```

---

# 74. Security Testing

Minimum:

```text
SAST
Dependency scanning
DAST
Authorization testing
IDOR testing
XSS testing
CSRF testing
Rate-limit testing
File-upload testing
Session testing
```

For serious production deployment, consider independent penetration testing.

---

# 75. SAST

Scan:

```text
PHP
Blade
JavaScript/TypeScript
configuration
```

Detect:

```text
insecure SQL
unsafe output
hardcoded secrets
dangerous functions
```

---

# 76. Dependency Security

Scan dependencies:

```text
Composer
NPM
```

Check:

```text
known CVE
outdated security-critical dependency
license policy
```

Build should fail for configured critical vulnerabilities where appropriate.

---

# 77. DAST

Run against staging.

Test:

```text
authentication
authorization
API
admin
voting endpoints
file upload
export
```

Do not run destructive automated scans against production without explicit operational approval.

---

# 78. Secret Scanning

Repository must not contain:

```text
database password
API key
JWT secret
app secret
cloud credential
```

Use:

```text
environment variables
secret manager
```

Test CI pipeline for accidental secrets.

---

# 79. Logging Security Tests

Verify logs do not contain:

```text
password
PIN
plaintext credential
session token
Authorization header
candidate choice linked to voter
```

---

# 80. Session Security Tests

Verify:

```text
session ID regeneration
logout invalidation
timeout
secure cookie
HttpOnly
SameSite
HTTPS-only
```

Exact cookie flags depend on deployment architecture.

---

# 81. Backup Security Tests

Backup files must:

```text
not be public
be access-controlled
be encrypted where required
have retention policy
```

Test restore without exposing sensitive data.

---

# 82. UAT Acceptance Criteria

Admin can:

```text
create election
manage candidate
import voter
generate credentials
open election
monitor participation
close election
view results
export
```

Voter can:

```text
authenticate
view candidates
select one
confirm
submit once
receive success
```

---

# 83. UAT Privacy Acceptance

UAT must verify:

```text
Admin cannot determine who voted for which candidate
through normal application UI/API.
```

This is a mandatory acceptance criterion.

---

# 84. UAT Security Acceptance

Verify:

```text
double voting prevented
closed election rejects vote
unauthorized role rejected
candidate cross-election rejected
result hidden while open
credential cannot be reused
```

---

# 85. Election-Day Smoke Test

Before opening:

```text
[ ] Health endpoint OK
[ ] Database OK
[ ] Queue OK
[ ] Realtime OK
[ ] Admin login OK
[ ] Voter authentication OK
[ ] Test credential verified
[ ] Candidate list verified
[ ] Monitoring verified
[ ] Backup verified
```

---

# 86. Production Smoke Test

Use designated test account/election only.

Never create test ballots in a real election unless the operational procedure explicitly supports and isolates them.

Check:

```text
login
health
monitoring
non-destructive API
```

---

# 87. Test Automation in CI

Pipeline:

```text
Push
 ↓
Lint
 ↓
Unit Tests
 ↓
Feature Tests
 ↓
Security Static Scan
 ↓
Build
 ↓
Integration Tests
 ↓
E2E
 ↓
Deploy Staging
 ↓
DAST
```

Production deployment requires configured approval gates.

---

# 88. Quality Gates

Suggested minimum:

```text
Unit tests: pass
Feature tests: pass
Security tests: pass
E2E critical flows: pass
No critical vulnerability
No high-severity unresolved voting integrity issue
```

---

# 89. Critical Test Matrix

| Area | Priority |
|---|---|
| Single vote | P0 |
| Double vote | P0 |
| Concurrent vote | P0 |
| Anonymous ballot privacy | P0 |
| Election state | P0 |
| Authorization | P0 |
| Result integrity | P0 |
| Credential security | P0 |
| Transaction rollback | P0 |
| Result visibility | P0 |
| API contract | P1 |
| Import | P1 |
| Export | P1 |
| Realtime monitoring | P1 |
| Accessibility | P1 |
| Performance | P1 |
| Usability | P1 |

---

# 90. P0 Release Blockers

Release must be blocked if any of these occur:

```text
Duplicate vote possible
Voter → candidate relationship exposed
Unauthorized user can vote
Closed election accepts vote
Cross-election candidate accepted
Ballot transaction can partially commit
Result totals incorrect
Credential plaintext persisted
Critical authentication bypass
Critical IDOR
```

---

# 91. P1 Release Blockers

Depending on operational impact:

```text
Import corruption
Export corruption
Monitoring incorrect
Severe mobile usability failure
Critical accessibility failure
Unacceptable performance
```

---

# 92. Test Reporting

Each test run should record:

```text
Build/version
Environment
Database version
Browser/version
Test timestamp
Pass
Fail
Skipped
Known issues
```

---

# 93. Defect Severity

## Critical

Affects:

```text
vote integrity
privacy
authentication
authorization
```

Example:

```text
duplicate vote possible
```

---

## High

Major function unavailable or data incorrect.

Example:

```text
result calculation incorrect
```

---

## Medium

Function degraded but workaround exists.

---

## Low

Minor UI/copy issue.

---

# 94. Test Evidence

For critical security tests, retain evidence:

```text
test result
request ID
database assertion
application log
screenshots where useful
```

Do not retain sensitive credentials in test evidence.

---

# 95. Regression Suite

Every release must rerun:

```text
P0 tests
critical API tests
authorization tests
voting E2E
result integrity
privacy tests
```

---

# 96. Recommended Test Stack

Untuk Laravel:

```text
Pest / PHPUnit
Laravel HTTP tests
Laravel database testing
Playwright
OWASP ZAP
PHPStan / Larastan
ESLint
Composer Audit
npm audit
```

Tool final dapat disesuaikan dengan stack implementasi.

---

# 97. Test Folder Structure

Rekomendasi:

```text
tests/
├── Unit/
│   ├── Election/
│   ├── Voting/
│   ├── Credential/
│   └── Result/
│
├── Feature/
│   ├── Auth/
│   ├── Elections/
│   ├── Candidates/
│   ├── Voters/
│   ├── Voting/
│   ├── Results/
│   └── Audit/
│
├── Security/
│   ├── Authorization/
│   ├── Privacy/
│   ├── IDOR/
│   ├── CSRF/
│   └── RateLimit/
│
└── E2E/
    ├── admin/
    ├── voter/
    └── election/
```

---

# 98. Test Fixture Strategy

Gunakan factory:

```text
ElectionFactory
CandidateFactory
VoterFactory
EligibilityFactory
CredentialFactory
BallotFactory
```

Fixture harus memungkinkan pembuatan:

```text
OPEN election
CLOSED election
eligible voter
voted voter
unused credential
used credential
```

---

# 99. Testing the Anonymous Ballot Model

Test harus membuktikan dua hal sekaligus:

```text
A. Sistem tahu apakah voter sudah memilih.
B. Sistem tidak menyediakan mapping normal voter → candidate.
```

Jangan hanya menguji:

```text
ballot exists
```

Uji juga:

```text
normal admin queries/API cannot identify voter's candidate.
```

---

# 100. Final Acceptance Test

Simulasi penuh:

```text
1 election
3 candidates
842 voters
671 voters vote
171 do not vote
```

Expected:

```text
Eligible = 842
Valid ballots = 671
Not voted = 171
Participation = 79.69%
```

Candidate totals harus berjumlah:

```text
671
```

dan:

```text
no voter → candidate mapping exposed
```

---

# 101. Final Security Acceptance

Sistem tidak boleh production-ready sebelum semua berikut lulus:

```text
[ ] Double vote test
[ ] Concurrent vote test
[ ] Idempotency test
[ ] Transaction rollback test
[ ] Closed election test
[ ] Cross-election candidate test
[ ] Authorization/IDOR test
[ ] Credential security test
[ ] Anonymous ballot privacy test
[ ] Result visibility test
[ ] Audit privacy test
[ ] Rate-limit test
[ ] Session security test
[ ] Input/XSS test
[ ] File upload test
[ ] Dependency/security scan
```

---

# 102. Definition of Done

`08_TEST_PLAN.md` dianggap terpenuhi jika:

- [ ] Test strategy disepakati.
- [ ] P0 test cases didefinisikan.
- [ ] Voting concurrency test didefinisikan.
- [ ] Anonymous ballot privacy test didefinisikan.
- [ ] Authorization matrix didefinisikan.
- [ ] Result integrity test didefinisikan.
- [ ] Failure/rollback test didefinisikan.
- [ ] E2E flow didefinisikan.
- [ ] Performance test didefinisikan.
- [ ] Security test didefinisikan.
- [ ] UAT criteria didefinisikan.
- [ ] Election-day checklist didefinisikan.

---

# 103. Next Document

Dokumen berikutnya yang direkomendasikan:

```text
09_DEPLOYMENT.md
```

Fokus:

```text
Production architecture
Server requirements
Docker
Nginx
PHP-FPM
PostgreSQL
Redis
Queue worker
Object storage
HTTPS
Environment variables
Backup
Monitoring
Logging
CI/CD
Deployment strategy
Rollback
Disaster recovery
```
