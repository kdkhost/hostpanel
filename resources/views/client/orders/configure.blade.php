@extends('client.layouts.app')
@section('title', 'Configurar — ' . $product->name)
@section('page-title', 'Configurar Plano')

@section('content')
<div class="max-w-3xl mx-auto" x-data="configureProduct({{ $product->id }})">

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ $product->name }}</h2>
                <p class="text-gray-500 text-sm">{{ $product->tagline ?? $product->group?->name }}</p>
            </div>
        </div>
    </div>

    {{-- Ciclo de Cobrança --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4">
        <h3 class="font-bold text-gray-900 mb-4">Ciclo de Cobrança</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($product->pricing as $price)
            @php
                $labels  = ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','triennially'=>'Trienal','free'=>'Grátis'];
                $months  = ['monthly'=>1,'quarterly'=>3,'semiannually'=>6,'annually'=>12,'biennially'=>24,'triennially'=>36];
                $m = $months[$price->billing_cycle] ?? 1;
            @endphp
            <label class="relative cursor-pointer">
                <input type="radio" name="billing_cycle" value="{{ $price->billing_cycle }}"
                       x-model="selectedCycle" class="sr-only peer">
                <div class="border-2 rounded-xl p-4 text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 transition hover:border-blue-300">
                    <div class="font-semibold text-sm text-gray-700">{{ $labels[$price->billing_cycle] ?? $price->billing_cycle }}</div>
                    <div class="text-lg font-extrabold text-gray-900 mt-1">R$ {{ number_format($price->price, 2, ',', '.') }}</div>
                    @if($m > 1)
                    <div class="text-xs text-gray-400">R$ {{ number_format($price->price / $m, 2, ',', '.') }}/mês</div>
                    @endif
                </div>
                <div class="absolute top-2 right-2 hidden peer-checked:block">
                    <div class="w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </div>
                </div>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Configurações Adicionais --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4">
        <h3 class="font-bold text-gray-900 mb-4">Configurações</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Domínio / Hostname</label>
                <input type="text" x-model="domain"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                       placeholder="meudominio.com.br">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cupom de Desconto</label>
                <div class="flex gap-2">
                    <input type="text" x-model="coupon" class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 uppercase" placeholder="CÓDIGO">
                    <button type="button" @click="validateCoupon()"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-3 rounded-xl text-sm transition">
                        Aplicar
                    </button>
                </div>
                <p class="text-green-600 text-xs mt-1 font-semibold" x-show="couponValid" x-text="couponMsg"></p>
                <p class="text-red-500 text-xs mt-1" x-show="couponError" x-text="couponError"></p>
            </div>
        </div>
    </div>

    {{-- Resumo + Botão --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex justify-between items-center mb-2 text-sm">
            <span class="text-gray-600">{{ $product->name }}</span>
            <span class="font-bold" x-text="'R$ ' + currentPrice"></span>
        </div>
        <div class="flex justify-between items-center mb-4 text-sm" x-show="discount > 0">
            <span class="text-green-600">Desconto (cupom)</span>
            <span class="text-green-600 font-semibold" x-text="'- R$ ' + discount.toFixed(2).replace('.',',')"></span>
        </div>
        <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
            <span class="font-bold text-gray-900">Total</span>
            <span class="text-xl font-extrabold text-blue-700" x-text="'R$ ' + total"></span>
        </div>
        <button @click="addToCart()"
                :disabled="!selectedCycle || loading"
                class="w-full mt-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold py-3.5 rounded-xl transition text-sm">
            <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
            Continuar para o Checkout
        </button>
        <a href="{{ route('client.orders.catalog') }}" class="block text-center text-gray-400 hover:text-gray-600 text-sm mt-3">
            ← Voltar ao catálogo
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
const PRODUCT_PRICES = @json($product->pricing->keyBy('billing_cycle')->map(fn($p) => (float)$p->price));

function configureProduct(productId) {
    return {
        productId, selectedCycle: '{{ $product->pricing->first()?->billing_cycle ?? "monthly" }}',
        domain: '', coupon: '', couponValid: false, couponMsg: '', couponError: '', discount: 0, loading: false,

        get currentPrice() {
            const p = PRODUCT_PRICES[this.selectedCycle] ?? 0;
            return p.toFixed(2).replace('.', ',');
        },

        get total() {
            const p = PRODUCT_PRICES[this.selectedCycle] ?? 0;
            return Math.max(0, p - this.discount).toFixed(2).replace('.', ',');
        },

        async validateCoupon() {
            if (!this.coupon) return;
            this.couponError = '';
            this.couponValid = false;
            this.discount = 0;
            const d = await HostPanel.fetch('{{ route("client.orders.coupon.validate") }}', {
                method: 'POST',
                body: JSON.stringify({ code: this.coupon, product_id: this.productId, cycle: this.selectedCycle })
            });
            if (d.valid) {
                this.couponValid = true;
                this.couponMsg   = d.message;
                this.discount    = d.discount ?? 0;
            } else {
                this.couponError = d.message ?? 'Cupom inválido.';
            }
        },

        addToCart() {
            const params = new URLSearchParams({
                product: this.productId,
                cycle:   this.selectedCycle,
                domain:  this.domain,
                coupon:  this.coupon,
            });
            window.location = '{{ route("client.orders.checkout") }}?' + params.toString();
        }
    }
}
</script>
@endpush
