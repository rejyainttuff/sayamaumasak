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
        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->json('gallery_images')->nullable()->after('thumbnail');
            $table->string('video_url')->nullable()->after('gallery_images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('culinary_stories', function (Blueprint $table) {
            $table->dropColumn(['gallery_images', 'video_url']);
        });
    }
};
