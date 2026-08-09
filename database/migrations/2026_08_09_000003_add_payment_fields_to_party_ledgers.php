<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_ledgers', function (Blueprint $table) {
            $table->decimal('advance', 15, 2)->nullable()->after('amount');
            $table->decimal('paid_amount', 15, 2)->nullable()->after('advance');
            $table->date('paid_date')->nullable()->after('paid_amount');
            $table->string('cheque_no')->nullable()->after('paid_date');
            $table->string('bank_name')->nullable()->after('cheque_no');
        });
    }

    public function down(): void
    {
        Schema::table('party_ledgers', function (Blueprint $table) {
            $table->dropColumn([
                'advance',
                'paid_amount',
                'paid_date',
                'cheque_no',
                'bank_name',
            ]);
        });
    }
};