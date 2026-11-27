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
        Schema::create('contributor_level_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 30);
            $table->foreignId('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->integer('levels_gained');
            $table->unsignedInteger('level_before');
            $table->unsignedInteger('level_after');
            $table->string('description');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->unique(['source', 'recipe_id'], 'contributor_level_logs_unique_recipe_bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributor_level_logs');
    }
};
