<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_shows_timeline_and_conflicts(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'bupati@agpim.test')->firstOrFail();

        $response = $this->actingAs($user)->get('/dashboard?leader=bupati');

        $response
            ->assertOk()
            ->assertSee('Timeline Hari Ini')
            ->assertSee('Agenda Bentrok')
            ->assertSee('Rapat Infrastruktur Digital');
    }
}
