<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleCheckTest extends TestCase
{
    use RefreshDatabase;

    private function makeAgenda(array $attributes): Agenda
    {
        $agenda = Agenda::query()->firstOrFail()->replicate(['uuid']);
        $agenda->fill(array_merge([
            'title' => 'Agenda Pembanding',
            'event_date' => '2026-12-10',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'time_unknown' => false,
            'leader_target' => 'bupati',
            'status' => 'approved',
        ], $attributes))->save();

        return $agenda;
    }

    public function test_overlapping_time_is_reported_without_titles_for_guests(): void
    {
        $this->seed();
        $this->makeAgenda(['title' => 'Rapat Rahasia']);

        $response = $this->getJson('/cek-jadwal?date=2026-12-10&start=10:00&end=12:00&leader=bupati')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.time', '09:00 - 11:00')
            ->assertJsonPath('items.0.title', null);

        $this->assertTrue($response->json('time_known'));
    }

    public function test_logged_in_user_sees_titles_and_non_overlapping_is_ignored(): void
    {
        $this->seed();
        $this->makeAgenda(['title' => 'Rapat Pagi']);
        $this->makeAgenda(['title' => 'Rapat Dibatalkan', 'status' => 'cancelled']);
        $opd = User::query()->where('role', 'opd')->firstOrFail();

        $this->actingAs($opd)->getJson('/cek-jadwal?date=2026-12-10&start=09:30&end=10:00&leader=bupati')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.title', 'Rapat Pagi');

        // Jam tidak beririsan → tidak ada peringatan.
        $this->actingAs($opd)->getJson('/cek-jadwal?date=2026-12-10&start=13:00&end=14:00&leader=bupati')
            ->assertOk()
            ->assertJsonCount(0, 'items');
    }

    public function test_submission_with_conflict_is_still_accepted(): void
    {
        $this->seed();
        $this->makeAgenda([]);

        $this->get('/usulan-tanpa-login')->assertOk()->assertSee('Pengajuan tetap bisa dikirim', false);
    }
}
