<?php

namespace Tests\Feature;

use App\Models\TaskRecord;
use App\Models\User;
use App\Support\ReportDocxPdfConverter;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportPdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_all_report_types_are_returned_as_pdf(): void
    {
        $this->fakePdfConverter("%PDF-1.4\n%%EOF");

        $admin = $this->createUser('admin');
        $technician = $this->createUser('tecnico');
        $task = $this->createTask($technician);

        $taskReport = $this->withSession($this->sessionFor($admin))
            ->post(route('admin.tasks.report', $task), [
                'report_request_key' => 'task-report-test',
            ]);

        $taskReport->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="Registro_INF-001.pdf"');

        $weeklyReport = $this->withSession($this->sessionFor($admin))
            ->post(route('admin.reports.weekly'), [
                'mode' => 'created',
                'report_request_key' => 'weekly-report-test',
            ]);

        $weeklyReport->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="Registro_INF-002.pdf"');

        $dateReport = $this->withSession($this->sessionFor($admin))
            ->post(route('admin.reports.dates'), [
                'mode' => 'created',
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'report_request_key' => 'date-report-test',
            ]);

        $dateReport->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="Registro_INF-003.pdf"');
    }

    public function test_report_generation_falls_back_to_docx_when_pdf_conversion_fails(): void
    {
        $this->fakePdfConverter(null);

        $admin = $this->createUser('admin');
        $technician = $this->createUser('tecnico');
        $task = $this->createTask($technician);

        $response = $this->withSession($this->sessionFor($admin))
            ->post(route('admin.tasks.report', $task), [
                'report_request_key' => 'failed-pdf-report-test',
            ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('Content-Disposition', 'attachment; filename="Registro_INF-001.docx"');
    }

    private function fakePdfConverter(?string $pdf): void
    {
        $this->app->instance(ReportDocxPdfConverter::class, new class($pdf) extends ReportDocxPdfConverter {
            public function __construct(private readonly ?string $pdf)
            {
            }

            public function convert(string $docx, string $filename): ?string
            {
                return $this->pdf;
            }
        });
    }

    private function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Reporte '.uniqid(),
            'username' => $role.'_reporte_'.uniqid(),
            'password' => 'secret123',
            'role' => $role,
            'area' => 'AREA SISTEMAS',
            'cargo' => $role === 'admin' ? 'Tecnico de sistemas' : 'Responsable',
            'active' => true,
        ]);
    }

    private function createTask(User $technician): TaskRecord
    {
        return TaskRecord::create([
            'start_date' => now()->toDateString(),
            'technician_id' => $technician->id,
            'state' => 'pendiente',
            'assigned_task' => 'Preparar informe institucional',
            'business_days_deadline' => 5,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
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
