<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialDay extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'date', 'description', 'active'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'active' => 'boolean',
        ];
    }
}
