<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActiveSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_token',
        'user_agent',
        'ip_address',
        'started_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
