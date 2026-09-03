# ADR-004: Redis hanya Cache, bukan Source of Truth

**Status:** Accepted  
**Tanggal:** 2026-08-20

## Konteks
Realtime monitoring dan queue membutuhkan Redis.

## Keputusan
Redis digunakan untuk cache, queue, dan duplikasi pesan Reverb — tetapi TIDAK pernah menjadi source of truth untuk data voting. Jika Redis hilang, counter dihitung ulang dari PostgreSQL.

## Konsekuensi
- PostgreSQL selalu authoritative untuk eligibility, credential, ballot, audit, dan result.
- Pemulihan dari kehilangan Redis tidak mengubah hasil voting.