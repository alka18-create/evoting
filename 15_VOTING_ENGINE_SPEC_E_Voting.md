# Voting Engine Specification — Sistem E-Voting Sekolah

**Dokumen:** 15 — Voting Engine Specification  
**Versi:** 1.0  
**Status:** Draft / Critical Design  
**Mengacu pada:** `02_ERD_DATABASE_DESIGN.md`, `03_SECURITY_THREAT_MODEL.md`, `04_ARCHITECTURE.md`, `08_TEST_PLAN.md`, `13_DATABASE_MIGRATIONS.md`, `14_AUTH_RBAC_SPEC.md`

---

# 1. Tujuan

Dokumen ini mendefinisikan engine inti yang bertanggung jawab menerima dan mencatat suara secara:

```text
correct
atomic
consistent
private
auditable
race-condition resistant
```

Voting engine adalah komponen paling kritis dalam sistem.

Kesalahan pada bagian ini dapat menyebabkan:

```text
double voting
lost vote
wrong candidate count
vote duplication
privacy breach
inconsistent election state
```

---

# 2. Core Principle

Voting harus diproses sebagai **atomic domain transaction**.

Secara konseptual:

```text
Authenticate voter
      ↓
Resolve eligibility
      ↓
Verify election
      ↓
Verify candidate
      ↓
BEGIN TRANSACTION
      ↓
LOCK eligibility
      ↓
RE-CHECK eligibility
      ↓
CREATE BALLOT
      ↓
MARK ELIGIBILITY = VOTED
      ↓
COMMIT
      ↓
Consume voting credential/session
      ↓
Return confirmation
```

---

# 3. Source of Truth

Authoritative source:

```text
PostgreSQL
```

Bukan:

```text
browser
Redis
frontend state
session
cache
```

---

# 4. Voting Invariants

Invariant utama:

```text
I1. One eligible voter can successfully cast at most one ballot per election.
I2. Every successful ballot belongs to exactly one election.
I3. Every successful ballot points to exactly one candidate.
I4. Candidate must belong to the same election as the ballot.
I5. A successful vote changes eligibility from eligible → voted.
I6. Ballot and eligibility state change commit atomically.
I7. Failed vote must not leave a partial ballot.
I8. Failed vote must not consume eligibility.
I9. Ballot must not directly identify voter.
I10. Ballot must be immutable.
```

---

# 5. Privacy Invariant

Ballot storage must not contain:

```text
voter_id
student_id
credential_id
user_id
session_id
IP address
user agent
```

if these fields can create a direct relationship between voter identity and candidate choice.

---

# 6. Identity Boundary

Before vote:

```text
Authentication / Eligibility Domain
```

knows:

```text
who is voting
```

During ballot creation:

```text
Voting Domain
```

knows:

```text
what candidate was selected
```

Persistent ballot storage must not join the two identities directly.

---

# 7. Voting Use Case

Primary use case:

```text
CastVote
```

Input:

```text
election_id
candidate_id
voter eligibility context
authenticated voter session
```

Output:

```text
success
or
domain error
```

---

# 8. CastVote Does Not Accept Trust

Frontend data is untrusted.

The browser may send:

```json
{
  "candidate_id": 2
}
```

The server must independently verify:

```text
current voter
current election
eligibility
election status
candidate membership
voting state
```

---

# 9. Pre-Transaction Validation

Some validation may happen before transaction:

```text
request shape
integer/type validation
authentication
basic resource existence
```

But correctness-critical checks must be repeated inside the transaction where concurrent requests can affect the result.

---

# 10. Election State

Vote is allowed only when:

```text
election.status = open
```

and the current time is within the configured voting window if time boundaries are enforced.

---

# 11. Election Timing

If:

```text
starts_at
ends_at
```

are authoritative:

```text
now >= starts_at
AND
now < ends_at
```

or another explicitly documented boundary.

Use one consistent rule throughout the application.

---

# 12. Candidate Validation

Candidate must satisfy:

```text
candidate.id = requested candidate
candidate.election_id = current election
candidate is active/available
```

A candidate from another election must always be rejected.

---

# 13. Eligibility Resolution

Voter eligibility is identified by:

```text
election_id
+
voter_id
```

or an equivalent opaque eligibility reference.

The lookup must use the unique constraint:

```text
UNIQUE(election_id, voter_id)
```

---

# 14. Eligibility State

Expected states:

```text
eligible
voted
revoked
```

Only:

```text
eligible
```

can transition to:

```text
voted
```

---

