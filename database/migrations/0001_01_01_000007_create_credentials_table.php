<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_eligibility_id')->constrained()->restrictOnDelete();
            $table->string('credential_hash')->unique();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
        });

        Schema::table('credentials', function (Blueprint $table) {
            $table->index('voter_eligibility_id');
            $table->index('expires_at');
        });

        DB::statement('CREATE UNIQUE INDEX credentials_one_active_per_eligibility ON credentials (voter_eligibility_id) WHERE revoked_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS credentials_one_active_per_eligibility');
        Schema::dropIfExists('credentials');
    }
};