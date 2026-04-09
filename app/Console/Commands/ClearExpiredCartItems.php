<?php

namespace App\Console\Commands;

use App\Services\CartService;
use Illuminate\Console\Command;

class ClearExpiredCartItems extends Command
{
    protected $signature = 'cart:clear-expired';
    protected $description = 'Remove itens expirados do carrinho (mais de 24h)';

    public function handle(CartService $cartService): int
    {
        $cartService->clearExpired();
        $this->info('Itens expirados do carrinho removidos com sucesso.');
        return 0;
    }
}
