# Authentication & RBAC Specification — Sistem E-Voting Sekolah

**Dokumen:** 14 — Auth & RBAC Specification  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD` s/d `13_DATABASE_MIGRATIONS`

---

# 1. Tujuan

Dokumen ini mendefinisikan autentikasi, authorization, role, credential, session, dan access control untuk aplikasi e-voting.

Tujuan utama:

```text
Confidentiality
Integrity
Least Privilege
Accountability
Privacy
Abuse Resistance
```

---

# 2. Security Principles

Sistem harus menerapkan:

```text
1. Authentication ≠ Authorization
2. Server-side authorization is mandatory
3. Least privilege
4. Secure credential storage
5. Rate limiting
6. Session protection
7. No sensitive data in logs
8. No voter identity → vote choice relationship
9. Fail closed
10. Database remains authoritative
```

---

# 3. Actor Types

Aktor utama:

```text
ADMIN
OPERATOR
VOTER
```

Optional:

```text
AUDITOR
```

Jika `AUDITOR` belum diperlukan untuk MVP, jangan membuat role hanya untuk future speculation.

---

# 4. Role Overview

| Role | Fungsi |
|---|---|
| ADMIN | Mengelola seluruh sistem dan pemilihan |
| OPERATOR | Membantu operasional pemilihan dengan permission terbatas |
| VOTER | Memberikan suara |
| AUDITOR | Read-only audit/report jika diperlukan |

---

# 5. Admin

Admin dapat:

```text
view dashboard
create election
update election
open election
close election
manage candidates
import voters
manage credentials
view monitoring
view results after close
export results
view audit logs
manage operators
```

Admin tidak boleh:

```text
mengubah ballot
mengubah candidate pada ballot
menghapus historical vote
melihat hubungan voter → candidate
```

---

# 6. Operator

Operator digunakan untuk pekerjaan operasional.

Contoh permission:

```text
election.view
candidate.view
candidate.manage
voter.view
voter.import
credential.issue
credential.revoke
monitoring.view
```

Operator tidak default memiliki:

```text
election.open
election.close
result.publish
audit.manage
user.manage
```

Permission final harus mengikuti kebutuhan sekolah.

---

# 7. Voter

Voter hanya dapat:

```text
authenticate
view assigned election
view eligible candidates
cast one vote
view vote confirmation
logout
```

Voter tidak dapat:

```text
view other voters
view admin dashboard
view result before allowed
view audit logs
modify election
modify candidate
access another voter session
```

---

# 8. Authentication Models

Ada dua authentication boundary:

```text
Admin/Operator Authentication
```

dan:

```text
Voter Authentication
```

Keduanya tidak boleh diperlakukan sebagai user type yang identik jika hal tersebut meningkatkan risiko privacy atau privilege escalation.

---

# 9. Admin Authentication

Rekomendasi:

```text
email/username
+
password
```

Optional:

```text
MFA
```

MFA sangat direkomendasikan untuk ADMIN.

---

# 10. Admin Password Policy

Minimum:

```text
12 characters
```

Rekomendasi:

```text
password manager
unique password
breach-password screening
MFA
```

Jangan memaksakan komposisi karakter yang tidak memberikan manfaat keamanan yang jelas jika passphrase lebih efektif.

---

# 11. Password Storage

Password harus menggunakan Laravel-supported password hashing.

Contoh:

```text
Argon2id
```

atau algorithm aman yang direkomendasikan framework/runtime.

Jangan:

```text
MD5
SHA1
plain text
reversible encryption
```

---

# 12. Voter Authentication

Voter dapat menggunakan:

```text
student_id
+
PIN/token
```

atau:

```text
QR/token
+
secondary verification
```

Pilihan final mengikuti credential strategy.

---

# 13. Voter Credential

Credential harus:

```text
unique
random
high entropy
single-purpose
rate-limited
revocable
```

Credential plaintext hanya boleh tersedia saat issuance jika memang diperlukan.

Database menyimpan:

```text
credential_hash
```

---

# 14. PIN

Jika menggunakan PIN numerik:

```text
minimum entropy harus ditentukan
```

PIN pendek seperti:

```text
1234
0000
1111
```

tidak boleh dibuat secara predictable.

Jika constraint UX memaksa PIN pendek, kompensasi dengan:

```text
rate limiting
lockout
limited attempts
additional identity verification
```

---

# 15. Token

Jika menggunakan token:

```text
cryptographically secure random
```

Contoh conceptual:

```text
random_bytes(...)
```

Jangan:

```text
student_id + birth_date
```

atau:

```text
hash(student_id)
```

sebagai token.

---

# 16. QR Code

QR Code sebaiknya hanya membawa:

```text
opaque token
```

bukan:

```text
student name
student ID
candidate choice
```

Contoh:

```text
https://evoting.example/vote?t=<opaque-token>
```

Token tetap harus diverifikasi server-side.

---

# 17. QR Reuse

QR tidak otomatis berarti one-time voting.

Server harus memastikan:

```text
credential valid
+
credential belongs to eligible voter
+
eligibility has not voted
+
election is open
```

---

# 18. Credential Lifecycle

```text
generated
   ↓
