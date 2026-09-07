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
            $table->decimal('tyre_amount', 15, 2)->default(0); // Total tyre cost

            // Tyre details (JSON array of strings)
            $table->json('tyre_details')->nullable(); // e.g., ["New tyres", "Balancing", "Alignment"]

            // Total calculations
            $table->decimal('total_amount', 15, 2)->default(0); // Same as tyre_amount

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('truck_tyres');
    }
};
