<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retruns', function (Blueprint $table) {
            $table->foreignId('counter_session_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('retruns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counter_session_id');
        });
    }
};
