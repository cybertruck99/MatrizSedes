<?php

namespace Database\Seeders;

use App\Models\SpecialDay;
use App\Models\TaskRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['username' => 'ADM001'],
            [
                'name' => 'Administrador SEDES',
                'ci' => '1000001',
                'cargo' => 'Responsable de Proyectos y Planificación',
                'admission_date' => '2026-01-10',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'area' => 'AREA PROYECTOS',
                'phone' => '70000001',
                'email' => 'admin@sedespotosi.gob.bo',
                'active' => true,
            ]
        );

        $tecnico = User::updateOrCreate(
            ['username' => 'TEC001'],
            [
                'name' => 'Técnico de Seguimiento',
                'ci' => '2000002',
                'cargo' => 'Técnico Informático',
                'admission_date' => '2026-02-01',
                'password' => Hash::make('tecnico123'),
                'role' => 'tecnico',
                'area' => 'AREA SISTEMAS',
                'phone' => '70000002',
                'email' => 'tecnico@sedespotosi.gob.bo',
                'active' => true,
            ]
        );

        $user = User::updateOrCreate(
            ['username' => 'USR001'],
            [
                'name' => 'Usuario de Proyectos',
                'ci' => '3000003',
                'cargo' => 'Auxiliar de Proyectos',
                'admission_date' => '2026-02-12',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'area' => 'AREA PROYECTOS',
                'phone' => '70000003',
                'email' => 'usuario@sedespotosi.gob.bo',
                'active' => true,
            ]
        );

        $this->saveSpecialDay('2026-05-27', [
            'name' => 'Día de la Madre',
            'description' => 'Día especial considerado no hábil para fines de seguimiento interno.',
            'active' => true,
        ]);

        $this->saveSpecialDay('2026-08-06', [
            'name' => 'Día de la Independencia',
            'description' => 'Feriado nacional.',
            'active' => true,
        ]);

        $tasks = [
            [
                'start_date' => Carbon::now()->subDays(4)->toDateString(),
                'technician_id' => $tecnico->id,
                'state' => 'pendiente',
                'assigned_task' => 'Revisar la consolidación inicial de proyectos registrados por las unidades solicitantes.',
                'business_days_deadline' => 5,
                'initial_observation' => 'Priorizar registros con fecha de entrega próxima.',
            ],
            [
                'start_date' => Carbon::now()->subDays(7)->toDateString(),
                'technician_id' => $user->id,
                'state' => 'cumplido',
                'assigned_task' => 'Actualizar el listado de responsables por proyecto y validar datos de contacto.',
                'business_days_deadline' => 3,
                'initial_observation' => 'Coordinar con secretaría para contraste con cuadernos físicos.',
                'compliance' => 'SI CUMPLIÓ',
                'compliance_date' => Carbon::now()->subDays(2)->toDateString(),
                'final_observations' => 'Datos revisados y completados.',
            ],
            [
                'start_date' => Carbon::now()->subDays(10)->toDateString(),
                'technician_id' => $tecnico->id,
                'state' => 'retraso',
                'assigned_task' => 'Preparar respaldo digital de la matriz antigua en Excel.',
                'business_days_deadline' => 4,
                'initial_observation' => 'El archivo debe quedar disponible para consulta interna.',
                'compliance' => 'RETRASO',
                'compliance_date' => Carbon::now()->toDateString(),
                'final_observations' => 'Se completó con demora por revisión de duplicados.',
            ],
        ];

        foreach ($tasks as $task) {
            $task['due_date'] = $this->calculateDueDate($task['start_date'], $task['business_days_deadline']);
            $task['created_by'] = $admin->id;
            TaskRecord::firstOrCreate(
                ['assigned_task' => $task['assigned_task']],
                $task
            );
        }
    }

    private function saveSpecialDay(string $date, array $attributes): SpecialDay
    {
        $day = SpecialDay::whereDate('date', $date)->first() ?? new SpecialDay(['date' => $date]);
        $day->fill($attributes);
        $day->save();

        return $day;
    }

    private function calculateDueDate(string $startDate, int $businessDays): string
    {
        $specialDays = SpecialDay::where('active', true)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $date = Carbon::parse($startDate);
        $added = 0;

        while ($added < $businessDays) {
            $date->addDay();
            if ($date->isWeekend() || in_array($date->toDateString(), $specialDays, true)) {
                continue;
            }
            $added++;
        }

        return $date->toDateString();
    }
}
