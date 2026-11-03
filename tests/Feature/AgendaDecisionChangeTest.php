<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AgendaDecisionChangeTest extends TestCase
{
    use RefreshDatabase;

    private function makeAgenda(string $status, User $opdUser): Agenda
    {
        $agenda = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $agenda->fill([
            'title' => 'Uji Ubah Keputusan',
            'status' => $status,
            'submitted_by' => $opdUser->id,
            'opd_id' => $opdUser->opd_id,
            'leader_target' => 'bupati',
            'attendance_source' => 'proposed',
        ])->save();

        return $agenda;
    }

    public function test_approved_agenda_can_be_cancelled_and_keeps_disposition(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();
        $agenda = $this->makeAgenda('approved', $opdUser);

        $this->actingAs($admin)
            ->postJson("/agendas/{$agenda->id}/verify", ['action' => 'cancel', 'note' => 'Pimpinan berhalangan.'])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $agenda->refresh();
        $this->assertSame('cancelled', $agenda->status);
        $this->assertSame('bupati', $agenda->leader_target);
        $this->assertSame('proposed', $agenda->attendance_source);
    }

    public function test_approved_agenda_can_be_sent_back_for_revision(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();
        $agenda = $this->makeAgenda('approved', $opdUser);

        $this->actingAs($admin)
            ->postJson("/agendas/{$agenda->id}/verify", ['action' => 'needs_revision', 'note' => 'Perbaiki jam acara.'])
            ->assertOk()
            ->assertJsonPath('status', 'needs_revision');

        $this->assertNull($agenda->fresh()->decided_at);
    }

    public function test_opd_can_revise_and_resubmit(): void
    {
        $this->seed();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();
        $agenda = $this->makeAgenda('needs_revision', $opdUser);

        $this->actingAs($opdUser)->put("/agendas/{$agenda->id}", [
            'modal' => 'edit-'.$agenda->id,
            'agenda_type_id' => $agenda->agenda_type_id,
            'title' => 'Uji Ubah Keputusan (revisi)',
            'event_date' => now()->addDays(5)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Ruang Rapat',
            'description' => 'Sudah diperbaiki.',
            'priority' => 'biasa',
            'leader_target' => 'bupati',
        ])->assertRedirect('/agendas');

        $agenda->refresh();
        $this->assertSame('submitted', $agenda->status);
        $this->assertSame('Uji Ubah Keputusan (revisi)', $agenda->title);
        $this->assertDatabaseHas('agenda_verifications', ['agenda_id' => $agenda->id, 'action' => 'resubmitted']);
    }
}
