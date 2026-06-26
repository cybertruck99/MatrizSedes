<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class BaseAdminSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_base_admin_notice_is_shown_only_once_on_clean_installation(): void
    {
        $baseAdmin = User::query()->where('username', 'adminbase1')->firstOrFail();
        $this->assertNull($baseAdmin->base_credentials_shown_at);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Administrador base del sistema')
            ->assertSee('adminbase1')
            ->assertSee('admin123');

        $this->assertNotNull($baseAdmin->fresh()->base_credentials_shown_at);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Administrador base del sistema')
            ->assertDontSee('admin123');
    }

    public function test_base_admin_notice_is_not_shown_when_system_already_has_more_users(): void
    {
        $baseAdmin = User::query()->where('username', 'adminbase1')->firstOrFail();

        User::create([
            'name' => 'Administrador Secundario',
            'username' => 'admin_secundario',
            'password' => 'clave123',
            'role' => 'admin',
            'area' => 'AREA SISTEMAS',
            'active' => true,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Administrador base del sistema')
            ->assertDontSee('admin123');

        $this->assertNotNull($baseAdmin->fresh()->base_credentials_shown_at);
    }

    public function test_base_admin_setup_rejects_default_credentials_and_accepts_new_credentials(): void
    {
        $baseAdmin = User::query()->where('username', 'adminbase1')->firstOrFail();

        $this->withSession($this->sessionFor($baseAdmin))
            ->patch(route('admin.base-admin.update'), [
                'username' => 'adminbase1',
                'password' => 'admin123',
                'password_confirmation' => 'admin123',
            ])
            ->assertSessionHasErrors(['username', 'password']);

        $baseAdmin->refresh();
        $this->assertTrue((bool) $baseAdmin->is_base_admin);
        $this->assertNull($baseAdmin->base_setup_completed_at);

        $this->withSession($this->sessionFor($baseAdmin))
            ->patch(route('admin.base-admin.update'), [
                'username' => 'admin_sedes',
                'password' => 'Nuevo123',
                'password_confirmation' => 'Nuevo123',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $baseAdmin->refresh();
        $this->assertSame('admin_sedes', $baseAdmin->username);
        $this->assertFalse((bool) $baseAdmin->is_base_admin);
        $this->assertNotNull($baseAdmin->base_setup_completed_at);
        $this->assertTrue(Hash::check('Nuevo123', $baseAdmin->password));
    }

    private function sessionFor(User $user): array
    {
        return [
            'auth_user_id' => $user->id,
            'auth_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'actual_role' => $user->role,
            ],
            'active_session_token' => Str::random(64),
            'last_activity_at' => now()->timestamp,
        ];
    }
}
