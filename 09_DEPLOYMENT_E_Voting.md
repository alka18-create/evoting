# Deployment Specification — Sistem E-Voting Sekolah

**Dokumen:** 09 — Deployment  
**Versi:** 1.0  
**Status:** Draft  
**Mengacu pada:** `01_PRD`, `02_ERD`, `03_SECURITY_Threat_Model`, `04_ARCHITECTURE`, `05_API_SPEC`, `06_UI_UX_SPEC`, `07_USER_FLOWS`, `08_TEST_PLAN`

---

# 1. Tujuan

Dokumen ini mendefinisikan rancangan deployment aplikasi e-voting untuk lingkungan staging dan production.

Prioritas:

```text
Availability
Security
Vote Integrity
Privacy
Recoverability
Observability
```

Prinsip utama:

> Database adalah source of truth untuk transaksi voting. Cache, realtime, queue, dan frontend tidak boleh menjadi sumber kebenaran suara.

---

# 2. Recommended Production Stack

Stack yang direkomendasikan:

```text
OS
└── Linux

Reverse Proxy
└── Nginx

Application Runtime
└── PHP-FPM

Application
└── Laravel

Database
└── PostgreSQL

Cache / Queue / Realtime Support
└── Redis

Frontend
└── Blade + Livewire + Tailwind CSS

Object/File Storage
└── S3-compatible storage atau local private storage

TLS
└── HTTPS

Process Management
└── Supervisor atau systemd

Containerization
└── Docker
```

Versi software harus mengikuti versi Laravel/PHP/PostgreSQL yang didukung dan masih mendapatkan security updates pada saat implementasi.

---

# 3. Recommended Architecture

```text
                    INTERNET / SCHOOL NETWORK
                              │
                              ▼
                         Cloud / Server
                              │
                         ┌────▼────┐
                         │  Nginx  │
                         └────┬────┘
                              │ HTTPS
                              ▼
                     ┌─────────────────┐
                     │ Laravel App     │
                     │ PHP-FPM         │
                     └────┬───────┬────┘
                          │       │
                ┌─────────┘       └──────────┐
                ▼                            ▼
          ┌───────────┐                 ┌─────────┐
          │ PostgreSQL│                 │  Redis  │
          └───────────┘                 └────┬────┘
                                            │
                                     ┌──────┴──────┐
                                     ▼             ▼
                                  Queue       Realtime
                                  Worker       Support

                     ┌─────────────────────────┐
                     │ Private Object Storage  │
                     └─────────────────────────┘
```

---

# 4. Production Components

Minimum:

```text
1 × Application
1 × PostgreSQL
1 × Redis
1 × Queue Worker
1 × Nginx
1 × TLS
1 × Backup destination
```

Untuk sekolah kecil, seluruh komponen dapat berada pada satu server dengan container terpisah.

Untuk deployment dengan kebutuhan availability lebih tinggi:

```text
Load Balancer
    ↓
App 1 ─┐
App 2 ─┤
App 3 ─┘
    ↓
PostgreSQL
Redis
Object Storage
```

---

# 5. Deployment Profiles

## Profile A — School / Small Deployment

Cocok untuk:

```text
< 2,000 voters
```

Arsitektur:

```text
1 VPS
├── Nginx
├── Laravel
├── Queue Worker
├── PostgreSQL
└── Redis
```

Backup:

```text
off-server
```

---

## Profile B — Medium

```text
Load Balancer
     │
 ┌───┴────┐
 ▼        ▼
App 1    App 2
 │        │
 └───┬────┘
     ▼
PostgreSQL
Redis
Object Storage
```

---

## Profile C — High Availability

Untuk pemilihan dengan kebutuhan tinggi:

```text
Load Balancer
      │
 ┌────┼────┐
 ▼    ▼    ▼
App 1 App 2 App 3
      │
 ┌────┴────┐
 ▼         ▼
DB Primary DB Replica
      │
      ▼
Redis HA
```

Detail HA harus ditentukan berdasarkan SLA dan budget.

---

# 6. Recommended Initial Server

