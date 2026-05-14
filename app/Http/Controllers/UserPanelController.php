<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserPanelController extends Controller
{
    public function dashboard()
    {
        $userId = session('auth_user_id');
        $stats = [
            'pendientes' => TaskRecord::where('technician_id', $userId)->where('state', 'pendiente')->count(),
            'cumplidos' => TaskRecord::where('technician_id', $userId)->where('state', 'cumplido')->count(),
            'no_cumplidos' => TaskRecord::where('technician_id', $userId)->where('state', 'no cumplido')->count(),
            'retrasos' => TaskRecord::where('technician_id', $userId)->where('state', 'retraso')->count(),
        ];
        $recentTasks = TaskRecord::where('technician_id', $userId)->latest()->take(8)->get();

        return view('user.dashboard', compact('stats', 'recentTasks'));
    }

    public function tasks(Request $request)
    {
        $query = TaskRecord::where('technician_id', session('auth_user_id'))->latest();

        if ($request->filled('estado')) {
            $query->where('state', $request->string('estado'));
        }

        $tasks = $query->paginate(15)->withQueryString();
        return view('user.tasks', compact('tasks'));
    }

    public function showTask(TaskRecord $task)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);
        return view('user.task_show', compact('task'));
    }

    public function upload(Request $request, TaskRecord $task)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);

        $request->validate([
            'archivo' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar'],
        ]);

        if ($task->uploaded_file_path) {
            Storage::disk('public')->delete($task->uploaded_file_path);
        }

        $path = $request->file('archivo')->store('tasks', 'public');
        $task->update([
            'uploaded_file_path' => $path,
            'submitted_at' => now(),
            'submitted_by' => session('auth_user_id'),
        ]);

        return back()->with('success', 'Archivo subido correctamente.');
    }

    public function profile()
    {
        $user = User::findOrFail(session('auth_user_id'));
        return view('user.profile', compact('user'));
    }
}
