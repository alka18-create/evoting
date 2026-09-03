# API Specification — Sistem E-Voting Sekolah

**Dokumen:** 05 — API Specification  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:**
- `01_PRD`
- `02_ERD / Database Design`
- `03_SECURITY_Threat_Model`
- `04_ARCHITECTURE`

---

# 1. Tujuan

Dokumen ini mendefinisikan kontrak API aplikasi e-voting sekolah.

API harus:

- Konsisten.
- Aman.
- Mudah diuji.
- Tidak membocorkan identitas pemilih dan pilihan kandidat.
- Mendukung web/Livewire maupun client lain bila dibutuhkan.
- Memiliki authorization yang jelas.
- Memiliki error response yang konsisten.
- Menjamin proses voting atomic.

---

# 2. API Style

Gunakan:

```text
RESTful HTTP API
JSON
HTTPS only
```

Base URL production:

```text
https://<domain>/api/v1
```

Contoh:

```text
GET /api/v1/elections
```

---

# 3. Authentication Model

Terdapat dua jenis authentication.

## 3.1 Admin / Operator

Menggunakan:

```text
Session Authentication
```

atau token authentication jika aplikasi nantinya membutuhkan SPA/mobile client.

Untuk Laravel + Livewire, session authentication direkomendasikan.

---

## 3.2 Voter

Voter menggunakan:

```text
Voting Credential
```

Credential dapat berasal dari:

- Token.
- PIN.
- QR Code.

Credential digunakan untuk memperoleh voting session.

---

# 4. Authorization

Role:

```text
SUPER_ADMIN
ADMIN
OPERATOR
VOTER
```

Authorization menggunakan:

```text
Middleware
+
Policies
+
Gates
```

Jangan mengandalkan frontend untuk authorization.

---

# 5. Common Headers

Request JSON:

```http
Accept: application/json
Content-Type: application/json
```

Untuk mutation:

```http
X-CSRF-TOKEN: <token>
```

Jika menggunakan session-based authentication.

---

# 6. Common Response Format

## Success

```json
{
  "success": true,
  "data": {}
}
```

## Success with message

```json
{
  "success": true,
  "message": "Operation completed.",
  "data": {}
}
```

## Error

```json
{
  "success": false,
  "message": "Request could not be processed.",
  "errors": {}
}
```

---

# 7. HTTP Status Codes

| Status | Penggunaan |
|---|---|
| 200 | Successful read/update |
| 201 | Resource created |
| 204 | Successful delete/no content |
| 400 | Invalid request |
| 401 | Unauthenticated |
| 403 | Unauthorized |
| 404 | Resource not found |
| 409 | Conflict |
| 422 | Validation error |
| 429 | Rate limited |
| 500 | Internal error |
| 503 | Service unavailable |

---

# 8. Security Error Policy

Error response tidak boleh membocorkan:

- Voter identity.
- Credential existence secara berlebihan.
- Candidate selection.
- Database identifiers yang sensitif.
- Internal SQL error.
- Stack trace.
- Internal infrastructure.

Production tidak boleh mengembalikan:

```text
APP_DEBUG=true
```

---

# 9. API Versioning

Gunakan:

```text
/api/v1
```

Jika terdapat breaking change:

```text
/api/v2
```

Jangan mengubah kontrak `v1` secara breaking tanpa migration strategy.

---

# 10. Authentication Endpoints

## 10.1 Admin Login

```http
POST /api/v1/auth/login
```

Request:

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Response:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator",
      "role": "ADMIN"
    }
  }
}
```

Security:

- Rate limited.
- Session regenerated.
- Generic error message.
- Password tidak pernah dikembalikan.

---

# 11. Admin Logout

```http
POST /api/v1/auth/logout
```

Response:

```json
{
  "success": true,
  "message": "Logout successful."
}
```

---

# 12. Current User

```http
GET /api/v1/auth/me
```

Response:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Administrator",
    "role": "ADMIN"
  }
}
```

---

# 13. Election API

## 13.1 List Elections

```http
GET /api/v1/elections
```

Query:

