<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_base_admin')->default(false)->after('active');
            $table->timestamp('base_credentials_shown_at')->nullable()->after('is_base_admin');
            $table->timestamp('base_setup_completed_at')->nullable()->after('base_credentials_shown_at');
        });

        DB::table('users')
            ->where('username', 'adminbase1')
            ->update(['is_base_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_base_admin',
                'base_credentials_shown_at',
                'base_setup_completed_at',
            ]);
        });
    }
};
