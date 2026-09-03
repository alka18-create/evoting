# Database Migrations — Sistem E-Voting Sekolah

**Dokumen:** 13 — Database Migrations  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `02_ERD_DATABASE_DESIGN.md`, `04_ARCHITECTURE.md`, `11_CODING_STANDARDS.md`, `12_PROJECT_STRUCTURE.md`

---

# 1. Tujuan

Dokumen ini menerjemahkan desain database menjadi migration plan Laravel yang dapat diimplementasikan secara bertahap dan aman.

Fokus:

- urutan migration
- struktur tabel
- primary key
- foreign key
- unique constraint
- check constraint
- index
- nullable rules
- delete/update behavior
- transaction safety
- migration deployment
- seed strategy
- rollback strategy

---

# 2. Database Engine

Database utama:

```text
PostgreSQL
```

PostgreSQL menjadi authoritative source untuk:

```text
election state
voter eligibility
credential state
ballot
candidate
result source data
audit record
```

Redis tidak boleh menjadi source of truth untuk data voting.

---

# 3. Migration Principles

Migration harus:

```text
deterministic
repeatable
reviewable
forward-compatible
safe for production
```

Jangan melakukan perubahan schema besar tanpa migration khusus.

---

# 4. Naming Convention

Table:

```text
snake_case
plural
```

Contoh:

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

Column:

```text
snake_case
```

---

# 5. Primary Key Strategy

Rekomendasi:

```text
bigint generated identity
```

untuk entity internal.

Contoh:

```text
id BIGINT
```

Identifier publik yang dapat diekspos ke client sebaiknya tidak selalu menggunakan sequential database ID.

Jika ERD final menetapkan UUID/ULID, migration harus mengikuti keputusan tersebut secara konsisten.

---

# 6. Timestamp Strategy

Setiap tabel transactional utama menggunakan:

```text
created_at
updated_at
```

Jika record bersifat event/immutable:

```text
created_at
```

sudah cukup.

Gunakan timezone-aware timestamp PostgreSQL:

```text
TIMESTAMPTZ
```

dan simpan waktu dalam UTC.

---

# 7. Migration Order

Urutan rekomendasi:

```text
01 users
02 elections
03 candidates
04 voters
05 voter_eligibilities
06 credentials
07 ballots
08 audit_logs
09 supporting indexes/constraints
```

Urutan aktual file migration mengikuti timestamp Laravel.

---

# 8. Users Table

Tujuan:

```text
admin/operator authentication
```

Contoh field:

```text
id
name
email
password
role
remember_token
created_at
updated_at
```

Constraint:

```text
email UNIQUE
role NOT NULL
password NOT NULL
```

Jangan menyimpan voter PIN pada tabel `users` kecuali arsitektur autentikasi secara eksplisit mengharuskannya.

---

# 9. Users Role

Jika hanya ada role sederhana:

```text
admin
operator
```

gunakan enum atau constrained string.

Contoh:

```text
role VARCHAR(30) NOT NULL
```

Tambahkan CHECK:

```sql
CHECK (role IN ('admin', 'operator'))
```

Jika role/permission membutuhkan fleksibilitas lebih tinggi, gunakan RBAC tables sesuai security architecture.

---

# 10. Elections Table

Contoh:

```text
elections
```

Field:

```text
id
name
description
status
starts_at
ends_at
created_by
created_at
updated_at
```

---

# 11. Elections Constraints

Minimal:

```text
name NOT NULL
status NOT NULL
starts_at NOT NULL
ends_at NOT NULL
created_by NOT NULL
```

Check:

```sql
CHECK (ends_at > starts_at)
```

Foreign key:

```text
created_by → users.id
```

---

# 12. Election Status

Recommended values:

```text
draft
scheduled
open
closed
archived
```

Status harus menggunakan finite state machine/domain transition.

Database CHECK dapat membatasi nilai:

```sql
CHECK (
    status IN (
        'draft',
        'scheduled',
        'open',
        'closed',
        'archived'
    )
)
```

---

# 13. Election State Safety

Jangan mengandalkan database enum saja untuk business transition.

Contoh invalid transition:

```text
closed → open
archived → open
```

harus dicegah oleh domain layer.

---

# 14. Candidates Table

Table:

```text
candidates
```

Field:

```text
id
election_id
candidate_number
name
photo_path
vision
mission
created_at
updated_at
```

---

# 15. Candidate Constraints