# 15. Eligibility State Machine

```text
             ┌──────────┐
             │ ELIGIBLE │
             └────┬─────┘
                  │
              CastVote
                  │
                  ▼
             ┌──────────┐
             │  VOTED   │
             └──────────┘

ELIGIBLE ───────► REVOKED
```

Normal voting must never perform:

```text
voted → eligible
voted → revoked
```

---

# 16. Critical Transaction Boundary

The following operations must be in the same database transaction:

```text
lock eligibility
re-check eligibility
validate candidate relationship if needed
insert ballot
update eligibility to voted
```

If any operation fails:

```text
ROLLBACK
```

---

# 17. Transaction Pseudocode

```php
DB::transaction(function () use ($command) {

    $eligibility = VoterEligibility::query()
        ->where('election_id', $command->electionId)
        ->where('voter_id', $command->voterId)
        ->lockForUpdate()
        ->firstOrFail();

    if ($eligibility->status !== 'eligible') {
        throw new AlreadyVotedException();
    }

    $candidate = Candidate::query()
        ->whereKey($command->candidateId)
        ->where('election_id', $command->electionId)
        ->firstOrFail();

    Ballot::create([
        'election_id' => $command->electionId,
        'candidate_id' => $candidate->id,
        'ballot_hash' => $this->generateBallotHash(...),
        'created_at' => now(),
    ]);

    $eligibility->update([
        'status' => 'voted',
        'voted_at' => now(),
    ]);
});
```

Actual implementation must follow the project's final domain classes and privacy model.

---

# 18. Why Lock Eligibility?

Without row locking:

```text
Request A reads eligible
Request B reads eligible
Request A creates ballot
Request B creates ballot
Request A marks voted
Request B marks voted
```

Result:

```text
2 ballots
1 voter
```

This is unacceptable.

---

# 19. Row Lock

Use:

```sql
SELECT ...
FOR UPDATE
```

through Laravel:

```php
->lockForUpdate()
```

The lock should target the single eligibility row.

---

# 20. Lock Ordering

To reduce deadlock risk, define a consistent order.

Recommended:

```text
1. election state
2. eligibility
3. candidate validation
4. ballot insert
5. eligibility update
```

If election does not need locking, do not lock it unnecessarily.

---

# 21. Prefer Minimal Locking

Do not lock:

```text
all voters
all candidates
whole election
```

for every vote.

Lock only the row needed to guarantee the invariant:

```text
one voter → one successful vote
```

---

# 22. Candidate Locking

Candidate normally does not need a row lock for voting.

Use:

```text
read + FK/integrity validation
```

unless candidate state can change concurrently in a way that affects voting correctness.

---

# 23. Election Locking

Election row locking is optional.

If election status can change concurrently with voting, the implementation must define a precise consistency rule.

Possible approaches:

```text
application-level state transition
+
transaction re-check
```

or:

```text
lock election row
```

Use the simplest design that guarantees correctness.

---

# 24. Race Condition Scenario

Two requests:

```text
A = voter session 1
B = voter session 2
```

Both use the same eligibility.

Timeline:

```text
A → lock eligibility
B → waits

A → sees eligible
A → insert ballot
A → update voted
A → commit

B → acquires lock
B → sees voted
B → reject
```

Expected:

```text
1 successful vote
1 ballot
1 rejected request
```

---

# 25. Database Constraint Defense

Application locking should be combined with database constraints where possible.

At minimum:

```text
UNIQUE(election_id, voter_id)
```

on eligibility.

However:

```text
unique eligibility
```

alone does not prevent multiple ballots.

The application transaction is still required.

---

# 26. Stronger Ballot Uniqueness

A ballot table should not expose voter identity merely to create a unique constraint.

Do not solve double voting by adding:

```text
UNIQUE(election_id, voter_id)
```

to ballots.

That would violate the privacy boundary.

---

# 27. Idempotency

Voting requests can be retried because of:

```text
network timeout
browser retry
proxy retry
mobile connection
```

The system must distinguish:

```text
retry of same operation
```

from:

```text
second independent vote attempt
```

---

# 28. Idempotency Strategy

Recommended approach:

```text
idempotency key
```

or an equivalent server-generated operation reference.

However, the idempotency record must not create a persistent:

```text
voter → candidate
```

relationship if anonymity is required.

---

# 29. Idempotency Key Boundary

The idempotency mechanism should store only what is necessary to determine:

```text
request already processed
```

It should not become a hidden voter-choice table.

---

# 30. Important Retry Scenario

Example:

