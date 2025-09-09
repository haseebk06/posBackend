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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->integer("quantity");
            $table->string("barcode")->nullable();
            $table->string("category")->nullable();
            $table->decimal("costPrice", 10, 2)->nullable();
            $table->decimal("sellingPrice", 10, 2)->nullable();
            $table->integer("stock")->nullable();
            $table->decimal("subtotal", 10, 2);
            $table->string("unit")->nullable();
            $table->boolean('is_return')->default(false);
            $table->string('return_reason')->nullable();
            $table->integer('original_quantity')->nullable();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
