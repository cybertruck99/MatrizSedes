<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'ci', 'cargo', 'admission_date', 'username', 'password',
        'role', 'area', 'phone', 'email', 'profile_photo_path', 'active',
        'is_base_admin', 'base_credentials_shown_at', 'base_setup_completed_at',
        'last_seen_at', 'active_session_token', 'active_session_started_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'active' => 'boolean',
            'is_base_admin' => 'boolean',
            'base_credentials_shown_at' => 'datetime',
            'base_setup_completed_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'active_session_started_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function assignedTasks()
    {
        return $this->hasMany(TaskRecord::class, 'technician_id');
    }

    public function latestAssignedTask()
    {
        return $this->hasOne(TaskRecord::class, 'technician_id')->latestOfMany('created_at');
    }

    public function createdTasks()
    {
        return $this->hasMany(TaskRecord::class, 'created_by');
    }

    public function passwordRecoveryTokens()
    {
        return $this->hasMany(PasswordRecoveryToken::class);
    }

    public function adminPermissionRequests()
    {
        return $this->hasMany(AdminPermissionRequest::class);
    }

    public function pendingAdminPermissionRequest()
    {
        return $this->hasOne(AdminPermissionRequest::class)
            ->where('status', AdminPermissionRequest::STATUS_PENDING)
            ->latestOfMany('requested_at');
    }

    public function temporaryAdminPermissions()
    {
        return $this->hasMany(TemporaryAdminPermission::class);
    }

    public function activeSessions()
    {
        return $this->hasMany(UserActiveSession::class);
    }

    public function activeTemporaryAdminPermission()
    {
        return $this->hasOne(TemporaryAdminPermission::class)
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latestOfMany('ends_at');
    }

    public function hasActiveTemporaryAdminPermission(): bool
    {
        return $this->role === 'tecnico'
            && $this->temporaryAdminPermissions()->active()->exists();
    }

    public function activePasswordRecoveryToken()
    {
        return $this->hasOne(PasswordRecoveryToken::class)->active()->latestOfMany();
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Administrador',
            'tecnico' => 'Tecnico',
            default => 'Usuario',
        };
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path
            ? asset('storage/'.$this->profile_photo_path)
            : null;
    }

    public function getProfileInitialAttribute(): string
    {
        $ignoredPrefixes = [
            'lic', 'licenciado', 'licenciada', 'ing', 'ingeniero', 'ingeniera',
            'dr', 'dra', 'doctor', 'doctora', 'sr', 'sra', 'srta',
            'arq', 'arquitecto', 'arquitecta', 'abg', 'abogado', 'abogada',
            'msc', 'mg', 'mgs',
        ];

        foreach (preg_split('/\s+/', trim((string) $this->name)) ?: [] as $part) {
            $clean = trim($part, " \t\n\r\0\x0B.");
            $normalized = mb_strtolower($clean);

            if ($clean === '' || in_array($normalized, $ignoredPrefixes, true)) {
                continue;
            }

            return mb_strtoupper(mb_substr($clean, 0, 1));
        }

        return 'U';
    }

    public function getRecentTaskStatusLabelAttribute(): string
    {
        $task = $this->latestAssignedTask;

        if (! $task || $task->state !== 'pendiente') {
            return 'No hay Tareas Pendientes';
        }

        if ($task->due_date && $task->due_date->copy()->endOfDay()->isPast() && ! $task->has_uploaded_files) {
            return 'No Subió Tarea';
        }

        return $task->has_uploaded_files ? 'Tarea Subida' : 'Aun no Subió Tarea';
    }
}
