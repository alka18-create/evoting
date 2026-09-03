<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->restrictOnDelete();
            $table->foreignId('voter_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('ELIGIBLE');
            $table->timestampTz('voted_at')->nullable();
            $table->timestampsTz();

            $table->unique(['election_id', 'voter_id']);
            $table->index(['election_id', 'status']);
        });

        DB::statement("ALTER TABLE voter_eligibilities ADD CONSTRAINT voter_eligibilities_status_check CHECK (status IN ('ELIGIBLE', 'VOTED', 'REVOKED'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_eligibilities');
    }
};