@extends('client.layouts.app')
@section('title', 'Contratar Serviços')
@section('page-title', 'Catálogo de Serviços')

@section('content')
<style>
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 2px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
<div x-data="catalogPage()">
    {{-- Grupos de Produtos --}}
    @forelse($groups as $group)
    <section class="mb-10">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-900">{{ $group->name }}</h2>
            @if($group->description)
                <p class="text-sm text-gray-500 mt-1">{{ $group->description }}</p>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($group->products as $product)
            @php
                $prices = $product->prices ?? [];
                $monthly = $prices['monthly'] ?? null;
                $annually = $prices['annually'] ?? null;
                $features = $product->features ?? [];
            @endphp
            <div class="bg-white rounded-xl border {{ $product->featured ? 'border-blue-500 shadow-md shadow-blue-50' : 'border-gray-200' }} overflow-hidden flex flex-col h-full">
                {{-- Header com preço destacado --}}
                <div class="px-5 pt-5 pb-4 {{ $product->featured ? 'bg-gradient-to-br from-blue-50 to-purple-50' : '' }}">
                    @if($product->featured)
                    <div class="inline-flex items-center gap-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full mb-3">
                        <i class="bi bi-star-fill"></i> MAIS POPULAR
                    </div>
                    @endif

                    <h3 class="font-bold text-gray-900 text-lg leading-tight">{{ $product->name }}</h3>
                    @if($product->tagline)
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $product->tagline }}</p>
                    @endif

                    {{-- Preço compacto --}}
                    <div class="mt-4">
                        @if($monthly !== null)
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-extrabold text-gray-900">R$ {{ number_format($monthly, 2, ',', '.') }}</span>
                            <span class="text-gray-500 text-sm">/mês</span>
                        </div>
                        @if($annually !== null)
                        <div class="text-xs text-green-600 font-medium mt-0.5">
                            <i class="bi bi-arrow-down-circle me-1"></i>R$ {{ number_format($annually / 12, 2, ',', '.') }}/mês no anual
                        </div>
                        @endif
                        @else
                        <div class="text-xl font-bold text-gray-400">Sob consulta</div>
                        @endif
                    </div>
                </div>

                {{-- Features compactas --}}
                @if(count($features))
                <div class="px-5 flex-1">
                    <div class="border-t border-gray-100 pt-4">
                        <ul class="grid grid-cols-1 gap-1.5 max-h-40 overflow-y-auto pr-1 custom-scrollbar">
                            @foreach($features as $feature)
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <i class="bi bi-check2 text-green-500 flex-shrink-0 mt-0.5 text-xs"></i>
                                <span class="truncate" title="{{ $feature }}">{{ $feature }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                {{-- Ação --}}
                <div class="px-5 pb-5 pt-4 mt-auto">
                    <div class="flex gap-2">
                        <select class="flex-1 min-w-0 border border-gray-200 rounded-lg px-2 py-2 text-xs focus:outline-none focus:border-blue-500 bg-white"
                            x-model="selectedCycles[{{ $product->id }}]"
                            title="Ciclo de cobrança">
                            @foreach(array_keys($product->prices ?? []) as $cycle)
                            <option value="{{ $cycle }}">
                                {{ ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','free'=>'Grátis'][$cycle] ?? ucfirst($cycle) }}
                            </option>
                            @endforeach
                        </select>
                        <button class="{{ $product->featured ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold px-4 py-2 rounded-lg text-sm transition whitespace-nowrap"
                            @click="addToCart({{ $product->id }}, '{{ $product->name }}', selectedCycles[{{ $product->id }}])">
                            <i class="bi bi-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @empty
    <div class="text-center py-16">
        <i class="bi bi-box-seam text-5xl text-gray-200 block mb-3"></i>
        <p class="text-gray-500">Nenhum produto disponível no momento.</p>
    </div>
    @endforelse

    {{-- Carrinho Flutuante --}}
    <div x-show="cart.length > 0" x-cloak
        class="fixed bottom-6 right-6 z-50 bg-white rounded-2xl shadow-2xl border border-gray-100 w-80 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-5 py-3 flex justify-between items-center">
            <span class="font-bold text-white"><i class="bi bi-cart3 me-2"></i>Carrinho (<span x-text="cart.length"></span>)</span>
            <button @click="cart=[]" class="text-blue-200 hover:text-white text-xs">Limpar</button>
        </div>
        <div class="p-4 space-y-3 max-h-48 overflow-y-auto">
            <template x-for="item in cart" :key="item.id + item.cycle">
                <div class="flex justify-between items-center text-sm">
                    <div>
                        <div class="font-medium text-gray-800" x-text="item.name"></div>
                        <div class="text-xs text-gray-400" x-text="item.cycle_label"></div>
                    </div>
                    <button @click="removeFromCart(item)" class="text-red-400 hover:text-red-600 ml-2"><i class="bi bi-x"></i></button>
                </div>
            </template>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            <div class="mb-2">
                <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Cupom de desconto (opcional)" x-model="coupon">
            </div>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm" @click="checkout">
                Finalizar Pedido <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function catalogPage() {
    return {
        cart: [], coupon: '',
        selectedCycles: @json(collect($groups)->flatMap->products->mapWithKeys(fn($p) => [$p->id => array_key_first($p->prices ?? ['monthly' => 0])])),

        addToCart(id, name, cycle) {
            const labels = {monthly:'Mensal',quarterly:'Trimestral',semiannually:'Semestral',annually:'Anual',biennially:'Bienal',free:'Grátis'};
            const existing = this.cart.findIndex(i => i.id === id && i.cycle === cycle);
            if (existing >= 0) { HostPanel.toast('Item já está no carrinho.', 'info'); return; }
            this.cart.push({ id, name, cycle, cycle_label: labels[cycle] || cycle });
            HostPanel.toast(`${name} adicionado ao carrinho!`);
        },

        removeFromCart(item) {
            this.cart = this.cart.filter(i => !(i.id === item.id && i.cycle === item.cycle));
        },

        async checkout() {
            const d = await HostPanel.fetch('{{ route("client.orders.checkout") }}', {
                method: 'POST',
                body: JSON.stringify({ items: this.cart.map(i => ({ product_id: i.id, billing_cycle: i.cycle })), coupon_code: this.coupon })
            });
            if (d.redirect) { window.location.href = d.redirect; }
            else if (d.order) { window.location.href = `/cliente/pedidos/${d.order.id}`; }
            else HostPanel.toast(d.message || 'Erro ao processar pedido.', 'danger');
        }
    }
}
</script>
@endpush
