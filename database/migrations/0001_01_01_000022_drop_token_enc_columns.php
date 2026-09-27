<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P0-SEC: hash-only — hapus kolom `token_enc` reversible.
     * Jika APP_KEY bocor, token_enc memungkinkan pemulihan semua token.
     * Setelah ini cetak ulang dari DB tidak mungkin; gunakan rotasi
     * (terbitkan ulang token baru, plain tampil sekali via flash session).
     */
    public function up(): void
    {
        foreach (['voting_event_voters', 'voter_eligibilities'] as $table) {
            if (Schema::hasColumn($table, 'token_enc')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('token_enc');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['voting_event_voters', 'voter_eligibilities'] as $table) {
            if (! Schema::hasColumn($table, 'token_enc')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('token_enc')->nullable();
                });
            }
        }
    }
};
