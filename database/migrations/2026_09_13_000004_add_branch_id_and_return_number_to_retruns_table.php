<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retruns', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('return_number')->nullable()->unique()->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('retruns', function (Blueprint $table) {
            $table->dropUnique(['return_number']);
            $table->dropColumn('return_number');
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
