<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminProkopim = 'admin_prokopim';
    case Bupati = 'bupati';
    case WakilBupati = 'wakil_bupati';
    case Sekda = 'sekda';
    case Opd = 'opd';
    case Verifikator = 'verifikator';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminProkopim => 'Admin Prokopim',
            self::Bupati => 'Bupati',
            self::WakilBupati => 'Wakil Bupati',
            self::Sekda => 'Sekretaris Daerah',
            self::Opd => 'OPD',
            self::Verifikator => 'Verifikator',
        };
    }

    public function isLeader(): bool
    {
        return in_array($this, [self::Bupati, self::WakilBupati, self::Sekda], true);
    }
}
