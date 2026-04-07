<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPricing;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected BillingService     $billing,
        protected ProvisioningService $provisioning,
        protected NotificationService $notification,
    ) {}

    public function create(Client $client, array $items, ?string $couponCode = null, ?string $paymentMethod = null): Order
    {
        return DB::transaction(function () use ($client, $items, $couponCode, $paymentMethod) {
            $coupon   = $couponCode ? Coupon::where('code', $couponCode)->first() : null;
            $subtotal = 0;
            $setupFee = 0;
            $discount = 0;

            $processedItems = [];

            foreach ($items as $item) {
                $product = Product::with('pricing')->findOrFail($item['product_id']);
                $pricing = $product->getPriceForCycle($item['billing_cycle'] ?? 'monthly');

                if (!$pricing) {
                    throw new \RuntimeException("Preço não encontrado para o produto {$product->name}");
                }

                $itemPrice    = $pricing->price;
                $itemSetupFee = $pricing->setup_fee;
                $subtotal    += $itemPrice;
                $setupFee    += $itemSetupFee;

                $processedItems[] = [
                    'product'      => $product,
                    'pricing'      => $pricing,
                    'price'        => $itemPrice,
                    'setup_fee'    => $itemSetupFee,
                    'billing_cycle'=> $pricing->billing_cycle,
                    'domain'       => $item['domain'] ?? null,
                    'config_opts'  => $item['configurable_options'] ?? [],
                    'custom_fields'=> $item['custom_fields'] ?? [],
                ];
            }

            if ($coupon && $coupon->isValid()) {
                $discount = $coupon->calculateDiscount($subtotal);
                $coupon->increment('uses_count');
            }

            $total = max(0, $subtotal + $setupFee - $discount);

            $order = Order::create([
                'client_id'      => $client->id,
                'coupon_id'      => $coupon?->id,
                'status'         => 'pending',
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'setup_fee'      => $setupFee,
                'total'          => $total,
                'currency'       => 'BRL',
                'payment_method' => $paymentMethod,
                'ip_address'     => request()->ip(),
            ]);

            foreach ($processedItems as $item) {
                OrderItem::create([
                    'order_id'              => $order->id,
                    'product_id'            => $item['product']->id,
                    'product_name'          => $item['product']->name,
                    'domain'                => $item['domain'],
                    'billing_cycle'         => $item['billing_cycle'],
                    'price'                 => $item['price'],
                    'setup_fee'             => $item['setup_fee'],
                    'discount'              => $discount > 0 ? round($discount / count($processedItems), 2) : 0,
                    'configurable_options'  => $item['config_opts'],
                    'custom_fields'         => $item['custom_fields'],
                ]);

                Service::create([
                    'client_id'      => $client->id,
                    'order_id'       => $order->id,
                    'product_id'     => $item['product']->id,
                    'product_name'   => $item['product']->name,
                    'domain'         => $item['domain'],
                    'billing_cycle'  => $item['billing_cycle'],
                    'price'          => $item['price'],
                    'setup_fee'      => $item['setup_fee'],
                    'currency'       => 'BRL',
                    'status'         => 'pending',
                    'provision_status' => 'pending',
                    'configurable_options' => $item['config_opts'],
                    'custom_fields'  => $item['custom_fields'],
                ]);
            }

            $invoice = $this->billing->generateInvoiceForService(
                $order->services()->first()
            );

            $order->update(['status' => 'pending']);

            $this->notification->send($client, 'order_created', [
                'name'         => $client->name,
                'order_number' => $order->number,
                'total'        => 'R$ ' . number_format($total, 2, ',', '.'),
                'action_url'   => url('/cliente/pedidos/' . $order->id),
                'message'      => "Seu pedido #{$order->number} foi criado com sucesso.",
            ]);

            return $order->load(['items', 'services']);
        });
    }

    public function activate(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'active', 'accepted_at' => now()]);

            foreach ($order->services as $service) {
                if ($service->provision_status === 'pending') {
                    dispatch(new \App\Jobs\ProvisionServiceJob($service->id));
                }
            }
        });
    }
}
