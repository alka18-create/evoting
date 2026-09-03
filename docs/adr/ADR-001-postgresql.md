# ADR-001: PostgreSQL sebagai Database

**Status:** Accepted  
**Tanggal:** 2026-08-20

## Konteks
Sistem e-voting membutuhkan database yang mendukung transaction, row locking, unique/check constraint, dan JSONB.

## Keputusan
Gunakan PostgreSQL 17 sebagai satu-satunya source of truth.

## Konsekuensi
- Voting transaction memanfaatkan row locking (`SELECT ... FOR UPDATE`) pada voter_eligibilities.
- Ballot anonymity ditegakkan dengan tidak adanya kolom identitas pada tabel `ballots`.
- Redis hanya cache, bukan source of truth.