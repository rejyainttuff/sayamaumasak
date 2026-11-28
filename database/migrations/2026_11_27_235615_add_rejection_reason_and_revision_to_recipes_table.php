<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add rejection reason and revision tracking columns to recipes.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approval_status');
            $table->unsignedInteger('revision_count')->default(0)->after('rejection_reason');
            $table->timestamp('last_revised_at')->nullable()->after('revision_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'revision_count', 'last_revised_at']);
        });
    }
};
