<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_record_id', 'uploaded_by', 'file_path', 'original_name', 'mime_type', 'size_bytes',
    ];

    public function task()
    {
        return $this->belongsTo(TaskRecord::class, 'task_record_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->original_name ?: basename($this->file_path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
