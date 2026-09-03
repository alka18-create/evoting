<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P0-01 + P0-06: simpan token sebagai hash, tambah ciphertext untuk cetak ulang
     * dan expiry. Kolom plain `token` dipertahankan sementara untuk transisi
     * (dual-read), akan di-drop di migrasi berikutnya setelah backfill + rotasi.
     */
    public function up(): void
    {
        foreach (['voting_event_voters', 'voter_eligibilities'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'token_hash')) {
                    $t->string('token_hash', 64)->nullable()->after('token');
                }
                if (! Schema::hasColumn($table, 'token_enc')) {
                    $t->text('token_enc')->nullable()->after('token_hash');
                }
                if (! Schema::hasColumn($table, 'expires_at')) {
                    $t->timestampTz('expires_at')->nullable()->after('token_enc');
                }
            });
        }

        // Index untuk lookup hash yang cepat.
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS voting_event_voters_token_hash_idx ON voting_event_voters (token_hash)');
        } catch (\Throwable) {
        }
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS voter_eligibilities_token_hash_idx ON voter_eligibilities (token_hash)');
        } catch (\Throwable) {
        }

        // Backfill: hash token plain lama agar login lama tetap jalan,
        // tanpa mengubah nilai plain (agar masa transisi dual-read mulus).
        // Pepper diambil dari APP_KEY dengan cara yang sama seperti VotingToken::hash().
        $this->backfill('voting_event_voters');
        $this->backfill('voter_eligibilities');
    }

    private function backfill(string $table): void
    {
        $pepper = $this->pepper();

        DB::table($table)
            ->whereNotNull('token')
            ->whereNull('token_hash')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $pepper) {
                foreach ($rows as $row) {
                    $hash = hash_hmac('sha256', (string) $row->token, $pepper);
                    DB::table($table)->where('id', $row->id)->update(['token_hash' => $hash]);
                }
            });
    }

    private function pepper(): string
    {
        $key = (string) config('app.key', '');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }

    public function down(): void
    {
        Schema::table('voting_event_voters', function (Blueprint $table) {
            $table->dropColumn(['token_hash', 'token_enc', 'expires_at']);
        });
        Schema::table('voter_eligibilities', function (Blueprint $table) {
            $table->dropColumn(['token_hash', 'token_enc', 'expires_at']);
        });
    }
};
