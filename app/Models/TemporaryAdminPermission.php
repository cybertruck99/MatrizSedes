<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryAdminPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'granted_by',
        'admin_permission_request_id',
        'starts_at',
        'ends_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by')->withTrashed();
    }

    public function request()
    {
        return $this->belongsTo(AdminPermissionRequest::class, 'admin_permission_request_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
