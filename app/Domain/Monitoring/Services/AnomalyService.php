<?php

namespace App\Domain\Monitoring\Services;

use App\Models\AuditLog;
use App\Models\Ballot;
use Illuminate\Support\Collection;

/**
 * P3-02: deteksi anomali ringan untuk dashboard admin.
 * - Lonjakan vote (ballot per 5 menit vs baseline 1 jam).
 * - Login voter gagal massal (5 menit terakhir).
 * - Login admin gagal massal.
 * Threshold via config; murni read-only, tanpa aksi otomatis.
 */
class AnomalyService
{
    /**
     * @return Collection<int, array{level: string, title: string, detail: string}>
     */
    public function check(): Collection
    {
        $alerts = collect();

        $voteSpike = (int) config('monitoring.vote_spike_per_5m', 100);
        $voterFailThreshold = (int) config('monitoring.voter_login_fail_per_5m', 20);
        $adminFailThreshold = (int) config('monitoring.admin_login_fail_per_15m', 10);

        // 1) Lonjakan vote 5 menit terakhir
        $recentVotes = Ballot::where('created_at', '>=', now()->subMinutes(5))->count();
        if ($recentVotes >= $voteSpike) {
            $alerts->push([
                'level' => 'warning',
                'title' => 'Lonjakan suara 5 menit terakhir',
                'detail' => "{$recentVotes} ballot dalam 5 menit (ambang {$voteSpike}). Verifikasi kehadiran TPS bila di luar jam sibuk.",
            ]);
        }

        // 2) Voter login gagal massal
        $voterFails = AuditLog::where('action', 'VOTER_LOGIN_FAILED')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
        if ($voterFails >= $voterFailThreshold) {
            $alerts->push([
                'level' => 'danger',
                'title' => 'Banyak login voter gagal',
                'detail' => "{$voterFails} kegagalan dalam 5 menit (ambang {$voterFailThreshold}). Kemungkinan brute force atau salah distribusi token.",
            ]);
        }

        // 3) MFA gagal / admin login anomali
        $mfaFails = AuditLog::where('action', 'MFA_FAILED')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
        if ($mfaFails >= $adminFailThreshold) {
            $alerts->push([
                'level' => 'danger',
                'title' => 'Banyak verifikasi MFA gagal',
                'detail' => "{$mfaFails} kegagalan dalam 15 menit (ambang {$adminFailThreshold}). Periksa akun admin terkait.",
            ]);
        }

        return $alerts;
    }
}
