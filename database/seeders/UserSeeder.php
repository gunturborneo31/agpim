<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');
        $opds = Opd::query()->pluck('id', 'code');

        $users = [
            ['name' => 'Super Admin AGPIM', 'email' => 'superadmin@agpim.test', 'role' => UserRole::SuperAdmin, 'title' => 'Super Admin', 'opd_id' => null],
            ['name' => 'Admin Prokopim', 'email' => 'prokopim@agpim.test', 'role' => UserRole::AdminProkopim, 'title' => 'Admin Prokopim', 'opd_id' => $opds['SETDA'] ?? null],
            ['name' => 'Bupati Mahakam Ulu', 'email' => 'bupati@agpim.test', 'role' => UserRole::Bupati, 'title' => 'Bupati', 'opd_id' => null],
            ['name' => 'Wakil Bupati Mahakam Ulu', 'email' => 'wabup@agpim.test', 'role' => UserRole::WakilBupati, 'title' => 'Wakil Bupati', 'opd_id' => null],
            ['name' => 'Sekretaris Daerah', 'email' => 'sekda@agpim.test', 'role' => UserRole::Sekda, 'title' => 'Sekretaris Daerah', 'opd_id' => $opds['SETDA'] ?? null],
            ['name' => 'Verifikator AGPIM', 'email' => 'verifikator@agpim.test', 'role' => UserRole::Verifikator, 'title' => 'Verifikator', 'opd_id' => $opds['SETDA'] ?? null],
            ['name' => 'Operator Diskominfo', 'email' => 'opd@agpim.test', 'role' => UserRole::Opd, 'title' => 'Admin OPD', 'opd_id' => $opds['DISKOMINFO'] ?? null],
            ['name' => 'Dinas Pendidikan dan Kebudayaan', 'email' => 'disdik@simpelsibang.test', 'role' => UserRole::Opd, 'title' => 'Admin OPD', 'opd_id' => $opds['DISDIK'] ?? null],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user + ['phone' => '081234567890', 'password' => $password, 'email_verified_at' => now()],
            );
        }
    }
}