Minimal:

```text
election_id NOT NULL
candidate_number NOT NULL
name NOT NULL
```

Unique:

```text
UNIQUE(election_id, candidate_number)
```

Dengan demikian:

```text
Election A / Candidate 01
Election B / Candidate 01
```

boleh.

Tetapi:

```text
Election A / Candidate 01
Election A / Candidate 01
```

tidak boleh.

---

# 16. Candidate Foreign Key

```text
candidate.election_id
        ↓
elections.id
```

Delete policy:

```text
RESTRICT
```

atau soft-delete/application rule jika candidate sudah digunakan.

Jangan cascade delete candidate yang sudah memiliki ballot.

---

# 17. Voters Table

Table:

```text
voters
```

Field contoh:

```text
id
student_id
name
class_name
is_active
created_at
updated_at
```

---

# 18. Voter Constraints

Minimal:

```text
student_id NOT NULL
name NOT NULL
class_name NOT NULL
is_active NOT NULL DEFAULT true
```

Unique:

```text
student_id UNIQUE
```

Jika student ID hanya unik dalam sekolah tertentu, scope uniqueness sesuai model tenancy/sekolah.

---

# 19. Voter Identity

`student_id` adalah identifier pemilih.

Jangan gunakan:

```text
candidate_id
```

atau ballot identifier sebagai voter identity.

---

# 20. Voter Eligibility Table

Table:

```text
voter_eligibilities
```

Tujuan:

```text
menghubungkan voter
dengan election
```

Field:

```text
id
election_id
voter_id
status
voted_at
created_at
updated_at
```

---

# 21. Eligibility Constraints

Unique:

```text
UNIQUE(election_id, voter_id)
```

Dengan demikian satu voter hanya mempunyai satu eligibility record untuk satu election.

---

# 22. Eligibility Status

Recommended:

```text
eligible
voted
revoked
```

Jika status digunakan, gunakan CHECK:

```sql
CHECK (
    status IN (
        'eligible',
        'voted',
        'revoked'
    )
)
```

Default:

```text
eligible
```

---

# 23. Eligibility Vote State

`voted_at`:

```text
NULL
```

ketika belum memilih.

Setelah vote berhasil:

```text
voted_at = current timestamp
```

Tetapi status eligibility bukan satu-satunya defense terhadap double voting.

---

# 24. Credential Table

Table:

```text
credentials
```

Field konseptual:

```text
id
voter_eligibility_id
credential_hash
expires_at
last_used_at
revoked_at
created_at
updated_at
```

---

# 25. Credential Security

Jangan menyimpan:

```text
PIN plaintext
token plaintext
```

Simpan:

```text
credential_hash
```

Jika credential membutuhkan lookup berdasarkan token, desain hash/index harus mengikuti `03_SECURITY_THREAT_MODEL.md` dan `15_VOTING_ENGINE_SPEC.md`.

---

# 26. Credential Constraints

Minimal:

```text
voter_eligibility_id NOT NULL
credential_hash NOT NULL
```

Unique:

```text
UNIQUE(credential_hash)
```

Jika satu eligibility hanya boleh mempunyai satu credential aktif, tambahkan constraint/partial unique index sesuai desain.

---

# 27. Ballots Table

Table:

```text
ballots
```

Ini adalah tabel paling sensitif.

Field konseptual:

```text
id
election_id
candidate_id
ballot_hash
created_at
```

---

# 28. Ballot Privacy Boundary

Ballot TIDAK boleh memiliki:

```text
voter_id
student_id
credential_id
user_id
```

jika tujuan arsitektur adalah memutus hubungan langsung identitas pemilih dengan pilihan.

Ballot hanya menyimpan informasi yang diperlukan untuk menghitung suara dan memastikan integritas.

---

# 29. Ballot Election Foreign Key

```text
ballots.election_id
        ↓
elections.id
```

Candidate:

```text
ballots.candidate_id
        ↓
candidates.id
```

Namun foreign key biasa belum cukup untuk memastikan candidate berasal dari election yang sama.

---

# 30. Cross-Election Candidate Integrity

Sistem harus memastikan:

```text
ballot.election_id
=
candidate.election_id
```

Pilihan implementasi:

### Option A — application/domain validation

```text
CastVote
→ verify candidate belongs to election
```

### Option B — composite FK

Gunakan:

```text
candidates
UNIQUE(id, election_id)
```

kemudian:

