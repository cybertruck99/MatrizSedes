<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use Illuminate\Http\Request;

class MatrixController extends Controller
{
    public function index(Request $request, ?string $filter = null)
    {
        $query = TaskRecord::with('technician')->latest('created_at');

        if ($filter) {
            $state = match ($filter) {
                'cumplidos' => 'cumplido',
                'no-cumplidos' => 'no cumplido',
                'pendientes' => 'pendiente',
                'retrasos' => 'retraso',
                default => null,
            };
            if ($state) {
                $query->where('state', $state);
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('assigned_task', 'like', "%{$search}%")
                  ->orWhereHas('technician', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $history = $request->boolean('historial');
        $records = $history
            ? $query->paginate(30)->withQueryString()
            : $query->take(15)->get();

        return view('admin.matrix.index', compact('records', 'filter', 'history'));
    }

    public function updateCompliance(Request $request, TaskRecord $record)
    {
        $data = $request->validate([
            'compliance' => ['nullable', 'in:SI CUMPLIÓ,NO CUMPLIÓ,RETRASO'],
            'final_observations' => ['nullable', 'string', 'max:3000'],
        ]);

        $state = match ($data['compliance'] ?? null) {
            'SI CUMPLIÓ' => 'cumplido',
            'NO CUMPLIÓ' => 'no cumplido',
            'RETRASO' => 'retraso',
            default => 'pendiente',
        };

        $record->update([
            'compliance' => $data['compliance'] ?? null,
            'state' => $state,
            'compliance_date' => in_array($data['compliance'] ?? null, ['SI CUMPLIÓ', 'RETRASO'], true) ? now()->toDateString() : null,
            'final_observations' => $data['final_observations'] ?? null,
        ]);

        return back()->with('success', 'Matriz actualizada correctamente.');
    }
}