Untuk MVP sekolah:

```text
CPU:     4 vCPU
RAM:     8 GB
Storage: 80–160 GB SSD
Network: 100 Mbps+
```

Ini adalah baseline awal, bukan jaminan kapasitas.

Kapasitas final harus divalidasi melalui load test dari `08_TEST_PLAN.md`.

---

# 7. Storage Planning

Pisahkan:

```text
Application
Database
Uploads
Backups
Logs
```

Jangan menyimpan backup hanya pada disk yang sama dengan database.

---

# 8. Directory Layout

Contoh container/server:

```text
/var/www/evoting
├── current/
├── shared/
│   ├── storage/
│   ├── .env
│   └── backups/
└── releases/
```

Jika Docker digunakan:

```text
/app
/storage
/backups
```

Gunakan volume persisten untuk data yang harus bertahan restart.

---

# 9. Docker Services

Contoh:

```text
nginx
app
queue
scheduler
postgres
redis
```

Optional:

```text
realtime
minio
```

---

# 10. Container Principle

Container aplikasi harus:

```text
immutable
stateless
replaceable
```

Jangan menyimpan:

```text
database
persistent credential
important upload
```

hanya di filesystem container.

---

# 11. Application Container

Responsibilities:

```text
Laravel application
PHP-FPM
application dependencies
```

Tidak menjalankan:

```text
PostgreSQL
Redis
Nginx
```

dalam container yang sama jika deployment memerlukan isolation.

---

# 12. Queue Worker

Worker menangani:

```text
voter import
export generation
notifications
non-critical background jobs
```

Jangan menjadikan queue sebagai langkah wajib untuk commit ballot.

---

# 13. Scheduler

Laravel scheduler dapat digunakan untuk:

```text
scheduled election opening
scheduled election closing
cleanup
maintenance
health checks
```

Contoh:

```text
scheduler container/process
```

harus menjalankan scheduler secara konsisten.

---

# 14. PostgreSQL

PostgreSQL adalah:

```text
source of truth
```

untuk:

```text
election
candidate
voter eligibility
credential hash
ballot
audit
```

Database tidak boleh dapat diakses langsung dari internet.

---

# 15. PostgreSQL Network

Allowed:

```text
Laravel → PostgreSQL
Queue → PostgreSQL
Admin DB tools → PostgreSQL melalui secure tunnel/VPN
```

Denied:

```text
Internet → PostgreSQL
```

---

# 16. PostgreSQL Configuration

Production:

```text
SSL/TLS where required
strong authentication
least privilege
connection limits
logging policy
backup
```

Gunakan user database terpisah:

```text
application_user
backup_user
migration_user
```

jika operational model mendukung.

---

# 17. Database Migrations

Deployment:

```text
backup
  ↓
maintenance assessment
  ↓
php artisan migrate --force
  ↓
health check
```

Migration harus diuji terlebih dahulu di staging.

Jangan melakukan destructive migration tanpa:

```text
backup
rollback plan
```

---

# 18. Database Connection

Application menggunakan:

```text
environment variables
```

Contoh konseptual:

```text
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=evoting
DB_USERNAME=...
DB_PASSWORD=...
```

Jangan commit secret ke Git.

---

# 19. Redis

Redis digunakan untuk:

```text
cache
queue
rate limiting
session jika dipilih
realtime support
```

Redis bukan source of truth untuk ballot.

---

# 20. Redis Network

Redis:

```text
private network only
```

Tidak boleh:

```text
public internet
```

Authentication/TLS digunakan sesuai deployment.

---

# 21. Object Storage

Gunakan private bucket/container untuk:

```text
candidate photos
export files
temporary reports
```

Default:

```text
private
```

Jika file perlu di-download:

```text
authorized endpoint
```

atau:

```text
short-lived signed URL
```

Jangan membuat bucket publik secara default.

---

# 22. Candidate Photo Storage

Candidate photos:

```text
private or controlled public
```

Jika public URL digunakan, pastikan tidak mengandung:

```text
student identity
credential
private voter data
```

---

# 23. Export Storage

