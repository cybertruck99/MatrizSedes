<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'active_session_token')) {
                $table->string('active_session_token', 128)->nullable()->after('last_seen_at')->index();
            }

            if (! Schema::hasColumn('users', 'active_session_started_at')) {
                $table->timestamp('active_session_started_at')->nullable()->after('active_session_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'active_session_started_at')) {
                $table->dropColumn('active_session_started_at');
            }

            if (Schema::hasColumn('users', 'active_session_token')) {
                $table->dropColumn('active_session_token');
            }
        });
    }
};
