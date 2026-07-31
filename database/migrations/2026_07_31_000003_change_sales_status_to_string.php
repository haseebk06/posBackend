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
        // sales.status was a fixed enum('completed','refunded','returned') with no
        // 'partially_returned' value, so partial returns (a normal, common case for a
        // restaurant POS) failed to save and rolled back the whole return transaction.
        // Switched to a plain validated string instead of growing the enum forever.
        Schema::table('sales', function (Blueprint $table) {
            $table->string('status')->default('completed')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('status', ['completed', 'refunded', 'returned'])->default('completed')->change();
        });
    }
};
