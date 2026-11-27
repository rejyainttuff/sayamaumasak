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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->unsignedInteger('contributor_level')->default(1)->after('role');
            $table->unsignedBigInteger('reading_seconds')->default(0)->after('contributor_level');
            $table->unsignedInteger('approved_recipes_count')->default(0)->after('reading_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'contributor_level',
                'reading_seconds',
                'approved_recipes_count',
            ]);
        });
    }
};
