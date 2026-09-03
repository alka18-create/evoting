# ADR-005: Konkurensi Voting (Row Locking)

**Status:** Accepted  
**Tanggal:** 2026-08-20

## Konteks
Dua request bersamaan dari voter yang sama harus menghasilkan tepat satu ballot sukses.

## Keputusan
CastVote berjalan dalam satu transaction: row lock pada `voter_eligibilities` (`SELECT ... FOR UPDATE` via `UPDATE ... WHERE status='ELIGIBLE'` yang mengembalikan affected rows), re-check eligibility, insert ballot, mark VOTED, commit. Unique constraint `(election_id, voter_id)` sebagai defense kedua.

## Konsekuensi
- Satu voter → maksimal satu ballot valid.
- Request konkuren lain gagal aman dengan exception `AlreadyVoted`.