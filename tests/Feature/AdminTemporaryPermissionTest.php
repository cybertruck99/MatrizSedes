<?php

namespace Tests\Feature;

use App\Models\TemporaryAdminPermission;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTemporaryPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_can_grant_and_revoke_active_temporary_admin_permissions(): void
    {
        $admin = $this->createUser('admin', 'admin_perm_'.uniqid(), 'clave123');
        $technician = $this->createUser('tecnico', 'tec_perm_'.uniqid(), 'clave123');

        $this->withSession($this->sessionFor($admin))
            ->post(route('admin.users.admin-permissions.grant', $technician), [
                'starts_on' => now()->toDateString(),
                'ends_on' => now()->addDays(2)->toDateString(),
                'admin_password' => 'clave123',
                'admin_permission_action' => 'grant',
            ])
            ->assertRedirect(route('admin.users.show', $technician));

        $permission = $technician->temporaryAdminPermissions()->active()->first();
        $this->assertNotNull($permission);

        $this->withSession($this->sessionFor($admin))
            ->get(route('admin.users.show', $technician))
            ->assertOk()
            ->assertSee('Cancelar Permisos')
            ->assertSee('revoke-admin-permission-modal', false)
            ->assertDontSee('data-confirm-open="grant-admin-permission-modal"', false);

        $this->withSession($this->sessionFor($admin))
            ->post(route('admin.users.admin-permissions.revoke', $technician), [
                'admin_password' => 'malaclave',
                'admin_permission_action' => 'revoke',
            ])
            ->assertSessionHasErrors('admin_password');

        $this->assertNull($permission->fresh()->revoked_at);

        $this->withSession($this->sessionFor($admin))
            ->post(route('admin.users.admin-permissions.revoke', $technician), [
                'admin_password' => 'clave123',
                'admin_permission_action' => 'revoke',
            ])
            ->assertRedirect(route('admin.users.show', $technician));

        $this->assertNotNull($permission->fresh()->revoked_at);

        $this->withSession($this->sessionFor($admin))
            ->get(route('admin.users.show', $technician))
            ->assertOk()
            ->assertSee('Conceder permisos')
            ->assertDontSee('Cancelar Permisos');
    }

    public function test_expired_temporary_admin_permission_shows_grant_button_again(): void
    {
        $admin = $this->createUser('admin', 'admin_exp_'.uniqid(), 'clave123');
        $technician = $this->createUser('tecnico', 'tec_exp_'.uniqid(), 'clave123');

        TemporaryAdminPermission::create([
            'user_id' => $technician->id,
            'granted_by' => $admin->id,
            'starts_at' => now()->subDays(3)->startOfDay(),
            'ends_at' => now()->subDay()->endOfDay(),
        ]);

        $this->withSession($this->sessionFor($admin))
            ->get(route('admin.users.show', $technician))
            ->assertOk()
            ->assertSee('Conceder permisos')
            ->assertDontSee('Cancelar Permisos');
    }

    private function createUser(string $role, string $username, string $password): User
    {
        return User::create([
            'name' => ucfirst($role).' Permisos',
            'username' => $username,
            'password' => $password,
            'role' => $role,
            'area' => 'AREA SISTEMAS',
            'active' => true,
        ]);
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
