@extends('home.layouts.app')

@section('title', 'Carrinho — ' . config('app.name'))
@section('meta-description', 'Finalize sua compra de hospedagem.')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none}</style>
@endpush

@section('content')
{{-- Steps --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-5xl mx-auto px-4 py-4">
        <div class="flex items-center gap-0 text-xs font-semibold">
            <div class="flex items-center gap-1.5 text-green-600">
                <div class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center text-xs">
                    <i class="bi bi-check"></i>
                </div>
                Configurado
            </div>
            <div class="flex-1 h-px bg-green-300 mx-3"></div>
            <div class="flex items-center gap-1.5 text-blue-600">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">2</div>
                Carrinho
            </div>
            <div class="flex-1 h-px bg-gray-200 mx-3"></div>
            <div class="flex items-center gap-1.5 text-gray-400">
                <div class="w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs font-bold">3</div>
                Checkout
            </div>
        </div>
    </div>
</div>

{{-- Conteúdo do Carrinho --}}
<div class="max-w-5xl mx-auto px-4 py-8" x-data="cartPage()" x-init="loadCart()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Itens --}}
        <div class="lg:col-span-2 space-y-4" id="cart-items">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                                <i class="bi bi-server text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900" x-text="item.product_name"></h3>
                                <p class="text-sm text-gray-500" x-text="item.cycle_label"></p>
                                <p class="text-xs text-gray-400 mt-1" x-show="item.domain" x-text="item.domain"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-900">R$ <span x-text="formatPrice(item.total)"></span></div>
                            <button @click="removeItem(index)" class="text-red-500 hover:text-red-700 text-sm mt-1">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Carrinho Vazio --}}
            <div x-show="items.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-100">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-cart3 text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Carrinho vazio</h3>
                <p class="text-gray-500 mb-6">Você ainda não adicionou nenhum produto.</p>
                <a href="{{ route('store') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl transition">
                    Ver Produtos
                </a>
            </div>
        </div>

        {{-- Resumo --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
                <h3 class="font-bold text-gray-900 mb-4">Resumo do Pedido</h3>

                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-semibold">R$ <span x-text="formatPrice(subtotal)"></span></span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="text-xl font-extrabold text-blue-700">R$ <span x-text="formatPrice(subtotal)"></span></span>
                    </div>
                </div>

                <button @click="checkout()" :disabled="items.length === 0"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl text-sm transition">
                    Finalizar Compra
                </button>

                <a href="{{ route('store') }}" class="block w-full text-center text-gray-500 hover:text-gray-700 text-sm mt-3">
                    ← Continuar comprando
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cartPage() {
    return {
        items: [],
        loading: true,

        get subtotal() {
            return this.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0);
        },

        formatPrice(v) {
            return parseFloat(v).toFixed(2).replace('.', ',');
        },

        // Carregar carrinho da API (persistente)
        async loadCart() {
            this.loading = true;

            // Primeiro sincroniza localStorage com API (para itens antigos)
            const localCart = JSON.parse(localStorage.getItem('hostpanel_cart') || '[]');

            // Verificar expiração (24h) no frontend também
            const now = new Date();
            const validItems = localCart.filter(item => {
                const addedAt = new Date(item.added_at || item.created_at || now);
                const hoursDiff = (now - addedAt) / (1000 * 60 * 60);
                return hoursDiff < 24;
            });

            // Se houver itens válidos no localStorage, sincroniza com API
            if (validItems.length > 0) {
                try {
                    await fetch('{{ route("cart.api.sync") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ items: validItems })
                    });
                    // Limpa localStorage após sincronização
                    localStorage.removeItem('hostpanel_cart');
                } catch (e) {
                    console.error('Erro ao sincronizar carrinho:', e);
                }
            }

            // Carregar itens da API
            try {
                const response = await fetch('{{ route("cart.api.get") }}');
                const data = await response.json();
                this.items = data.items || [];
                this.updateBadge(data.count || 0);
            } catch (e) {
                console.error('Erro ao carregar carrinho:', e);
            } finally {
                this.loading = false;
            }
        },

        // Remover item via API
        async removeItem(index) {
            const item = this.items[index];
            if (!item) return;

            try {
                await fetch(`{{ route("cart.api.remove", "__ID__") }}`.replace('__ID__', item.id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                this.items.splice(index, 1);
                this.updateBadge(this.items.length);
            } catch (e) {
                console.error('Erro ao remover item:', e);
            }
        },

        updateBadge(count) {
            const badge = document.getElementById('cart-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        },

        checkout() {
            if (this.items.length === 0) return;
            // Converte itens da API para formato do checkout
            const checkoutItems = this.items.map(item => ({
                product_id: item.product_id,
                cycle: item.billing_cycle,
                domain: item.domain || '',
                product_name: item.product_name,
                cycle_label: item.cycle_label,
                price: item.price,
                total: item.total
            }));
            const itemsParam = encodeURIComponent(JSON.stringify(checkoutItems));
            window.location = '{{ route("checkout") }}?items=' + itemsParam;
        }
    }
}
</script>
@endpush
