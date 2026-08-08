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
            $table->decimal('wh_tax_percentage', 8, 2)->nullable()->after('sst');
            $table->decimal('sst_withholding_tax_percentage', 8, 2)->nullable()->after('wh_tax_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_information', function (Blueprint $table) {
            $table->dropColumn([
                'wh_tax_percentage',
                'sst_withholding_tax_percentage',
            ]);
        });
    }
};
