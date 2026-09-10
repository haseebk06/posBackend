<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('business_day_id')->nullable()->after('id')->constrained('business_days')->cascadeOnDelete();
            $table->foreignId('shift_type_id')->nullable()->after('business_day_id')->constrained('shift_types')->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->after('shift_type_id')->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->after('opened_by')->constrained('users')->nullOnDelete();

            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('counter_id')->nullable()->change();
            $table->decimal('opening_cash', 10, 2)->nullable()->default(null)->change();
            $table->decimal('closing_cash', 10, 2)->nullable()->default(null)->change();

            $table->unique(['business_day_id', 'shift_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['business_day_id', 'shift_type_id']);
            $table->dropConstrainedForeignId('business_day_id');
            $table->dropConstrainedForeignId('shift_type_id');
            $table->dropConstrainedForeignId('opened_by');
            $table->dropConstrainedForeignId('closed_by');
        });
    }
};
