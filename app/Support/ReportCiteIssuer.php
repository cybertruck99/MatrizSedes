<?php

namespace App\Support;

use App\Models\ReportCite;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReportCiteIssuer
{
    public function issue(string $type, User $admin, ?CarbonInterface $date = null, ?string $requestKey = null): ReportCite
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $requestKey = $requestKey ? mb_substr($requestKey, 0, 120, 'UTF-8') : null;

        return DB::transaction(function () use ($type, $admin, $year, $requestKey) {
            DB::table('report_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('report_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($requestKey) {
                $existing = ReportCite::query()
                    ->where('request_key', $requestKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $lastNumber = (int) ($sequence->last_number ?? 0);
            $number = $lastNumber + 1;

            DB::table('report_sequences')
                ->where('year', $year)
                ->update([
                    'last_number' => $number,
                    'updated_at' => now(),
                ]);

            return ReportCite::create([
                'year' => $year,
                'number' => $number,
                'code' => $this->formatCode($number, $year),
                'report_type' => $type,
                'request_key' => $requestKey,
                'generated_by' => $admin->id,
            ]);
        });
    }

    private function formatCode(int $number, int $year): string
    {
        return 'SEDES/PT/UPP/INF-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'/'.$year;
    }
}