```text
?page=1
&per_page=20
&status=DRAFT
```

Response:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 10
  }
}
```

---

# 14. Get Election

```http
GET /api/v1/elections/{election}
```

Response:

```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "Pemilihan Ketua OSIM 2026",
    "status": "DRAFT",
    "starts_at": "2026-09-01T07:00:00+07:00",
    "ends_at": "2026-09-01T13:00:00+07:00"
  }
}
```

---

# 15. Create Election

```http
POST /api/v1/elections
```

Permission:

```text
SUPER_ADMIN
ADMIN
```

Request:

```json
{
  "name": "Pemilihan Ketua OSIM 2026",
  "description": "Pemilihan ketua OSIM periode 2026/2027",
  "starts_at": "2026-09-01T07:00:00+07:00",
  "ends_at": "2026-09-01T13:00:00+07:00"
}
```

Response:

```http
201 Created
```

```json
{
  "success": true,
  "message": "Election created.",
  "data": {
    "id": 10,
    "status": "DRAFT"
  }
}
```

---

# 16. Update Election

```http
PUT /api/v1/elections/{election}
```

Hanya boleh dilakukan sebelum election `OPEN`.

Request:

```json
{
  "name": "Pemilihan Ketua OSIM 2026",
  "description": "Updated description",
  "starts_at": "2026-09-01T07:00:00+07:00",
  "ends_at": "2026-09-01T13:00:00+07:00"
}
```

---

# 17. Election State Transition

Election state:

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

Tidak boleh melakukan arbitrary update:

```text
PATCH status
```

Gunakan endpoint action khusus.

---

# 18. Schedule Election

```http
POST /api/v1/elections/{election}/schedule
```

Permission:

```text
ADMIN
```

---

# 19. Open Election

```http
POST /api/v1/elections/{election}/open
```

Validasi:

- Election valid.
- Candidate tersedia.
- Voter tersedia.
- Configuration lengkap.
- Current state memungkinkan transition.
- Tidak ada konflik schedule.

Response:

```json
{
  "success": true,
  "message": "Election opened.",
  "data": {
    "id": 10,
    "status": "OPEN"
  }
}
```

---

# 20. Close Election

```http
POST /api/v1/elections/{election}/close
```

Setelah CLOSED:

- Voting ditolak.
- Candidate mutation ditolak.
- Results tersedia.
- Export tersedia.

---

# 21. Archive Election

```http
POST /api/v1/elections/{election}/archive
```

Archive hanya dapat dilakukan setelah CLOSED.

---

# 22. Candidate API

## 22.1 List Candidates

```http
GET /api/v1/elections/{election}/candidates
```

Response:

```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "number": 1,
      "name": "Ahmad",
      "photo_url": "...",
      "vision": "...",
      "mission": []
    }
  ]
}
```

Untuk voter, endpoint ini hanya boleh mengembalikan kandidat yang valid untuk election tersebut.

---

# 23. Create Candidate

```http
POST /api/v1/elections/{election}/candidates
```

Request:

```json
{
  "number": 1,
  "name": "Ahmad",
  "vision": "Visi kandidat",
  "mission": [
    "Misi pertama",
    "Misi kedua"
  ]
}
```

---

# 24. Update Candidate

```http
PUT /api/v1/elections/{election}/candidates/{candidate}
```

Tidak boleh dilakukan jika election sudah `OPEN`.

---

# 25. Delete Candidate

```http
DELETE /api/v1/elections/{election}/candidates/{candidate}
```

Tidak boleh dilakukan jika election `OPEN`.

---

# 26. Voter API

## 26.1 List Voters

```http
GET /api/v1/elections/{election}/voters
```

Permission:

```text
ADMIN
OPERATOR
```

Response admin:

```json
{
  "success": true,
  "data": [
    {
      "id": 1001,
      "student_id": "S001",
      "name": "Siswa A",
      "class": "XII IPA 1",
      "voting_status": "NOT_VOTED"
    }
  ]
}
```

---

# 27. Voter Detail

```http
GET /api/v1/elections/{election}/voters/{voter}
```

Tidak boleh mengembalikan:

```text
candidate_id
ballot_id
```

karena voter endpoint tidak boleh memiliki hubungan pilihan suara.

---

# 28. Import Voters

```http
POST /api/v1/elections/{election}/voters/import
```

Content type:

```text
multipart/form-data
```

Field:

```text
file
```

Supported:

```text
CSV
XLSX
```

Response:

```json
{
  "success": true,
  "message": "Import queued.",
  "data": {
    "job_id": "..."
  }
}
```

Untuk file besar, proses dilakukan melalui queue.

---

# 29. Import Result

```http
GET /api/v1/imports/{import}
```

Response:

```json
{
  "success": true,
  "data": {
    "status": "COMPLETED",
    "total": 842,
    "success": 840,
    "failed": 2
  }
}
```

---

# 30. Generate Voting Credentials

```http
POST /api/v1/elections/{election}/credentials/generate
```

Permission:

```text
ADMIN
OPERATOR
```

Request:

```json
{
  "regenerate": false
}
```

Credential plaintext hanya tersedia saat proses issuance dan tidak boleh disimpan plaintext.

---

# 31. Credential Issuance

Response untuk operator/admin:

```json
{
  "success": true,
  "data": {
    "issued": 842
  }
}
```

Jika sistem menyediakan download credential, file harus dibuat melalui secure export process.

---

# 32. Credential Security

API tidak boleh menyediakan endpoint:

```http
GET /credentials/{id}/plaintext
```

Setelah credential diterbitkan, plaintext tidak dapat diambil kembali dari database.

---

# 33. Voter Voting Entry

```http
POST /api/v1/voting/session
```

Request:

```json
{
  "credential": "RANDOM-TOKEN"
}
```

Response sukses:

```json
{
  "success": true,
  "data": {
    "election": {
      "id": 10,
      "name": "Pemilihan Ketua OSIM 2026"
    },
    "expires_at": "2026-09-01T12:15:00+07:00"
  }
}
```

Response tidak boleh memberikan:

```text
student_id
class
credential database id
```

kepada browser jika tidak diperlukan.

---

# 34. Candidate List for Voter

```http
GET /api/v1/voting/candidates
```

Authentication:

```text
Voting Session
```

Response:

```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "number": 1,
      "name": "Ahmad",
      "photo_url": "...",
      "vision": "...",
      "mission": []
    }
  ]
}
```

---

# 35. Cast Vote

Ini adalah endpoint paling kritis.

```http
POST /api/v1/voting/cast
```

Request:

```json
{
  "candidate_id": 101
}
```

Authentication:

```text
Voting Session
```

---

# 36. Cast Vote Server Flow

Server melakukan:

```text
1. Authenticate voting session
2. Validate election
3. Validate election = OPEN
4. Lock credential
5. Verify credential unused
6. Validate candidate belongs to election
7. Begin transaction
8. Create anonymous ballot
9. Mark credential USED
10. Mark voter VOTED
11. Commit
12. Dispatch aggregate progress event
13. Return confirmation
```

---

# 37. Cast Vote Success

```http
200 OK
```

Response:

```json
{
  "success": true,
  "message": "Suara berhasil disimpan.",
  "data": {
    "confirmation": "8F4A-2C91"
  }
}
```

Confirmation:

- Tidak mengandung voter identity.
- Tidak mengandung candidate ID.
- Tidak dapat digunakan untuk mencari pilihan kandidat.

---

# 38. Cast Vote Conflict

Jika credential sudah digunakan:

```http
409 Conflict
```

Response:

```json
{
  "success": false,
  "message": "Voting tidak dapat diproses."
}
```

Jangan mengembalikan:

```text
credential_id
student_id
previous_vote
```

---

# 39. Cast Vote When Closed

```http
409 Conflict
```

Response:

```json
{
  "success": false,
  "message": "Voting tidak sedang dibuka."
}
```

---

# 40. Invalid Candidate

Jika candidate tidak termasuk election:

```http
422 Unprocessable Entity
```

Response:

```json
{
  "success": false,
  "message": "Pilihan tidak valid."
}
```

Jangan mengungkap candidate existence pada election lain.

---

# 41. Double Vote Protection

Double voting harus ditangani di:

```text
Application validation
+
Database constraint
+
Row locking
+
Transaction
```

Bukan hanya frontend.

---

# 42. Idempotency

Endpoint voting:

```http
POST /voting/cast
```

harus mendukung perlindungan terhadap retry/concurrent request.

Recommended header:

```http
Idempotency-Key: <random-uuid>
```

Server menyimpan hasil request secara aman selama transaction/retry window.

Idempotency record tidak boleh menghubungkan voter identity dengan candidate choice dalam audit log.

---

# 43. Idempotency Rules

Jika request yang sama dikirim ulang dengan:

```text
same session
same Idempotency-Key
same payload
```

server dapat mengembalikan confirmation sebelumnya.

Jika key digunakan dengan payload berbeda:

```http
409 Conflict
```

---

# 44. Result API

## 44.1 Result Summary

```http
GET /api/v1/elections/{election}/results
```

Hanya tersedia:

```text
CLOSED
```

Response:

```json
{
  "success": true,
  "data": {
    "election_id": 10,
    "total_voters": 842,
    "total_votes": 671,
    "participation_percentage": 79.69,
    "candidates": [
      {
        "candidate_id": 101,
        "number": 1,
        "name": "Ahmad",
        "votes": 282,
        "percentage": 42.03
      }
    ]
  }
}
```

---

# 45. Result Before Close

Request:

```http
GET /api/v1/elections/10/results
```

Jika election OPEN:

```http
403 Forbidden
```

atau:

```http
409 Conflict
```

Recommended:

```http
409 Conflict
```

Response:

```json
{
  "success": false,
  "message": "Hasil belum tersedia."
}
```

---

# 46. Participation API

Monitoring selama election OPEN hanya mengembalikan aggregate.

```http
GET /api/v1/elections/{election}/participation
```

Response:

```json
{
  "success": true,
  "data": {
    "total_voters": 842,
    "voted": 671,
    "remaining": 171,
    "percentage": 79.69
  }
}
```

Tidak ada:

```text
voter list
candidate result
```

pada endpoint ini.

---

# 47. Class / Group Participation

Jika fitur kelas/TPS digunakan:

```http
GET /api/v1/elections/{election}/participation/groups
```

Response:

```json
{
  "success": true,
  "data": [
    {
      "group": "XII IPA 1",
      "total": 40,
      "voted": 31,
      "remaining": 9,
      "percentage": 77.5
    }
  ]
}
```

Jika kelompok terlalu kecil, pertimbangkan menyembunyikan statistik untuk mengurangi risiko correlation.

---

# 48. Realtime Subscription

Channel:

```text
private-election.{election}.monitoring
```

Authorization:

```text
ADMIN
OPERATOR
```

Event:

```text
VotingProgressUpdated
```

Payload:

```json
{
  "voted": 671,
  "remaining": 171,
  "percentage": 79.69
}
```

Tidak boleh:

```text
candidate_id
voter_id
student_id
credential
```

---

# 49. Export Result

```http
POST /api/v1/elections/{election}/exports
```

Request:

```json
{
  "format": "xlsx"
}
```

Supported:

```text
xlsx
pdf
csv
```

Hanya setelah election CLOSED.

---

# 50. Export Response

Untuk file kecil:

```http
200 OK
```

Untuk file besar:

```http
202 Accepted
```

Response:

```json
{
  "success": true,
  "message": "Export queued.",
  "data": {
    "job_id": "..."
  }
}
```

---

# 51. Export Status

```http
GET /api/v1/exports/{export}
```

Response:

```json
{
  "success": true,
  "data": {
    "status": "COMPLETED",
    "download_available": true
  }
}
```

---

# 52. Secure Download

```http
GET /api/v1/exports/{export}/download
```

Authorization wajib diterapkan.

Download URL harus:

- Expiring.
- Tidak predictable.
- Tidak dapat diakses publik tanpa authorization.

---

# 53. Audit Log API

```http
GET /api/v1/audit-logs
```

Permission:

```text
SUPER_ADMIN
ADMIN
```

Filter:

```text
election
actor
action
date_from
date_to
```

---

# 54. Audit Log Response

```json
{
  "success": true,
  "data": [
    {
      "id": 9001,
      "action": "ELECTION_CLOSED",
      "actor": {
        "id": 1,
        "name": "Administrator"
      },
      "created_at": "2026-09-01T13:01:00+07:00"
    }
  ]
}
```

Audit log tidak boleh menampilkan pilihan suara individual.

---

# 55. Health API

```http
GET /api/v1/health
```

Public response:

```json
{
  "status": "ok"
}
```

Jangan mengembalikan:

```text
database hostname
Redis hostname
version internal
credentials
```

---

# 56. Admin Dashboard API

Jika dashboard menggunakan API:

```http
GET /api/v1/dashboard
```

Response:

```json
{
  "success": true,
  "data": {
    "elections": {
      "total": 4,
      "open": 1,
      "closed": 2,
      "draft": 1
    },
    "active": {
      "id": 10,
      "name": "Pemilihan Ketua OSIM 2026"
    }
  }
}
```

---

# 57. Pagination

Gunakan:

```text
?page=1
&per_page=20
```

Batas maksimum:

```text
per_page <= 100
```

Jangan menerima:

```text
per_page=1000000
```

---

# 58. Filtering

Gunakan query parameter.

Contoh:

```http
GET /api/v1/elections/10/voters?status=NOT_VOTED&class=XII%20IPA%201
```

Filter harus divalidasi whitelist.

---

# 59. Sorting

Contoh:

```http
GET /api/v1/elections?sort=-created_at
```

Hanya field yang diizinkan.

Jangan langsung memasukkan user input ke SQL `ORDER BY`.

---

# 60. Search

Contoh:

```http
GET /api/v1/elections/10/voters?search=Ahmad
```

Search harus:

- Parameterized.
- Rate limited.
- Tidak melakukan full table scan tanpa kontrol pada dataset besar.

---

# 61. Resource Scoping

Endpoint nested:

```text
/elections/{election}/candidates/{candidate}
```

harus memastikan:

```text
candidate.election_id == election.id
```

Jangan hanya mencari:

```php
Candidate::find($candidateId)
```

tanpa memeriksa parent resource.

---

# 62. IDOR Protection

Contoh serangan:

```http
GET /api/v1/elections/10/voters/100
```

kemudian mengganti:

```text
10 → 11
```

Server harus memverifikasi:

- User memiliki akses election 11.
- Voter 100 memang bagian dari election 11.

---

# 63. Mass Assignment Protection

Request tidak boleh bebas mengisi:

```text
id
created_at
updated_at
status
voted_at
```

Field tersebut dikontrol server.

---

# 64. Election Status Protection

Jangan menyediakan endpoint generic:

```http
PATCH /elections/{id}
```

dengan:

```json
{
  "status": "CLOSED"
}
```

Gunakan action endpoint:

```text
POST /open
POST /close
POST /archive
```

agar state transition tervalidasi.

---

# 65. Candidate Status Protection

Candidate tidak boleh:

- Diubah setelah election OPEN.
- Dihapus setelah election OPEN.
- Dipindahkan ke election lain setelah election OPEN.

---

# 66. Voter Status Protection

Client tidak boleh mengirim:

```json
{
  "voting_status": "VOTED"
}
```

Voter status hanya berubah sebagai konsekuensi dari successful voting transaction.

---

# 67. Ballot API Restriction

Tidak ada public endpoint:

```text
POST /ballots
GET /ballots/{id}
PUT /ballots/{id}
DELETE /ballots/{id}
```

Ballot hanya dibuat oleh:

```text
VotingService
```

dan digunakan internal oleh ResultService.

---

# 68. Ballot Immutability

Setelah ballot dibuat:

```text
UPDATE = prohibited
DELETE = prohibited
```

kecuali mekanisme administrative recovery yang sangat terbatas dan diaudit secara khusus.

Untuk MVP, rekomendasi:

```text
No ballot modification.
No ballot deletion.
```

---

# 69. Credential Endpoint Restriction

Jangan expose:

```http
GET /credentials
```

ke voter.

Credential management hanya untuk:

```text
ADMIN
OPERATOR
```

dan hanya metadata minimum.

---

# 70. QR Code API

Jika QR digunakan:

```http
POST /api/v1/elections/{election}/credentials/qr
```

QR berisi random token/reference.

Jangan encode:

```text
student_id
name
class
candidate
```

---

# 71. QR Scan Flow

```text
Scan QR
   ↓
