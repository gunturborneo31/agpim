<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_shows_timeline_and_conflicts(): void
    {
        $this->seed();

        $response = $this->get('/dashboard?leader=bupati');

        $response
            ->assertOk()
            ->assertSee('Timeline Hari Ini')
            ->assertSee('Agenda Bentrok')
            ->assertSee('Rapat Infrastruktur Digital');
    }
}
