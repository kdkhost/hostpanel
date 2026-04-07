<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['client:id,name,email', 'items'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['client', 'items', 'services', 'invoices']));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'client_id'              => 'required|exists:clients,id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.billing_cycle'  => 'required|string',
            'payment_method'         => 'required|string',
        ]);

        $client = \App\Models\Client::findOrFail($request->client_id);
        $order  = app(OrderService::class)->create($client, $request->items, $request->coupon_code, $request->payment_method);

        return response()->json($order->load('items'), 201);
    }
}