issued
   ↓
active
   ↓
used / revoked / expired
```

Status final harus mengikuti database design.

---

# 19. Credential Reissue

Jika credential hilang:

```text
revoke old credential
generate new credential
audit event
```

Jangan memiliki dua credential aktif tanpa alasan yang jelas.

---

# 20. Credential Reveal

Admin/operator sebaiknya tidak dapat melihat plaintext credential setelah issuance.

Jika diperlukan:

```text
regenerate
```

bukan:

```text
show existing PIN
```

---

# 21. Credential Reset

Reset credential harus:

```text
authorized
rate-limited
audited
```

Tidak boleh dilakukan hanya berdasarkan:

```text
student_id
```

jika student ID mudah diketahui publik.

---

# 22. Login Flow — Admin

```text
Admin
  ↓
Login form
  ↓
Validate credentials
  ↓
Rate-limit check
  ↓
Authenticate
  ↓
Regenerate session
  ↓
Optional MFA
  ↓
Admin dashboard
```

---

# 23. Login Flow — Voter

```text
Voter
  ↓
Enter student ID / token
  ↓
Rate-limit
  ↓
Credential verification
  ↓
Find eligibility
  ↓
Check election access
  ↓
Create voter session
  ↓
Voting page
```

---

# 24. Voter Session

Voter session harus menyimpan hanya information yang diperlukan.

Contoh:

```text
authenticated voter eligibility reference
session ID
election context
```

Hindari menyimpan:

```text
candidate choice
ballot ID
```

di session.

---

# 25. Session Isolation

Admin session dan voter session harus memiliki clear authorization boundaries.

Jika memakai Laravel guards:

```text
web
voter
```

dapat digunakan jika memang diperlukan.

---

# 26. Session Fixation

Setelah successful login:

```text
regenerate session ID
```

Laravel authentication flow harus menggunakan mekanisme session regeneration yang aman.

---

# 27. Session Timeout

Admin:

```text
shorter inactivity timeout
```

Voter:

```text
short session lifetime
```

karena voting session merupakan high-value action.

Exact timeout harus dikonfigurasi berdasarkan deployment/security requirements.

---

# 28. Logout

Logout harus:

```text
invalidate session
regenerate CSRF token/session state
```

Voter yang logout sebelum vote dapat login kembali jika eligibility masih valid.

---

# 29. Concurrent Voter Sessions

Policy harus ditentukan.

Recommended:

```text
same eligibility can have multiple login attempts
but only one successful vote
```

Correctness tidak boleh bergantung pada hanya satu active browser session.

---

# 30. Authorization

Setelah authentication:

```text
Who are you?
```

authorization menjawab:

```text
What are you allowed to do?
```

Semua sensitive action harus melewati authorization.

---

# 31. RBAC Model

Minimal:

```text
roles
permissions
role_permissions
```

atau simpler enum role jika permission matrix kecil.

---

# 32. Recommended MVP RBAC

Jika kebutuhan sekolah sederhana:

```text
users.role
```

dengan:

```text
admin
operator
```

dan Policies.

Jangan langsung membuat permission system kompleks jika belum diperlukan.

---

# 33. Permission Naming

Jika granular RBAC digunakan:

```text
election.view
election.create
election.update
election.open
election.close

candidate.view
candidate.create
candidate.update
candidate.delete

