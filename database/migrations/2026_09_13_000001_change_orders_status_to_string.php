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
        // orders.status was a fixed enum('completed','pending','returned') with no
        // 'cancelled' value, so a pending order could never be voided -- only
        // completed or transferred. Switched to a plain validated string, same
        // as sales.status, instead of growing the enum forever.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['completed', 'pending', 'returned'])->default('pending')->change();
        });
    }
};
