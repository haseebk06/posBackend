<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('invoice_status', ['SBR Paid', 'SRB Pending', 'SBR declined'])
                ->default('SRB Pending')
                ->change();
        });

        DB::table('invoices')
            ->where('invoice_status', 'SBR Pending')
            ->update(['invoice_status' => 'SRB Pending']);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('invoice_status', ['SBR Paid', 'SBR Pending', 'SBR declined'])
                ->default('SBR Pending')
                ->change();
        });

        DB::table('invoices')
            ->where('invoice_status', 'SRB Pending')
            ->update(['invoice_status' => 'SBR Pending']);
    }
};