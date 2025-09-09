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
       Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("branch");
            $table->enum("status", ['open', 'closed', 'maintenance']);
            $table->string("system_id");
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->string("opened_by", 40)->nullable();
            $table->string("closed_by", 40)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
