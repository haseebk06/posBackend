<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Backfill: turn each existing distinct counters.branch name into a real Branch row.
        $branchNames = DB::table('counters')->whereNotNull('branch')->distinct()->pluck('branch');

        foreach ($branchNames as $branchName) {
            $branchId = DB::table('branches')->insertGetId([
                'name' => $branchName,
                'last_order_sequence' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('counters')->where('branch', $branchName)->update(['branch_id' => $branchId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('counters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