Export hasil harus:

```text
private
temporary
authorized
```

Retention:

```text
delete after configured period
```

atau disimpan sesuai kebijakan sekolah.

---

# 24. Nginx

Responsibilities:

```text
TLS termination
HTTP → HTTPS redirect
static assets
reverse proxy / PHP-FPM
request limits
security headers
```

---

# 25. Nginx Security

Configure:

```text
server_tokens off
request size limits
security headers
rate-limit zones where appropriate
```

Jangan expose:

```text
.env
.git
storage private files
database dumps
logs
```

---

# 26. TLS

Production wajib:

```text
HTTPS
```

Minimum:

```text
TLS 1.2+
```

Prioritaskan TLS 1.3 jika kompatibel.

Certificate:

```text
automated renewal
monitor expiration
```

---

# 27. HTTP Security Headers

Minimal pertimbangkan:

```text
Strict-Transport-Security
X-Content-Type-Options
Content-Security-Policy
Referrer-Policy
Permissions-Policy
```

Konfigurasi CSP harus diuji agar tidak merusak Laravel/Livewire.

---

# 28. Laravel Production Configuration

Production:

```text
APP_ENV=production
APP_DEBUG=false
```

Cache:

```text
config
route
view
```

sesuai deployment strategy.

Jangan mengaktifkan debug mode pada production.

---

# 29. Application Secret

`APP_KEY`:

```text
strong random value
```

Jangan regenerate pada deployment biasa.

Perubahan APP_KEY dapat membuat encrypted data/session lama tidak dapat digunakan.

---

# 30. Environment Variables

Minimal:

```text
APP_ENV
APP_KEY
APP_URL

DB_*

REDIS_*

CACHE_*

QUEUE_*

MAIL_*

FILESYSTEM_*

LOG_*
```

Secrets harus berasal dari:

```text
secret manager
CI/CD secret
secure environment configuration
```

---

# 31. Secret Management

Jangan:

```text
commit .env
print secrets in CI logs
put secrets in Docker image
put DB password in source code
```

Gunakan:

```text
GitHub/GitLab secrets
Docker secrets
cloud secret manager
```

sesuai platform.

---

# 32. CI/CD Pipeline

Recommended:

```text
Developer
   ↓
Git Push
   ↓
CI
   ├── Lint
   ├── Unit Test
   ├── Feature Test
   ├── Security Scan
   ├── Build
   └── E2E
          ↓
      Staging
          ↓
    Smoke Test
          ↓
    Approval Gate
          ↓
     Production
```

---

# 33. Branch Strategy

Contoh sederhana:

```text
main
 └── production

develop
 └── staging

feature/*
```

Model branching dapat disesuaikan dengan tim.

---

# 34. Build Artifact

Production sebaiknya deploy artifact yang sudah dibuild:

```text
composer install --no-dev
npm build
```

Hindari menjalankan build tidak terkontrol di server production.

---

# 35. Zero-Downtime Consideration

Untuk single-server MVP:

```text
short maintenance window
```

dapat diterima.

Untuk HA:

```text
blue/green
rolling
or atomic release
```

---

# 36. Deployment Sequence

Recommended:

```text
1. Verify backup
2. Enable maintenance strategy if required
3. Deploy application
4. Install dependencies
5. Run migrations
6. Clear/rebuild cache
7. Restart queue workers gracefully
8. Reload PHP-FPM
9. Health check
10. Smoke test
11. Disable maintenance
12. Monitor
```

Voting-critical deployments harus dihindari saat election OPEN jika tidak benar-benar diperlukan.

---

# 37. Deployment During Open Election

Default policy:

```text
NO DEPLOYMENT
```

selama election OPEN.

Jika emergency deployment diperlukan:

```text
incident approval
backup
risk assessment
rollback plan
monitoring
```

Prioritaskan vote integrity di atas fitur baru.

---

# 38. Queue Deployment

Saat deploy:

```text
queue:restart
```

atau mekanisme graceful restart sesuai process manager.

Pastikan job tidak terputus secara unsafe.

---

