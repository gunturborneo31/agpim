<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_agpim_copy(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('AGPIM')
            ->assertSee('Dashboard agenda pimpinan');
    }
}
