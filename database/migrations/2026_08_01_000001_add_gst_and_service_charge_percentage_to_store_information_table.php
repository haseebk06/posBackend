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
        Schema::table('store_information', function (Blueprint $table) {
            // Matches this table's existing camelCase column convention
            // (storeName, taxId, ...) rather than snake_case.
            $table->decimal('gstPercentage', 5, 2)->nullable()->default(15)->after('currency');
            $table->decimal('serviceChargePercentage', 5, 2)->nullable()->default(10)->after('gstPercentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_information', function (Blueprint $table) {
            $table->dropColumn(['gstPercentage', 'serviceChargePercentage']);
        });
    }
};
