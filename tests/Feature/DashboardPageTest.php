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
            ->assertSee('Hari ini')
            ->assertSee('Perlu perhatian')
            ->assertSee('Rapat Infrastruktur Digital');
    }

    public function test_calendar_month_can_be_loaded_without_full_page(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'bupati@agpim.test')->firstOrFail();
        $next = now()->addMonthNoOverflow();

        $response = $this->actingAs($user)
            ->getJson('/dashboard?calendar_month='.$next->month.'&calendar_year='.$next->year)
            ->assertOk()
            ->assertJsonStructure(['html', 'map']);

        $this->assertStringContainsString($next->translatedFormat('F Y'), $response->json('html'));
        $this->assertStringNotContainsString('<html', $response->json('html'));
    }
}
