<?php

namespace Database\Seeders;

use App\Models\Branch;
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
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'phoneNumber' => '03000000001',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Cashier',
            'email' => 'cashier@gmail.com',
            'phoneNumber' => '03000000002',
            'role' => 'cashier',
        ]);

        $branch = Branch::create(['name' => 'Main Branch']);

        Counter::create([
            'name' => 'Counter#01',
            'branch' => 'Main Branch',
            'branch_id' => $branch->id,
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

        $this->call(MenuSeeder::class);
    }
}