# 39. Database Migration During Election

Default:

```text
prohibited
```

untuk schema change yang menyentuh voting-critical tables.

Jika unavoidable:

```text
pre-tested
backward compatible
backup verified
rollback plan
```

---

# 40. Health Checks

Endpoint:

```text
/health
```

atau internal health endpoint.

Check:

```text
application
database
cache
queue
storage
```

Health endpoint publik tidak boleh membocorkan:

```text
credentials
database details
internal topology
```

---

# 41. Readiness vs Liveness

## Liveness

Menjawab:

```text
Process hidup?
```

## Readiness

Menjawab:

```text
Siap menerima traffic?
```

Load balancer sebaiknya menggunakan readiness check.

---

# 42. Monitoring

Monitor:

```text
CPU
RAM
Disk
Network
HTTP latency
HTTP errors
DB connections
DB locks
Redis
Queue
Storage
TLS certificate
```

---

# 43. Voting-Specific Monitoring

Tambahkan metric:

```text
votes accepted
votes rejected
vote transaction latency
concurrent vote conflicts
idempotency hits
voting session failures
```

Jangan memasukkan:

```text
voter identity + candidate
```

ke metric.

---

# 44. Alerts

Critical alerts:

```text
database unavailable
disk nearly full
high 5xx
high vote failure rate
database lock/deadlock spike
queue backlog
TLS expiration
backup failure
```

---

# 45. Logging

Application logs:

```text
JSON structured logging
```

Jika memungkinkan.

Fields:

```text
timestamp
level
service
environment
request_id
route
status
duration
```

---

# 46. Sensitive Logging Rule

Never log:

```text
password
PIN
plaintext credential
session token
Authorization header
full private ballot data
```

---

# 47. Audit Log vs Application Log

## Audit Log

Untuk:

```text
who performed administrative action
what action
when
resource
```

## Application Log

Untuk:

```text
technical diagnostics
error
performance
request lifecycle
```

Pisahkan konsep keduanya.

---

# 48. Request Correlation ID

Setiap request:

```text
X-Request-ID
```

atau equivalent.

Digunakan untuk:

```text
API
application log
audit correlation
support investigation
```

Jangan gunakan correlation ID sebagai credential.

---

# 49. Backup Strategy

Minimum:

```text
daily full backup
```

Untuk election-critical period:

```text
more frequent backup / WAL strategy
```

sesuai kebutuhan.

Backup harus:

```text
encrypted
off-server
access controlled
tested
```

---

# 50. PostgreSQL Backup

Gunakan:

```text
pg_dump
```

untuk logical backup dan/atau:

```text
physical backup + WAL
```

untuk recovery requirements yang lebih ketat.

Pilih berdasarkan RPO/RTO.

---

# 51. Backup Before Election

Sebelum OPEN:

```text
database backup
configuration backup
verification
```

Catat:

```text
backup timestamp
backup status
```

---

# 52. Backup Verification

Backup tidak dianggap berhasil hanya karena file dibuat.

Harus ada:

```text
restore test
```

secara berkala.

---

# 53. RPO / RTO

Baseline awal:

```text
RPO: ≤ 15 minutes
RTO: ≤ 1 hour
```

Angka final harus disepakati berdasarkan kebutuhan sekolah dan infrastructure budget.

---

# 54. Disaster Recovery

Scenario:

```text
Server failure
Database corruption
Storage failure
Credential service failure
Network outage
```

Procedure:

```text
Declare incident
   ↓
Protect evidence
   ↓
Assess impact
   ↓
Restore infrastructure
   ↓
Restore database
   ↓
Verify integrity
   ↓
Run smoke tests
   ↓
Resume or terminate election
```

---

# 55. Database Restore Validation

Setelah restore:

```text
check election state
check voter eligibility
check ballot count
check result consistency
check audit logs
```

Critical invariant:

```text
valid ballots <= eligible voters
```

---

# 56. Rollback Strategy

Application rollback:

```text
previous release
```

Database rollback:

```text
DO NOT blindly reverse migrations
```

Gunakan:

