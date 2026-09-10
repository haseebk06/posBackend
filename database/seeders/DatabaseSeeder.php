<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\Server;
use App\Models\ShiftType;
use App\Models\Table;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phoneNumber' => '03000000001',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'phoneNumber' => '03000000002',
            'role' => 'cashier',
        ]);

        Counter::create([
            'name' => 'Counter#01',
            'branch' => 'Main Branch',
            'status' => 'closed',
            'system_id' => 'PC-01',
            'user_id' => $admin->id,
        ]);

        foreach (range(1, 10) as $i) {
            Table::create([
                'name' => 'Table ' . $i,
                'seats' => 4,
                'status' => true,
            ]);
        }

        Server::create(['name' => 'Waiter One']);
        Server::create(['name' => 'Waiter Two']);

        ShiftType::create(['name' => 'Morning']);
    }
}
