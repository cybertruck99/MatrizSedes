<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $specialDays = DB::table('special_days')
            ->where('active', true)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        DB::table('task_records')
            ->select(['id', 'start_date', 'business_days_deadline'])
            ->orderBy('id')
            ->chunkById(100, function ($tasks) use ($specialDays): void {
                foreach ($tasks as $task) {
                    $date = Carbon::parse($task->start_date)->startOfDay();
                    $remainingDays = (int) $task->business_days_deadline;

                    while ($remainingDays > 0) {
                        $date->addDay();

                        if ($date->isWeekend() || $specialDays->has($date->toDateString())) {
                            continue;
                        }

                        $remainingDays--;
                    }

                    DB::table('task_records')
                        ->where('id', $task->id)
                        ->update(['due_date' => $date->toDateString()]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
