<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_active_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 128)->unique();
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_active_sessions');
    }
};
