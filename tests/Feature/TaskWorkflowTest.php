<?php

namespace Tests\Feature;

use App\Models\TaskFile;
use App\Models\TaskRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_upload_keeps_original_name_and_creates_green_notification(): void
    {
        Storage::fake('public');
        $user = $this->createUser('user');
        $task = $this->createTask($user);

        $response = $this->withSession($this->sessionFor($user))
            ->post(route('user.tasks.upload', $task), [
                'archivos' => [
                    UploadedFile::fake()->create('Informe Final.txt', 12, 'text/plain'),
                    UploadedFile::fake()->create('Cuadro Anual.xlsx', 18, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                ],
            ]);

        $response->assertRedirect();
        $task->refresh();

        $this->assertSame('new', $task->file_review_status);
        $this->assertNotNull($task->submitted_at);
        $this->assertDatabaseHas('task_files', [
            'task_record_id' => $task->id,
            'original_name' => 'Informe Final.txt',
            'file_path' => 'tasks/'.$task->id.'/Informe Final.txt',
        ]);
        Storage::disk('public')->assertExists('tasks/'.$task->id.'/Informe Final.txt');
        Storage::disk('public')->assertExists('tasks/'.$task->id.'/Cuadro Anual.xlsx');
    }

    public function test_later_upload_marks_files_as_updated(): void
    {
        Storage::fake('public');
        $user = $this->createUser('tecnico');
        $task = $this->createTask($user);

        $this->withSession($this->sessionFor($user))
            ->post(route('tecnico.tasks.upload', $task), [
                'archivos' => [UploadedFile::fake()->create('Primera evidencia.pdf', 10, 'application/pdf')],
            ])
            ->assertRedirect();

        $this->withSession($this->sessionFor($user))
            ->post(route('tecnico.tasks.upload', $task), [
                'archivos' => [UploadedFile::fake()->create('Corrección evidencia.pdf', 10, 'application/pdf')],
            ])
            ->assertRedirect();

        $this->assertSame('updated', $task->fresh()->file_review_status);
        $this->assertSame(2, $task->taskFiles()->count());
    }

    public function test_opening_admin_task_profile_clears_notification_and_lists_all_files(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin');
        $user = $this->createUser('user');
        $task = $this->createTask($user, [
            'uploaded_file_path' => 'tasks/'.$user->id.'/foto-dos.jpg',
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'file_review_status' => 'updated',
        ]);

        foreach (['foto-uno.jpg', 'foto-dos.jpg'] as $name) {
            Storage::disk('public')->put('tasks/'.$task->id.'/'.$name, 'image');
            TaskFile::create([
                'task_record_id' => $task->id,
                'uploaded_by' => $user->id,
                'file_path' => 'tasks/'.$task->id.'/'.$name,
                'original_name' => $name,
                'mime_type' => 'image/jpeg',
                'size_bytes' => 5,
            ]);
        }

        $this->withSession($this->sessionFor($admin))
            ->get(route('admin.matrix.index'))
            ->assertOk()
            ->assertSee('submission-dot-updated', false)
            ->assertSee(route('admin.tasks.show', $task), false);

        $response = $this->withSession($this->sessionFor($admin))
            ->get(route('admin.tasks.show', $task));

        $response->assertOk()
            ->assertSee('foto-uno.jpg')
            ->assertSee('foto-dos.jpg')
            ->assertSee('Cumplimiento y Observaciones')
            ->assertSee('Ver Perfil');

        $this->assertNull($task->fresh()->file_review_status);
        $this->assertNotNull($task->fresh()->files_reviewed_at);
    }

    public function test_deleting_last_file_resets_delivery_status(): void
    {
        Storage::fake('public');
        $user = $this->createUser('user');
        $task = $this->createTask($user);
        Storage::disk('public')->put('tasks/'.$task->id.'/evidencia.txt', 'contenido');
        $file = TaskFile::create([
            'task_record_id' => $task->id,
            'uploaded_by' => $user->id,
            'file_path' => 'tasks/'.$task->id.'/evidencia.txt',
            'original_name' => 'evidencia.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 9,
        ]);
        $task->update([
            'uploaded_file_path' => $file->file_path,
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'file_review_status' => 'new',
        ]);

        $this->withSession($this->sessionFor($user))
            ->delete(route('user.tasks.files.destroy', [$task, $file]))
            ->assertRedirect();

        $task->refresh();
        $this->assertFalse($task->has_uploaded_files);
        $this->assertNull($task->uploaded_file_path);
        $this->assertNull($task->submitted_at);
        $this->assertNull($task->file_review_status);
        Storage::disk('public')->assertMissing('tasks/'.$task->id.'/evidencia.txt');
    }

    public function test_admin_can_download_file_but_unrelated_user_cannot(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin');
        $owner = $this->createUser('user');
        $other = $this->createUser('user');
        $task = $this->createTask($owner);
        Storage::disk('public')->put('tasks/'.$task->id.'/reporte.txt', 'contenido');
        $file = TaskFile::create([
            'task_record_id' => $task->id,
            'uploaded_by' => $owner->id,
            'file_path' => 'tasks/'.$task->id.'/reporte.txt',
            'original_name' => 'Reporte Institucional.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 9,
        ]);

        $this->withSession($this->sessionFor($admin))
            ->get(route('task-files.download', $file))
            ->assertOk()
            ->assertDownload('Reporte Institucional.txt');

        $this->withSession($this->sessionFor($other))
            ->get(route('task-files.download', $file))
            ->assertForbidden();
    }

    public function test_menu_uses_desktop_block_and_mobile_curtain_controls(): void
    {
        $admin = $this->createUser('admin');

        $this->withSession($this->sessionFor($admin))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('class="app-shell" id="appShell"', false)
            ->assertSee('class="sidebar-backdrop"', false)
            ->assertSee('class="sidebar-close-toggle"', false)
            ->assertSee('data-sidebar-toggle', false)
            ->assertDontSee('class="app-shell sidebar-open"', false);
    }

    public function test_admin_can_update_review_state_and_observations(): void
    {
        $admin = $this->createUser('admin');
        $user = $this->createUser('user');
        $task = $this->createTask($user);

        $this->withSession($this->sessionFor($admin))
            ->patch(route('admin.matrix.updateCompliance', $task), [
                'state' => 'cumplido',
                'final_observations' => 'Entrega revisada y aprobada.',
            ])
            ->assertRedirect();

        $task->refresh();
        $this->assertSame('cumplido', $task->state);
        $this->assertSame('SI CUMPLIÓ', $task->compliance);
        $this->assertSame('Entrega Revisada y Aprobada.', $task->final_observations);
        $this->assertNotNull($task->compliance_date);
    }

    public function test_admin_permission_request_is_visible_on_admin_dashboard_until_resolved(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('tecnico');

        $this->withSession($this->sessionFor($technician))
            ->post(route('tecnico.admin-permissions.request'))
            ->assertRedirect();

        $this->withSession($this->sessionFor($admin))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Solicitudes de permisos de Admin')
            ->assertSee($technician->name)
            ->assertSee('Conceder permisos');

        $request = $technician->adminPermissionRequests()->pending()->firstOrFail();

        $this->withSession($this->sessionFor($admin))
            ->patch(route('admin.admin-permissions.deny', $request))
            ->assertRedirect();

        $this->withSession($this->sessionFor($admin))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Solicitudes de permisos de Admin')
            ->assertDontSee($technician->name);
    }

    public function test_new_task_assignment_dot_disappears_after_opening_task_profile(): void
    {
        $user = $this->createUser('user');
        $task = $this->createTask($user);

        $this->assertNull($task->assigned_viewed_at);

        $this->withSession($this->sessionFor($user))
            ->get(route('user.tasks'))
            ->assertOk()
            ->assertSee('has-nav-alert', false)
            ->assertSee('assignment-dot', false);

        $this->withSession($this->sessionFor($user))
            ->get(route('user.tasks.show', $task))
            ->assertOk();

        $this->assertNotNull($task->fresh()->assigned_viewed_at);

        $this->withSession($this->sessionFor($user))
            ->get(route('user.tasks'))
            ->assertOk()
            ->assertDontSee('has-nav-alert', false)
            ->assertDontSee('assignment-dot', false);
    }

    private function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Prueba '.uniqid(),
            'username' => $role.'_'.uniqid(),
            'password' => 'secret123',
            'role' => $role,
            'area' => 'AREA SISTEMAS',
            'active' => true,
        ]);
    }

    private function createTask(User $user, array $overrides = []): TaskRecord
    {
        return TaskRecord::create(array_merge([
            'start_date' => now()->toDateString(),
            'technician_id' => $user->id,
            'state' => 'pendiente',
            'assigned_task' => 'Preparar informe institucional',
            'business_days_deadline' => 5,
            'due_date' => now()->addDays(5)->toDateString(),
        ], $overrides));
    }

    private function sessionFor(User $user): array
    {
        return [
            'auth_user_id' => $user->id,
            'auth_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'active_session_token' => Str::random(64),
        ];
    }
}
