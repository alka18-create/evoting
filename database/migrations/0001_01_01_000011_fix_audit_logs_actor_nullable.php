<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing foreign key constraint
        $constraints = DB::select("
            SELECT conname FROM pg_constraint 
            WHERE conrelid = 'audit_logs'::regclass 
            AND contype = 'f'
        ");
        
        foreach ($constraints as $constraint) {
            if (str_contains($constraint->conname, 'actor_user_id')) {
                DB::statement("ALTER TABLE audit_logs DROP CONSTRAINT \"{$constraint->conname}\"");
            }
        }

        // Make column nullable
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('actor_user_id')->nullable()->change();
        });

        // Re-add foreign key
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        //
    }
};