```text
backup
forward-compatible migration
restore strategy
```

untuk perubahan berisiko tinggi.

---

# 57. Emergency Rollback

Trigger:

```text
critical bug
vote integrity risk
authentication bypass
data corruption
```

Procedure:

```text
Stop risky traffic/action
   ↓
Preserve logs
   ↓
Assess committed votes
   ↓
Rollback application
   ↓
Verify database
   ↓
Run smoke test
   ↓
Resume only after approval
```

Jangan menghapus ballot untuk "memperbaiki" bug tanpa prosedur terkontrol.

---

# 58. Election Incident Mode

Jika terjadi masalah serius:

```text
INCIDENT MODE
```

Operational action dapat berupa:

```text
freeze new voting
disable non-essential admin changes
preserve logs
preserve database
```

Keputusan melanjutkan/menutup pemilihan harus mengikuti prosedur organisasi/sekolah.

---

# 59. Firewall

Allow:

```text
80/443 → Nginx
```

Restrict:

```text
5432 PostgreSQL
6379 Redis
```

ke private network.

SSH:

```text
VPN/IP allowlist/key-based auth
```

jika memungkinkan.

---

# 60. SSH Security

Disable:

```text
password authentication
root login
```

Gunakan:

```text
SSH keys
least privilege
MFA/bastion/VPN where possible
```

---

# 61. OS Security

Production server:

```text
automatic security updates
minimal packages
time synchronization
firewall
fail2ban or equivalent where appropriate
```

Jangan menjalankan service yang tidak diperlukan.

---

# 62. Time Synchronization

Voting bergantung pada waktu.

Server harus menggunakan:

```text
NTP
```

Semua service harus memiliki timezone policy yang konsisten.

Application:

```text
UTC internally
```

atau policy timezone yang jelas.

Display:

```text
Asia/Jakarta
```

untuk pengguna Indonesia jika itu adalah timezone election.

---

# 63. Clock Drift Monitoring

Monitor:

```text
server clock
database clock
application clock
```

Perbedaan waktu signifikan dapat menyebabkan:

```text
early open
late close
session issues
```

---

# 64. Queue Reliability

Configure:

```text
retry
backoff
timeout
failed jobs
```

Queue job harus idempotent jika memungkinkan.

---

# 65. Realtime Reliability

Realtime monitoring adalah:

```text
non-critical enhancement
```

Jika realtime gagal:

```text
voting tetap harus aman
```

Fallback:

```text
manual refresh
polling
```

---

# 66. Cache Failure

Jika Redis/cache gagal:

```text
fallback to database
```

Jika fallback tidak memungkinkan:

```text
fail safely
```

Jangan membuat cache failure menyebabkan:

```text
duplicate vote
```

---

# 67. Storage Failure

Jika candidate photo gagal:

```text
candidate management may fail
```

tetapi jangan menyebabkan:

```text
corrupted ballot
```

Jika export storage gagal:

```text
retry export
```

Ballot tidak boleh bergantung pada object storage.

---

# 68. Resource Limits

Set limits:

```text
PHP memory
upload size
request timeout
queue timeout
database connections
container CPU/RAM
```

Tujuan:

```text
prevent resource exhaustion
```

---

# 69. Rate Limiting

Rate limit:

```text
admin login
voter credential verification
public endpoints
export
import
sensitive admin operations
```

Voting endpoint harus dirancang agar:

```text
legitimate voting
```

tetap dapat dilakukan tanpa membuka brute-force path.

---

# 70. WAF / Reverse Proxy Protection

Optional:

```text
Cloudflare
AWS WAF
Nginx rate limiting
```

Jika digunakan, pastikan:

```text
real client IP handling
```

benar.

Jangan menganggap WAF menggantikan application authorization.

---

# 71. Domain Strategy

Contoh:

```text
vote.sekolah.example
admin.sekolah.example
```

atau satu domain:

```text
evoting.sekolah.example
```

dengan route:

```text
/vote
/admin
```

Untuk MVP, satu domain lebih sederhana.

---

# 72. DNS

