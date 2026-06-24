<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;

class AdminTaskController extends Controller
{
    public function show(TaskRecord $task)
    {
        if ((int) $task->technician_id === (int) session('auth_user_id') && $task->assigned_viewed_at === null) {
            $task->forceFill(['assigned_viewed_at' => now()])->save();
        }

        if ($task->file_review_status !== null) {
            TaskRecord::query()
                ->whereKey($task->getKey())
                ->whereNotNull('file_review_status')
                ->update([
                    'file_review_status' => null,
                    'files_reviewed_at' => now(),
                ]);

            $task->refresh();
        }

        $task->load(['technician', 'submitter', 'taskFiles.uploader']);

        return view('admin.tasks.show', compact('task'));
    }
}
