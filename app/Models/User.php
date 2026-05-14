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
        'role', 'area', 'phone', 'email', 'active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function assignedTasks()
    {
        return $this->hasMany(TaskRecord::class, 'technician_id');
    }

    public function createdTasks()
    {
        return $this->hasMany(TaskRecord::class, 'created_by');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Admin',
            'tecnico' => 'Técnico',
            default => 'User',
        };
    }
}