```text
Client submits vote
Server commits
Network fails before response
Client retries
```

The second request must return:

```text
already processed / previous success
```

or an equivalent safe response.

It must not create another ballot.

---

# 31. Idempotency vs Double Voting

These are different:

```text
Idempotency
= same operation retry

Double voting
= separate vote attempt
```

Eligibility state prevents the latter.

Idempotency handles the former.

---

# 32. Credential Consumption

After successful vote:

```text
credential becomes unusable
```

This can be represented by:

```text
used_at
revoked_at
status
```

depending on final credential schema.

---

# 33. Credential Transaction

Credential consumption must not create a situation where:

```text
credential consumed
but ballot not created
```

or:

```text
ballot created
but credential remains reusable
```

Define the exact atomic boundary before implementation.

---

# 34. Recommended Credential Boundary

If credential is directly tied to eligibility:

```text
lock eligibility
+
validate credential
+
create ballot
+
mark eligibility voted
+
consume credential
```

in one transaction where practical.

---

# 35. Session Invalidation

After successful voting:

```text
invalidate voter session
```

This is a security action after the authoritative database transaction.

If session invalidation fails:

```text
database vote remains valid
```

and future vote attempts must still be rejected by eligibility state.

---

# 36. Vote Success Response

Response should not reveal unnecessary sensitive information.

Example:

```json
{
  "success": true,
  "message": "Your vote has been recorded."
}
```

Optional:

```text
receipt/reference
```

must be carefully designed so it does not reveal candidate choice.

---

# 37. Do Not Return Candidate Choice in Receipt

Avoid:

```json
{
  "candidate_id": 2
}
```

after voting.

This can create privacy risks on:

```text
screenshots
browser history
logs
support tickets
```

---

# 38. Vote Receipt

If a receipt is required, it should contain only:

```text
opaque receipt/reference
timestamp
election reference
```

and no candidate choice.

Receipt design requires additional privacy analysis.

---

# 39. Ballot Hash

Ballot hash may provide an integrity reference.

Conceptual input:

```text
election_id
candidate_id
random nonce
creation timestamp
```

The nonce should be cryptographically random.

---

# 40. Ballot Hash Does Not Guarantee Anonymity

Important:

```text
hash ≠ anonymity
```

If the hash input or surrounding records reveal voter identity, the ballot can still be linked.

Privacy must come from:

```text
data model
transaction boundary
access control
logs
```

not hashing alone.

---

# 41. Randomness

Use cryptographically secure randomness.

Do not use:

```text
rand()
mt_rand()
timestamp only
student ID
```

for security-sensitive ballot identifiers/nonces.

---

# 42. Ballot Creation

Ballot insert must be:

```text
single immutable insert
```

After commit:

```text
UPDATE ballot
```

should not be part of normal application behavior.

---

# 43. Ballot Update Policy

Application must reject attempts to modify:

```text
candidate_id
election_id
created_at
ballot_hash
```

after creation.

---

# 44. Ballot Delete Policy

Application must not provide:

```text
DELETE /ballots/{id}
```

to normal admin/operator users.

Historical ballot deletion is prohibited through normal application flows.

---

# 45. Election Closure

Closing an election must ensure:

```text
no new successful vote after closure
```

The vote transaction must validate election state according to the defined consistency model.

---

# 46. Close-vs-Vote Race

Important race:

```text
Request A = CastVote
Request B = CloseElection
```

The system must define which operation wins based on transaction ordering.

Recommended semantic:

```text
A vote that commits while election is legitimately open is counted.
After the close transition is committed, new votes are rejected.
```

The exact implementation must prevent ambiguous state.

---

# 47. Recommended Close Strategy

Election closure should be a domain transaction:

```text
BEGIN
lock election state if required
verify close transition
set status = closed
COMMIT
```

Voting and closure must use consistent locking/state rules.

---

# 48. Time Boundary

Do not rely only on frontend countdown.

Frontend:

```text
timer = UX
```

Backend:

```text
authoritative clock
```

---

# 49. Server Clock

Use:

```text
database/server UTC time
```

consistently.

Do not trust:

```text
browser clock
```

---

# 50. Candidate Availability

If candidates can be edited during an open election, define policy.

Recommended:

```text
candidate configuration frozen before opening
```

Once election is:

```text
open
```

candidate create/update/delete should be restricted.

---

# 51. Election Freeze

Recommended:

```text
DRAFT
→ candidate/voter configuration allowed

OPEN
→ configuration frozen

CLOSED
→ results/reporting
```

