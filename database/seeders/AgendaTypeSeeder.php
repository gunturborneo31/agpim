<?php

namespace Database\Seeders;

use App\Models\AgendaType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgendaTypeSeeder extends Seeder
{
    public function run(): void
    {
        collect(['Rapat', 'Audiensi', 'Kunjungan Kerja', 'Peresmian', 'Seminar', 'Sosialisasi', 'Musrenbang', 'Upacara', 'Lainnya'])
            ->each(fn (string $name) => AgendaType::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $name.' agenda pemerintahan'],
            ));
    }
}
