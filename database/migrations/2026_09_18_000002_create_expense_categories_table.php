<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('expense'); // expense | income
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Seed the common categories here (not in a seeder) so they exist in
        // production without having to re-run db:seed on the live database.
        $now = now();
        $defaults = [
            ['name' => 'Inventory/Stock', 'type' => 'expense'],
            ['name' => 'Rent', 'type' => 'expense'],
            ['name' => 'Salaries', 'type' => 'expense'],
            ['name' => 'Utilities', 'type' => 'expense'],
            ['name' => 'Maintenance', 'type' => 'expense'],
            ['name' => 'Supplies', 'type' => 'expense'],
            ['name' => 'Miscellaneous', 'type' => 'expense'],
            ['name' => 'Other Income', 'type' => 'income'],
        ];

        DB::table('expense_categories')->insert(array_map(fn ($c) => [
            'name' => $c['name'],
            'type' => $c['type'],
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $defaults));
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
