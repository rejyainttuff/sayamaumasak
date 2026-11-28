<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add rejection_reason column to culinary_stories for Admin moderation workflow.
     */
    public function up(): void
    {
        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
