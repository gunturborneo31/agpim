<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaIncomingTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifier_sees_submissions_since_given_time(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();

        $since = now()->subMinute();

        $old = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $old->fill(['title' => 'Usulan Lama', 'status' => 'submitted', 'submitted_by' => $opdUser->id, 'submitted_at' => now()->subHour()])->save();

        $fresh = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $fresh->fill(['title' => 'Usulan Baru Masuk', 'status' => 'submitted', 'submitted_by' => $opdUser->id, 'submitted_at' => now()])->save();

        $response = $this->actingAs($admin)
            ->getJson('/agendas/incoming?since='.urlencode($since->toIso8601String()))
            ->assertOk()
            ->assertJsonPath('pending', 2)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.title', 'Usulan Baru Masuk');

        $this->assertNotEmpty($response->json('now'));
    }

    public function test_opd_cannot_poll_incoming(): void
    {
        $this->seed();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();

        $this->actingAs($opdUser)->getJson('/agendas/incoming')->assertForbidden();
    }

    public function test_agenda_page_shows_notification_toggle_for_verifier(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();

        $this->actingAs($admin)->get('/agendas?status=all')->assertOk()->assertSee('Notifikasi otomatis');
    }
}
