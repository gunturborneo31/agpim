<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    public function run(): void
    {
        $opds = [
            ['code' => 'BAPPEDA', 'name' => 'Badan Perencanaan Pembangunan Daerah', 'alias' => 'Bappeda'],
            ['code' => 'PUPR', 'name' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'alias' => 'PUPR'],
            ['code' => 'DISKOMINFO', 'name' => 'Dinas Komunikasi dan Informatika', 'alias' => 'Diskominfo'],
            ['code' => 'SETDA', 'name' => 'Sekretariat Daerah', 'alias' => 'Setda'],
        ];

        foreach ($opds as $opd) {
            Opd::updateOrCreate(['code' => $opd['code']], $opd + ['is_active' => true]);
        }
    }
}
