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
        Schema::create('hold_carts', function (Blueprint $table) {
            $table->id();
            $table->string("holdId");
            $table->string("name");
            $table->integer("quantity");
            $table->string("barcode")->nullable();
            $table->string("category")->nullable();
            $table->decimal("costPrice", 10, 2);
            $table->decimal("sellingPrice", 10, 2);
            $table->integer("stock");
            $table->decimal("subtotal", 10, 2);
            $table->string("unit")->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_carts');
    }
};
