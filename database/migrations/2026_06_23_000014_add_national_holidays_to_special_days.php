<?php

use App\Support\BoliviaNationalHolidayCalendar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_days', function (Blueprint $table) {
            $table->boolean('is_national_holiday')->default(false)->after('active');
            $table->string('holiday_key', 120)->nullable()->after('is_national_holiday');
        });

        app(BoliviaNationalHolidayCalendar::class)->ensureCurrentAndNextYear();
    }

    public function down(): void
    {
        Schema::table('special_days', function (Blueprint $table) {
            $table->dropColumn(['is_national_holiday', 'holiday_key']);
        });
    }
};
