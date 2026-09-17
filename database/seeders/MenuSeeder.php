<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Bloom Bite Cafe's printed menu card, transcribed by the owner (the
     * printed item names are decorative Urdu calligraphy that can't be
     * OCR'd reliably). Item names are kept in Roman Urdu to match how
     * they're printed on the card and how staff/customers refer to them.
     * Cold Drink / Water prices weren't legible on the card -- set to 100
     * per the owner, update later if that's off.
     */
    public function run(): void
    {
        // Replace whatever menu data exists (hand-entered through the UI or
        // an earlier ad-hoc seed) with this as the single source of truth.
        // Safe to wipe: orders/sales store each item's name and price as a
        // snapshot at sale time, not a foreign key to menu_items, so past
        // orders/sales are unaffected either way.
        MenuItemVariant::query()->delete();
        MenuItem::query()->delete();
        MenuCategory::query()->delete();

        $menu = [
            'Rolls' => [
                'description' => null,
                'items' => [
                    ['Kebab Roll', 60],
                    ['Chicken Roll', 160],
                    ['Chicken Achari Roll', 180],
                    ['Beef Roll', 180],
                    ['Reshmi Kebab Roll', 180],
                    ['Seekh Kebab Roll', 180],
                ],
            ],
            'BBQ' => [
                'description' => null,
                'items' => [
                    ['Chicken Tikka', 450],
                    ['Malai Tikka', 500],
                    ['Green Tikka', 500],
                    ['Achari Tikka', 500],
                    ['Chicken Boti', 400],
                    ['Malai Boti', 450],
                    ['Green Boti', 450],
                    ['Achari Boti', 450],
                    ['Beef Boti', 450],
                    ['Beef Hariyali Boti', 450],
                    ['Seekh Kebab', 400],
                    ['Dhaga Kebab', 400],
                    ['Reshmi Kebab', 350],
                ],
            ],
            'Other Fried Items' => [
                'description' => null,
                'items' => [
                    ['Anda Ghotala', 200],
                    ['Daal Makhni', 300],
                    ['Daal Chana', 200],
                    ['Daal Mash', 200],
                    ['Chicken Fry', 350],
                    ['Chicken White Fry', 400],
                    ['Chicken Achari Fry', 400],
                    ['Golden Chicken', 450],
                    ['Chicken Qeema', 350],
                    ['Beef Qeema', 400],
                    ['White Qeema', 400],
                    ['Kaleji Fry', 350],
                    ['White Kaleji Fry', 400],
                ],
            ],
            'Additional Items / Drinks' => [
                'description' => null,
                'items' => [
                    ['Masala Fry', 400],
                    ['White Masala Fry', 450],
                    ['Kebab Fry', 200],
                    ['Malai Boti Fry', 600],
                    ['Seekh Kebab Fry', 600],
                    ['Beef Boti Fry', 600],
                    ['Tikka Fry', 600],
                    ['Raita', 50],
                    ['Salad', 50],
                    ['Cold Drink', 100],
                    ['Water', 100],
                    ['Chai Full', 80],
                    ['Green Tea', 100],
                    ['Coffee', 100],
                ],
            ],
            'Deals' => [
                'description' => 'Offers: 5 Rolls ke saath 1 Roll Free. 2 Tikkay ke saath 500ml Cold Drink Free.',
                'items' => [
                    ['Deal 1', 700, '1 Chicken + 2 Shami Kebab + Malai Boti'],
                    ['Deal 2', 1000, '1 Chicken Kebab + 1 Chicken item + 2 Shami Kebab + 300ml Cold Drink'],
                    ['Deal 3', 1200, '1 Chicken Tikka + 2 Seekh Kebab + 500ml Cold Drink'],
                    ['Deal 4', 1500, '1 Reshmi Kebab + 1 Chicken Kebab + 1 Chicken item + 2 Shami Kebab + 300ml Cold Drink'],
                    ['Deal 5', 2000, '3 Cold Drinks + 4 Parathas + 2 Naan + assorted BBQ items'],
                ],
            ],
        ];

        $categorySort = 0;
        foreach ($menu as $categoryName => $categoryData) {
            $category = MenuCategory::create([
                'name' => $categoryName,
                'description' => $categoryData['description'],
                'is_active' => true,
                'sort_order' => $categorySort++,
            ]);

            $itemSort = 0;
            foreach ($categoryData['items'] as $item) {
                [$itemName, $price] = $item;
                $itemDescription = $item[2] ?? null;

                $menuItem = MenuItem::create([
                    'category_id' => $category->id,
                    'name' => $itemName,
                    'description' => $itemDescription,
                    'is_active' => true,
                    'sort_order' => $itemSort++,
                ]);

                MenuItemVariant::create([
                    'menu_item_id' => $menuItem->id,
                    'name' => 'Regular',
                    'costPrice' => 0,
                    'sellingPrice' => $price,
                ]);
            }
        }
    }
}
