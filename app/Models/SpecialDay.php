<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialDay extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'date', 'description', 'active', 'is_national_holiday', 'holiday_key'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'active' => 'boolean',
            'is_national_holiday' => 'boolean',
        ];
    }
}
