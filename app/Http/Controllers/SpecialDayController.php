<?php

namespace App\Http\Controllers;

use App\Models\SpecialDay;
use App\Models\TaskRecord;
use App\Support\BoliviaNationalHolidayCalendar;
use App\Support\BusinessDayCalculator;
use App\Support\TextFormatter;
use Illuminate\Http\Request;

class SpecialDayController extends Controller
{
    public function __construct(
        private readonly BusinessDayCalculator $businessDayCalculator,
        private readonly BoliviaNationalHolidayCalendar $nationalHolidayCalendar,
    )
    {
    }

    public function index()
    {
        $this->nationalHolidayCalendar->ensureCurrentAndNextYear();
        $days = SpecialDay::orderByDesc('date')->paginate(15);
        return view('admin.special_days.index', compact('days'));
    }

    public function create()
    {
        return view('admin.special_days.create');
    }

    public function store(Request $request)
    {
        SpecialDay::create($this->normalizeData($this->validated($request)));
        $this->recalculateTaskDueDates();

        return redirect()->route('admin.special-days.index')->with('success', 'Día especial creado correctamente.');
    }

    public function edit(SpecialDay $specialDay)
    {
        return view('admin.special_days.edit', compact('specialDay'));
    }

    public function update(Request $request, SpecialDay $specialDay)
    {
        $specialDay->update($this->normalizeData($this->validated($request, $specialDay->id)));
        $this->recalculateTaskDueDates();

        return redirect()->route('admin.special-days.index')->with('success', 'Día especial actualizado correctamente.');
    }

    public function destroy(SpecialDay $specialDay)
    {
        $specialDay->delete();
        $this->recalculateTaskDueDates();

        return redirect()->route('admin.special-days.index')->with('success', 'Día especial eliminado correctamente.');
    }


    private function normalizeData(array $data): array
    {
        $data['name'] = TextFormatter::title($data['name'] ?? null);
        $data['description'] = TextFormatter::sentence($data['description'] ?? null);

        return $data;
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'unique:special_days,date'.($id ? ','.$id : '')],
            'description' => ['nullable', 'string', 'max:2000'],
            'active' => ['required', 'boolean'],
        ]);
    }

    private function recalculateTaskDueDates(): void
    {
        TaskRecord::query()
            ->select(['id', 'start_date', 'business_days_deadline'])
            ->chunkById(100, function ($tasks): void {
                foreach ($tasks as $task) {
                    $task->update([
                        'due_date' => $this->businessDayCalculator->calculate(
                            $task->start_date->toDateString(),
                            (int) $task->business_days_deadline
                        ),
                    ]);
                }
            });
    }
}
