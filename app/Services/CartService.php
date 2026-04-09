<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductPricing;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const EXPIRATION_HOURS = 24;

    /**
     * Obter identificador da sessão atual
     */
    private function getSessionId(): string
    {
        $sessionId = Session::getId();
        
        // Se cliente está logado, usar ID do cliente como parte da sessão
        if (auth('client')->check()) {
            return 'client_' . auth('client')->id();
        }
        
        return $sessionId;
    }

    /**
     * Limpar itens expirados
     */
    public function clearExpired(): void
    {
        CartItem::where('expires_at', '<', now())->delete();
    }

    /**
     * Obter itens do carrinho atual
     */
    public function getItems(): array
    {
        $this->clearExpired();

        $sessionId = $this->getSessionId();
        $clientId = auth('client')->id();

        $query = CartItem::with('product')
            ->where('expires_at', '>', now());

        if ($clientId) {
            $query->where('client_id', $clientId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $items = $query->get();

        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'billing_cycle' => $item->billing_cycle,
                'cycle_label' => $this->getCycleLabel($item->billing_cycle),
                'domain' => $item->domain,
                'price' => $item->price,
                'setup_fee' => $item->setup_fee,
                'discount' => $item->discount,
                'total' => $item->total,
                'coupon_code' => $item->coupon_code,
                'custom_fields' => $item->custom_fields,
            ];
        })->toArray();
    }

    /**
     * Adicionar item ao carrinho
     */
    public function addItem(array $data): CartItem
    {
        $this->clearExpired();

        $product = Product::findOrFail($data['product_id']);
        $pricing = ProductPricing::where('product_id', $product->id)
            ->where('billing_cycle', $data['billing_cycle'])
            ->where('active', true)
            ->first();

        $price = $pricing?->price ?? 0;
        $setupFee = $pricing?->setup_fee ?? 0;

        // Verificar se já existe item igual (mesmo produto e ciclo)
        $sessionId = $this->getSessionId();
        $clientId = auth('client')->id();

        $existingQuery = CartItem::where('product_id', $data['product_id'])
            ->where('billing_cycle', $data['billing_cycle'])
            ->where('expires_at', '>', now());

        if ($clientId) {
            $existingQuery->where('client_id', $clientId);
        } else {
            $existingQuery->where('session_id', $sessionId);
        }

        $existing = $existingQuery->first();

        if ($existing) {
            // Atualizar item existente (adiciona domínios ou atualiza)
            $existing->update([
                'domain' => $data['domain'] ?? $existing->domain,
                'custom_fields' => $data['custom_fields'] ?? $existing->custom_fields,
                'coupon_code' => $data['coupon_code'] ?? $existing->coupon_code,
                'discount' => $data['discount'] ?? $existing->discount,
                'expires_at' => now()->addHours(self::EXPIRATION_HOURS),
            ]);
            return $existing;
        }

        // Criar novo item
        return CartItem::create([
            'client_id' => $clientId,
            'session_id' => $clientId ? null : $sessionId,
            'product_id' => $data['product_id'],
            'billing_cycle' => $data['billing_cycle'],
            'domain' => $data['domain'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null,
            'price' => $price,
            'setup_fee' => $setupFee,
            'coupon_code' => $data['coupon_code'] ?? null,
            'discount' => $data['discount'] ?? 0,
            'expires_at' => now()->addHours(self::EXPIRATION_HOURS),
        ]);
    }

    /**
     * Remover item do carrinho
     */
    public function removeItem(int $itemId): bool
    {
        $sessionId = $this->getSessionId();
        $clientId = auth('client')->id();

        $query = CartItem::where('id', $itemId);

        if ($clientId) {
            $query->where('client_id', $clientId);
        } else {
            $query->where('session_id', $sessionId);
        }

        return $query->delete() > 0;
    }

    /**
     * Limpar todo o carrinho
     */
    public function clearCart(): void
    {
        $sessionId = $this->getSessionId();
        $clientId = auth('client')->id();

        $query = CartItem::query();

        if ($clientId) {
            $query->where('client_id', $clientId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $query->delete();
    }

    /**
     * Sincronizar carrinho do localStorage para o banco
     */
    public function syncFromLocalStorage(array $items): array
    {
        $syncedItems = [];

        foreach ($items as $item) {
            // Verificar se item não expirou (front-end envia created_at)
            $createdAt = $item['added_at'] ?? $item['created_at'] ?? now();
            if (now()->diffInHours($createdAt) > self::EXPIRATION_HOURS) {
                continue; // Ignorar itens expirados
            }

            $cartItem = $this->addItem([
                'product_id' => $item['product_id'],
                'billing_cycle' => $item['billing_cycle'],
                'domain' => $item['domain'] ?? null,
                'custom_fields' => $item['custom_fields'] ?? null,
                'coupon_code' => $item['coupon_code'] ?? null,
                'discount' => $item['discount'] ?? 0,
            ]);

            $syncedItems[] = $cartItem;
        }

        return $this->getItems();
    }

    /**
     * Transferir carrinho de sessão para cliente (após login)
     */
    public function transferToClient(int $clientId): void
    {
        $sessionId = Session::getId();

        CartItem::where('session_id', $sessionId)
            ->whereNull('client_id')
            ->update(['client_id' => $clientId, 'session_id' => null]);
    }

    /**
     * Obter label do ciclo de faturamento
     */
    private function getCycleLabel(string $cycle): string
    {
        $labels = [
            'monthly' => 'Mensal',
            'quarterly' => 'Trimestral',
            'semiannually' => 'Semestral',
            'annually' => 'Anual',
            'biennially' => 'Bienal',
            'triennially' => 'Trienal',
            'free' => 'Grátis',
            'one_time' => 'Único',
        ];

        return $labels[$cycle] ?? ucfirst($cycle);
    }

    /**
     * Obter contagem de itens
     */
    public function getCount(): int
    {
        $sessionId = $this->getSessionId();
        $clientId = auth('client')->id();

        $query = CartItem::where('expires_at', '>', now());

        if ($clientId) {
            $query->where('client_id', $clientId);
        } else {
            $query->where('session_id', $sessionId);
        }

        return $query->count();
    }
}
