<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('active', true)->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('ci', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['assignedTasks as recent_tasks_count' => function ($q) {
            $q->where('created_at', '>=', now()->subDays(30));
        }])->paginate(12)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ci' => ['nullable', 'string', 'max:30'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'admission_date' => ['nullable', 'date'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,user,tecnico'],
            'area' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);
        $data['active'] = true;

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        $tasks = TaskRecord::where('technician_id', $user->id)->latest()->paginate(10);
        $counts = [
            'pendientes' => TaskRecord::where('technician_id', $user->id)->where('state', 'pendiente')->count(),
            'cumplidos' => TaskRecord::where('technician_id', $user->id)->where('state', 'cumplido')->count(),
            'no_cumplidos' => TaskRecord::where('technician_id', $user->id)->where('state', 'no cumplido')->count(),
            'retrasos' => TaskRecord::where('technician_id', $user->id)->where('state', 'retraso')->count(),
        ];

        return view('admin.users.show', compact('user', 'tasks', 'counts'));
    }

    public function destroy(User $user)
    {
        if ((int) $user->id === (int) session('auth_user_id')) {
            return back()->with('error', 'No puede eliminar el usuario que tiene la sesión actual.');
        }

        $user->update(['active' => false]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario retirado de la lista activa. Queda guardado en historial.');
    }

    public function history(Request $request)
    {
        $query = User::withTrashed()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('ci', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.history', compact('users'));
    }
}
