<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->text('settings')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('opened_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE elections ADD CONSTRAINT elections_status_check CHECK (status IN ('DRAFT', 'SCHEDULED', 'OPEN', 'CLOSED', 'ARCHIVED'))");
        DB::statement('ALTER TABLE elections ADD CONSTRAINT elections_ends_after_starts CHECK (ends_at IS NULL OR starts_at IS NULL OR ends_at > starts_at)');

        Schema::table('elections', function (Blueprint $table) {
            $table->index('status');
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};