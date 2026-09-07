<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('truck_tyres', function (Blueprint $table) {
            $table->id();
            $table->string('truck_number')->index();
            $table->date('date')->default(now());

            // Tyre information
            $table->string('tyre_type')->nullable(); // e.g., Radial, Tubeless, etc.
            $table->integer('tyre_quantity')->default(0); // Number of tyres
            $table->decimal('tyre_amount', 15, 2)->default(0); // Cost per tyre or total

            // Tyre details (JSON array: [{detail, cost}])
            $table->json('tyre_details')->nullable();
            $table->decimal('tyre_details_cost', 15, 2)->default(0); // Sum of details costs

            // Total calculations
            $table->decimal('total_amount', 15, 2)->default(0); // tyre_amount + tyre_details_cost

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('truck_tyres');
    }
};