Open voting page
   ↓
Credential validation
   ↓
Create voting session
   ↓
Display candidates
```

QR bukan bukti final bahwa suara boleh diberikan.

Server tetap melakukan eligibility check.

---

# 72. Rate Limits

Recommended initial values:

| Endpoint | Limit |
|---|---:|
| Login | 5/min/IP |
| Credential session | 10/min/IP |
| Cast vote | 10/min/session |
| Public health | 60/min/IP |
| Admin CRUD | 120/min/user |
| Import | 10/hour/admin |
| Export | 20/hour/admin |

Angka harus dikalibrasi melalui load test dan kondisi deployment sekolah.

---

# 73. CSRF

Untuk session-based browser requests:

```text
CSRF protection = required
```

Jangan menonaktifkan CSRF untuk endpoint voting hanya karena endpoint dianggap "API".

---

# 74. CORS

Default:

```text
deny cross-origin
```

Jika API benar-benar membutuhkan cross-origin:

```text
allowlist specific origins
```

Jangan:

```text
Access-Control-Allow-Origin: *
```

untuk authenticated API.

---

# 75. Security Headers

Production harus menggunakan:

```text
Content-Security-Policy
X-Content-Type-Options
Referrer-Policy
Permissions-Policy
Strict-Transport-Security
```

Konfigurasi harus disesuaikan dengan asset/CDN yang benar-benar digunakan.

---

# 76. Input Validation

Semua input divalidasi:

```text
Type
Length
Format
Allowed values
Business rules
```

Contoh candidate number:

```text
integer
positive
unique per election
```

---

# 77. File Upload Security

Import:

```text
CSV
XLSX
```

Validasi:

- MIME.
- Extension.
- File size.
- Content structure.
- Row limit.

File tidak boleh langsung dieksekusi.

---

# 78. API Time Handling

Gunakan ISO 8601:

```text
2026-09-01T07:00:00+07:00
```

Server menyimpan timestamp dalam timezone yang konsisten, direkomendasikan:

```text
UTC
```

Display dikonversi ke timezone sekolah:

```text
Asia/Jakarta
```

---

# 79. Concurrency

Endpoint kritis:

```text
POST /voting/cast
POST /elections/{id}/open
POST /elections/{id}/close
```

harus aman terhadap concurrent requests.

Voting menggunakan:

```text
DB transaction
row locking
constraints
idempotency
```

---

# 80. Transaction Boundary

Transaction voting:

```text
BEGIN
  lock credential
  validate state
  create ballot
  mark credential used
  mark voter voted
