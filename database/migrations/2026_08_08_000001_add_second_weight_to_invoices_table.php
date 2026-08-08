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
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('size_description_2')->nullable()->after('size_description');
            $table->decimal('weight_2', 15, 2)->nullable()->after('weight');
            $table->decimal('rate_2', 15, 2)->nullable()->after('rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'size_description_2',
                'weight_2',
                'rate_2',
            ]);
        });
    }
};
