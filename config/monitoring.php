<?php

return [
    // P3-02: ambang alert dashboard (sesuaikan dengan skala sekolah).
    'vote_spike_per_5m' => (int) env('MONITOR_VOTE_SPIKE_PER_5M', 100),
    'voter_login_fail_per_5m' => (int) env('MONITOR_VOTER_FAIL_PER_5M', 20),
    'admin_login_fail_per_15m' => (int) env('MONITOR_ADMIN_FAIL_PER_15M', 10),
];
