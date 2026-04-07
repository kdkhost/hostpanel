<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected function client() { return Auth::guard('client')->user(); }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $orders = Order::with(['items', 'services.product:id,name'])
                ->where('client_id', $this->client()->id)
                ->orderByDesc('created_at')
                ->paginate(15);
            return response()->json($orders);
        }
        return view('client.orders.index');
    }

    public function show(Request $request, Order $order)
    {
        if ($order->client_id !== $this->client()->id) abort(403);
        $order->load(['items', 'services.product', 'invoices', 'coupon']);
        if ($request->expectsJson()) return response()->json($order);
        return view('client.orders.show', compact('order'));
    }

    public function catalog(Request $request)
    {
        $groups = ProductGroup::with(['products' => function ($q) {
            $q->where('active', true)
              ->where('hidden', false)
              ->with(['pricing' => fn($p) => $p->where('active', true)])
              ->orderBy('sort_order');
        }])->where('active', true)->where('show_on_order', true)->orderBy('sort_order')->get();

        if ($request->expectsJson()) return response()->json($groups);
        return view('client.orders.catalog', compact('groups'));
    }

    public function product(Request $request, Product $product)
    {
        if (!$product->active || $product->hidden) abort(404);
        $product->load(['pricing' => fn($q) => $q->where('active', true), 'group']);
        if ($request->expectsJson()) return response()->json($product);
        return view('client.orders.configure', compact('product'));
    }

    public function checkout()
    {
        return view('client.orders.checkout');
    }

    public function place(Request $request): JsonResponse
    {
        $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.billing_cycle'    => 'required|string',
            'items.*.domain'           => 'nullable|string|max:255',
            'coupon_code'              => 'nullable|string|max:50',
            'payment_method'           => 'required|string',
        ]);

        try {
            $order = app(OrderService::class)->create(
                $this->client(),
                $request->items,
                $request->coupon_code,
                $request->payment_method
            );

            return response()->json([
                'message'  => 'Pedido realizado com sucesso!',
                'order_id' => $order->id,
                'redirect' => url('/cliente/pedidos/' . $order->id),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);

        $coupon = \App\Models\Coupon::where('code', strtoupper($request->code))->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['valid' => false, 'message' => 'Cupom inválido ou expirado.'], 422);
        }

        // Suporta amount direto ou product_id+cycle para cálculo
        $amount = (float) $request->amount;
        if (!$amount && $request->product_id && $request->cycle) {
            $pricing = \App\Models\ProductPricing::where('product_id', $request->product_id)
                ->where('billing_cycle', $request->cycle)
                ->where('active', true)
                ->first();
            $amount = $pricing ? (float) $pricing->price : 0;
        }

        $discount = $amount > 0 ? $coupon->calculateDiscount($amount) : 0;

        return response()->json([
            'valid'    => true,
            'message'  => 'Cupom aplicado! Desconto de R$ ' . number_format($discount, 2, ',', '.'),
            'discount' => $discount,
            'coupon'   => $coupon->only(['code', 'type', 'value', 'description']),
        ]);
    }

    public function placeFromCart(Request $request): JsonResponse
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cycle'      => 'required|string',
            'items.*.domain'     => 'nullable|string|max:255',
            'items.*.coupon'     => 'nullable|string|max:50',
        ]);

        // Mapear itens para o formato esperado pelo OrderService
        $mappedItems = collect($request->items)->map(fn($item) => [
            'product_id'    => $item['product_id'],
            'billing_cycle' => $item['cycle'],
            'domain'        => $item['domain'] ?? null,
        ])->toArray();

        $couponCode = collect($request->items)->pluck('coupon')->filter()->first();

        try {
            $order = app(OrderService::class)->create(
                $this->client(),
                $mappedItems,
                $couponCode,
                'pending'
            );

            return response()->json([
                'message' => 'Pedido criado com sucesso!',
                'order'   => ['id' => $order->id],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
