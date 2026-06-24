<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->where('username', 'adminbase1')->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name' => 'Administrador Base',
            'ci' => 'ADMINBASE1',
            'cargo' => 'Administrador del Sistema',
            'admission_date' => now()->toDateString(),
            'username' => 'adminbase1',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'area' => 'AREA SISTEMAS',
            'phone' => null,
            'email' => 'adminbase1@sedespotosi.gob.bo',
            'active' => true,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('username', 'adminbase1')->delete();
    }
};
