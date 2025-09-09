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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('finalTotal', 10, 2)->nullable();
            $table->enum('paymentMethod', ['cash', 'card', 'mobile', 'return'])->nullable();
            $table->decimal('amountReceived', 10, 2)->nullable();
            $table->decimal('changeAmount', 10, 2)->nullable();
            $table->string('mode')->nullable();
            $table->string('original_order_id')->nullable();
            $table->string('return_reason')->nullable();
            $table->enum('status', ['completed', 'pending', 'returned'])->default('pending');
            $table->boolean('is_return')->default(false);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
