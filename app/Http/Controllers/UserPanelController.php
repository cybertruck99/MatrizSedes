<?php

namespace App\Http\Controllers;

use App\Models\TaskFile;
use App\Models\TaskRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserPanelController extends Controller
{
    private const TASK_FILE_EXTENSIONS = 'pdf,txt,csv,doc,docx,xls,xlsx,xlsm,xlsb,ods,ppt,pptx,pptm,pps,ppsx,odp,jpg,jpeg,png,webp,gif,bmp,heic,heif,mp4,mov,avi,mkv,webm,m4v,3gp,zip,rar';

    private const TASK_FILE_MAX_KILOBYTES = 51200;

    private const TASK_FILE_MAX_TOTAL_BYTES = 52428800;

    private const TASK_FILE_MAX_COUNT = 30;

    public function dashboard()
    {
        $userId = session('auth_user_id');
        $stats = [
            'pendientes' => TaskRecord::where('technician_id', $userId)->where('state', 'pendiente')->count(),
            'cumplidos' => TaskRecord::where('technician_id', $userId)->where('state', 'cumplido')->count(),
            'no_cumplidos' => TaskRecord::where('technician_id', $userId)->where('state', 'no cumplido')->count(),
            'retrasos' => TaskRecord::where('technician_id', $userId)->where('state', 'retraso')->count(),
            'usuarios_en_linea' => $this->onlineUsersCount(),
        ];
        $recentTasks = TaskRecord::withCount('taskFiles')
            ->where('technician_id', $userId)
            ->latest()
            ->take(8)
            ->get();

        return view('user.dashboard', compact('stats', 'recentTasks'));
    }

    public function tasks(Request $request)
    {
        $query = TaskRecord::withCount('taskFiles')
            ->where('technician_id', session('auth_user_id'))
            ->latest();

        if ($request->filled('estado')) {
            $query->where('state', $request->string('estado'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('assigned_task', 'like', "%{$search}%");
        }

        $tasks = $query->paginate(15)->withQueryString();

        return view('user.tasks', compact('tasks'));
    }

    public function uploadTasks(Request $request)
    {
        $showAll = $request->boolean('todas');
        $query = TaskRecord::with('taskFiles')
            ->where('technician_id', session('auth_user_id'))
            ->latest();

        if (! $showAll) {
            $query->where('state', 'pendiente')
                ->whereNull('uploaded_file_path')
                ->whereDoesntHave('taskFiles');
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('assigned_task', 'like', "%{$search}%");
        }

        $tasks = $query->paginate(12)->withQueryString();

        return view('user.upload_tasks', compact('tasks', 'showAll'));
    }

    public function showTask(TaskRecord $task)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);

        if ($task->assigned_viewed_at === null) {
            $task->forceFill(['assigned_viewed_at' => now()])->save();
        }

        $task->load('taskFiles.uploader');
        $preparedUploads = $this->getPreparedUploads($task);

        return view('user.task_show', compact('task', 'preparedUploads'));
    }

    public function prepareUpload(Request $request, TaskRecord $task)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);

        $request->validate([
            'archivo' => [
                'required',
                'file',
                'max:'.self::TASK_FILE_MAX_KILOBYTES,
                'extensions:'.self::TASK_FILE_EXTENSIONS,
            ],
        ], $this->taskFileValidationMessages('archivo'));

        $drafts = $this->getPreparedUploads($task);
        $existingFiles = $task->taskFiles()->count()
            + ($task->uploaded_file_path && ! $task->taskFiles()->exists() ? 1 : 0);

        if (($existingFiles + count($drafts)) >= self::TASK_FILE_MAX_COUNT) {
            throw ValidationException::withMessages([
                'archivo' => 'La tarea solo puede tener un máximo de 30 archivos en total.',
            ]);
        }

        /** @var UploadedFile $file */
        $file = $request->file('archivo');
        $preparedSize = array_sum(array_column($drafts, 'size_bytes'));

        if (($preparedSize + $file->getSize()) > self::TASK_FILE_MAX_TOTAL_BYTES) {
            throw ValidationException::withMessages([
                'archivo' => 'La suma total de los archivos seleccionados no puede superar los 50 MB.',
            ]);
        }

        $token = (string) Str::uuid();
        $safeName = $this->sanitizeOriginalName($file->getClientOriginalName());
        $directory = 'task-upload-drafts/'.session('auth_user_id').'/'.$task->id.'/'.$token;
        $path = $file->storeAs($directory, $safeName, 'public');

        $drafts[$token] = [
            'path' => $path,
            'name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'created_at' => now()->timestamp,
        ];

        session()->put($this->preparedUploadsSessionKey($task), $drafts);

        return response()->json([
            'token' => $token,
            'name' => $drafts[$token]['name'],
            'size' => $drafts[$token]['size_bytes'],
        ]);
    }

    public function clearPreparedUploads(TaskRecord $task)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);

        $this->deletePreparedUploads($task);

        return response()->json(['cleared' => true]);
    }

    public function upload(Request $request, TaskRecord $task)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);

        $request->validate([
            'prepared_files' => ['nullable', 'array', 'max:'.self::TASK_FILE_MAX_COUNT],
            'prepared_files.*' => ['string', 'uuid'],
            'archivos' => ['nullable', 'array', 'max:'.self::TASK_FILE_MAX_COUNT],
            'archivos.*' => [
                'required',
                'file',
                'max:'.self::TASK_FILE_MAX_KILOBYTES,
                'extensions:'.self::TASK_FILE_EXTENSIONS,
            ],
        ], $this->taskFileValidationMessages('archivos.*'));

        $files = $request->file('archivos', []);
        $preparedTokens = array_values(array_unique(array_filter(
            $request->input('prepared_files', []),
            fn ($token) => is_string($token) && $token !== ''
        )));
        $availableDrafts = $this->getPreparedUploads($task);
        $preparedFiles = [];

        foreach ($preparedTokens as $token) {
            if (! isset($availableDrafts[$token]) || ! Storage::disk('public')->exists($availableDrafts[$token]['path'])) {
                throw ValidationException::withMessages([
                    'archivos' => 'Uno de los archivos preparados ya no está disponible. Selecciónelo nuevamente.',
                ]);
            }

            $preparedFiles[$token] = $availableDrafts[$token];
        }

        if ($preparedFiles === [] && $files === []) {
            throw ValidationException::withMessages([
                'archivos' => 'Debe seleccionar al menos un archivo.',
            ]);
        }

        $preparedSize = array_sum(array_column($preparedFiles, 'size_bytes'));
        $directSize = array_sum(array_map(
            fn (UploadedFile $file) => $file->getSize(),
            $files
        ));

        if (($preparedSize + $directSize) > self::TASK_FILE_MAX_TOTAL_BYTES) {
            throw ValidationException::withMessages([
                'archivos' => 'La suma total de los archivos seleccionados no puede superar los 50 MB.',
            ]);
        }

        $taskFileCount = $task->taskFiles()->count();
        $hadFiles = $taskFileCount > 0 || filled($task->uploaded_file_path);
        $existingFiles = $taskFileCount + ($task->uploaded_file_path && $taskFileCount === 0 ? 1 : 0);

        if (($existingFiles + count($preparedFiles) + count($files)) > self::TASK_FILE_MAX_COUNT) {
            return back()
                ->withErrors(['archivos' => 'La tarea solo puede tener un máximo de 30 archivos en total.'])
                ->withInput();
        }

        $lastPath = null;
        $storedPaths = [];

        try {
            DB::transaction(function () use ($preparedFiles, $files, $task, $hadFiles, &$lastPath, &$storedPaths) {
                foreach ($preparedFiles as $draft) {
                    $path = $this->movePreparedFileToTask($task, $draft);
                    $lastPath = $path;
                    $storedPaths[] = $path;

                    TaskFile::create([
                        'task_record_id' => $task->id,
                        'uploaded_by' => session('auth_user_id'),
                        'file_path' => $path,
                        'original_name' => $draft['name'],
                        'mime_type' => $draft['mime_type'],
                        'size_bytes' => $draft['size_bytes'],
                    ]);
                }

                foreach ($files as $file) {
                    $path = $this->storeWithOriginalName($task, $file);
                    $lastPath = $path;
                    $storedPaths[] = $path;

                    TaskFile::create([
                        'task_record_id' => $task->id,
                        'uploaded_by' => session('auth_user_id'),
                        'file_path' => $path,
                        'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                        'mime_type' => $file->getClientMimeType(),
                        'size_bytes' => $file->getSize(),
                    ]);
                }

                $task->update([
                    'uploaded_file_path' => $lastPath,
                    'submitted_at' => now(),
                    'submitted_by' => session('auth_user_id'),
                    'file_review_status' => $hadFiles ? 'updated' : 'new',
                    'files_reviewed_at' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        $remainingDrafts = array_diff_key($availableDrafts, $preparedFiles);
        session()->put($this->preparedUploadsSessionKey($task), $remainingDrafts);

        return back()->with('success', 'Documentos subidos correctamente. Puede agregar más archivos si corresponde.');
    }

    public function deleteFile(TaskRecord $task, TaskFile $file)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);
        abort_unless((int) $file->task_record_id === (int) $task->id, 403);

        Storage::disk('public')->delete($file->file_path);
        $deletedPath = $file->file_path;
        $file->delete();

        $latestPath = $task->taskFiles()->latest()->value('file_path');
        $remainingPath = $latestPath;

        if (! $remainingPath && $task->uploaded_file_path !== $deletedPath) {
            $remainingPath = $task->uploaded_file_path;
        }

        $task->update($remainingPath ? [
            'uploaded_file_path' => $remainingPath,
            'submitted_at' => now(),
            'submitted_by' => session('auth_user_id'),
            'file_review_status' => 'updated',
            'files_reviewed_at' => null,
        ] : [
            'uploaded_file_path' => null,
            'submitted_at' => null,
            'submitted_by' => null,
            'file_review_status' => null,
            'files_reviewed_at' => null,
        ]);

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    public function deleteLegacyFile(TaskRecord $task)
    {
        abort_unless((int) $task->technician_id === (int) session('auth_user_id'), 403);
        abort_if($task->taskFiles()->exists(), 409, 'La tarea ya utiliza el registro de archivos múltiples.');

        if ($task->uploaded_file_path) {
            Storage::disk('public')->delete($task->uploaded_file_path);
            $task->update([
                'uploaded_file_path' => null,
                'submitted_at' => null,
                'submitted_by' => null,
                'file_review_status' => null,
                'files_reviewed_at' => null,
            ]);
        }

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    public function profile()
    {
        $user = User::findOrFail(session('auth_user_id'));
        $pendingAdminPermissionRequest = $user->role === 'tecnico'
            ? $user->adminPermissionRequests()->pending()->latest('requested_at')->first()
            : null;
        $activeTemporaryAdminPermission = $user->role === 'tecnico'
            ? $user->temporaryAdminPermissions()->active()->latest('ends_at')->first()
            : null;

        return view('user.profile', compact(
            'user',
            'pendingAdminPermissionRequest',
            'activeTemporaryAdminPermission'
        ));
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'profile_photo.required' => 'Seleccione una fotografía.',
            'profile_photo.image' => 'El archivo debe ser una imagen válida.',
            'profile_photo.mimes' => 'La fotografía debe estar en formato JPG, JPEG, PNG o WEBP.',
            'profile_photo.max' => 'La fotografía debe pesar como máximo 5 MB.',
        ]);

        $user = User::findOrFail(session('auth_user_id'));
        $previousPath = $user->profile_photo_path;
        $path = $request->file('profile_photo')->store('profile-photos/'.$user->id, 'public');

        $user->update(['profile_photo_path' => $path]);

        if ($previousPath && $previousPath !== $path) {
            Storage::disk('public')->delete($previousPath);
        }

        return back()->with('success', 'Fotografía de perfil actualizada correctamente.');
    }

    public function deleteProfilePhoto()
    {
        $user = User::findOrFail(session('auth_user_id'));

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return back()->with('success', 'Fotografía de perfil eliminada correctamente.');
    }

    private function storeWithOriginalName(TaskRecord $task, UploadedFile $file): string
    {
        $path = $this->availableTaskFilePath($task, $file->getClientOriginalName());
        $stored = $file->storeAs(dirname($path), basename($path), 'public');

        if ($stored === false) {
            throw new \RuntimeException('No se pudo guardar el archivo de la tarea.');
        }

        return $path;
    }

    private function movePreparedFileToTask(TaskRecord $task, array $draft): string
    {
        $path = $this->availableTaskFilePath($task, $draft['name']);

        if (! Storage::disk('public')->move($draft['path'], $path)) {
            throw new \RuntimeException('No se pudo mover el archivo preparado a la tarea.');
        }

        return $path;
    }

    private function availableTaskFilePath(TaskRecord $task, string $originalName): string
    {
        $disk = Storage::disk('public');
        $directory = 'tasks/'.$task->id;
        $safeName = $this->sanitizeOriginalName($originalName);
        $extension = mb_strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $baseName = pathinfo($safeName, PATHINFO_FILENAME);
        $candidate = $safeName;
        $counter = 2;

        while ($disk->exists($directory.'/'.$candidate)) {
            $candidate = $baseName.' ('.$counter.')'.($extension ? '.'.$extension : '');
            $counter++;
        }

        return $directory.'/'.$candidate;
    }

    private function sanitizeOriginalName(string $originalName): string
    {
        $originalName = basename(str_replace('\\', '/', $originalName));
        $extension = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/u', '_', $baseName);
        $baseName = trim((string) $baseName, ". \t\n\r\0\x0B");
        $baseName = mb_substr($baseName ?: 'archivo', 0, 160);

        return $baseName.($extension ? '.'.$extension : '');
    }

    private function preparedUploadsSessionKey(TaskRecord $task): string
    {
        return 'task_upload_drafts.'.session('auth_user_id').'.'.$task->id;
    }

    private function getPreparedUploads(TaskRecord $task): array
    {
        $key = $this->preparedUploadsSessionKey($task);
        $drafts = session()->get($key, []);
        $validDrafts = [];

        foreach ($drafts as $token => $draft) {
            $isRecent = ($draft['created_at'] ?? 0) >= now()->subHours(2)->timestamp;
            $exists = isset($draft['path']) && Storage::disk('public')->exists($draft['path']);

            if ($isRecent && $exists) {
                $validDrafts[$token] = $draft;
                continue;
            }

            if ($exists) {
                Storage::disk('public')->delete($draft['path']);
            }
        }

        session()->put($key, $validDrafts);

        return $validDrafts;
    }

    private function deletePreparedUploads(TaskRecord $task): void
    {
        $drafts = session()->get($this->preparedUploadsSessionKey($task), []);
        Storage::disk('public')->delete(array_column($drafts, 'path'));
        session()->forget($this->preparedUploadsSessionKey($task));
    }

    private function taskFileValidationMessages(string $field): array
    {
        return [
            $field.'.required' => 'Debe seleccionar al menos un archivo.',
            $field.'.uploaded' => 'El archivo no pudo cargarse. Verifique que no supere los 50 MB.',
            $field.'.max' => 'Un archivo individual no puede superar los 50 MB.',
            $field.'.extensions' => 'Formato no admitido. Use documentos, imágenes, videos, ZIP o RAR.',
            'archivos.max' => 'Solo puede seleccionar hasta 30 archivos por carga.',
            'prepared_files.max' => 'Solo puede seleccionar hasta 30 archivos por carga.',
        ];
    }

    private function onlineUsersCount(): int
    {
        return User::query()
            ->where('active', true)
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->count();
    }
}