```text
ballots(candidate_id, election_id)
→ candidates(id, election_id)
```

**Rekomendasi:** gunakan defense-in-depth jika PostgreSQL schema tetap manageable.

---

# 31. Ballot Hash

`ballot_hash` dapat digunakan untuk integrity/audit purpose.

Contoh konseptual:

```text
hash(
    election_id
    +
    candidate_id
    +
    random_nonce
    +
    created_at
)
```

Algoritma final harus ditentukan pada:

```text
15_VOTING_ENGINE_SPEC.md
```

Jangan menganggap hash otomatis membuat ballot anonymous.

---

# 32. Ballot Hash Constraint

Jika ballot hash dimaksudkan sebagai unique identifier:

```text
UNIQUE(ballot_hash)
```

Pastikan collision strategy dan generation mechanism telah diuji.

---

# 33. Audit Logs

Table:

```text
audit_logs
```

Field:

```text
id
actor_user_id
action
resource_type
resource_id
metadata
ip_address
user_agent
created_at
```

---

# 34. Audit Privacy

Audit metadata tidak boleh menyimpan:

```text
voter → candidate
```

Contoh yang aman:

```text
ELECTION_OPENED
ELECTION_CLOSED
VOTER_IMPORTED
CREDENTIAL_REVOKED
RESULT_EXPORTED
```

Untuk voting:

```text
VOTE_CAST
```

boleh dicatat hanya jika metadata tidak menghubungkan actor dengan candidate choice.

---

# 35. JSONB Metadata

PostgreSQL:

```text
metadata JSONB
```

digunakan untuk data audit tambahan.

Contoh:

```json
{
  "import_count": 842
}
```

Jangan masukkan:

```json
{
  "student_id": "...",
  "candidate_id": "..."
}
```

ke event voting jika privacy boundary melarangnya.

---

# 36. Audit Indexes

Recommended:

```text
INDEX(actor_user_id)
INDEX(resource_type, resource_id)
INDEX(action)
INDEX(created_at)
```

Jika audit volume besar:

```text
BRIN(created_at)
```

dapat dipertimbangkan setelah measurement.

---

# 37. Index Strategy

Index harus mendukung:

```text
foreign key lookup
authorization lookup
eligibility lookup
result aggregation
admin listing
audit filtering
```

---

# 38. Election Indexes

Recommended:

```text
INDEX(status)
INDEX(starts_at)
INDEX(ends_at)
INDEX(created_by)
```

Jangan membuat index berlebihan tanpa workload evidence.

---

# 39. Candidate Indexes

Minimal:

```text
INDEX(election_id)
UNIQUE(election_id, candidate_number)
```

---

# 40. Voter Indexes

Minimal:

```text
UNIQUE(student_id)
```

Tambahkan:

```text
INDEX(class_name)
```

jika monitoring/filtering per kelas memang diperlukan.

---

# 41. Eligibility Indexes

Minimal:

```text
UNIQUE(election_id, voter_id)
INDEX(election_id, status)
```

Jika query:

```text
voter_id + election_id
```

sering digunakan, unique composite index sudah mencukupi.

---

# 42. Ballot Indexes

Minimal:

```text
INDEX(election_id)
INDEX(election_id, candidate_id)
UNIQUE(ballot_hash)
```

`election_id + candidate_id` penting untuk aggregate result.

---

# 43. Partial Indexes

PostgreSQL partial index dapat digunakan jika workload membutuhkan.

Contoh:

```sql
CREATE UNIQUE INDEX ...
ON credentials(voter_eligibility_id)
WHERE revoked_at IS NULL;
```

Gunakan hanya jika business rule memang:

```text
one active credential per eligibility
```

---

# 44. Check Constraints

Gunakan database CHECK untuk invariant sederhana.

Contoh:

```sql
CHECK (ends_at > starts_at)
```

dan finite states.

Jangan mencoba memasukkan seluruh business workflow ke CHECK constraint.

---

# 45. Foreign Key Behavior

Default policy harus konservatif.

Untuk election:

```text
created_by → RESTRICT
```

Untuk candidate:

```text
election_id → RESTRICT
```

Untuk ballot:

```text
election_id → RESTRICT
candidate_id → RESTRICT
```

Ballot adalah historical record dan tidak boleh hilang karena cascade delete.

---

# 46. Cascade Delete

Hindari:

```text
ON DELETE CASCADE
```

pada voting-critical historical data.

