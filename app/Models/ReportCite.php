<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCite extends Model
{
    protected $fillable = [
        'year',
        'number',
        'code',
        'report_type',
        'request_key',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'number' => 'integer',
        ];
    }

    public function getShortCodeAttribute(): string
    {
        return 'INF-'.str_pad((string) $this->number, 3, '0', STR_PAD_LEFT);
    }
}
