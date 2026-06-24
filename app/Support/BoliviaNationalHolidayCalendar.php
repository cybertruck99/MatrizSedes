<?php

namespace App\Support;

use App\Models\SpecialDay;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class BoliviaNationalHolidayCalendar
{
    public function ensureCurrentAndNextYear(): void
    {
        $year = (int) now()->format('Y');
        $this->ensureYears([$year, $year + 1]);
    }

    public function ensureForTaskDate(string $startDate): void
    {
        $year = (int) Carbon::parse($startDate)->format('Y');
        $this->ensureYears([$year, $year + 1]);
    }

    public function ensureYears(array $years): void
    {
        foreach (array_unique(array_map('intval', $years)) as $year) {
            $holidays = $this->holidaysForYear($year);
            $dates = array_column($holidays, 'date');
            $validKeys = array_column($holidays, 'key');
            $knownKeys = array_merge($validKeys, $this->obsoleteHolidayKeys());

            SpecialDay::query()
                ->where('is_national_holiday', true)
                ->whereYear('date', $year)
                ->whereIn('holiday_key', $knownKeys)
                ->whereNotIn('date', $dates)
                ->delete();

            foreach ($holidays as $holiday) {
                SpecialDay::query()->updateOrCreate(
                    ['date' => $holiday['date']],
                    [
                        'holiday_key' => $holiday['key'],
                        'name' => $holiday['name'],
                        'description' => $holiday['description'],
                        'active' => true,
                        'is_national_holiday' => true,
                    ]
                );
            }
        }
    }

    public function holidaysForYear(int $year): array
    {
        $holidays = [
            $this->fixed($year, 1, 1, 'bolivia-new-year', 'Año Nuevo', 'Feriado nacional. Base: DS 2750.'),
            $this->fixed($year, 1, 22, 'bolivia-plurinational-state', 'Día de la Creación del Estado Plurinacional de Bolivia', 'Feriado nacional. Base: DS 2750.'),
            $this->fixed($year, 5, 1, 'bolivia-labor-day', 'Día del Trabajo', 'Feriado nacional. Base: DS 2750.'),
            $this->andeanNewYear($year),
            $this->fixed($year, 8, 6, 'bolivia-independence-day', 'Día de la Independencia de Bolivia', 'Feriado nacional.'),
            $this->fixed($year, 11, 2, 'bolivia-all-souls', 'Día de Todos los Difuntos', 'Feriado nacional.'),
            $this->fixed($year, 12, 25, 'bolivia-christmas', 'Navidad', 'Feriado nacional.'),
        ];

        return array_values($holidays);
    }

    private function fixed(int $year, int $month, int $day, string $key, string $name, string $description): array
    {
        return [
            'key' => $key,
            'date' => CarbonImmutable::create($year, $month, $day)->toDateString(),
            'name' => $name,
            'description' => $description,
        ];
    }

    private function andeanNewYear(int $year): array
    {
        $date = CarbonImmutable::create($year, 6, 21);
        $description = 'Feriado nacional. Año Nuevo Andino Amazónico Chaqueño.';

        return [
            'key' => 'bolivia-andean-amazonian-chaqueno-new-year',
            'date' => $date->toDateString(),
            'name' => 'Año Nuevo Andino Amazónico Chaqueño',
            'description' => $description,
        ];
    }

    private function obsoleteHolidayKeys(): array
    {
        return [
            'bolivia-new-year-complementary',
            'bolivia-carnival-monday',
            'bolivia-carnival-tuesday',
            'bolivia-good-friday',
            'bolivia-corpus-christi',
            'bolivia-corpus-christi-additional',
            'bolivia-independence-additional',
        ];
    }
}
