<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\MenuCategory;
use App\Models\MenuItem;
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

        $menu = [
            'Beverages' => [
                ['name' => 'Coca Cola', 'cost' => 40, 'price' => 80],
                ['name' => 'Coffee', 'cost' => 60, 'price' => 150],
                ['name' => 'Fresh Lime', 'cost' => 50, 'price' => 120],
            ],
            'Starters' => [
                ['name' => 'Spring Rolls', 'cost' => 100, 'price' => 250],
                ['name' => 'Chicken Wings', 'cost' => 150, 'price' => 350],
            ],
            'Main Course' => [
                ['name' => 'Chicken Karahi', 'cost' => 400, 'price' => 900],
                ['name' => 'Beef Biryani', 'cost' => 300, 'price' => 650],
                ['name' => 'Grilled Fish', 'cost' => 450, 'price' => 950],
            ],
            'Desserts' => [
                ['name' => 'Chocolate Cake', 'cost' => 150, 'price' => 350],
                ['name' => 'Ice Cream', 'cost' => 80, 'price' => 200],
            ],
        ];

        foreach ($menu as $categoryName => $items) {
            $category = MenuCategory::create(['name' => $categoryName]);

            foreach ($items as $item) {
                $menuItem = MenuItem::create([
                    'category_id' => $category->id,
                    'name' => $item['name'],
                ]);

                $menuItem->variants()->create([
                    'name' => 'Regular',
                    'costPrice' => $item['cost'],
                    'sellingPrice' => $item['price'],
                ]);
            }
        }
    }
}