---

# 52. Voter Eligibility Freeze

Recommended:

```text
before OPEN
→ import/update eligibility

OPEN
→ eligibility changes restricted
```

Emergency changes must be audited and controlled.

---

# 53. Result Calculation

Do not calculate result from:

```text
voter status
```

Result must be calculated from:

```text
ballots
```

Example:

```sql
SELECT candidate_id, COUNT(*)
FROM ballots
WHERE election_id = ?
GROUP BY candidate_id;
```

---

# 54. Result Invariant

For a closed election:

```text
SUM(all candidate ballot counts)
=
TOTAL BALLOTS
```

---

# 55. Participation Invariant

Expected:

```text
voted eligibility count
=
successful ballots
```

provided every successful vote changes eligibility atomically.

If mismatch occurs:

```text
critical integrity alert
```

---

# 56. Reconciliation Job

After election close, run:

```text
VotingIntegrityCheck
```

Checks:

```text
ballot count
voted eligibility count
candidate aggregate
election aggregate
```

---

# 57. Integrity Failure

If:

```text
ballots != voted eligibilities
```

do not silently "fix" production data.

Process:

```text
alert
freeze result publication
investigate
preserve evidence
```

---

# 58. Result Publication

Result publication should occur only when:

```text
election closed
+
integrity check passed
```

Optional status:

```text
results_pending_verification
results_published
```

---

# 59. Live Monitoring

During voting, dashboard may show:

```text
eligible
voted
remaining
participation
```

It should not show candidate vote counts if policy requires results hidden.

---

# 60. Candidate Vote Count During Voting

Default:

```text
NOT EXPOSED
```

This prevents:

```text
bandwagon effect
strategic voting
pressure
```

---

# 61. Error Classification

Voting errors should be domain-specific.

Examples:

```text
ElectionNotOpen
VoterNotEligible
AlreadyVoted
CandidateNotInElection
CredentialInvalid
CredentialExpired
CredentialRevoked
VotingTemporarilyUnavailable
```

---

# 62. Public Error Messages

Do not expose internal state unnecessarily.

Example:

```text
Your voting session is no longer valid.
```

instead of:

```text
Eligibility row 123 is status=revoked.
```

---

# 63. HTTP Mapping

Recommended:

```text
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
429 Too Many Requests
503 Service Unavailable
```

Exact mapping depends on API conventions.

---

# 64. Already Voted

If a voter already voted:

```text
409 Conflict
```

or a privacy-safe equivalent.

Do not reveal candidate choice.

---

# 65. Invalid Candidate

If candidate does not belong to election:

```text
422
```

or:

```text
404
```

depending on information disclosure policy.

---

# 66. Closed Election

If election is closed:

```text
409
```

or:

```text
403
```

depending on API semantics.

---

# 67. Transaction Failure

Unexpected database failure:

```text
rollback
log internal error
return generic 5xx
```

Never return stack trace to voter.

---

# 68. Retry Policy

Safe retry:

```text
deadlock
transient DB failure
connection failure
```

only when operation can be safely retried.

Do not blindly retry every exception.

---

# 69. Transaction Retry

If framework/database supports transaction retry:

```text
limited attempts
backoff
```

must be used.

After each retry:

```text
re-read authoritative state
```

---

# 70. Duplicate Ballot Defense

Defense layers:

```text
1. voter authentication
2. election eligibility
3. row lock
4. eligibility state check
5. database constraints
6. atomic transaction
7. credential consumption
8. idempotency
```

No single layer should be treated as sufficient by itself.

---

# 71. Atomicity Failure Example

Bad:

```text
insert ballot
COMMIT

update voter status
COMMIT
```

If second operation fails:

```text
ballot exists
voter still eligible
```

This must never be the normal implementation.

---

# 72. Correct Pattern

```text
BEGIN

lock eligibility
verify eligibility
verify election
verify candidate

insert ballot
mark eligibility voted
consume credential

COMMIT
```

---

# 73. Logging Boundary

Application may log:

```text
vote attempt
vote success/failure
request ID
election ID
technical error
```

But must not log:

```text
voter ID + candidate ID
```

as a combined record.

---

# 74. Request ID

Every vote request should have:

```text
request_id
```

for operational tracing.

Request ID is not a vote receipt and should not expose candidate choice.

---

# 75. Observability

Metrics:

```text
votes_attempted
votes_succeeded
votes_rejected
already_voted
invalid_candidate
credential_failures
transaction_failures
deadlocks
latency
```

