<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use App\Support\AdminPasswordVerifier;
use App\Support\BusinessDayCalculator;
use App\Support\TextFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminRecordController extends Controller
{
    public function __construct(
        private readonly BusinessDayCalculator $businessDayCalculator,
        private readonly AdminPasswordVerifier $adminPasswordVerifier
    ) {
    }

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
            ->whereIn('role', ['admin', 'tecnico', 'user'])
            ->orderBy('name')
            ->get();

        return view('admin.records.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $this->normalizeData($this->validatedData($request));
        $data['created_by'] = session('auth_user_id');
        $data['due_date'] = $this->businessDayCalculator->calculate($data['start_date'], (int) $data['business_days_deadline']);
        $data['state'] = 'pendiente';

        TaskRecord::create($data);

        return redirect()->route('admin.records.index')->with('success', 'Registro creado correctamente.');
    }

    public function edit(TaskRecord $record)
    {
        $users = User::where('active', true)
            ->whereIn('role', ['admin', 'tecnico', 'user'])
            ->orderBy('name')
            ->get();

        return view('admin.records.edit', compact('record', 'users'));
    }

    public function update(Request $request, TaskRecord $record)
    {
        $data = $this->normalizeData($this->validatedData($request));
        $shouldResetAssignmentNotice = (int) $record->technician_id !== (int) $data['technician_id'];
        $data['due_date'] = $this->businessDayCalculator->calculate($data['start_date'], (int) $data['business_days_deadline']);
        unset($data['state']);

        if ($shouldResetAssignmentNotice) {
            $data['assigned_viewed_at'] = null;
        }

        $record->update($data);

        return redirect()->route('admin.records.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Request $request, TaskRecord $record)
    {
        $this->adminPasswordVerifier->verify($request);

        $paths = $record->taskFiles()->pluck('file_path')->all();
        if ($record->uploaded_file_path) {
            $paths[] = $record->uploaded_file_path;
        }

        $record->delete();
        Storage::disk('public')->delete(array_values(array_unique($paths)));

        return redirect()->route('admin.records.index')->with('success', 'Registro eliminado correctamente.');
    }


    private function normalizeData(array $data): array
    {
        $data['assigned_task'] = TextFormatter::sentence($data['assigned_task'] ?? null);
        $data['initial_observation'] = TextFormatter::sentence($data['initial_observation'] ?? null);

        return $data;
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date'],
            'technician_id' => ['required', 'exists:users,id'],
            'assigned_task' => ['required', 'string', 'max:3000'],
            'business_days_deadline' => ['required', 'integer', 'min:1', 'max:365'],
            'initial_observation' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
