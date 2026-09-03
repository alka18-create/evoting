<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * P2-01: index untuk lookup wizard (voter_id, election_id, status)
     * dan daftar eligibilitas per election+status.
     */
    public function up(): void
    {
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS voter_eligibilities_voter_election_status_idx ON voter_eligibilities (voter_id, election_id, status)');
        } catch (\Throwable) {
        }

        try {
            DB::statement('CREATE INDEX IF NOT EXISTS voter_eligibilities_election_status_idx ON voter_eligibilities (election_id, status)');
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS voter_eligibilities_voter_election_status_idx');
        } catch (\Throwable) {
        }

        try {
            DB::statement('DROP INDEX IF EXISTS voter_eligibilities_election_status_idx');
        } catch (\Throwable) {
        }
    }
};
