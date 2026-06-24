<?php

namespace App\Http\Controllers;

use App\Models\AdminPermissionRequest;
use App\Models\TaskRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function admin()
    {
        $userId = session('auth_user_id');
        $stats = [
            'pendientes' => TaskRecord::where('technician_id', $userId)->where('state', 'pendiente')->count(),
            'usuarios_en_linea' => $this->onlineUsersCount(),
        ];

        $adminPermissionRequests = AdminPermissionRequest::query()
            ->with('user')
            ->pending()
            ->whereHas('user', function ($query) {
                $query->where('role', 'tecnico')
                    ->where('active', true)
                    ->whereNull('deleted_at');
            })
            ->latest('requested_at')
            ->get()
            ->unique('user_id')
            ->values();

        $recentTasks = TaskRecord::with('technician')->withCount('taskFiles')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentTasks', 'adminPermissionRequests'));
    }

    public function onlineUsers(): JsonResponse
    {
        return response()
            ->json(['count' => $this->onlineUsersCount()])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    private function onlineUsersCount(): int
    {
        return User::query()
            ->where('active', true)
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->count();
    }
}
