<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('party_ledgers', fn (Blueprint $table) => $table->softDeletes());
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('party_ledgers', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};