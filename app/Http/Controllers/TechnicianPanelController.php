<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use Illuminate\Http\Request;

class TechnicianPanelController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'todas' => TaskRecord::count(),
            'pendientes' => TaskRecord::where('state', 'pendiente')->count(),
            'cumplidos' => TaskRecord::where('state', 'cumplido')->count(),
            'no_cumplidos' => TaskRecord::where('state', 'no cumplido')->count(),
            'retrasos' => TaskRecord::where('state', 'retraso')->count(),
        ];
        $assigned = TaskRecord::with('technician')->where('technician_id', session('auth_user_id'))->latest()->take(8)->get();
        return view('tecnico.dashboard', compact('stats', 'assigned'));
    }

    public function matrix(Request $request)
    {
        $query = TaskRecord::with('technician')->latest();

        if ($request->filled('estado')) {
            $query->where('state', $request->string('estado'));
        }

        $records = $query->paginate(15)->withQueryString();
        return view('tecnico.matrix', compact('records'));
    }

    public function users(Request $request)
    {
        $query = User::where('active', true)->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $users = $query->paginate(12)->withQueryString();
        return view('tecnico.users', compact('users'));
    }
}
