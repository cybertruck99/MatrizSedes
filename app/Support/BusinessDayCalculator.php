<?php

namespace App\Support;

use App\Models\SpecialDay;
use Carbon\Carbon;

class BusinessDayCalculator
{
    public function __construct(private readonly BoliviaNationalHolidayCalendar $nationalHolidayCalendar)
    {
    }

    public function calculate(string $startDate, int $businessDays): string
    {
        $this->nationalHolidayCalendar->ensureForTaskDate($startDate);

        $specialDays = SpecialDay::query()
            ->where('active', true)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $date = Carbon::parse($startDate)->startOfDay();
        $remainingDays = $businessDays;

        while ($remainingDays > 0) {
            $date->addDay();

            if ($date->isWeekend() || $specialDays->has($date->toDateString())) {
                continue;
            }

            $remainingDays--;
        }

        return $date->toDateString();
    }
}
