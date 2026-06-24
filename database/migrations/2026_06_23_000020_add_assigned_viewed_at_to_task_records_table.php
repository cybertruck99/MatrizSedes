<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_records', function (Blueprint $table) {
            if (! Schema::hasColumn('task_records', 'assigned_viewed_at')) {
                $table->timestamp('assigned_viewed_at')->nullable()->after('created_by')->index();
            }
        });

        DB::table('task_records')
            ->whereNull('assigned_viewed_at')
            ->update(['assigned_viewed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('task_records', function (Blueprint $table) {
            if (Schema::hasColumn('task_records', 'assigned_viewed_at')) {
                $table->dropColumn('assigned_viewed_at');
            }
        });
    }
};
