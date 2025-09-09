<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuSection;   
use App\Models\MenuItem; 

class MenuSectionController extends Controller
{
    public function index()
    {
        $sections = MenuSection::with('items')->get();
        return response()->json($sections);
    }

    // Store new section
    public function addSections(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        $section = MenuSection::create([
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
            'is_active'   => $request->input('is_active', true),
            'sort_order'  => $request->input('sort_order', 0),
        ]);
    
        return response()->json($section, 201);
    }

    // Show single section with items
    public function show($id)
    {
        return MenuSection::with('items')->findOrFail($id);
    }

    // Update section
    public function update(Request $request, $id)
    {
        $section = MenuSection::findOrFail($id);
        $section->update($request->only('name','description','is_active','sort_order'));
        return response()->json($section);
    }

    // Delete section
    public function destroy($id)
    {
        MenuSection::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function attachItems(Request $request, $id)
    {
        $request->validate([
            'menu_item_ids' => 'required|array',
            'menu_item_ids.*' => 'exists:menu_items,id',
        ]);
    
        $section = MenuSection::findOrFail($id);
        $section->items()->syncWithoutDetaching($request->menu_item_ids);
    
        return response()->json([
            'message' => 'Items assigned',
            'section' => $section->load('items')
        ]);
    }

    public function detachItem($id, $itemId)
    {
        $section = MenuSection::findOrFail($id);
        $section->items()->detach($itemId);

        return response()->json(['message' => 'Item removed']);
    }
}
