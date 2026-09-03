<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('candidate_number');
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->timestamps();

            $table->unique(['election_id', 'candidate_number']);
            $table->index('election_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};