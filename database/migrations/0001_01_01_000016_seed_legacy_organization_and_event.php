<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed default organization "Umum" untuk elections legacy tanpa organization_id
        $orgExists = DB::table('organizations')->where('slug', 'umum')->exists();
        $orgId = null;

        if (! $orgExists) {
            $orgId = DB::table('organizations')->insertGetId([
                'name' => 'Umum',
                'slug' => 'umum',
                'description' => 'Organisasi default untuk pemilihan legacy',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $orgId = DB::table('organizations')->where('slug', 'umum')->value('id');
        }

        // Voting event legacy — created_by pakai user pertama (SUPER_ADMIN) atau fallback 1
        $eventExists = DB::table('voting_events')->where('slug', 'legacy-event')->exists();
        $eventId = null;

        if (! $eventExists) {
            $creatorId = DB::table('users')->orderBy('id')->value('id') ?? 1;

            // Pastikan user ada, jika belum ada seed (migrate:fresh tanpa seed), skip event creation
            if (DB::table('users')->where('id', $creatorId)->exists()) {
                $eventId = DB::table('voting_events')->insertGetId([
                    'name' => 'Legacy Event',
                    'slug' => 'legacy-event',
                    'description' => 'Event default untuk pemilihan sebelum fitur multi-voting',
                    'status' => 'DRAFT',
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(),
                    'created_by' => $creatorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            $eventId = DB::table('voting_events')->where('slug', 'legacy-event')->value('id');
        }

        // Assign elections yang masih null
        if ($eventId) {
            DB::table('elections')->whereNull('voting_event_id')->update(['voting_event_id' => $eventId]);
        }
        if ($orgId) {
            DB::table('elections')->whereNull('organization_id')->update(['organization_id' => $orgId]);
        }
    }

    public function down(): void
    {
        // Tidak hapus data, hanya reset FK jika perlu
        // DB::table('elections')->where('voting_event_id', DB::table('voting_events')->where('slug','legacy-event')->value('id'))->update(['voting_event_id'=>null]);
    }
};