voter.view
voter.import

credential.issue
credential.revoke

result.view
result.export

audit.view

user.manage
```

---

# 34. Permission Matrix

| Action | Admin | Operator | Voter |
|---|---:|---:|---:|
| Dashboard | Yes | Yes | No |
| Create election | Yes | No | No |
| Update election | Yes | Limited | No |
| Open election | Yes | No | No |
| Close election | Yes | No | No |
| Manage candidates | Yes | Limited | No |
| Import voters | Yes | Yes | No |
| Manage credentials | Yes | Limited | No |
| View monitoring | Yes | Yes | No |
| Cast vote | No | No* | Yes |
| View results | Yes | Configurable | No/After publish |
| Export results | Yes | Configurable | No |
| View audit logs | Yes | Configurable | No |
| Manage users | Yes | No | No |

`*` Operator tidak boleh voting sebagai operator kecuali memang juga terdaftar sebagai voter dan menggunakan separate voter identity/session.

---

# 35. Separation of Admin and Voter Identity

Satu account admin sebaiknya tidak otomatis menjadi voter identity.

Jika staff juga mempunyai hak memilih:

```text
admin account
+
separate voter eligibility
```

harus digunakan.

---

# 36. IDOR Protection

Semua resource ID dari request harus di-authorize.

Buruk:

```text
GET /admin/elections/123
```

langsung mengambil data karena user sudah login.

Benar:

```text
authenticate
+
authorize election
+
load resource
```

---

# 37. Election Scope

Voter hanya boleh mengakses election yang:

```text
assigned
+
open
+
eligible
```

Tidak cukup:

```text
election.status = open
```

---

# 38. Candidate Scope

Voter hanya boleh memilih candidate:

```text
candidate.election_id = current election
```

Candidate ID dari browser tidak dipercaya.

---

# 39. Result Authorization

Result harus diperiksa:

```text
election closed
+
user authorized
```

Jika policy menyatakan hasil hanya tersedia setelah close, endpoint harus menegakkan rule tersebut.

---

# 40. Result Privacy

Sebelum election closed:

```text
public/voter result = unavailable
```

Admin/operator monitoring:

```text
participation count only
```

jika memang dibutuhkan.

Jangan menampilkan vote count kandidat selama voting jika policy mencegah influence.

---

# 41. Audit Authorization

Audit log hanya untuk:

```text
admin
authorized auditor
```

Operator hanya jika diberi permission.

Voter:

```text
never
```

---

# 42. CSRF Protection

Browser-based state-changing requests harus menggunakan CSRF protection.

Contoh:

```text
POST
PUT
PATCH
DELETE
```

Voting endpoint wajib memiliki CSRF protection jika menggunakan cookie/session authentication.

---

# 43. API Authentication

Jika API digunakan:

```text
token/session mechanism
```

harus jelas.

Jangan mencampur:

```text
cookie authentication
bearer token
QR credential
```

tanpa boundary yang jelas.

---

# 44. API Token

Admin API token:

```text
scoped
revocable
short-lived where possible
```

Jangan gunakan satu global API token untuk seluruh admin.

---

# 45. Rate Limiting

Rate limit wajib pada:

```text
login
credential verification
voter authentication
vote endpoint
credential reset
QR/token verification
```

---

# 46. Rate Limiting Strategy

Rate limit berdasarkan kombinasi:

```text
IP
credential identifier
account/eligibility where safe
session
```

Jangan hanya berdasarkan IP karena:

```text
school NAT
shared network
```

dapat menyebabkan false positive.

---

# 47. Brute Force Protection

Jika credential salah berulang:

```text
progressive delay
temporary lock
rate limit
audit security event
```

Exact thresholds harus configurable.

---

# 48. Account Enumeration

Login error tidak boleh membocorkan:

```text
whether student exists
whether credential exists
```

Gunakan generic error:

```text
Invalid credentials.
```

---

# 49. Credential Enumeration

API tidak boleh memberikan detail seperti:

```text
student exists
credential expired
credential belongs to another election
```

kepada unauthenticated attacker.

Response dapat dibuat generic:

```text
Invalid or unavailable credential.
```

---

# 50. Password Reset

Admin password reset harus:

```text
secure reset token
expiration
single use
rate limit
audit
```

Voter credential reset mengikuti credential lifecycle, bukan generic password reset.

---

# 51. Admin Account Lock

Lock policy dapat digunakan:

```text
temporary lock after repeated failures
```

Tetapi harus mencegah attacker mengunci account secara permanen dengan mudah.

Gunakan:

```text
progressive delay
temporary lock
```

daripada permanent lockout sebagai satu-satunya defense.

---

# 52. MFA

MFA recommended untuk:

```text
ADMIN
```

Optional/configurable:

```text
OPERATOR
```

Voter MFA tidak wajib untuk MVP jika credential + operational controls sudah memenuhi threat model.

---

# 53. MFA Recovery

MFA recovery harus:

```text
audited
strongly verified
rare
```

Jangan menyediakan:

```text
"disable MFA" via simple admin button
```

tanpa re-authentication.

---

# 54. Re-authentication

Sensitive admin operations dapat meminta re-authentication:

```text
manage administrators
rotate credentials
change security settings
export sensitive data
```

---

# 55. Privilege Escalation

Server harus menolak:

```text
operator
→ admin
```

melalui:

```text
request payload
hidden field
mass assignment
route parameter
```

Role assignment harus melalui explicit authorization.

---

# 56. Mass Assignment Protection

Jangan:

```php
User::create($request->all());
```

untuk role-bearing input.

Gunakan explicit fields:

```php
User::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
]);
```

Role diberikan melalui authorized domain operation.

---

# 57. Admin User Management

Jika Admin dapat membuat Operator:

```text
authorize
validate
create
audit
```

Password/credential tidak boleh muncul di audit log.

---

# 58. Operator Scope

Jika operator dibatasi pada election tertentu, authorization harus mendukung:

```text
operator
→ assigned election
```

Jangan hanya role-based:

```text
operator = access all elections
```

jika operational scope membutuhkan isolation.

---

# 59. Election-Level Authorization

Model dapat menggunakan:

```text
election_operator_assignments
```

jika operator hanya boleh mengelola election tertentu.

Contoh:

```text
operator_id
election_id
```

dengan unique constraint:

```text
UNIQUE(operator_id, election_id)
```

Implementasi final tergantung ERD.

---

# 60. Voter Authorization Boundary

Voter authorization harus menggunakan:

```text
eligibility
```

bukan hanya:

```text
authenticated user
```

Karena:

```text
authenticated ≠ eligible
```

---

# 61. Voting Authorization Flow

```text
Authenticate
    ↓
