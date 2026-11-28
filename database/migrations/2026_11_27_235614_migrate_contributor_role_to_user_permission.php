<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Safely migrate any legacy 'contributor' role to role = 'user'
     * with can_submit_recipes = true, preserving all accounts and Admin/Helper roles.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'can_submit_recipes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('can_submit_recipes')->default(false)->after('role');
            });
        }

        // Safely migrate legacy 'contributor' users without deleting any accounts
        DB::table('users')
            ->where('role', 'contributor')
            ->update([
                'role' => 'user',
                'can_submit_recipes' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'can_submit_recipes')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('can_submit_recipes');
            });
        }
    }
};
