<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('content_type', 20);
            $table->unsignedBigInteger('content_id');
            $table->string('session_token', 64);
            $table->unsignedInteger('seconds_read')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'content_type', 'content_id', 'session_token'], 'reading_sessions_unique_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_sessions');
    }
};
