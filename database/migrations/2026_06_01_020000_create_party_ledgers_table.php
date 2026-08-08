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
        Schema::create('party_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('location_from')->nullable();
            $table->string('location_to')->nullable();
            $table->date('billing_date_from')->nullable();
            $table->date('billing_date_to')->nullable();
            $table->foreignId('po_from_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('po_to_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('port_name')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('vessel_name')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('ledger_date')->nullable();
            $table->string('truck_no')->nullable();
            $table->decimal('weight', 15, 2)->default(0);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_ledgers');
    }
};
