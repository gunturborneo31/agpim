<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_is_redirected_to_user_list_after_login(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'email' => 'superadmin@agpim.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/users');
    }

    public function test_opd_is_redirected_to_agendas_after_login(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'email' => 'disdik@simpelsibang.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/agendas');

        $user = User::query()->where('email', 'disdik@simpelsibang.test')->first();
        $this->assertNotNull($user);
    }
}
