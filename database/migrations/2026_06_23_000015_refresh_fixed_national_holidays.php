<?php

use App\Support\BoliviaNationalHolidayCalendar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(BoliviaNationalHolidayCalendar::class)->ensureCurrentAndNextYear();
    }

    public function down(): void
    {
        //
    }
};
