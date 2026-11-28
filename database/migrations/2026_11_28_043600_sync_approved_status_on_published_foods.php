<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Synchronize approval_status = 'approved' for catalog foods (user_id IS NULL)
     * and foods that already have at least one approved recipe.
     */
    public function up(): void
    {
        if (! Schema::hasTable('foods') || ! Schema::hasColumn('foods', 'approval_status')) {
            return;
        }

        DB::table('foods')
            ->whereNull('user_id')
            ->where('approval_status', 'pending')
            ->update(['approval_status' => 'approved']);

        if (Schema::hasTable('recipes') && Schema::hasColumn('recipes', 'approval_status')) {
            $approvedFoodIds = DB::table('recipes')
                ->where('approval_status', 'approved')
                ->whereNotNull('food_id')
                ->pluck('food_id')
                ->unique()
                ->all();

            if ($approvedFoodIds !== []) {
                DB::table('foods')
                    ->whereIn('id', $approvedFoodIds)
                    ->where('approval_status', 'pending')
                    ->update(['approval_status' => 'approved']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data synchronization migration; no destructive rollback.
    }
};
