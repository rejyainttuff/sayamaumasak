<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->dropForeign(['food_id']);
            $table->foreignId('food_id')->nullable()->change();
            $table->foreign('food_id')->references('id')->on('foods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->dropForeign(['food_id']);
            $table->foreignId('food_id')->nullable(false)->change();
            $table->foreign('food_id')->references('id')->on('foods');
        });
    }
};