<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P3-01: MFA TOTP untuk SUPER_ADMIN/ADMIN. Secret + recovery codes
     * disimpan terenkripsi (cast encrypted). Kolom plain tidak ada.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestampTz('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            }
            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_confirmed_at', 'two_factor_recovery_codes']);
        });
    }
};
