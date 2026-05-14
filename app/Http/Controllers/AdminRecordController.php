<?php

namespace App\Http\Controllers;

use App\Models\SpecialDay;
use App\Models\TaskRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = TaskRecord::with('technician')->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('assigned_task', 'like', "%{$search}%")
                  ->orWhereHas('technician', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $records = $query->paginate(15)->withQueryString();

        return view('admin.records.index', compact('records'));
    }

    public function create()
    {
        $users = User::where('active', true)
            ->whereIn('role', ['tecnico', 'user'])
            ->orderBy('name')
            ->get();

        return view('admin.records.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = session('auth_user_id');
        $data['due_date'] = $this->calculateDueDate($data['start_date'], (int) $data['business_days_deadline']);
        $data['state'] = $data['state'] ?? 'pendiente';

        TaskRecord::create($data);

        return redirect()->route('admin.records.index')->with('success', 'Registro creado correctamente.');
    }

    public function edit(TaskRecord $record)
    {
        $users = User::where('active', true)
            ->whereIn('role', ['tecnico', 'user'])
            ->orderBy('name')
            ->get();

        return view('admin.records.edit', compact('record', 'users'));
    }

    public function update(Request $request, TaskRecord $record)
    {
        $data = $this->validatedData($request);
        $data['due_date'] = $this->calculateDueDate($data['start_date'], (int) $data['business_days_deadline']);

        $record->update($data);

        return redirect()->route('admin.records.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(TaskRecord $record)
    {
        $record->delete();

        return redirect()->route('admin.records.index')->with('success', 'Registro eliminado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'state' => ['required', 'in:cumplido,no cumplido,pendiente,retraso'],
            'assigned_task' => ['required', 'string', 'max:3000'],
            'business_days_deadline' => ['required', 'integer', 'min:1', 'max:365'],
            'initial_observation' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function calculateDueDate(string $startDate, int $businessDays): string
    {
        $specialDays = SpecialDay::where('active', true)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $date = Carbon::parse($startDate);
        $added = 0;

        while ($added < $businessDays) {
            $date->addDay();
            if ($date->isWeekend() || in_array($date->toDateString(), $specialDays, true)) {
                continue;
            }
            $added++;
        }

        return $date->toDateString();
    }
}
