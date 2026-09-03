<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->foreignId('voting_event_id')->nullable()->after('id')->constrained('voting_events')->restrictOnDelete();
            $table->foreignId('organization_id')->nullable()->after('voting_event_id')->constrained('organizations')->restrictOnDelete();

            $table->index('voting_event_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voting_event_id');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
