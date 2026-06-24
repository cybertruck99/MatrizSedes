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
            $table->enum('file_review_status', ['new', 'updated'])
                ->nullable()
                ->after('submitted_by');
            $table->timestamp('files_reviewed_at')
                ->nullable()
                ->after('file_review_status');
        });

        DB::table('task_records')
            ->whereNotNull('uploaded_file_path')
            ->update(['file_review_status' => 'new']);
    }

    public function down(): void
    {
        Schema::table('task_records', function (Blueprint $table) {
            $table->dropColumn(['file_review_status', 'files_reviewed_at']);
        });
    }
};
