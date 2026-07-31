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
        Schema::table('retruns', function (Blueprint $table) {
            $table->decimal('gst', 10, 2)->nullable()->after('tax');
            $table->decimal('service_charges', 10, 2)->nullable()->after('gst');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retruns', function (Blueprint $table) {
            $table->dropColumn(['gst', 'service_charges']);
        });
    }
};
