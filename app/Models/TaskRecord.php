<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date', 'technician_id', 'state', 'assigned_task',
        'business_days_deadline', 'initial_observation', 'due_date',
        'compliance', 'compliance_date', 'final_observations',
        'uploaded_file_path', 'submitted_at', 'submitted_by',
        'file_review_status', 'files_reviewed_at', 'created_by',
        'assigned_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'compliance_date' => 'date',
            'submitted_at' => 'datetime',
            'files_reviewed_at' => 'datetime',
            'assigned_viewed_at' => 'datetime',
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

    public function taskFiles()
    {
        return $this->hasMany(TaskFile::class)->latest();
    }

    public function getStateLabelAttribute(): string
    {
        return match ($this->state) {
            'cumplido' => 'Sí cumplió',
            'no cumplido' => 'No cumplió',
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

    public function getStateBadgeClassAttribute(): string
    {
        return match ($this->state) {
            'cumplido' => 'success',
            'no cumplido' => 'danger',
            'retraso' => 'warning',
            default => 'neutral',
        };
    }

    public function getHasUploadedFilesAttribute(): bool
    {
        if (filled($this->uploaded_file_path)) {
            return true;
        }

        if ($this->relationLoaded('taskFiles')) {
            return $this->taskFiles->isNotEmpty();
        }

        if (array_key_exists('task_files_count', $this->attributes)) {
            return (int) $this->attributes['task_files_count'] > 0;
        }

        return $this->taskFiles()->exists();
    }

    public function getUploadedFilesTotalAttribute(): int
    {
        $count = $this->relationLoaded('taskFiles')
            ? $this->taskFiles->count()
            : $this->taskFiles()->count();

        return $count ?: (filled($this->uploaded_file_path) ? 1 : 0);
    }

    public function getUploadedFilesSummaryAttribute(): string
    {
        $groups = [];

        foreach ($this->uploadedFileTypes() as $type) {
            $groups[$type] = ($groups[$type] ?? 0) + 1;
        }

        if ($groups === []) {
            return 'Sin envíos';
        }

        $parts = [];
        foreach ($groups as $type => $count) {
            $parts[] = $count.' '.$this->pluralFileType($type, $count);
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return implode(', ', array_slice($parts, 0, -1)).' y '.end($parts);
    }

    public function getFilesReviewedAtLabelAttribute(): string
    {
        return $this->files_reviewed_at
            ? self::spanishDateTime($this->files_reviewed_at)
            : 'Primera revisión';
    }

    public function getDeliveryStatusLabelAttribute(): string
    {
        return $this->has_uploaded_files
            ? 'Enviado para Revisión'
            : 'Aún no se Realizó Envíos';
    }

    public function getDeliveryStatusClassAttribute(): string
    {
        return $this->has_uploaded_files ? 'info' : 'neutral';
    }

    public function getFileReviewLabelAttribute(): ?string
    {
        return match ($this->file_review_status) {
            'new' => 'Nuevo envío pendiente de revisión',
            'updated' => 'Archivos modificados pendientes de revisión',
            default => null,
        };
    }

    public function getIsNewAssignmentAttribute(): bool
    {
        return $this->assigned_viewed_at === null;
    }

    public static function spanishDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return $date->format('j').' de '.$months[(int) $date->format('n')].' de '.$date->format('Y');
    }

    public static function spanishDateTime(CarbonInterface $date): string
    {
        $hour = (int) $date->format('G');
        $period = $hour < 12 ? 'a.m.' : 'p.m.';

        return self::spanishDate($date).' a las '.$date->format('g:i').' '.$period;
    }

    private function uploadedFileTypes(): array
    {
        $types = [];
        $files = $this->relationLoaded('taskFiles')
            ? $this->taskFiles
            : $this->taskFiles()->get(['original_name', 'file_path', 'mime_type']);

        foreach ($files as $file) {
            $types[] = $this->fileTypeLabel(
                $file->display_name,
                (string) $file->mime_type
            );
        }

        if ($types === [] && filled($this->uploaded_file_path)) {
            $types[] = $this->fileTypeLabel((string) $this->uploaded_file_path, '');
        }

        return $types;
    }

    private function fileTypeLabel(string $name, string $mime): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'heif'], true)) {
            return 'imagen';
        }

        if (str_starts_with($mime, 'video/')) {
            return $extension ? 'video .'.$extension : 'video';
        }

        if (str_starts_with($mime, 'audio/')) {
            return $extension ? 'audio .'.$extension : 'audio';
        }

        return $extension ? 'documento .'.$extension : 'documento';
    }

    private function pluralFileType(string $type, int $count): string
    {
        if ($count === 1) {
            return $type;
        }

        return match ($type) {
            'imagen' => 'imágenes',
            'documento' => 'documentos',
            'video' => 'videos',
            'audio' => 'audios',
            default => str_starts_with($type, 'documento .')
                ? str_replace('documento .', 'documentos .', $type)
                : (str_ends_with($type, 's') ? $type : $type.'s'),
        };
    }
}
