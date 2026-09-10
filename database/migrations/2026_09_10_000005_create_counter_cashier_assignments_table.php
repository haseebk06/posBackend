<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_cashier_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->constrained('counters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['counter_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_cashier_assignments');
    }
};