Cascade dapat menyebabkan penghapusan massal yang sulit dipulihkan.

---

# 47. Soft Delete

Soft delete bukan default untuk:

```text
ballots
audit_logs
voter_eligibilities
```

Jika digunakan pada voter/candidate, efek terhadap historical data harus ditentukan terlebih dahulu.

---

# 48. Election Deletion

Rekomendasi:

```text
do not physically delete election
```

Gunakan:

```text
archived
```

setelah election selesai.

---

# 49. Ballot Immutability

Ballot harus diperlakukan sebagai immutable record.

Setelah insert:

```text
candidate_id
election_id
created_at
```

tidak boleh diubah melalui normal application flow.

---

# 50. Database Permissions

Production application user sebaiknya tidak memiliki privilege berlebihan.

Pisahkan bila memungkinkan:

```text
migration role
application role
read-only reporting role
```

Application runtime tidak membutuhkan:

```text
DROP DATABASE
CREATE DATABASE
```

---

# 51. Migration Transactions

PostgreSQL mendukung transactional DDL untuk banyak operasi.

Gunakan migration transaction jika aman.

Namun hati-hati terhadap operasi PostgreSQL tertentu yang tidak dapat berjalan dalam transaction.

---

# 52. Safe Migration Rule

Migration production harus memperhatikan:

```text
table size
lock duration
traffic
election state
rollback feasibility
```

---

# 53. Election-Day Migration Freeze

Jangan menjalankan schema migration berisiko ketika election:

```text
OPEN
```

kecuali emergency procedure sudah disetujui.

Ideal:

```text
migration freeze
```

selama voting window.

---

# 54. Adding Columns

Untuk tabel besar:

```text
add nullable column
deploy code
backfill
add constraint/default later
```

hindari migration yang menyebabkan long table lock tanpa analisis.

---

# 55. Renaming Columns

Jangan langsung:

```text
rename column
```

jika old application version masih aktif.

Gunakan expand/contract:

```text
add new column
 ↓
write both
 ↓
migrate data
 ↓
switch reads
 ↓
remove old column
```

---

# 56. Dropping Columns

Sebelum drop:

```text
verify no code references
verify no reports
verify no jobs
verify no external integration
```

Idealnya dilakukan setelah satu atau lebih release.

---

# 57. Adding NOT NULL

Untuk tabel berisi data:

```text
add nullable
 ↓
backfill
 ↓
validate
 ↓
set NOT NULL
```

Jangan langsung membuat NOT NULL pada column baru jika production table sudah memiliki data.

---

# 58. Adding Unique Constraint

Sebelum:

```text
UNIQUE
```

cek duplicate data.

Flow:

```text
detect duplicates
 ↓
resolve
 ↓
create unique index
```

---

# 59. Migration Validation

CI harus menjalankan:

```text
php artisan migrate:fresh
php artisan db:seed
php artisan test
```

atau equivalent test workflow.

---

# 60. Fresh Database Test

Project harus dapat dibuat dari database kosong:

```text
migration
 ↓
seed
 ↓
application works
```

Tidak boleh bergantung pada manual SQL yang tidak terdokumentasi.

---

# 61. Migration Rollback

Setiap migration harus memiliki rollback yang masuk akal.

Namun production rollback tidak selalu berarti:

```text
php artisan migrate:rollback
```

karena rollback schema dapat menyebabkan data loss.

Production rollback harus mengikuti deployment plan.

---

# 62. Data Migration vs Schema Migration

Pisahkan:

```text
schema change
```

dengan:

```text
large data transformation
```

Jika data migration berat:

```text
queue/batch/controlled command
```

jangan memaksa semuanya ke migration deployment step.

---

# 63. Seed Strategy

Seed dibagi:

```text
Base/reference seed
Development/demo seed
Test factories
```

---

# 64. Reference Data

Jika ada data reference:

```text
roles
permissions
system settings
```

gunakan deterministic seeder.

Contoh:

```text
RoleSeeder
PermissionSeeder
```

---

# 65. Demo Seed

Demo seed harus membuat:

```text
1 election
3 candidates
sample voters
```

dan tidak pernah menggunakan real student data.

---

# 66. Production Data

Jangan seed:

```text
demo voter
demo credentials
demo candidates
```

ke production.

---

# 67. Test Factories

Gunakan factory untuk:

```text
Voter
Election
Candidate
Eligibility
Credential
Ballot
```

