<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\MenuItemVariant;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\Table;

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

    public function updateCategory(Request $request, $id)
    {
        $category = MenuCategory::findOrFail($id);
        $category->update($request->only(['name', 'description', 'is_active', 'sort_order', 'parent_id']));
        return response()->json(['success' => true, 'category' => $category]);
    }

    public function deleteCategory($id)
    {
        $category = MenuCategory::findOrFail($id);
        $category->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted']);
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

    public function updateItem(Request $request, $id)
    {
        $item = MenuItem::findOrFail($id);
        $item->update($request->only(['name', 'description', 'is_active', 'sort_order', 'category_id']));
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function deleteItem($id)
    {
        $item = MenuItem::findOrFail($id);
        $item->delete();
        return response()->json(['success' => true, 'message' => 'Item deleted']);
    }

    // ----------------- VARIANT -----------------
   public function storeVariant(Request $request, $itemId)
{
    try {
        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'costPrice' => 'required|numeric|min:0',
            'sellingPrice' => 'required|numeric|min:0',
        ]);

        // Check if parent menu item exists
        $menuItem = MenuItem::find($itemId);
        if (!$menuItem) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found.'
            ], 404);
        }

        // Create variant
        $variant = MenuItemVariant::create([
            'menu_item_id' => $itemId,
            'name' => $validated['name'],
            'costPrice' => $validated['costPrice'],
            'sellingPrice' => $validated['sellingPrice'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variant created successfully.',
            'variant' => $variant
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Handle validation errors
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        // Catch all other errors
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while saving the variant.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function updateVariant(Request $request, $id)
    {
        $variant = MenuItemVariant::findOrFail($id);
        $variant->update($request->only(['name', 'costPrice', 'sellingPrice']));
        return response()->json(['success' => true, 'variant' => $variant]);
    }

    public function deleteVariant($id)
    {
        $variant = MenuItemVariant::findOrFail($id);
        $variant->delete();
        return response()->json(['success' => true, 'message' => 'Variant deleted']);
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
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $table = Table::create([
            'name'   => $request->name,
            'status' => true,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Table created successfully.',
            'data'    => $table
        ], 201);
    }

    //  Get all tables
    public function index()
    {
        $tables = Table::with('server')
    ->orderByRaw("CAST(SUBSTRING_INDEX(name, '#', -1) AS UNSIGNED) ASC")
    ->get();
        return response()->json([
            'status' => true,
            'data'   => $tables
        ]);
    }

    //  Get single table
    public function show($id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'status'  => false,
                'message' => 'Table not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $table
        ]);
    }
    
        //  Update table
    public function addOrder($id, $orderId)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'status'  => false,
                'message' => 'Table not found.'
            ], 404);
        }

        $data['table_id'] = $orderId;

        $table->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Table updated successfully.',
            'data'    => $table
        ]);
    }

    //  Update table
    public function update(Request $request, $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'status'  => false,
                'message' => 'Table not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $table->update($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'Table updated successfully.',
            'data'    => $table
        ]);
    }
    
    //  Update table
    public function updateStatus(Request $request, $id, $status, $pay, $orderId = null, $serverId = null)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'status'  => false,
                'message' => 'Table not found.'
            ], 404);
        }
        
        if ($orderId != null && $serverId != null) {
            $table->update([
                'status' => $status,
                'payment_status' => $pay,
                'order_id' => $orderId,
                'server_id' => $serverId,
            ]);
        } else {
            $table->update([
                'status' => $status,
                'payment_status' => $pay,
            ]);
        }


        return response()->json([
            'status'  => true,
            'message' => 'Table updated successfully.',
            'data'    => $table
        ]);
    }
        
    //  transfer table
    public function transferTable(Request $request, $fromTableId)
    {
        $request->validate([
            'to_table_id'   => 'nullable', // can be empty
            'waiter_id'     => 'nullable',
            'order_id'      => 'required',
        ]);
    
        try {
            DB::beginTransaction();
    
            $fromTable = Table::findOrFail($fromTableId);
            $order_id  = $request->order_id;
            $waiter_id = $request->waiter_id ?? $fromTable->server_id;
    
            // CASE 1: Transfer to another table
            if (!empty($request->to_table_id)) {
                $toTable = Table::findOrFail($request->to_table_id);
    
                // Ensure destination table is available
                if ($toTable->status != 1) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'The selected table is not available.',
                    ], 400);
                }
    
                // Unlink order from old table
                $fromTable->update([
                    'status'          => 1, // mark old table available
                    'payment_status'  => 'completed',
                    'order_id'        => null,
                    'server_id'       => null,
                ]);
    
                // Assign order to the new table
                $toTable->update([
                    'status'          => 0, // occupied
                    'payment_status'  => 'pending',
                    'order_id'        => $order_id,
                    'server_id'       => $waiter_id,
                ]);
    
                $result = [
                    'from_table' => $fromTable,
                    'to_table'   => $toTable,
                    'order_id'   => $order_id,
                ];
            } 
            // CASE 2: No "to_table_id" provided → only update the current (from) table
            else {
                $fromTable->update([
                    'server_id'       => $waiter_id,
                    'payment_status'  => 'pending',
                    'order_id'        => $order_id,
                ]);
    
                $result = [
                    'updated_table' => $fromTable,
                    'order_id'      => $order_id,
                ];
            }
    
            DB::commit();
    
            return response()->json([
                'status'  => true,
                'message' => !empty($request->to_table_id)
                    ? 'Table transferred successfully.'
                    : 'Table updated successfully.',
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Failed to transfer or update table.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    //  Delete table
    public function destroy($id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'status'  => false,
                'message' => 'Table not found.'
            ], 404);
        }

        $table->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Table deleted successfully.'
        ]);
    }
}
