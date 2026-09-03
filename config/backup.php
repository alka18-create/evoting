<?php

return [

    'notifications' => [
        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],
        ],
    ],

    'default_password' => env('BACKUP_BACKUP_PASSWORD', ''),

    'dump' => [
        'compression' => 'gzip',
    ],

    'destination' => [
        'filename_prefix' => 'backup_',
        'disks' => [
            'local',
        ],
        'path' => 'backups',
    ],

    'database' => [
        'connection' => env('DB_CONNECTION', 'pgsql'),
        'dump' => [
            'exclude_tables' => [],
            'use_single_transaction' => true,
            'timeout' => 60,
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'defaultLookAndFeelBackups' => 14,
        'defaultLookAndFeelDays' => 30,
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_URL', 'localhost'),
            'disks' => ['local'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\IsReachable::class,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class,
            ],
        ],
    ],

];
