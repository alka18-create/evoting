<?php

namespace App\Enums;

enum Permission: string
{
    // User Management
    case ManageUsers = 'manage_users';
    case ViewUsers = 'view_users';

    // Election Management
    case ManageElections = 'manage_elections';
    case ViewElections = 'view_elections';

    // Voter Management
    case ManageVoters = 'manage_voters';
    case ViewVoters = 'view_voters';

    // Credential Management
    case ManageCredentials = 'manage_credentials';
    case ViewCredentials = 'view_credentials';

    // Results
    case ViewResults = 'view_results';
    case ExportResults = 'export_results';

    // Audit Log
    case ViewAuditLogs = 'view_audit_logs';

    /**
     * Mapping role → permissions.
     */
    public static function forRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::SuperAdmin => array_column(self::cases(), 'value'),

            UserRole::Admin => [
                self::ManageElections->value,
                self::ViewElections->value,
                self::ManageVoters->value,
                self::ViewVoters->value,
                self::ManageCredentials->value,
                self::ViewCredentials->value,
                self::ViewResults->value,
                self::ExportResults->value,
                self::ViewUsers->value,
            ],

            UserRole::Operator => [
                self::ViewElections->value,
                self::ManageVoters->value,
                self::ViewVoters->value,
                self::ManageCredentials->value,
                self::ViewCredentials->value,
                self::ViewResults->value,
            ],

            UserRole::Voter => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ManageUsers => 'Kelola Pengguna',
            self::ViewUsers => 'Lihat Pengguna',
            self::ManageElections => 'Kelola Pemilihan',
            self::ViewElections => 'Lihat Pemilihan',
            self::ManageVoters => 'Kelola Pemilih',
            self::ViewVoters => 'Lihat Pemilih',
            self::ManageCredentials => 'Kelola Kredensial',
            self::ViewCredentials => 'Lihat Kredensial',
            self::ViewResults => 'Lihat Hasil',
            self::ExportResults => 'Export Hasil',
            self::ViewAuditLogs => 'Lihat Audit Log',
        };
    }
}