Factory harus mematuhi foreign key dan domain invariants.

---

# 68. Ballot Factory

Ballot factory hanya untuk:

```text
unit/integration test
```

Tidak boleh digunakan sebagai cara normal untuk membuat vote di application production code.

Production ballot harus dibuat melalui:

```text
CastVote
```

---

# 69. Database Transaction Test

Minimal test:

```text
if ballot insert fails
→ eligibility remains eligible
```

dan:

```text
if eligibility update fails
→ ballot insert rolls back
```

---

# 70. Concurrency Test

Test database harus mensimulasikan:

```text
two requests
same voter
same election
same time
```

Expected:

```text
exactly one successful vote
exactly one ballot
exactly one voted eligibility
```

---

# 71. Cross-Election Test

Pastikan:

```text
Candidate A belongs to Election A
```

tidak dapat digunakan untuk:

```text
Election B
```

---

# 72. Closed Election Constraint

Application harus menolak:

```text
vote after close
```

Database dapat membantu melalui design, tetapi state validation tetap berada di domain transaction.

---

# 73. Result Integrity Query

Result harus dapat diverifikasi:

```sql
SELECT COUNT(*)
FROM ballots
WHERE election_id = ?;
```

dibandingkan dengan aggregate candidate votes.

Invariant:

```text
sum(candidate votes) = ballot count
```

---

# 74. Monitoring Queries

Dashboard dapat menggunakan aggregate queries seperti:

```text
eligible voters
voted voters
participation
ballots
```

Tetapi jangan menyimpan duplicated counters sebagai authoritative values kecuali ada alasan kuat dan reconciliation mechanism.

---

# 75. Counter Cache

Jika realtime monitoring membutuhkan counter:

```text
Redis
```

boleh digunakan sebagai cache.

Tetapi:

```text
PostgreSQL = source of truth
```

Jika Redis hilang:

```text
recalculate from database
```

---

# 76. Database Connection

Production:

```text
TLS where supported
strong credentials
connection pooling
least privilege
```

Connection settings berada di environment/secret manager.

---

# 77. Backup

Minimal:

```text
automated PostgreSQL backup
point-in-time recovery if feasible
backup encryption
restore testing
```

Backup policy mengikuti `09_DEPLOYMENT.md`.

---

# 78. Backup Privacy

Database backup mengandung data sensitif.

Perlakukan backup sebagai:

```text
highly sensitive asset
```

Akses harus dibatasi.

---

# 79. Restore Testing

Secara berkala:

```text
restore backup
 ↓
run integrity checks
 ↓
verify election data
 ↓
verify ballot count
```

Backup yang belum pernah diuji restore bukan backup yang dapat dianggap fully reliable.

---

# 80. Database Health Checks

Production monitoring:

```text
connection count
CPU
memory
disk
WAL
replication lag
slow queries
locks
deadlocks
```

---

# 81. Lock Monitoring

Voting-critical database harus dimonitor untuk:

```text
long transactions
deadlocks
lock waits
```

Long transaction pada voting path harus dianggap serius.

---

# 82. Deadlock Handling

Jika transaction mengalami deadlock:

```text
database may abort transaction
```

Application dapat retry secara terbatas jika operasi aman dan idempotent.

Retry harus tidak menyebabkan duplicate ballot.

---

# 83. Isolation Level

Default PostgreSQL isolation:

```text
READ COMMITTED
```

dapat digunakan jika voting transaction dirancang dengan row locking dan constraints yang tepat.

Jika menggunakan isolation berbeda:

```text
document in ADR
test under concurrency
```

---

# 84. Row Locking

Eligibility record adalah kandidat utama untuk lock:

```text
SELECT ...
FOR UPDATE
```

Lock hanya selama transaction diperlukan.

Jangan mengunci seluruh election jika tidak diperlukan.

---

# 85. Index and Lock Interaction

Query yang mengambil row untuk locking harus menggunakan index yang tepat.

Contoh:

```text
WHERE election_id = ?
AND voter_id = ?
```

harus didukung oleh:

```text
UNIQUE(election_id, voter_id)
```

agar lookup predictable.

---

# 86. Migration Naming

Laravel migration names harus deskriptif.

Contoh:

