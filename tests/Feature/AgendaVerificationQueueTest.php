<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaVerificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifier_lands_on_submitted_queue_by_default(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin_prokopim')->firstOrFail();
        $opdUser = User::query()->where('role', 'opd')->firstOrFail();

        $submitted = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $submitted->fill(['title' => 'Antrean Verifikasi Uji', 'status' => 'submitted', 'submitted_by' => $opdUser->id])->save();

        $approved = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $approved->fill(['title' => 'Agenda Sudah Disetujui Uji', 'status' => 'approved', 'submitted_by' => $opdUser->id])->save();

        $this->actingAs($admin)
            ->get('/agendas')
            ->assertOk()
            ->assertSee('Verifikasi pengajuan agenda')
            ->assertSee('Antrean Verifikasi Uji')
            ->assertDontSee('Agenda Sudah Disetujui Uji');

        $this->actingAs($admin)
            ->get('/agendas?status=all')
            ->assertOk()
            ->assertSee('Agenda Sudah Disetujui Uji');
    }
}
