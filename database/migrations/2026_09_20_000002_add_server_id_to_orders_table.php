<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which waiter an order belongs to previously only lived on
     * tables.server_id -- a live, mutable pointer that gets cleared the
     * moment a table frees up, so there was no durable historical record to
     * build waiter performance reports from. Captured on the order itself
     * at creation time instead.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('server_id')->nullable()->after('user_id')->constrained('servers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('server_id');
        });
    }
};
