<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
{
    $query = Order::with(['user', 'orderItems.product'])
        ->forUser($request->user()->id);

    if ($request->has('status')) {
        $query->byStatus($request->status);
    }

    $orders = $query->latest()->get();

    return response()->json([
        'success' => true,
        'data'    => $orders,
    ]);
}

    public function show(Request $request, $id): JsonResponse
    {
        $order = Order::with(['user', 'orderItems.product'])
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address'   => 'required|string',
            'payment_method'     => 'required|in:cash,credit_card,gcash',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $totalAmount = 0;
            $orderItems  = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    abort(422, "Insufficient stock for: {$product->name}");
                }

                $subtotal     = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $subtotal,
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $order = Order::create([
                'user_id'          => $request->user()->id,
                'shipping_address' => $validated['shipping_address'],
                'payment_method'   => $validated['payment_method'],
                'notes'            => $validated['notes'] ?? null,
                'total_amount'     => $totalAmount,
                'status'           => Order::STATUS_PENDING,
                'payment_status'   => Order::PAYMENT_UNPAID,
                'ordered_at'       => now(),
            ]);

            $order->orderItems()->createMany($orderItems);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data'    => $order->load('orderItems.product'),
            ], 201);
        });
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $order->update([
            'status'       => $request->status,
            'delivered_at' => $request->status === Order::STATUS_DELIVERED ? now() : $order->delivered_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated.',
            'data'    => $order,
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be cancelled.',
            ], 422);
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
        ]);
    }
}
