<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\AgendaType;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoAgendaSeeder extends Seeder
{
    public function run(): void
    {
        $diskominfo = Opd::query()->where('code', 'DISKOMINFO')->first();
        $setda = Opd::query()->where('code', 'SETDA')->first();
        $opdUser = User::query()->where('email', 'opd@agpim.test')->first();
        $prokopim = User::query()->where('email', 'prokopim@agpim.test')->first();

        if (! $diskominfo || ! $setda || ! $opdUser || ! $prokopim) {
            return;
        }

        $types = AgendaType::query()->pluck('id', 'slug');
        $today = Carbon::today();

        $agendas = [
            [
                'title' => 'Rapat Infrastruktur Digital',
                'agenda_type_id' => $types['rapat'],
                'opd_id' => $diskominfo->id,
                'submitted_by' => $opdUser->id,
                'reviewed_by' => $prokopim->id,
                'event_date' => $today,
                'start_time' => '08:00',
                'end_time' => '09:30',
                'location' => 'Ruang Rapat Bupati',
                'description' => 'Rapat koordinasi infrastruktur digital dan konektivitas distrik prioritas.',
                'priority' => 'tinggi',
                'status' => 'approved',
                'leader_target' => 'bupati',
                'person_in_charge' => 'Kepala Diskominfo',
                'pic_phone' => '081234567890',
                'invitation_letter_path' => 'documents/invitation-1.pdf',
                'verification_note' => 'Lengkap dan siap dijadwalkan.',
                'disposition_note' => 'Bupati hadir langsung.',
                'submitted_at' => now()->subDays(2),
                'verified_at' => now()->subDays(1),
                'decided_at' => now()->subDay(),
                'is_internal_public' => true,
            ],
            [
                'title' => 'Peresmian Gedung Pelayanan',
                'agenda_type_id' => $types['peresmian'],
                'opd_id' => $setda->id,
                'submitted_by' => $opdUser->id,
                'reviewed_by' => $prokopim->id,
                'event_date' => $today,
                'start_time' => '10:00',
                'end_time' => '11:00',
                'location' => 'Long Bagun',
                'description' => 'Peresmian gedung pelayanan publik terpadu.',
                'priority' => 'sedang',
                'status' => 'awaiting_disposition',
                'leader_target' => 'bupati',
                'person_in_charge' => 'Bagian Umum',
                'pic_phone' => '081234567891',
                'invitation_letter_path' => 'documents/invitation-2.pdf',
                'verification_note' => 'Menunggu keputusan pimpinan.',
                'submitted_at' => now()->subDay(),
            ],
            [
                'title' => 'Audiensi DPRD',
                'agenda_type_id' => $types['audiensi'],
                'opd_id' => $setda->id,
                'submitted_by' => $opdUser->id,
                'reviewed_by' => $prokopim->id,
                'event_date' => $today,
                'start_time' => '10:30',
                'end_time' => '12:00',
                'location' => 'Kantor DPRD',
                'description' => 'Audiensi koordinasi rancangan prioritas pembangunan daerah.',
                'priority' => 'tinggi',
                'status' => 'approved',
                'leader_target' => 'bupati',
                'person_in_charge' => 'Setwan',
                'pic_phone' => '081234567892',
                'invitation_letter_path' => 'documents/invitation-3.pdf',
                'verification_note' => 'Bentrok dengan agenda lain, butuh perhatian.',
                'submitted_at' => now()->subDay(),
                'verified_at' => now()->subHours(18),
                'decided_at' => now()->subHours(8),
                'is_internal_public' => true,
            ],
            [
                'title' => 'Musrenbang Kecamatan',
                'agenda_type_id' => $types['musrenbang'],
                'opd_id' => $diskominfo->id,
                'submitted_by' => $opdUser->id,
                'reviewed_by' => $prokopim->id,
                'event_date' => $today->copy()->addDay(),
                'start_time' => '13:00',
                'end_time' => '15:00',
                'location' => 'Long Hubung',
                'description' => 'Agenda Musrenbang kecamatan untuk prioritas RKPD.',
                'priority' => 'biasa',
                'status' => 'approved',
                'leader_target' => 'sekda',
                'person_in_charge' => 'Bappeda',
                'pic_phone' => '081234567893',
                'invitation_letter_path' => 'documents/invitation-4.pdf',
                'submitted_at' => now()->subDays(3),
                'verified_at' => now()->subDays(2),
                'decided_at' => now()->subDays(1),
                'is_internal_public' => true,
            ],
        ];

        foreach ($agendas as $agenda) {
            Agenda::updateOrCreate(
                ['title' => $agenda['title'], 'event_date' => $agenda['event_date']],
                $agenda,
            );
        }
    }
}
