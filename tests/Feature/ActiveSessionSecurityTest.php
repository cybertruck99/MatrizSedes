<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_six_active_sessions_but_not_seven(): void
    {
        $user = User::create([
            'name' => 'Usuario Sesiones',
            'username' => 'sesiones_'.uniqid(),
            'password' => 'clave123',
            'role' => 'user',
            'area' => 'AREA SISTEMAS',
            'active' => true,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            $this->withHeader('User-Agent', 'Navegador de prueba '.$i)
                ->post(route('login.post'), [
                    'username' => $user->username,
                    'password' => 'clave123',
                ])
                ->assertRedirect(route('user.dashboard'));
        }

        $this->assertSame(6, UserActiveSession::where('user_id', $user->id)->count());

        $this->withHeader('User-Agent', 'Navegador de prueba 7')
            ->post(route('login.post'), [
                'username' => $user->username,
                'password' => 'clave123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Esta cuenta ya tiene una sesión activa en otro dispositivo. Cierre sesión en ese dispositivo.');

        $this->assertSame(6, UserActiveSession::where('user_id', $user->id)->count());
    }
}
