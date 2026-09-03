<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retensi Audit Log (P1-05, Threat Model §41)
    |--------------------------------------------------------------------------
    | app log 30–90 hari (via LOG channel), audit log 1–3 tahun.
    | Perintah: php artisan audit:prune
    */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 1095),

    'prune_chunk' => 1000,
];
