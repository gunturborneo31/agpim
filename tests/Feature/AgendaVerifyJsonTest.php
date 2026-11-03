<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaVerifyJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_via_ajax_returns_status_and_whatsapp_link(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();

        $agenda = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $agenda->fill([
            'title' => 'Uji Keputusan Ajax',
            'status' => 'submitted',
            'submitted_by' => $opdUser->id,
            'person_in_charge' => 'Budi',
            'pic_phone' => '0812-3456-7890',
        ])->save();

        $response = $this->actingAs($admin)->postJson("/agendas/{$agenda->id}/verify", [
            'action' => 'needs_revision',
            'note' => 'Lengkapi telaahan staf.',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'needs_revision')
            ->assertJsonPath('whatsapp_number', '6281234567890');

        $this->assertSame('needs_revision', $agenda->fresh()->status);

        // Pesan WhatsApp dibuat lewat endpoint terpisah, sekaligus link revisi.
        $wa = $this->actingAs($admin)->postJson("/agendas/{$agenda->id}/whatsapp-status")->assertOk();
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $wa->json('url'));
        $this->assertStringContainsString(rawurlencode('Uji Keputusan Ajax'), $wa->json('url'));
        $this->assertTrue($wa->json('revision_link'));
    }

    public function test_verify_via_ajax_returns_validation_errors(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();

        $agenda = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $agenda->fill(['title' => 'Uji Validasi Ajax', 'status' => 'submitted', 'submitted_by' => $opdUser->id])->save();

        $this->actingAs($admin)
            ->postJson("/agendas/{$agenda->id}/verify", ['action' => 'approve'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attendance_source', 'leader_target']);
    }
}
