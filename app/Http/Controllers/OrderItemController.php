<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderItemController extends Controller
{
    /**
     * Display all items for a specific order.
     * GET /api/pos/orders/{orderId}/items
     */
    public function index(Request $request, $orderId): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $items = $order->orderItems()->with('product')->get();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * Display a specific item within an order.
     * GET /api/pos/orders/{orderId}/items/{itemId}
     */
    public function show(Request $request, $orderId, $itemId): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $item = OrderItem::with('product')
            ->where('order_id', $orderId)
            ->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $item,
        ]);
    }

    /**
     * Add a new item to an existing pending order.
     * POST /api/pos/orders/{orderId}/items
     */
    public function store(Request $request, $orderId): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Items can only be added to pending orders.',
            ], 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock for: {$product->name}",
            ], 422);
        }

        $subtotal = $product->price * $validated['quantity'];

        $item = $order->orderItems()->create([
            'product_id' => $product->id,
            'quantity'   => $validated['quantity'],
            'unit_price' => $product->price,
            'subtotal'   => $subtotal,
        ]);

        // Deduct stock and update order total
        $product->decrement('stock', $validated['quantity']);
        $order->increment('total_amount', $subtotal);

        return response()->json([
            'success' => true,
            'message' => 'Item added to order successfully.',
            'data'    => $item->load('product'),
        ], 201);
    }

    /**
     * Update the quantity of an order item.
     * PUT /api/pos/orders/{orderId}/items/{itemId}
     */
    public function update(Request $request, $orderId, $itemId): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Items can only be updated on pending orders.',
            ], 422);
        }

        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found.',
            ], 404);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product    = Product::findOrFail($item->product_id);
        $oldQty     = $item->quantity;
        $newQty     = $validated['quantity'];
        $qtyDiff    = $newQty - $oldQty;

        if ($qtyDiff > 0 && $product->stock < $qtyDiff) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock for: {$product->name}",
            ], 422);
        }

        $newSubtotal  = $product->price * $newQty;
        $subtotalDiff = $newSubtotal - $item->subtotal;

        // Update item
        $item->update([
            'quantity' => $newQty,
            'subtotal' => $newSubtotal,
        ]);

        // Adjust stock and order total
        $product->decrement('stock', $qtyDiff);
        $order->increment('total_amount', $subtotalDiff);

        return response()->json([
            'success' => true,
            'message' => 'Order item updated successfully.',
            'data'    => $item->load('product'),
        ]);
    }

    public function destroy(Request $request, $orderId, $itemId): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Items can only be removed from pending orders.',
            ], 422);
        }

        $item = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found.',
            ], 404);
        }

        // Restore stock and reduce order total
        Product::find($item->product_id)?->increment('stock', $item->quantity);
        $order->decrement('total_amount', $item->subtotal);

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order item removed successfully.',
        ]);
    }
}