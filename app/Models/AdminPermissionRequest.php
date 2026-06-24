<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPermissionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'user_id',
        'status',
        'requested_at',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by')->withTrashed();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
