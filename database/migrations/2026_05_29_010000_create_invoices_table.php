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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_number')->unique();
            $table->date('invoice_date');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('store_information_id')->constrained('store_information')->restrictOnDelete();
            $table->string('po_number')->nullable();
            $table->text('invoice_details')->nullable();
            $table->decimal('weight', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('sst_percentage', 8, 2)->default(0);
            $table->decimal('sst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('wh_tax_percentage', 8, 2)->default(0);
            $table->decimal('wh_tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('sst_withholding_tax_percentage', 8, 2)->default(0);
            $table->decimal('sst_withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('net_sst_amount_sbr', 15, 2)->default(0);
            $table->decimal('received', 15, 2)->default(0);
            $table->string('chq_number')->nullable();
            $table->date('cheque_received_date')->nullable();
            $table->enum('invoice_status', ['SBR Paid', 'SBR Pending', 'SBR declined'])->default('SBR Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
