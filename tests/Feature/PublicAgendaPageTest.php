<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAgendaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_resume_page(): void
    {
        $this->get('/agenda-internal')->assertRedirect('/login');
    }

    public function test_logged_in_user_can_see_resume_page(): void
    {
        $this->seed();
        $user = User::query()->where('role', 'admin_prokopim')->firstOrFail();

        $this->actingAs($user)
            ->get('/agenda-internal')
            ->assertOk()
            ->assertSee('Ringkasan Pengajuan OPD')
            ->assertSee('Periode aktif');
    }
}