Do not use metrics labels that combine:

```text
voter identity
candidate
```

---

# 76. Privacy-Safe Metrics

Good:

```text
election_id
result = success
```

Potentially unsafe:

```text
student_id
candidate_id
```

in high-cardinality logs/metrics.

---

# 77. Alerting

Alert on:

```text
sudden vote failure spike
deadlock spike
database unavailable
integrity mismatch
credential attack
unusual request rate
```

---

# 78. Voting Service Boundary

Recommended service:

```text
App\Domain\Voting\Actions\CastVote
```

or:

```text
App\Application\Voting\CastVote
```

depending on architecture.

It should orchestrate the domain transaction.

---

# 79. Controller Responsibility

Controller should:

```text
authenticate
validate request
resolve context
call CastVote
map response
```

Controller should NOT implement:

```text
transaction logic
locking
ballot creation
eligibility transition
```

---

# 80. Action Responsibility

`CastVote` should own:

```text
vote business flow
transaction
critical validation
locking
ballot creation
eligibility transition
```

---

# 81. Repository Responsibility

If repository abstraction is used:

```text
find eligibility
lock eligibility
insert ballot
update eligibility
```

Repository should not silently commit transactions.

Transaction ownership remains explicit.

---

# 82. Domain Events

After successful commit:

```text
VoteCast
```

may be dispatched.

Do not dispatch external side effects before commit.

---

# 83. After-Commit Events

Recommended:

```text
DB commit
 ↓
VoteCast event
 ↓
metrics / notification / audit-safe processing
```

If framework supports after-commit events, use them where appropriate.

---

# 84. Vote Notification

Do not send:

```text
"You voted for Candidate X"
```

to external systems.

If notification exists:

```text
"Your vote was recorded."
```

---

# 85. Audit Event

A privacy-safe audit event may be:

```text
VOTE_CAST
```

with:

```text
election reference
timestamp
request ID
technical metadata
```

but no voter-choice mapping.

---

# 86. Audit Actor

Avoid:

```text
actor_user_id = voter
```

if that would create an identity → choice relationship.

The audit design must preserve the anonymity boundary.

---

# 87. Admin Override

There must be no normal feature:

```text
Admin → change candidate of existing ballot
```

Admin can:

```text
close election
revoke credential
manage configuration
```

but not rewrite votes.

---

# 88. Emergency Correction

If a serious integrity issue occurs:

```text
do not edit ballot manually
```

Use:

```text
incident procedure
forensic evidence
database backup
documented recovery
```

Any exceptional data repair must be separately governed and audited.

---

# 89. Ballot Immutability Test

Test:

```text
admin attempts ballot update
→ denied
```

and:

```text
application service attempts ballot mutation
→ unavailable/forbidden
```

---

# 90. Anonymous Ballot Test

Test that after successful vote:

```text
query ballot
```

cannot directly identify:

```text
voter
```

---

# 91. Double Vote Test

Scenario:

```text
same voter
same election
two concurrent requests
```

Expected:

```text
success = 1
failure = 1
ballots = 1
eligibility status = voted
```

---

# 92. Different Candidate Race

Scenario:

```text
Request A → Candidate 1
Request B → Candidate 2
same voter
same election
```

Expected:

```text
exactly one succeeds
```

The losing request must not create any ballot.

---

# 93. Network Retry Test

Scenario:

```text
server commits
response lost
client retries
```

Expected:

```text
no second ballot
```

---

# 94. Candidate Tampering Test

Scenario:

```text
voter authenticated for Election A
POST candidate_id belonging to Election B
```

Expected:

```text
rejected
no ballot
eligibility remains eligible
```

---

# 95. Election Closure Test

Scenario:

```text
election closed
voter attempts vote
```

Expected:

```text
rejected
no ballot
```

---

# 96. Expired Credential Test

Scenario:

```text
expired credential
```

Expected:

```text
rejected
no ballot
eligibility unchanged
```

---

# 97. Revoked Credential Test

Scenario:

```text
revoked credential
```

Expected:

```text
rejected
no ballot
```

---

# 98. Transaction Rollback Test

Force:

```text
ballot insert failure
```

Expected:

```text
no ballot
eligibility remains eligible
credential remains valid according to policy
```

---

# 99. Reverse Failure Test

Force:

```text
eligibility update failure
```

Expected:

```text
ballot insert rolled back
eligibility unchanged
```

---

# 100. Database Failure Test

Simulate:

```text
database connection loss
```

Expected:

```text
no partial successful vote
```

where transaction semantics permit.

