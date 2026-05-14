<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use App\Models\SpecialDay;

class DashboardController extends Controller
{
    public function admin()
    {
        $stats = [
            'registros' => TaskRecord::count(),
            'pendientes' => TaskRecord::where('state', 'pendiente')->count(),
            'cumplidos' => TaskRecord::where('state', 'cumplido')->count(),
            'no_cumplidos' => TaskRecord::where('state', 'no cumplido')->count(),
            'retrasos' => TaskRecord::where('state', 'retraso')->count(),
            'usuarios' => User::where('active', true)->count(),
            'festivos' => SpecialDay::where('active', true)->count(),
        ];

        $recentTasks = TaskRecord::with('technician')->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'recentTasks'));
    }
}
