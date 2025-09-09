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
        Schema::create('retruns', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 10, 2);
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('finalTotal', 10, 2);
            $table->enum('paymentMethod', ['cash', 'card', 'mobile', 'return']);
            $table->decimal('amountReceived', 10, 2);
            $table->decimal('changeAmount', 10, 2)->nullable();
            $table->foreignId('sale_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retruns');
    }
};