Resolve eligibility
    ↓
Verify election
    ↓
Verify election is open
    ↓
Verify eligibility active
    ↓
CastVote transaction
```

---

# 62. Important: Authorization vs Vote Transaction

Pre-check:

```text
allowed to attempt vote
```

bukan jaminan:

```text
vote will succeed
```

Final eligibility check harus dilakukan di authoritative transaction.

Detail ada pada:

```text
15_VOTING_ENGINE_SPEC.md
```

---

# 63. Session Authorization After Vote

Setelah berhasil vote:

```text
eligibility = voted
```

Voter session tidak boleh dapat membuat vote kedua.

Tetapi server tetap wajib memverifikasi database pada setiap attempt.

---

# 64. Logout After Vote

Recommended:

```text
successful vote
 ↓
confirmation
 ↓
invalidate voter session
```

Ini mengurangi risiko reuse session pada shared computer.

---

# 65. Shared Computer / TPS Mode

Jika operator membantu voter:

```text
operator session
```

dan:

```text
voter session
```

harus tetap terpisah.

Operator tidak boleh mendapatkan akses ke ballot choice.

---

# 66. Assisted Voting

Jika TPS mode digunakan:

```text
operator authenticates voter
```

tetapi:

```text
operator must not choose candidate on behalf of voter
```

kecuali policy sekolah secara eksplisit mengizinkan assisted voting dengan safeguards.

---

# 67. Kiosk Mode

Jika voting menggunakan kiosk:

```text
short-lived voter session
auto logout
screen reset
browser storage cleanup
```

Setelah vote:

```text
session invalidated
```

---

# 68. Browser Storage

Jangan simpan voter credential di:

```text
localStorage
```

jika dapat dihindari.

Jangan simpan:

```text
candidate choice
```

di persistent browser storage.

---

# 69. Cookie Security

Session cookie:

```text
Secure
HttpOnly
SameSite
```

konfigurasi sesuai deployment.

---

# 70. HTTPS

Authentication dan voting harus selalu menggunakan:

```text
HTTPS
```

Production tidak boleh menerima credential melalui HTTP plaintext.

---

# 71. HSTS

Production dapat menggunakan:

```text
Strict-Transport-Security
```

setelah HTTPS configuration benar dan domain policy siap.

---

# 72. Security Headers

Recommended:

```text
Content-Security-Policy
X-Content-Type-Options
Referrer-Policy
Frame-ancestors / X-Frame-Options
```

Policy final harus kompatibel dengan UI.

---

# 73. Clickjacking Protection

Voting page sebaiknya tidak dapat di-iframe oleh origin asing.

Gunakan:

```text
CSP frame-ancestors
```

atau equivalent.

---

# 74. XSS Protection

User-generated fields:

```text
candidate name
vision
mission
election description
```

harus escaped/sanitized.

Jangan render raw HTML tanpa kebutuhan.

---

# 75. Authentication Audit Events

Audit events:

```text
ADMIN_LOGIN_SUCCESS
ADMIN_LOGIN_FAILURE
ADMIN_LOGOUT
VOTER_LOGIN_SUCCESS
VOTER_LOGIN_FAILURE
CREDENTIAL_REVOKED
CREDENTIAL_ISSUED
MFA_ENABLED
MFA_DISABLED
```

Pastikan metadata privacy-safe.

---

# 76. Security Event Logging

Security events dapat mencatat:

```text
timestamp
event type
actor/session reference
IP
user agent
request ID
```

Tetapi jangan log:

```text
password
PIN plaintext
token plaintext
candidate choice
```

---

# 77. Login IP Logging

IP address dapat dianggap sensitive operational data.

Retention harus mengikuti:

```text
privacy policy
school policy
legal requirements
```

---

# 78. Authentication Error Handling

Internal:

```text
detailed reason
```

External:

```text
generic message
```

Contoh:

```text
Internal:
credential expired

