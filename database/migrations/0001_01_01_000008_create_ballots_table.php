<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ballots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->string('ballot_hash', 128)->unique();
            $table->timestampTz('created_at');

            $table->index(['election_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ballots');
    }
};