Production:

```text
A / AAAA
```

dengan TTL sesuai kebutuhan.

Sebelum election:

```text
DNS resolution test
TLS test
```

---

# 73. CDN

CDN dapat digunakan untuk:

```text
static assets
candidate photos
```

Tetapi:

```text
voting API
admin API
private exports
```

harus mengikuti security policy.

---

# 74. Production Deployment Checklist

## Infrastructure

- [ ] Server provisioned
- [ ] Firewall configured
- [ ] SSH hardened
- [ ] HTTPS active
- [ ] DNS configured
- [ ] Time synchronization active

## Application

- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_KEY configured
- [ ] dependencies installed
- [ ] cache built

## Database

- [ ] PostgreSQL secured
- [ ] migrations applied
- [ ] indexes verified
- [ ] backup configured
- [ ] restore tested

## Redis

- [ ] private network
- [ ] authentication/TLS as required
- [ ] queue tested

## Monitoring

- [ ] logs
- [ ] metrics
- [ ] alerts
- [ ] health checks

---

# 75. Pre-Election Checklist

Minimal 24–48 jam sebelum election:

```text
[ ] Production health check
[ ] Backup verified
[ ] Restore test recent
[ ] Credentials verified
[ ] Candidate data verified
[ ] Voter count verified
[ ] Election schedule verified
[ ] HTTPS verified
[ ] DNS verified
[ ] Monitoring verified
[ ] Alerting verified
[ ] Load test completed
[ ] E2E smoke test completed
[ ] Incident contact ready
```

---

# 76. Election Opening Checklist

Sesaat sebelum OPEN:

```text
[ ] Final voter count confirmed
[ ] Final candidate list confirmed
[ ] Credential distribution confirmed
[ ] Database backup completed
[ ] Monitoring connected
[ ] No critical alerts
[ ] Election configuration frozen
```

Kemudian:

```text
OPEN
```

---

# 77. During Election

Monitor:

```text
vote acceptance
error rate
database
CPU
memory
disk
queue
network
```

Jangan melakukan:

```text
schema migration
candidate modification
voter mass update
credential mass regeneration
```

kecuali emergency procedure mengizinkan.

---

# 78. Election Closing Checklist

```text
[ ] Stop new votes
[ ] Verify election CLOSED
[ ] Verify final participation
[ ] Verify ballot count
[ ] Calculate results
[ ] Validate result invariant
[ ] Generate export
[ ] Preserve logs
```

---

# 79. Post-Election Checklist

```text
[ ] Results verified
[ ] Export verified
[ ] Backup completed
[ ] Audit logs retained
[ ] Incident review
[ ] Performance review
[ ] Error review
[ ] Archive election
```

---

# 80. Cost Consideration

MVP sekolah dapat dimulai dengan:

```text
1 VPS
Docker
PostgreSQL
Redis
S3-compatible backup
Cloudflare/managed TLS
```

Hindari overengineering sebelum kebutuhan availability benar-benar ada.

Namun:

> Jangan menghemat pada backup, HTTPS, database integrity, dan security monitoring.

---

# 81. Scaling Strategy

Scale berdasarkan bottleneck.

## Application

```text
horizontal scaling
```

## Database

```text
vertical scaling
read replica for non-critical reporting if needed
```

Jangan memindahkan voting write transaction ke read replica.

## Redis

Scale berdasarkan:

```text
memory
connections
throughput
```

---

# 82. Database Scaling Principle

Voting write path:

```text
Voter
 ↓
Primary PostgreSQL
```

Result/reporting dapat menggunakan:

```text
read replica
```

jika diperlukan.

Tetapi final vote acceptance tetap harus bergantung pada authoritative primary transaction.

---

# 83. Observability Dashboard

Dashboard operasional:

```text
Application
  Requests/sec
  p95 latency
  5xx

Voting
  votes/minute
  rejected votes
  conflict rate

Database
  connections
  locks
  latency

Queue
  pending
  failed
  latency

Infrastructure
  CPU
  RAM
  Disk
```

---

# 84. Privacy in Observability