---

# 101. Deadlock Test

Simulate concurrent transactions.

Expected:

```text
at most one successful vote
```

and retry behavior must not create duplicates.

---

# 102. Integrity Test

After N successful votes:

```text
ballot_count = N
voted_eligibility_count = N
```

---

# 103. Result Aggregation Test

For ballots:

```text
Candidate A = 40
Candidate B = 35
Candidate C = 25
```

total:

```text
100
```

Result engine must produce:

```text
40
35
25
```

and:

```text
sum = 100
```

---

# 104. Vote Count Source

Never calculate:

```text
candidate result
```

from:

```text
voter status
```

Use:

```text
ballots
```

as the source.

---

# 105. Result Cache

Result may be cached after election close.

But:

```text
cache ≠ source of truth
```

If cache is deleted:

```text
recalculate from ballots
```

---

# 106. Reconciliation

After close:

```text
IntegrityCheck
```

should compare:

```text
COUNT(ballots)
COUNT(voted eligibilities)
SUM(candidate results)
```

---

# 107. Election Close Workflow

Recommended:

```text
Admin requests close
       ↓
authorize
       ↓
close election
       ↓
prevent new votes
       ↓
run integrity check
       ↓
publish results
```

If integrity check fails:

```text
results remain unpublished
```

---

# 108. Result Publication Safety

Do not publish result merely because:

```text
status = closed
```

if the system requires integrity verification.

Recommended state:

```text
closed
 ↓
integrity_verified
 ↓
results_published
```

---

# 109. Data Retention

Ballots are historical election records.

Retention policy must be explicitly defined.

Do not delete ballots simply because:

```text
election is old
```

without approved retention policy.

---

# 110. Backup During Election

At minimum:

```text
pre-election backup
scheduled backups
post-election backup
```

Exact RPO/RTO defined in deployment documentation.

---

# 111. Disaster Recovery

If database fails during election:

```text
stop voting
preserve system state
restore/recover database
verify integrity
resume only after verification
```

Do not resume based only on:

```text
application health = green
```

---

# 112. Fail Closed

If authoritative database state cannot be verified:

```text
do not accept vote
```

Example:

```text
DB unavailable
→ voting unavailable
```

rather than:

```text
accept vote into temporary cache
```

---

# 113. Redis Rule

Redis may be used for:

```text
rate limiting
session/cache
temporary coordination
```

but not as the authoritative vote store.

Never:

```text
Redis vote first
PostgreSQL later
```

for normal voting.

---

# 114. Queue Rule

Do not queue the actual authoritative ballot write as an asynchronous job after returning success.

Bad:

```text
HTTP request
→ queue vote
→ return success
```

because queue failure could lose the vote.

Correct:

```text
HTTP request
→ DB transaction
→ commit
→ return success
```

Optional post-commit jobs may handle:

```text
metrics
notifications
non-critical reporting
```

---

# 115. Performance Target

Voting transaction should be short.

Avoid inside transaction:

```text
HTTP calls
email
file generation
image processing
large queries
external APIs
```

---

# 116. External Dependency Rule

The critical voting transaction must not depend on:

```text
external API
SMTP
object storage
analytics
third-party service
```

---

# 117. Transaction Timing

Ideal:

```text
milliseconds to low hundreds of milliseconds
```

depending on infrastructure.

Monitor:

```text
p50
p95
p99
```

---

# 118. Load Testing

Simulate:

```text
hundreds of concurrent voters
```

at least.

For larger deployments, test expected peak concurrency plus safety margin.

Metrics:

```text
success rate
latency
deadlocks
DB connections
lock waits
CPU
memory
```

---

# 119. Stress Scenario

Test:

```text
many voters
same election
different eligibility
```

and:

```text
many concurrent requests
same eligibility
```

The second is more important for correctness.

---

# 120. Security Boundary Review

Before production:

```text
[ ] no voter_id in ballots
[ ] no student_id in ballots
[ ] no credential_id in ballots
[ ] no candidate choice in logs
[ ] no candidate choice in session
[ ] no candidate choice in receipt
[ ] no candidate choice in analytics
```

---

# 121. Implementation Checklist

Before implementing `CastVote`:

```text
[ ] election state rule finalized
[ ] eligibility schema finalized
[ ] credential schema finalized
[ ] ballot schema finalized
[ ] anonymity model approved
[ ] transaction boundary approved
[ ] lock strategy approved
[ ] idempotency strategy approved
[ ] error mapping approved
[ ] audit model approved
[ ] concurrency tests designed
```