External:
Unable to authenticate with the provided credential.
```

---

# 79. Authentication Monitoring

Monitor:

```text
failed login rate
credential failures
rate limit triggers
account lock events
suspicious IP activity
```

Alert threshold harus configurable.

---

# 80. Abuse Scenarios

Minimal threat cases:

```text
brute force PIN
credential enumeration
credential replay
stolen QR
session theft
session fixation
IDOR
privilege escalation
CSRF
XSS
admin account takeover
```

Semua harus tercakup pada threat model/test plan.

---

# 81. Credential Replay

Jika credential memang one-time:

```text
after successful vote
→ invalidate/revoke credential
```

Jika credential digunakan untuk login session dan vote terpisah:

```text
session can exist
but vote transaction checks eligibility
```

Detail final mengikuti voting engine.

---

# 82. Stolen Credential

Jika token/PIN dicuri sebelum vote:

attacker dapat mencoba menggunakannya.

Mitigasi:

```text
rate limiting
short credential validity
secondary verification where necessary
operator monitoring
credential revocation
```

---

# 83. Credential Delivery

Jika credential dikirim:

```text
print
school-controlled distribution
secure messaging
QR
```

jangan menaruh credential dalam:

```text
public URL
unprotected spreadsheet
shared admin chat
logs
```

---

# 84. Bulk Credential Export

Jika export credential diperlukan:

```text
authorized admin only
one-time generated file
encrypted storage
short retention
audit event
```

Idealnya credential didistribusikan tanpa membuat long-lived plaintext export.

---

# 85. Admin Export Authorization

Export hasil:

```text
result.export
```

Export voter data:

```text
voter.export
```

harus merupakan permission berbeda jika data sensitivity berbeda.

---

# 86. Sensitive Data Minimization

UI/API hanya mengembalikan field yang dibutuhkan.

Voter tidak membutuhkan:

```text
internal IDs
credential hashes
audit metadata
```

---

# 87. API Response Rules

Jangan expose:

```text
password
password_hash
credential_hash
session internals
private audit metadata
```

gunakan API Resources/DTO.

---

# 88. Re-authentication Before Result Export

Jika result export sangat sensitive, Admin dapat diminta:

```text
recent authentication
```

tergantung threat model.

---

# 89. Authorization Testing

Setiap sensitive endpoint minimal memiliki:

```text
admin allowed
operator allowed/denied
voter denied
unauthenticated denied
wrong election denied
```

---

# 90. Auth Test Matrix

Contoh:

```text
[ ] unauthenticated → admin dashboard = 401/redirect
[ ] voter → admin dashboard = 403
[ ] operator → user management = 403
[ ] voter → other voter data = 403/404
[ ] voter → closed election = denied
[ ] voter → another election = denied
```

---

# 91. Voter Privacy Test

Test harus memastikan:

```text
voter API response
session
logs
audit
```

tidak mengungkap candidate choice.

---

# 92. Session Security Test

Test:

```text
session ID regenerated after login
logout invalidates session
expired session rejected
cross-role session rejected
```

---

# 93. Rate Limit Test

Test:

```text
repeated credential failure
→ rate limit
```

dan:

```text
valid credential after rate limit
```

harus tetap dapat bekerja setelah cooldown sesuai policy.

---

# 94. Authorization Policy Test

Setiap Policy harus memiliki test.

Contoh:

```text
ElectionPolicyTest
CandidatePolicyTest
VoterPolicyTest
ResultPolicyTest
ExportPolicyTest
```

---

# 95. Security Review Checklist

Sebelum auth feature dianggap ready:

```text
[ ] password hashing
[ ] credential hashing
[ ] session regeneration
[ ] CSRF
[ ] HTTPS
[ ] rate limiting
[ ] authorization
[ ] IDOR protection
[ ] privilege escalation protection
[ ] secure cookies
[ ] generic auth errors
[ ] security logging
[ ] no sensitive logs
[ ] privacy review
```

---

# 96. Recommended Laravel Mapping

| Requirement | Laravel mechanism |
|---|---|
| Authentication | Laravel Auth |
| Password hashing | Hash facade |
| Authorization | Policies / Gates |
| Validation | Form Requests |
| CSRF | VerifyCsrfToken / framework defaults |
| Session | Laravel Session |
| Rate limiting | Laravel RateLimiter |
| Encryption | Crypt |
| Password reset | Laravel password broker jika digunakan |
| Events | Laravel Events |
| Audit | Custom domain implementation |

---

# 97. Guard Strategy

Recommended starting point:

```text
web
```

untuk admin/operator.

Jika voter authentication membutuhkan isolation:

```text
voter
```

guard dapat digunakan.

Jangan menambah guard hanya untuk naming preference.

---

# 98. Middleware Structure

Recommended:

```text
auth
guest
verified
role
permission
election.context
voter.session
throttle
```

Custom middleware harus memiliki satu responsibility.

---

# 99. Voter Middleware

Contoh:

```text
auth:voter
voter.session
```

Kemudian controller memanggil:

```text
CastVote
```

untuk authoritative validation.

Middleware tidak boleh menjadi satu-satunya defense terhadap double voting.

---

# 100. Admin Middleware

Contoh:

```text
auth:web
role:admin
```

atau Policy-based authorization.

---

# 101. Operator Middleware

Contoh:

```text
auth:web
role:operator
```

ditambah:

```text
policy
```

untuk resource/election scope.

---

# 102. Permission Check

Jika permission system digunakan:

```php
$user->can('election.open')
```

Namun final authorization harus tetap memiliki resource context.

---

# 103. Policy Example

Conceptual:

```php
public function update(User $user, Election $election): bool
{
    return $user->isAdmin()
        || $user->isOperatorFor($election);
}
```

Actual implementation mengikuti project architecture.

---

# 104. Voter Policy

Voter policy harus memeriksa:

```text
authenticated voter
+
eligibility
+
same election
```

bukan hanya:

```text
$user->id === $voter->id
```

---

# 105. Auth Data Model

Minimal:

```text
users
voters
voter_eligibilities
credentials
```

Relationship:

```text
User
  └── admin/operator identity

