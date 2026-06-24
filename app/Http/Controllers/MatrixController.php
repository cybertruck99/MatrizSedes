<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Support\TextFormatter;
use Illuminate\Http\Request;

class MatrixController extends Controller
{
    public function index(Request $request, ?string $filter = null)
    {
        $sort = $request->string('sort', 'recent')->toString();
        $allowedSorts = ['recent', 'technician', 'task', 'pending'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'recent';
        }

        $query = TaskRecord::with('technician')->select('task_records.*');

        if ($filter) {
            match ($filter) {
                'cumplidos' => $query->whereIn('state', ['cumplido', 'retraso']),
                'no-cumplidos' => $query->where('state', 'no cumplido'),
                'pendientes' => $query->where('state', 'pendiente'),
                'retrasos' => $query->where('state', 'retraso'),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('assigned_task', 'like', "%{$search}%")
                    ->orWhereHas('technician', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        match ($sort) {
            'technician' => $query
                ->leftJoin('users as technicians', 'technicians.id', '=', 'task_records.technician_id')
                ->orderByRaw('technicians.name IS NULL')
                ->orderBy('technicians.name')
                ->orderByDesc('task_records.updated_at')
                ->orderByDesc('task_records.created_at'),
            'task' => $query
                ->orderBy('task_records.assigned_task')
                ->orderByDesc('task_records.updated_at')
                ->orderByDesc('task_records.created_at'),
            'pending' => $query
                ->orderByRaw("CASE WHEN task_records.state = 'pendiente' THEN 0 ELSE 1 END")
                ->orderByDesc('task_records.updated_at')
                ->orderByDesc('task_records.created_at'),
            default => $query
                ->orderByDesc('task_records.updated_at')
                ->orderByDesc('task_records.created_at'),
        };

        $history = $request->boolean('historial');
        $records = $history
            ? $query->paginate(30)->withQueryString()
            : $query->take(15)->get();

        return view('admin.matrix.index', compact('records', 'filter', 'history', 'sort'));
    }

    public function updateCompliance(Request $request, TaskRecord $record)
    {
        $data = $request->validate([
            'state' => ['required', 'in:pendiente,cumplido,no cumplido,retraso'],
            'final_observations' => ['nullable', 'string', 'max:3000'],
        ]);

        $compliance = match ($data['state']) {
            'cumplido' => 'SI CUMPLIÓ',
            'no cumplido' => 'NO CUMPLIÓ',
            'retraso' => 'RETRASO',
            default => null,
        };

        $record->update([
            'compliance' => $compliance,
            'state' => $data['state'],
            'compliance_date' => now()->toDateString(),
            'final_observations' => TextFormatter::sentence($data['final_observations'] ?? null),
        ]);

        return back()->with('success', 'Tarea Actualizada correctamente.');
    }
}
