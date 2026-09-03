<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * P1-04: keunikan hash per scope agar tabrakan tertolak di DB,
     * bukan hanya di memori. Partial index (hanya token_hash NOT NULL)
     * agar baris belum punya token tetap boleh banyak.
     */
    public function up(): void
    {
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS voting_event_voters_event_hash_uidx ON voting_event_voters (voting_event_id, token_hash) WHERE token_hash IS NOT NULL');
        } catch (\Throwable) {
        }

        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS voter_eligibilities_election_hash_uidx ON voter_eligibilities (election_id, token_hash) WHERE token_hash IS NOT NULL');
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS voting_event_voters_event_hash_uidx');
        } catch (\Throwable) {
        }

        try {
            DB::statement('DROP INDEX IF EXISTS voter_eligibilities_election_hash_uidx');
        } catch (\Throwable) {
        }
    }
};