Voter
  └── voter identity

VoterEligibility
  ├── election
  └── voter

Credential
  └── voter eligibility
```

---

# 106. Privacy Boundary

Authentication layer mengetahui:

```text
who is eligible
```

Voting layer mengetahui:

```text
vote being cast
```

Ballot storage tidak boleh menyimpan:

```text
who voted for whom
```

---

# 107. Important Architectural Rule

Jangan membuat:

```text
Ballot
  belongsTo(Voter::class)
```

hanya karena mudah untuk query.

Convenience relationship tidak boleh mengalahkan privacy architecture.

---

# 108. Admin Visibility

Admin dapat melihat:

```text
voter participation status
```

contoh:

```text
671 voted
171 not voted
```

tetapi tidak boleh melihat:

```text
A → Candidate 02
B → Candidate 01
```

---

# 109. Voter Status Visibility

Voter hanya boleh melihat status dirinya sendiri:

```text
already voted
```

bukan:

```text
other voter status
```

jika policy privasi melarangnya.

---

# 110. Re-authentication After Long Idle

Jika admin idle terlalu lama:

```text
session expires
```

dan sensitive actions meminta login kembali.

---

# 111. Account Deactivation

Admin/operator dapat dinonaktifkan:

```text
is_active = false
```

atau equivalent.

Disabled account:

```text
cannot authenticate
```

Existing session harus diinvalidasi pada strategy yang dipilih.

---

# 112. Voter Deactivation

Voter dapat inactive sebelum election.

Namun jika eligibility sudah created:

```text
policy harus menentukan
```

apakah deactivation:

```text
revokes eligibility
```

atau hanya mencegah future elections.

Jangan mengubah historical ballot.

---

# 113. Election-Specific Eligibility

Eligibility harus authoritative untuk election.

Contoh:

```text
Voter A
Election 2026
eligible

