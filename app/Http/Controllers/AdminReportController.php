<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use App\Support\ReportCiteIssuer;
use App\Support\ReportDocxPdfConverter;
use App\Support\ReportTableDocxGenerator;
use App\Support\TaskReportDocxGenerator;
use App\Support\TextFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly TaskReportDocxGenerator $taskReports,
        private readonly ReportTableDocxGenerator $tableReports,
        private readonly ReportCiteIssuer $cites,
        private readonly ReportDocxPdfConverter $pdfConverter,
    )
    {
    }

    public function index(Request $request)
    {
        $sort = $this->validSort($request->string('sort', 'date')->toString());
        [$filterMode, $from, $to] = $this->previewFilter($request);

        $query = TaskRecord::with(['technician', 'taskFiles'])
            ->withCount('taskFiles')
            ->select('task_records.*');

        $this->applyPeriodFilter($query, $filterMode, $from, $to);
        $this->applySearch($query, $request);
        $this->applySort($query, $sort);

        $records = $query->paginate(25)->withQueryString();

        return view('admin.reports.index', compact('records', 'sort', 'filterMode', 'from', 'to'));
    }

    public function task(Request $request, TaskRecord $task)
    {
        $data = $request->validate([
            'report_observations' => ['nullable', 'string', 'max:3000'],
            'report_request_key' => ['nullable', 'string', 'max:120'],
        ]);

        $admin = $this->admin();
        $cite = $this->cites->issue('task', $admin, null, $data['report_request_key'] ?? null);
        $docx = $this->taskReports->make($task, $admin, $cite->code, TextFormatter::paragraph($data['report_observations'] ?? null));

        return $this->reportResponse($docx, $cite->short_code);
    }

    public function weekly(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['created', 'submitted'])],
            'disposition' => ['nullable', Rule::in(['inline', 'attachment'])],
            'report_observations' => ['nullable', 'string', 'max:3000'],
            'report_request_key' => ['nullable', 'string', 'max:120'],
        ]);

        [$from, $to] = $this->currentBusinessWeek();
        $tasks = $this->reportTasks($data['mode'], $from, $to);

        $admin = $this->admin();
        $cite = $this->cites->issue('weekly-'.$data['mode'], $admin, null, $data['report_request_key'] ?? null);
        $docx = $this->tableReports->make(
            $tasks,
            $admin,
            'INFORME DE REGISTRO SEMANAL',
            $cite->code,
            $from,
            $to,
            TextFormatter::paragraph($data['report_observations'] ?? null),
            false,
        );
        return $this->reportResponse($docx, $cite->short_code);
    }

    public function dateRange(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['created', 'submitted'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'disposition' => ['nullable', Rule::in(['inline', 'attachment'])],
            'report_observations' => ['nullable', 'string', 'max:3000'],
            'report_request_key' => ['nullable', 'string', 'max:120'],
        ]);

        $from = Carbon::parse($data['start_date'])->startOfDay();
        $to = Carbon::parse($data['end_date'])->endOfDay();
        if ($to->lessThan($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $tasks = $this->reportTasks($data['mode'], $from, $to);

        $admin = $this->admin();
        $cite = $this->cites->issue('date-'.$data['mode'], $admin, null, $data['report_request_key'] ?? null);
        $docx = $this->tableReports->make(
            $tasks,
            $admin,
            'INFORME DE REGISTRO POR FECHAS',
            $cite->code,
            $from,
            $to,
            TextFormatter::paragraph($data['report_observations'] ?? null),
            true,
        );
        return $this->reportResponse($docx, $cite->short_code);
    }

    private function previewFilter(Request $request): array
    {
        $filterMode = $request->string('filter_mode')->toString();
        $allowed = ['', 'weekly_created', 'weekly_submitted', 'date_created', 'date_submitted'];
        if (! in_array($filterMode, $allowed, true)) {
            $filterMode = '';
        }

        if (str_starts_with($filterMode, 'weekly_')) {
            [$from, $to] = $this->currentBusinessWeek();
            return [$filterMode, $from, $to];
        }

        $from = $request->filled('start_date')
            ? Carbon::parse($request->date('start_date'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('end_date')
            ? Carbon::parse($request->date('end_date'))->endOfDay()
            : now()->endOfDay();

        if ($to->lessThan($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$filterMode, $from, $to];
    }

    private function reportTasks(string $mode, Carbon $from, Carbon $to)
    {
        $query = TaskRecord::with(['technician', 'taskFiles'])
            ->select('task_records.*');

        $this->applyPeriodFilter($query, $mode === 'created' ? 'date_created' : 'date_submitted', $from, $to);

        return $query
            ->orderByDesc($mode === 'created' ? 'task_records.created_at' : 'task_records.submitted_at')
            ->orderBy('task_records.assigned_task')
            ->get();
    }

    private function applyPeriodFilter(Builder $query, string $filterMode, Carbon $from, Carbon $to): void
    {
        match ($filterMode) {
            'weekly_created', 'date_created' => $query->whereBetween('task_records.created_at', [$from, $to]),
            'weekly_submitted', 'date_submitted' => $query->whereNotNull('task_records.submitted_at')
                ->whereBetween('task_records.submitted_at', [$from, $to]),
            default => null,
        };
    }

    private function applySearch(Builder $query, Request $request): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = $request->string('search');
        $query->where(function ($q) use ($search) {
            $q->where('assigned_task', 'like', "%{$search}%")
                ->orWhereHas('technician', fn ($u) => $u->where('name', 'like', "%{$search}%"));
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'task' => $query
                ->orderBy('task_records.assigned_task')
                ->orderByDesc('task_records.created_at'),
            'technician' => $query
                ->leftJoin('users as report_technicians', 'report_technicians.id', '=', 'task_records.technician_id')
                ->orderByRaw('report_technicians.name IS NULL')
                ->orderBy('report_technicians.name')
                ->orderByDesc('task_records.created_at'),
            default => $query
                ->orderByDesc('task_records.created_at')
                ->orderByDesc('task_records.updated_at'),
        };
    }

    private function currentBusinessWeek(): array
    {
        $from = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $to = $from->copy()->addDays(4)->endOfDay();

        return [$from, $to];
    }

    private function validSort(string $sort): string
    {
        return in_array($sort, ['date', 'task', 'technician'], true) ? $sort : 'date';
    }

    private function admin(): User
    {
        return User::query()->whereKey(session('auth_user_id'))->firstOrFail();
    }

    private function reportFilename(string $shortCode, string $extension): string
    {
        return 'Registro_'.$shortCode.'.'.$extension;
    }

    private function reportResponse(string $docx, string $shortCode)
    {
        $docxFilename = $this->reportFilename($shortCode, 'docx');
        $pdf = $this->pdfConverter->convert($docx, $docxFilename);

        if ($pdf !== null) {
            return $this->pdfResponse($pdf, $this->reportFilename($shortCode, 'pdf'), 'inline');
        }

        return $this->docxResponse($docx, $docxFilename);
    }

    private function docxResponse(string $docx, string $filename)
    {
        return response($docx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function pdfResponse(string $pdf, string $filename, string $disposition)
    {
        $disposition = $disposition === 'attachment' ? 'attachment' : 'inline';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
