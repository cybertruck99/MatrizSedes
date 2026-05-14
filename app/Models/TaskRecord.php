<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date', 'technician_id', 'state', 'assigned_task',
        'business_days_deadline', 'initial_observation', 'due_date',
        'compliance', 'compliance_date', 'final_observations',
        'uploaded_file_path', 'submitted_at', 'submitted_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'compliance_date' => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by')->withTrashed();
    }

    public function getStateLabelAttribute(): string
    {
        return match ($this->state) {
            'cumplido' => 'Cumplido',
            'no cumplido' => 'No cumplido',
            'retraso' => 'Retraso',
            default => 'Pendiente',
        };
    }

    public function getStateClassAttribute(): string
    {
        return match ($this->state) {
            'cumplido' => 'row-success',
            'no cumplido' => 'row-danger',
            'retraso' => 'row-warning',
            default => 'row-neutral',
        };
    }
}
