<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AgendaType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_opd_user_can_submit_agenda(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'opd@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Sosialisasi Smart Village',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Balai Pertemuan',
            'description' => 'Sosialisasi transformasi digital kampung.',
            'priority' => 'sedang',
            'leader_target' => 'sekda',
            'person_in_charge' => 'Kabid Infrastruktur',
            'pic_phone' => '081111111111',
            'invitation_letter_path' => 'documents/invitation-smart-village.pdf',
            'speech_draft_path' => null,
        ]);

        $response->assertRedirect('/agendas');

        $this->assertDatabaseHas('agendas', [
            'title' => 'Sosialisasi Smart Village',
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'type' => 'agenda_submitted',
        ]);
        $this->assertSame(5, Agenda::count());
    }

    public function test_non_opd_role_is_forbidden_to_submit_agenda(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'bupati@agpim.test')->firstOrFail();
        $agendaType = AgendaType::query()->firstOrFail();

        $response = $this->actingAs($user)->post('/agendas', [
            'agenda_type_id' => $agendaType->id,
            'title' => 'Agenda Tidak Sah',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Kerja',
            'description' => 'Tidak boleh lolos.',
            'priority' => 'biasa',
            'invitation_letter_path' => 'documents/invalid.pdf',
        ]);

        $response->assertForbidden();
    }
}
