<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_records', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('state', ['cumplido', 'no cumplido', 'pendiente', 'retraso'])->default('pendiente');
            $table->text('assigned_task');
            $table->unsignedInteger('business_days_deadline')->default(1);
            $table->date('due_date')->nullable();
            $table->text('initial_observation')->nullable();
            $table->enum('compliance', ['SI CUMPLIÓ', 'NO CUMPLIÓ', 'RETRASO'])->nullable();
            $table->date('compliance_date')->nullable();
            $table->text('final_observations')->nullable();
            $table->string('uploaded_file_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_records');
    }
};