---

# 122. Recommended Class Structure

Conceptual:

```text
App/
└── Domain/
    └── Voting/
        ├── Actions/
        │   └── CastVote.php
        ├── DTOs/
        │   └── CastVoteData.php
        ├── Exceptions/
        │   ├── AlreadyVotedException.php
        │   ├── ElectionNotOpenException.php
        │   ├── CandidateNotInElectionException.php
        │   └── InvalidVotingCredentialException.php
        ├── Services/
        │   └── BallotHashGenerator.php
        └── Events/
            └── VoteCast.php
```

Actual path follows `12_PROJECT_STRUCTURE.md`.

---

# 123. CastVote Input

Recommended DTO:

```php
final readonly class CastVoteData
{
    public function __construct(
        public int $electionId,
        public int $candidateId,
        public int $eligibilityId,
        public string $idempotencyKey,
    ) {}
}
```

Do not trust these values merely because they came from DTO construction.

---

# 124. CastVote Output

Possible:

```php
final readonly class CastVoteResult
{
    public function __construct(
        public bool $success,
        public string $confirmationReference,
    ) {}
}
```

Confirmation reference must not encode candidate identity.

---

# 125. Service Contract

Conceptual:

```php
interface VotingService
{
    public function cast(CastVoteData $data): CastVoteResult;
}
```

---

# 126. Transaction Responsibility

`CastVote` owns the transaction.

Avoid:

```text
Controller transaction
+
Service transaction
+
Repository transaction
```

nested unpredictably.

There should be one clear authoritative transaction boundary.

---

# 127. Exception Handling

Domain exceptions:

```text
known business failure
```

Infrastructure exceptions:

```text
database/network/system failure
```

must be distinguished.

---

# 128. Error Response Rule

Never expose:

```text
SQL errors
stack trace
table names
column names
internal IDs
```

to voter.

---

# 129. Audit Event Ordering

Recommended:

```text
DB transaction commit
       ↓
after-commit event
       ↓
audit/metrics processing
```

If an audit event is legally required to be atomic with the vote, it must be explicitly included in the transaction without violating anonymity.

---

# 130. Audit Privacy

A vote audit record must never accidentally become:

```text
voter_id → candidate_id
```

through:

```text
request ID
session ID
credential ID
```

correlation.

---

# 131. Request ID Privacy

Request IDs are operational identifiers.

They should not be publicly displayed together with:

```text
voter identity
candidate choice
```

unless explicitly required and privacy-reviewed.

---

# 132. Support Workflow

If voter reports:

```text
"I cannot vote"
```

support staff should be able to investigate:

```text
credential status
eligibility status
election status
technical errors
```

without seeing:

```text
candidate choice
```

---

# 133. Admin Dashboard

Admin may see:

```text
Total eligible
Total voted
Participation
System health
```

Candidate results:

```text
only after allowed
```

---

# 134. Operator Dashboard

Operator may see:

```text
operational status
participation
credential issues
```

but not:

```text
candidate vote totals
```

unless explicitly authorized after close.

---

# 135. Voting Confirmation UI

After successful vote:

```text
✓ Suara Anda telah berhasil direkam.
```

Do not show:

```text
Anda memilih Kandidat 02
```

by default.

---

# 136. Browser Back Button

After successful vote:

```text
invalidate session
```

and prevent browser back navigation from creating a valid voting state.

Server must still reject any replayed POST.

---

# 137. Refresh After Vote

Refreshing confirmation page must not:

```text
create another vote
```

Use:

```text
POST → PRG/redirect
```

or an idempotent confirmation flow.

---

# 138. Double-Click Protection

Frontend may disable button:

```text
Submitting...
```

but this is only UX protection.

Backend must remain safe against:

```text
double click
multiple tabs
parallel requests
```

---

# 139. Multiple Tabs

Scenario:

```text
tab A → candidate 1
tab B → candidate 2
```

Expected:

```text
one succeeds
one fails
```

No duplicate ballot.

---

# 140. Multiple Devices

Scenario:

```text
device A
device B
same credential
```

Expected:

```text
one successful vote
```

assuming credential/eligibility policy allows concurrent attempts.

---

# 141. Token Theft Scenario

If attacker gets credential before legitimate voter:

```text
attacker may win race
```

Therefore high-risk deployments may require additional verification.

Possible controls:

```text
secondary identity check
school network restriction
operator verification
short credential lifetime
device-bound flow
```

These are deployment/security policy decisions.

---

# 142. Stronger Voter Verification

