<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('truck_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('truck_number')->index(); // NOT NULL, indexed for searching
            $table->date('date')->default(now());
            $table->decimal('advance', 15, 2)->default(0);

            // Diesel information
            $table->decimal('diesel_liters', 15, 2)->default(0);
            $table->decimal('diesel_rate_per_liter', 15, 2)->default(0);
            $table->decimal('diesel_amount', 15, 2)->default(0); // liters * rate
            $table->string('diesel_location')->nullable();

            // Spare parts (JSON array: [{name, amount}])
            $table->json('spare_parts')->nullable();
            $table->decimal('spare_parts_cost', 15, 2)->default(0); // Sum of spare parts amounts

            // Maintenance details (JSON array: [{detail, cost}])
            $table->json('maintenance_details')->nullable();
            $table->decimal('maintenance_cost', 15, 2)->default(0); // Sum of maintenance costs

            // Calculated totals
            $table->decimal('total_amount', 15, 2)->default(0); // diesel_amount + spare_parts_cost + maintenance_cost
            $table->decimal('balance', 15, 2)->default(0); // total_amount - advance

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('truck_expenses');
    }
};
