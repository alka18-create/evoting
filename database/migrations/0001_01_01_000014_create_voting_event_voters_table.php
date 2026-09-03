<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voting_event_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_event_id')->constrained('voting_events')->restrictOnDelete();
            $table->foreignId('voter_id')->constrained('voters')->restrictOnDelete();
            $table->string('token', 6)->nullable();
            $table->timestampsTz();

            $table->unique(['voting_event_id', 'voter_id']);
            $table->index(['voting_event_id', 'token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_event_voters');
    }
};
