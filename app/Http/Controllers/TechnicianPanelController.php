<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use Illuminate\Http\Request;

class TechnicianPanelController extends Controller
{
    public function dashboard()
    {
        $userId = session('auth_user_id');
        $stats = [
            'pendientes' => TaskRecord::where('technician_id', $userId)->where('state', 'pendiente')->count(),
            'usuarios_en_linea' => $this->onlineUsersCount(),
        ];
        $assigned = TaskRecord::with('technician')->where('technician_id', $userId)->latest()->take(8)->get();
        return view('tecnico.dashboard', compact('stats', 'assigned'));
    }

    public function matrix(Request $request)
    {
        $query = TaskRecord::with('technician')->latest();

        if ($request->filled('estado')) {
            $query->where('state', $request->string('estado'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('assigned_task', 'like', "%{$search}%")
                  ->orWhereHas('technician', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $records = $query->paginate(15)->withQueryString();
        return view('tecnico.matrix', compact('records'));
    }

    public function users(Request $request)
    {
        $query = User::where('active', true)->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(12)->withQueryString();
        return view('tecnico.users', compact('users'));
    }

    private function onlineUsersCount(): int
    {
        return User::query()
            ->where('active', true)
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->count();
    }
}
