<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case Admin = 'ADMIN';
    case Operator = 'OPERATOR';
    case Voter = 'VOTER';

    /**
     * Role yang boleh mengakses area admin.
     */
    public static function adminRoles(): array
    {
        return [
            self::SuperAdmin,
            self::Admin,
            self::Operator,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Operator => 'Operator',
            self::Voter => 'Voter',
        };
    }
}