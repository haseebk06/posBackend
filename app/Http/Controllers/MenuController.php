<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // ----------------- CATEGORY -----------------
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:menu_categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $category = MenuCategory::create($validated);

        return response()->json(['success' => true, 'category' => $category], 201);
    }

    public function getCategories()
    {
        $categories = MenuCategory::with('children')->orderBy('sort_order')->get();
        return response()->json($categories);
    }

    // ----------------- ITEM -----------------
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $item = MenuItem::create($validated);

        return response()->json(['success' => true, 'item' => $item], 201);
    }

    public function getItemsByCategory($categoryId)
    {
        $items = MenuItem::with('variants')->where('category_id', $categoryId)->get();
        return response()->json($items);
    }

    // ----------------- VARIANT -----------------
    public function storeVariant(Request $request, $itemId)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $variant = MenuItemVariant::create([
            'menu_item_id' => $itemId,
            'name' => $validated['name'],
            'price' => $validated['price'],
        ]);

        return response()->json(['success' => true, 'variant' => $variant], 201);
    }

    // ----------------- FULL MENU -----------------
    public function getFullMenu()
    {
        $menu = MenuCategory::with(['items.variants'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($menu);
    }
}
