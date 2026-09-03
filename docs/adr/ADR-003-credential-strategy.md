# ADR-003: Credential (Token/PIN) untuk Voting

**Status:** Accepted  
**Tanggal:** 2026-08-20

## Konteks
Voter login via credential (token/PIN/QR). Credential tidak boleh disimpan plaintext.

## Keputusan
Credential disimpan hanya sebagai `credential_hash` (hash kuat + salt), dengan unique constraint. Algoritma hash final ditentukan pada implementasi VotingEngine (`15_VOTING_ENGINE_SPEC.md`). Credential bersifat single-use: setelah vote sukses, credential di-mark last_used dan eligibility menjadi VOTED.

## Konsekuensi
- Tidak ada cara merekonstruksi credential dari database.
- Brute-force dilindungi rate limiting.