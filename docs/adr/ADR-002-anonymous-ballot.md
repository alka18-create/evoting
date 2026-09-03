# ADR-002: Ballot Anonim

**Status:** Accepted  
**Tanggal:** 2026-08-20

## Konteks
Pilihan pemilih harus anonim. Hubungan `voter → candidate` tidak boleh tersedia di alur normal aplikasi.

## Keputusan
Tabel `ballots` TIDAK menyimpan `voter_id`, `student_id`, `credential_id`, atau FK identitas apa pun. Isi hanya `election_id`, `candidate_id`, `ballot_hash`, `created_at`.

## Konsekuensi
- Pelacakan "sudah memilih atau belum" dilakukan via `voter_eligibilities` (terpisah dari pilihan).
- Hubungan pemilih→pilihan tidak dapat direkonstruksi dari skema database.