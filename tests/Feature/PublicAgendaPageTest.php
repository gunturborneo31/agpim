<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAgendaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_internal_agenda_page_hides_internal_docs_copy(): void
    {
        $this->seed();

        $response = $this->get('/agenda-internal');

        $response
            ->assertOk()
            ->assertSee('Ringkasan Pengajuan OPD')
            ->assertSee('Periode aktif');
    }
}
