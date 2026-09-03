<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune {--dry-run : Tampilkan jumlah yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus audit log lebih tua dari retensi (P1-05, default 1095 hari)';

    public function handle(): int
    {
        $days = (int) config('audit.retention_days', 1095);
        $cutoff = now()->subDays($days);
        $query = AuditLog::where('created_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $this->info('Akan dihapus: ' . $query->count() . " baris (lebih tua dari {$cutoff->toDateTimeString()}).");

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->orderBy('id')->chunkById((int) config('audit.prune_chunk', 1000), function ($rows) use (&$deleted) {
            $ids = $rows->pluck('id')->all();
            $deleted += AuditLog::whereIn('id', $ids)->delete();
        });

        $this->info("Audit log dihapus: {$deleted} baris.");

        return self::SUCCESS;
    }
}