Voter A
Election 2027
eligible
```

status voting tidak boleh global pada voter.

---

# 114. Credential Scope

Credential sebaiknya scoped ke:

```text
voter eligibility
```

sehingga credential Election A tidak dapat digunakan untuk Election B.

---

# 115. Cross-Election Credential Test

Test:

```text
credential for Election A
+
Election B
=
DENIED
```

---

# 116. Token Scope

Opaque token harus memiliki enough server-side context untuk menentukan:

```text
which eligibility
which election
```

tanpa menaruh sensitive information di token.

---

# 117. Token Rotation

Jika token reusable untuk session:

```text
session token
```

dan:

```text
credential
```

harus dibedakan.

Credential bukan session identifier.

---

# 118. Authentication vs Authorization Logging

Log:

```text
login success/failure
authorization denied
security events
```

tetapi jangan log:

```text
vote choice
```

sebagai bagian dari authorization trace.

---

# 119. Security Incident Response

Jika credential compromise terdeteksi:

```text
revoke credential
 ↓
issue replacement
 ↓
audit
 ↓
monitor
```

Jika admin compromise:

```text
disable account
rotate credentials
revoke sessions/tokens
review audit logs
```

---

# 120. Final Mandatory Rules

```text
1. Admin and voter identities are separate security boundaries.
2. Authentication never implies authorization.
3. Voter eligibility is election-specific.
4. Credential is scoped to eligibility.
5. Credential plaintext is never stored.
6. Ballot has no direct voter identity relationship.
7. Final vote authorization occurs inside the voting transaction.
8. All sensitive endpoints require server-side authorization.
9. Rate limiting is mandatory for authentication/voting endpoints.
10. Successful voting should invalidate or consume the voting credential/session according to final design.
11. Admin cannot edit/delete ballot records through normal UI.
12. No authentication or audit log may reveal voter → candidate choice.
```

---

# 121. Next Document

Dokumen paling penting berikutnya:

```text
15_VOTING_ENGINE_SPEC.md
```

Dokumen tersebut akan menjadi blueprint implementasi inti:

```text
Authenticate voter
      ↓
Resolve eligibility
      ↓
Verify election state
      ↓
Verify candidate
      ↓
BEGIN TRANSACTION
      ↓
LOCK eligibility
      ↓
Re-check eligibility
      ↓
Create anonymous ballot
      ↓
Mark eligibility as voted
      ↓
COMMIT
      ↓
Invalidate voting credential/session
      ↓
Return confirmation
```

Dokumen tersebut harus menjadi acuan utama sebelum `CastVote` diimplementasikan.
