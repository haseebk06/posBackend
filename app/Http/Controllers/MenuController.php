<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Models\MenuItemVariant;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\Order;
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
        $tables = Table::with('server')->get();
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
            if (!Order::where('id', $orderId)->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Order not found for this table.'
                ], 404);
            }

            $table->update([
                'status' => $status,
                'payment_status' => $pay,
                'order_id' => $orderId,
                'server_id' => $serverId,
            ]);
        } else {
            $data = [
                'status' => $status,
                'payment_status' => $pay,
            ];

            if ($pay === 'completed') {
                $data['order_id'] = null;
                $data['server_id'] = null;
            }

            $table->update($data);
        }


        return response()->json([
            'status'  => true,
            'message' => 'Table updated successfully.',
            'data'    => $table
        ]);
    }

    // Transfer an in-progress order to another table and/or reassign its waiter.
    // Tables have no reverse reference from Order back to Table, so the Table
    // row is the sole source of truth for which table/waiter currently holds
    // an order - transferring means moving order_id/server_id/status between
    // the two Table rows, not touching the Order itself.
    public function transferTable(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'to_table_id' => 'nullable|exists:tables,id',
            'waiter_id'   => 'nullable|exists:servers,id',
            'order_id'    => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $fromTable = Table::find($id);

        if (!$fromTable) {
            return response()->json([
                'status'  => false,
                'message' => 'Table not found.'
            ], 404);
        }

        if ((int) $fromTable->order_id !== (int) $request->order_id) {
            return response()->json([
                'status'  => false,
                'message' => 'This order is no longer on the selected table.'
            ], 409);
        }

        $toTableId = $request->to_table_id;

        if ($toTableId && (int) $toTableId !== (int) $id) {
            $toTable = Table::find($toTableId);

            if (!$toTable) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Destination table not found.'
                ], 404);
            }

            if ($toTable->order_id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Destination table already has an active order.'
                ], 409);
            }

            // Destination becomes occupied by this order.
            $toTable->update([
                'status'         => false,
                'payment_status' => 'pending',
                'order_id'       => $request->order_id,
                'server_id'      => $request->waiter_id ?? $fromTable->server_id,
            ]);

            // Source frees up, mirroring the "1/completed" reset used when a
            // table's payment completes.
            $fromTable->update([
                'status'         => true,
                'payment_status' => 'completed',
                'order_id'       => null,
                'server_id'      => null,
            ]);

            $result = $toTable->fresh();
        } else {
            // Same table - just reassigning the waiter serving this order.
            $fromTable->update([
                'server_id' => $request->waiter_id,
            ]);

            $result = $fromTable->fresh();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Table transferred successfully.',
            'data'    => $result,
        ]);
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