```text
2026_01_01_000001_create_users_table.php
2026_01_01_000002_create_elections_table.php
2026_01_01_000003_create_candidates_table.php
2026_01_01_000004_create_voters_table.php
2026_01_01_000005_create_voter_eligibilities_table.php
2026_01_01_000006_create_credentials_table.php
2026_01_01_000007_create_ballots_table.php
2026_01_01_000008_create_audit_logs_table.php
```

Timestamp aktual mengikuti waktu pembuatan.

---

# 87. Migration Responsibility

Satu migration sebaiknya fokus pada satu perubahan schema.

Buruk:

```text
create 8 unrelated tables
add indexes
change data
seed data
```

dalam satu migration.

Lebih baik:

```text
one coherent schema change
```

---

# 88. Example Migration Shape

Contoh konseptual:

```php
Schema::create('voter_eligibilities', function (Blueprint $table) {
    $table->id();

    $table->foreignId('election_id')
        ->constrained()
        ->restrictOnDelete();

    $table->foreignId('voter_id')
        ->constrained()
        ->restrictOnDelete();

    $table->string('status', 20)
        ->default('eligible');

    $table->timestampTz('voted_at')->nullable();

    $table->timestampsTz();

    $table->unique(['election_id', 'voter_id']);
    $table->index(['election_id', 'status']);
});
```

Implementasi final harus disesuaikan dengan ERD final.

---

# 89. Example Ballot Migration Shape

```php
Schema::create('ballots', function (Blueprint $table) {
    $table->id();

    $table->foreignId('election_id')
        ->constrained()
        ->restrictOnDelete();

    $table->foreignId('candidate_id')
        ->constrained()
        ->restrictOnDelete();

    $table->string('ballot_hash', 128)
        ->unique();

    $table->timestampTz('created_at');

    $table->index(['election_id', 'candidate_id']);
});
```

Tidak ada:

```text
voter_id
student_id
credential_id
```

---

# 90. Schema-Level Privacy Review

Sebelum migration ballot di-merge, reviewer wajib memastikan:

```text
[ ] no voter_id
[ ] no student_id
[ ] no credential_id
[ ] no admin actor_id
[ ] no accidental identity foreign key
```

---

# 91. Schema Review Checklist

Setiap migration:

```text
[ ] naming
[ ] type
[ ] nullability
[ ] default
[ ] FK
[ ] delete behavior
[ ] index
[ ] unique constraint
[ ] check constraint
[ ] rollback
[ ] production lock impact
```

---

# 92. Voting Schema Review Checklist

Khusus voting:

```text
[ ] ballot anonymity
[ ] eligibility uniqueness
[ ] candidate-election integrity
[ ] ballot immutability
[ ] double-vote defense
[ ] transaction support
[ ] concurrency support
[ ] result aggregation
```

---

# 93. Migration Deployment Workflow

Recommended:

```text
Developer
   ↓
Migration
   ↓
Fresh DB test
   ↓
Integration tests
   ↓
Security review
   ↓
Staging migration
   ↓
Staging verification
   ↓
Production deployment
```

---

# 94. Production Migration Gate

Sebelum production:

```text
[ ] backup verified
[ ] restore procedure available
[ ] migration tested on representative data
[ ] lock impact understood
[ ] rollback/recovery plan ready
[ ] election not in active voting window
```

---

# 95. Emergency Migration

Emergency migration selama election hanya dilakukan jika:

```text
security
data integrity
availability
```

terancam dan change telah melalui emergency approval.

---

# 96. Final Database Rules

```text
1. PostgreSQL is authoritative.
2. Ballot must not directly identify voter.
3. Eligibility is unique per voter/election.
4. Candidate must belong to election.
5. Ballot is immutable.
6. Historical voting data must not cascade-delete.
7. Critical invariants use database constraints where practical.
8. Voting transaction must be short.
9. Redis is never authoritative for vote correctness.
10. Production migrations require explicit safety review.
```

---

# 97. Next Document

Setelah database migration plan, dokumen berikutnya:

```text
14_AUTH_RBAC_SPEC.md
```

Dokumen tersebut akan mendefinisikan:

```text
admin authentication
operator authentication
voter authentication
PIN/token verification
session lifecycle
role/permission matrix
middleware
policies
rate limiting
credential lifecycle
logout
account lockout
```

Setelah itu, dokumen paling kritis untuk implementasi:

```text
15_VOTING_ENGINE_SPEC.md
```

yang akan menjadi blueprint detail `CastVote`, transaction, locking, idempotency, anonymous ballot, race condition, failure recovery, dan database invariants.