COMMIT
```

Broadcast event dilakukan setelah commit.

---

# 81. Audit Events

Minimal events:

```text
LOGIN_SUCCESS
LOGIN_FAILED
ELECTION_CREATED
ELECTION_UPDATED
ELECTION_OPENED
ELECTION_CLOSED
ELECTION_ARCHIVED
CANDIDATE_CREATED
CANDIDATE_UPDATED
CANDIDATE_DELETED
VOTER_IMPORTED
CREDENTIAL_ISSUED
CREDENTIAL_REVOKED
EXPORT_CREATED
EXPORT_DOWNLOADED
UNAUTHORIZED_ACCESS_ATTEMPT
```

Jangan membuat audit event:

```text
VOTER_123_SELECTED_CANDIDATE_101
```

---

# 82. Voting Audit Event

Jika perlu audit bahwa proses voting terjadi, gunakan event aggregate:

```text
VOTE_ACCEPTED
```

Payload minimal:

```json
{
  "election_id": 10,
  "timestamp": "..."
}
```

Jangan:

```text
voter_id
candidate_id
credential
```

Audit log voting dapat menyimpan metadata teknis minimum jika benar-benar dibutuhkan, dengan mempertimbangkan risiko correlation.

---

# 83. API Error Catalog

Contoh error codes internal:

```text
AUTH_INVALID
AUTH_REQUIRED
ACCESS_DENIED
ELECTION_NOT_OPEN
ELECTION_ALREADY_CLOSED
INVALID_CREDENTIAL
CREDENTIAL_ALREADY_USED
CANDIDATE_INVALID
VOTING_SESSION_EXPIRED
DUPLICATE_REQUEST
VALIDATION_FAILED
RATE_LIMITED
RESOURCE_NOT_FOUND
EXPORT_NOT_READY
```

Client boleh menggunakan `code` untuk menentukan perilaku UI.

---

# 84. Error Response with Code

```json
{
  "success": false,
  "message": "Voting tidak dapat diproses.",
  "code": "VOTING_SESSION_EXPIRED"
}
```

Untuk security-sensitive errors, beberapa code dapat digeneralisasi agar tidak membocorkan informasi.

---

# 85. Validation Error

```http
422 Unprocessable Entity
```

Contoh:

```json
{
  "success": false,
  "message": "Data tidak valid.",
  "code": "VALIDATION_FAILED",
  "errors": {
    "name": [
      "Nama wajib diisi."
    ]
  }
}
```

---

# 86. Authentication Error

```http
401 Unauthorized
```

Response:

```json
{
  "success": false,
  "message": "Authentication required.",
  "code": "AUTH_REQUIRED"
}
```

---

# 87. Authorization Error

```http
403 Forbidden
```

Response:

```json
{
  "success": false,
  "message": "Anda tidak memiliki akses.",
  "code": "ACCESS_DENIED"
}
```

---

# 88. Rate Limit Error

```http
429 Too Many Requests
```

Response:

```json
{
  "success": false,
  "message": "Terlalu banyak permintaan.",
  "code": "RATE_LIMITED"
}
```

Header:

```http
Retry-After: 60
```

---

# 89. Request Correlation ID

Setiap request production sebaiknya memiliki:

```http
X-Request-ID: <uuid>
```

Jika tidak diberikan client, server membuatnya.

Correlation ID digunakan untuk:

- Debugging.
- Log tracing.
- Incident investigation.

Correlation ID tidak boleh menjadi secret.

---

# 90. API Logging Policy

Log:

```text
request_id
route
method
status
duration
user role
election context jika aman
```

Jangan log:

```text
password
credential plaintext
session secret
CSRF token
candidate selection
```

---

# 91. Caching Policy

GET endpoint yang aman dapat dicache:

```text
candidate public metadata
election metadata
```

Jangan cache:

```text
voting session
credential validation
cast vote
admin private data
```

---

# 92. API Performance Targets

Target awal:

```text
Normal GET: < 300 ms
Admin mutation: < 500 ms
Cast vote: < 1 s
```

Target harus diukur pada staging dengan kondisi yang mendekati election day.

---

# 93. Load Expectations

Sistem harus diuji terhadap:

```text
Normal traffic
Peak voting traffic
Concurrent vote requests
Repeated credential attempts
Dashboard polling/realtime connections
```

Contoh skenario:

```text
100 concurrent voters
500 concurrent voters
1000 concurrent voters
```

Angka final harus disesuaikan jumlah pemilih sekolah.

---

# 94. API Acceptance Criteria

## Authentication

- [ ] Admin login berhasil.
- [ ] Invalid login ditolak.
- [ ] Rate limiting aktif.
- [ ] Session regeneration aktif.
- [ ] Logout invalidates session.

## Election

- [ ] State transition tervalidasi.
- [ ] Election OPEN dapat menerima vote.
- [ ] Election CLOSED menolak vote.

## Candidate

- [ ] Candidate hanya dapat dibuat sebelum OPEN.
- [ ] Candidate ID harus berasal dari election yang benar.

## Voter

- [ ] Import mendeteksi duplicate.
- [ ] Voter tidak dapat memodifikasi status sendiri.

## Voting

- [ ] Credential valid hanya dapat digunakan sekali.
- [ ] Concurrent request menghasilkan maksimal satu ballot.
- [ ] Ballot tidak menyimpan voter identity.
- [ ] Candidate selection tidak masuk audit log.
- [ ] Voting transaction atomic.
- [ ] Confirmation tidak mengungkap pilihan.

## Results

- [ ] Results tidak tersedia sebelum CLOSED.
- [ ] Result count akurat.
- [ ] Export hanya tersedia setelah CLOSED.

---

# 95. API Invariants

### API-01

Tidak ada public API yang mengembalikan hubungan:

```text
voter → candidate
```

### API-02

`POST /voting/cast` adalah satu-satunya public voting mutation.

### API-03

Ballot tidak dapat dibuat melalui generic CRUD API.

### API-04

Voting hanya dapat dilakukan ketika election `OPEN`.

### API-05

Satu credential hanya menghasilkan satu successful vote.

### API-06

Result hanya dapat dibaca setelah election `CLOSED`.

### API-07

Authorization dilakukan server-side.

### API-08

All authenticated mutations menggunakan CSRF/session protection sesuai authentication model.

### API-09

Sensitive data tidak masuk application logs.

### API-10

Realtime event tidak membawa candidate choice.

---

# 96. Endpoint Summary

| Method | Endpoint | Role |
|---|---|---|
| POST | `/auth/login` | Public |
| POST | `/auth/logout` | Authenticated |
| GET | `/auth/me` | Authenticated |
| GET | `/elections` | Admin |
| POST | `/elections` | Admin |
| GET | `/elections/{id}` | Admin |
| PUT | `/elections/{id}` | Admin |
| POST | `/elections/{id}/schedule` | Admin |
| POST | `/elections/{id}/open` | Admin |
| POST | `/elections/{id}/close` | Admin |
| POST | `/elections/{id}/archive` | Admin |
| GET | `/elections/{id}/candidates` | Auth/Admin |
| POST | `/elections/{id}/candidates` | Admin |
| PUT | `/elections/{id}/candidates/{candidate}` | Admin |
| DELETE | `/elections/{id}/candidates/{candidate}` | Admin |
| GET | `/elections/{id}/voters` | Admin/Operator |
| POST | `/elections/{id}/voters/import` | Admin/Operator |
| POST | `/elections/{id}/credentials/generate` | Admin/Operator |
| POST | `/voting/session` | Voter |
| GET | `/voting/candidates` | Voter |
| POST | `/voting/cast` | Voter |
| GET | `/elections/{id}/participation` | Admin/Operator |
| GET | `/elections/{id}/participation/groups` | Admin/Operator |
| GET | `/elections/{id}/results` | Admin |
| POST | `/elections/{id}/exports` | Admin |
| GET | `/exports/{id}` | Admin |
| GET | `/exports/{id}/download` | Admin |
| GET | `/audit-logs` | Admin |
| GET | `/health` | Public |

---

# 97. Recommended Implementation Order

Implement API/domain dalam urutan:

```text
1. Authentication
2. User / Role
3. Election
4. Candidate
5. Voter
6. Credential
7. Voting Session
8. VotingService
9. Participation
10. Result
11. Export
12. Audit
13. Realtime
```

VotingService harus dibuat setelah migration, model, credential, voter, candidate, dan election rules siap.

---

# 98. Next Document

Dokumen berikutnya:

```text
06_UI_UX_SPEC.md
```

Fokus:

- Struktur halaman.
- Navigation.
- Admin dashboard.
- Election management UI.
- Candidate management UI.
- Voter import UI.
- Credential issuance UI.
- Voting screen.
- Confirmation screen.
- Result dashboard.
- Responsive behavior.
- Accessibility.
- Empty/loading/error states.
- UX security considerations.
