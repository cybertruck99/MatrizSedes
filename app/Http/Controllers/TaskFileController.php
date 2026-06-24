<?php

namespace App\Http\Controllers;

use App\Models\TaskFile;
use App\Models\TaskRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class TaskFileController extends Controller
{
    public function show(TaskFile $file)
    {
        $this->authorizeFile($file);

        return $this->inlineResponse(
            $file->file_path,
            $file->original_name ?: basename($file->file_path),
            $file->mime_type
        );
    }

    public function download(TaskFile $file)
    {
        $this->authorizeFile($file);
        $this->ensureFileExists($file->file_path);

        return Storage::disk('public')->download(
            $file->file_path,
            $this->safeDownloadName($file->original_name ?: basename($file->file_path))
        );
    }

    public function showLegacy(TaskRecord $task)
    {
        $this->authorizeTask($task);
        abort_unless($task->uploaded_file_path, 404);

        return $this->inlineResponse(
            $task->uploaded_file_path,
            basename($task->uploaded_file_path)
        );
    }

    public function downloadLegacy(TaskRecord $task)
    {
        $this->authorizeTask($task);
        abort_unless($task->uploaded_file_path, 404);
        $this->ensureFileExists($task->uploaded_file_path);

        return Storage::disk('public')->download(
            $task->uploaded_file_path,
            $this->safeDownloadName(basename($task->uploaded_file_path))
        );
    }

    private function authorizeFile(TaskFile $file): void
    {
        $file->loadMissing('task');
        abort_unless($file->task, 404);
        $this->authorizeTask($file->task);
    }

    private function authorizeTask(TaskRecord $task): void
    {
        $role = session('auth_user.role');
        $isAdmin = $role === 'admin';
        $isOwner = (int) $task->technician_id === (int) session('auth_user_id');

        abort_unless($isAdmin || $isOwner, 403);
    }

    private function inlineResponse(string $path, string $name, ?string $mimeType = null)
    {
        $this->ensureFileExists($path);

        $disk = Storage::disk('public');
        $name = $this->safeDownloadName($name);
        $fallbackName = Str::ascii($name) ?: 'archivo';
        $fallbackName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $fallbackName);

        return response()->file($disk->path($path), [
            'Content-Type' => $mimeType ?: $disk->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $name,
                $fallbackName
            ),
        ]);
    }

    private function ensureFileExists(string $path): void
    {
        abort_unless(Storage::disk('public')->exists($path), 404);
    }

    private function safeDownloadName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '_', $name);

        return mb_substr(trim((string) $name) ?: 'archivo', 0, 240);
    }
}
