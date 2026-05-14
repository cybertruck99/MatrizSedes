<?php

namespace App\Http\Controllers;

use App\Models\SpecialDay;
use Illuminate\Http\Request;

class SpecialDayController extends Controller
{
    public function index()
    {
        $days = SpecialDay::orderByDesc('date')->paginate(15);
        return view('admin.special_days.index', compact('days'));
    }

    public function create()
    {
        return view('admin.special_days.create');
    }

    public function store(Request $request)
    {
        SpecialDay::create($this->validated($request));
        return redirect()->route('admin.special-days.index')->with('success', 'Día especial creado correctamente.');
    }

    public function edit(SpecialDay $specialDay)
    {
        return view('admin.special_days.edit', compact('specialDay'));
    }

    public function update(Request $request, SpecialDay $specialDay)
    {
        $specialDay->update($this->validated($request, $specialDay->id));
        return redirect()->route('admin.special-days.index')->with('success', 'Día especial actualizado correctamente.');
    }

    public function destroy(SpecialDay $specialDay)
    {
        $specialDay->delete();
        return redirect()->route('admin.special-days.index')->with('success', 'Día especial eliminado correctamente.');
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
}