For high-stakes elections, consider:

```text
student ID
+
one-time credential
+
additional school-controlled verification
```

Do not rely solely on QR convenience.

---

# 143. Threat Model Dependency

Voting engine must be reviewed against:

```text
03_SECURITY_THREAT_MODEL.md
```

Any new attack path must update the threat model.

---

# 144. Architecture Dependency

Voting engine must respect:

```text
04_ARCHITECTURE.md
```

No controller-level shortcut may bypass:

```text
CastVote
```

for normal voting.

---

# 145. API Dependency

API implementation must respect:

```text
05_API_SPEC.md
```

Endpoint should map to:

```text
CastVote
```

rather than duplicate voting logic.

---

# 146. UI Dependency

UI follows:

```text
06_UI_UX_SPEC.md
07_USER_FLOWS.md
```

Frontend must never become the authority for:

```text
eligibility
candidate validity
election state
vote uniqueness
```

---

# 147. Test Dependency

Tests must implement:

```text
08_TEST_PLAN.md
```

with additional voting-specific concurrency tests from this document.

---

# 148. Deployment Dependency

Deployment must respect:

```text
09_DEPLOYMENT.md
```

especially:

```text
database availability
backup
monitoring
migration freeze
incident response
```

---

# 149. Final Voting Algorithm

Canonical algorithm:

```text
1. Authenticate voter.
2. Resolve election-specific eligibility.
3. Validate request shape.
4. Validate election context.
5. Begin database transaction.
6. Lock eligibility row.
7. Re-check eligibility status.
8. Re-check election state.
9. Validate candidate belongs to election.
10. Validate credential/session according to policy.
11. Resolve/replay-check idempotency state.
12. Generate anonymous ballot data.
13. Insert immutable ballot.
14. Mark eligibility as voted.
15. Consume/revoke voting credential as defined.
16. Commit transaction.
17. Invalidate voter session if required.
18. Emit privacy-safe after-commit event.
19. Return generic confirmation.
```

---

# 150. Failure Algorithm

If any critical operation from steps 5–15 fails:

```text
ROLLBACK
```

Then:

```text
do not return success
do not consume eligibility
do not leave partial ballot
do not expose internal error
```

---

# 151. Success Invariant

After successful response:

```text
exactly one ballot exists
eligibility = voted
ballot is immutable
credential cannot be reused according to policy
```

---

# 152. Failure Invariant

After failed vote:

```text
no new ballot
eligibility remains eligible
credential remains usable according to failure type
```

Exception:

```text
security policy may deliberately consume a credential after repeated invalid attempts
```

This must be explicitly documented and must not cause loss of a legitimate successful vote.

---

# 153. Critical Production Rule

Never implement:

```text
mark voter voted
then create ballot later
```

or:

```text
create ballot
then mark voter voted later
```

outside one authoritative transaction.

---

# 154. Critical Privacy Rule

Never "solve" double voting by adding voter identity to the ballot table.

Correctness must be achieved through:

```text
eligibility locking
+
transaction
+
state transition
+
credential controls
+
idempotency
```

while preserving ballot anonymity.

---

# 155. Critical Reliability Rule

If PostgreSQL cannot confirm the vote:

```text
vote is NOT considered successful
```

Do not return:

```text
success
```

based on frontend state, Redis, queue acceptance, or an external service.

---

# 156. Definition of Done

`CastVote` is ready only when:

```text
[ ] unit tests pass
[ ] feature tests pass
[ ] concurrency tests pass
[ ] race tests pass
[ ] rollback tests pass
[ ] idempotency tests pass
[ ] candidate tampering tests pass
[ ] closed-election tests pass
[ ] credential tests pass
[ ] privacy review passes
[ ] ballot schema review passes
[ ] load test passes
[ ] security review passes
[ ] production observability exists
```

---

# 157. Next Document

Setelah voting engine, dokumen berikutnya yang direkomendasikan:

```text
16_AUDIT_LOGGING_SPEC.md
```

Dokumen tersebut akan mendefinisikan:

```text
audit event taxonomy
event schema
who/what/when metadata
privacy-safe audit
security events
admin activity
operator activity
credential events
election lifecycle events
result export events
retention
tamper resistance
audit access control
```

Setelah itu dapat dilanjutkan dengan:

```text
17_RESULT_CALCULATION_SPEC.md
18_IMPORT_EXPORT_SPEC.md
19_OBSERVABILITY_SPEC.md
20_PRIVACY_DATA_RETENTION.md
```
