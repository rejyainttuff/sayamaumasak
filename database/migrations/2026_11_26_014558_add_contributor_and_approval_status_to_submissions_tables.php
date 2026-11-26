<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('category_id')->constrained('users')->nullOnDelete();
            $table->string('approval_status')->default('pending')->after('thumbnail');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('food_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('food_id')->constrained('users')->nullOnDelete();
            $table->string('approval_status')->default('pending')->after('thumbnail');
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('approval_status');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('approval_status');
        });
    }
};