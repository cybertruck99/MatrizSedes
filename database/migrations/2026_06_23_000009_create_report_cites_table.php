<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('report_cites', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('number');
            $table->string('code')->unique();
            $table->string('report_type', 40);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'number']);
            $table->index(['report_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cites');
        Schema::dropIfExists('report_sequences');
    }
};
