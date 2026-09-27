<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P0: hapus kolom `token` plaintext — token hanya tersimpan sebagai
     * hash (token_hash) + ciphertext (token_enc). Migrasi 000017 sudah
     * mem-backfill token_hash untuk baris legacy, sehingga login berbasis
     * hash tetap jalan. Baris legacy tanpa token_enc menjadi hash-only
     * (tidak bisa cetak ulang → terbitkan ulang token baru).
     */
    public function up(): void
    {
        foreach (['voting_event_voters', 'voter_eligibilities'] as $table) {
            if (Schema::hasColumn($table, 'token')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('token');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['voting_event_voters', 'voter_eligibilities'] as $table) {
            if (! Schema::hasColumn($table, 'token')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('token', 16)->nullable();
                });
            }
        }
    }
};
