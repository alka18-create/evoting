<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_eligibilities', function (Blueprint $table) {
            $table->string('token', 6)->nullable()->after('status');
        });

        Schema::dropIfExists('credentials');
    }

    public function down(): void
    {
        Schema::table('voter_eligibilities', function (Blueprint $table) {
            $table->dropColumn('token');
        });

        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_eligibility_id')->constrained()->cascadeOnDelete();
            $table->string('credential_hash', 255)->unique();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
        });
    }
};
