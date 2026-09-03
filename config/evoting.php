<?php

return [
    'voting' => [
        'idempotency_ttl' => env('VOTING_IDEMPOTENCY_TTL', 3600),

        'credential' => [
            'length' => (int) env('VOTING_CREDENTIAL_LENGTH', 8),
            'alphabet' => env('VOTING_CREDENTIAL_ALPHABET', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'),
        ],
    ],
];