Operational metrics harus tetap privacy-preserving.

Jangan membuat metric:

```text
voter_name + candidate_name
```

Gunakan:

```text
election_id
aggregate counts
technical identifiers
```

yang tidak mengungkap pilihan.

---

# 85. Production Access

Least privilege:

```text
Developer
Operator
Admin
Infrastructure Admin
Database Admin
```

memiliki akses berbeda.

Developer tidak otomatis memiliki akses production database.

---

# 86. Database Admin Access

Jika diperlukan:

```text
VPN
SSH tunnel
bastion
MFA
audit
```

Jangan membuka:

```text
5432 public
```

---

# 87. File Permissions

Application:

```text
least privilege
```

`.env`:

```text
owner-readable
```

Storage private:

```text
not executable
not public
```

---

# 88. Container Image Security

Image:

```text
minimal
non-root where possible
pinned dependencies
scanned
```

Jangan memasukkan:

```text
.env
private keys
production database dump
```

ke image.

---

# 89. Supply Chain Security

CI harus memeriksa:

```text
Composer dependencies
NPM dependencies
Docker images
```

Gunakan lockfiles:

```text
composer.lock
package-lock.json / equivalent
```

---

# 90. Deployment Approval

Production deployment harus memiliki:

```text
PR review
automated tests
staging verification
approval
rollback plan
```

Untuk election period:

```text
change freeze
```

sangat direkomendasikan.

---

# 91. Change Management

Setiap perubahan production dicatat:

```text
tanggal
release
operator
alasan
migration
rollback plan
```

---

# 92. Versioning

Application release:

```text
v1.0.0
v1.0.1
...
```

Database schema version:

```text
Laravel migrations
```

API:

```text
/api/v1
```

---

# 93. Rollout Strategy

Untuk perubahan non-critical:

```text
staging
 ↓
smoke
 ↓
production
 ↓
monitor
```

Untuk critical election:

```text
change freeze
```

---

# 94. Backup Retention

Contoh policy:

```text
Daily: 30 days
Weekly: 12 weeks
Monthly: 12 months
```

Policy final harus mengikuti kebutuhan sekolah dan regulasi yang berlaku.

---

# 95. Backup Encryption

Backup harus menggunakan:

```text
encryption at rest
```

Jika dipindahkan:

```text
TLS
```

Key management harus dipisahkan dari backup data jika memungkinkan.

---

# 96. Restore Drill

Minimal secara berkala:

```text
Provision clean environment
 ↓
Restore backup
 ↓
Run migrations/checks
 ↓
Verify ballots
 ↓
Verify results
```

Dokumentasikan:

```text
restore duration
issues
RPO achieved
RTO achieved
```

---

# 97. Deployment Definition of Done

Deployment dianggap siap jika:

- [ ] Production architecture defined.
- [ ] HTTPS configured.
- [ ] Database private.
- [ ] Redis private.
- [ ] Secrets managed securely.
- [ ] Backup configured.
- [ ] Restore tested.
- [ ] Monitoring configured.
- [ ] Alerts configured.
- [ ] CI/CD configured.
- [ ] Rollback documented.
- [ ] Election-day checklist ready.
- [ ] Change freeze policy defined.

---

# 98. Final Production Principle

Untuk sistem e-voting, prioritas deployment adalah:

```text
1. Vote Integrity
2. Privacy
3. Security
4. Availability
5. Performance
6. Convenience
```

Jika terjadi konflik:

```text
convenience < security
performance < integrity
feature delivery < election stability
```

---

# 99. Next Document

Dokumen berikutnya yang direkomendasikan:

```text
10_IMPLEMENTATION_ROADMAP.md
```

Fokus:

```text
Project setup
Database migration
Authentication
RBAC
Election module
Candidate module
Voter import
Credential system
Voting engine
Anonymous ballot
Monitoring
Result
Export
Audit
Testing
Deployment
Go-live
```

Roadmap akan mengubah seluruh dokumen spesifikasi menjadi urutan pekerjaan implementasi yang dapat langsung dikerjakan developer.
