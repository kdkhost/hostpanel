@extends('home.layouts.app')

@section('title', 'Contratar ' . ($product->name ?? 'Produto') . ' — ' . config('app.name'))
@section('meta-description', 'Configure seu plano de hospedagem e finalize a compra.')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none}</style>
@endpush

@section('content')
{{-- Breadcrumb --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('store') }}" class="hover:text-blue-600">Loja</a>
        <i class="bi bi-chevron-right text-xs"></i>
        @if($product->group)
        <a href="{{ route('store') }}?categoria={{ $product->group->slug }}" class="hover:text-blue-600">{{ $product->group->name }}</a>
        <i class="bi bi-chevron-right text-xs"></i>
        @endif
        <span class="text-gray-900 font-semibold">{{ $product->name }}</span>
    </div>
</div>

{{-- Order steps indicator --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-5xl mx-auto px-4 py-3">
        <div class="flex items-center gap-0 text-xs font-semibold">
            <div class="flex items-center gap-1.5 text-blue-600">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">1</div>
                Configurar
            </div>
            <div class="flex-1 h-px bg-gray-200 mx-3"></div>
            <div class="flex items-center gap-1.5 text-gray-400">
                <div class="w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs font-bold">2</div>
                Domínio
            </div>
            <div class="flex-1 h-px bg-gray-200 mx-3"></div>
            <div class="flex items-center gap-1.5 text-gray-400">
                <div class="w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs font-bold">3</div>
                Checkout
            </div>
        </div>
    </div>
</div>

@php
$cycles = ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','triennially'=>'Trienal','free'=>'Gratuito'];
$cycleMonths = ['monthly'=>1,'quarterly'=>3,'semiannually'=>6,'annually'=>12,'biennially'=>24,'triennially'=>36,'free'=>1];
$pricingMap = $product->pricing->keyBy('billing_cycle');
@endphp

<div class="max-w-5xl mx-auto px-4 py-8"
     x-data="orderProduct({{ $product->id }}, '{{ $selectedCycle }}', @json($pricingMap->map(fn($p) => (float)$p->price)))">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Coluna principal --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- 1. Ciclo de cobrança --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                    Ciclo de Cobrança
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($product->pricing as $price)
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="cycle" value="{{ $price->billing_cycle }}"
                               x-model="cycle" class="sr-only peer">
                        <div class="border-2 rounded-xl p-4 text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 group-hover:border-blue-300 transition">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                {{ $cycles[$price->billing_cycle] ?? $price->billing_cycle }}
                            </div>
                            <div class="text-xl font-extrabold text-gray-900">
                                R$ {{ number_format($price->price, 2, ',', '.') }}
                            </div>
                            @php $m = $cycleMonths[$price->billing_cycle] ?? 1; @endphp
                            @if($m > 1)
                            <div class="text-xs text-gray-400 mt-0.5">
                                R$ {{ number_format($price->price / $m, 2, ',', '.') }}/mês
                            </div>
                            @endif
                            @if($price->setup_fee > 0)
                            <div class="text-xs text-amber-600 mt-1">+ R$ {{ number_format($price->setup_fee, 2, ',', '.') }} setup</div>
                            @endif
                        </div>
                        <div class="absolute top-2 right-2 hidden peer-checked:flex">
                            <div class="w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                <i class="bi bi-check text-white text-xs"></i>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- 2. Domínio --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                    Domínio
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="domain_option" value="new" x-model="domainOption" class="sr-only peer">
                        <div class="border-2 rounded-xl p-3 text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-200 transition text-sm font-semibold">
                            <i class="bi bi-plus-circle d-block text-blue-500 mb-1 text-lg"></i>
                            Registrar novo
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="domain_option" value="transfer" x-model="domainOption" class="sr-only peer">
                        <div class="border-2 rounded-xl p-3 text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-200 transition text-sm font-semibold">
                            <i class="bi bi-arrow-left-right d-block text-green-500 mb-1 text-lg"></i>
                            Transferir
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="domain_option" value="existing" x-model="domainOption" class="sr-only peer">
                        <div class="border-2 rounded-xl p-3 text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-200 transition text-sm font-semibold">
                            <i class="bi bi-globe d-block text-purple-500 mb-1 text-lg"></i>
                            Já possuo
                        </div>
                    </label>
                </div>

                {{-- Campo de domínio --}}
                <div x-show="domainOption === 'new'">
                    <div class="flex gap-2">
                        <input type="text" x-model="domainName"
                               @input.debounce.500="checkDomain()"
                               class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                               placeholder="meudominio">
                        <select x-model="domainTld" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-blue-500">
                            @foreach($tlds as $tld)
                            <option value="{{ $tld->tld }}">.{{ $tld->tld }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-2 text-sm" x-show="domainStatus">
                        <span x-show="domainStatus === 'available'" class="text-green-600 font-semibold">
                            <i class="bi bi-check-circle-fill"></i> <span x-text="domainName + '.' + domainTld"></span> está disponível!
                        </span>
                        <span x-show="domainStatus === 'taken'" class="text-red-500 font-semibold">
                            <i class="bi bi-x-circle-fill"></i> Este domínio não está disponível.
                        </span>
                        <span x-show="domainStatus === 'checking'" class="text-gray-400">
                            <span class="inline-block w-3 h-3 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></span> Verificando...
                        </span>
                    </div>
                </div>

                <div x-show="domainOption === 'transfer'">
                    <input type="text" x-model="domainName"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                           placeholder="dominio.com.br">
                    <p class="text-xs text-gray-400 mt-1">Informe o domínio que deseja transferir para nós.</p>
                </div>

                <div x-show="domainOption === 'existing'">
                    <input type="text" x-model="domainName"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                           placeholder="meudominio.com.br">
                    <p class="text-xs text-gray-400 mt-1">O DNS do seu domínio será atualizado para apontar para nossos servidores.</p>
                </div>
            </div>

            {{-- 3. Cupom --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-7 h-7 bg-gray-100 text-gray-500 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                    Cupom de Desconto
                    <span class="text-xs text-gray-400 font-normal">(opcional)</span>
                </h2>
                <div class="flex gap-2">
                    <input type="text" x-model="couponCode"
                           class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 uppercase tracking-widest"
                           placeholder="SEU-CUPOM">
                    <button @click="validateCoupon()"
                            :disabled="!couponCode || couponLoading"
                            class="bg-gray-100 hover:bg-gray-200 disabled:opacity-50 text-gray-700 font-semibold px-5 py-3 rounded-xl text-sm transition">
                        <span x-show="couponLoading" class="inline-block w-3 h-3 border-2 border-gray-500 border-t-transparent rounded-full animate-spin"></span>
                        <span x-show="!couponLoading">Aplicar</span>
                    </button>
                </div>
                <p class="text-green-600 text-sm font-semibold mt-2" x-show="couponValid" x-text="couponMsg"></p>
                <p class="text-red-500 text-sm mt-2" x-show="couponError" x-text="couponError"></p>
            </div>
        </div>

        {{-- Resumo lateral --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
                <h3 class="font-bold text-gray-900 mb-4">Resumo do Pedido</h3>

                <div class="flex items-start gap-3 mb-5 pb-5 border-b border-gray-100">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                        <i class="bi bi-{{ $product->icon ?? 'server' }} text-blue-600"></i>
                    </div>
                    <div>
                        <div class="font-bold text-gray-900 text-sm">{{ $product->name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5" x-text="cycleLabel"></div>
                    </div>
                </div>

                <div class="space-y-2 text-sm mb-5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Plano</span>
                        <span class="font-semibold" x-text="'R$ ' + formatPrice(cyclePrice)"></span>
                    </div>
                    <div class="flex justify-between" x-show="setupFee > 0">
                        <span class="text-gray-500">Taxa de setup</span>
                        <span class="font-semibold text-amber-600" x-text="'R$ ' + formatPrice(setupFee)"></span>
                    </div>
                    <div class="flex justify-between text-green-600" x-show="discount > 0">
                        <span>Desconto (cupom)</span>
                        <span class="font-semibold" x-text="'- R$ ' + formatPrice(discount)"></span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="text-xl font-extrabold text-blue-700" x-text="'R$ ' + formatPrice(total)"></span>
                    </div>
                </div>

                @if($product->features && count((array)$product->features))
                <div class="border-t border-gray-100 pt-4 mb-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Incluso</p>
                    <ul class="space-y-1.5">
                        @foreach(array_slice(is_array($product->features) ? $product->features : json_decode($product->features ?? '[]', true), 0, 6) as $feat)
                        <li class="flex items-center gap-2 text-xs text-gray-600">
                            <i class="bi bi-check-circle-fill text-green-500 shrink-0"></i>
                            {{ is_array($feat) ? ($feat['name'] ?? $feat['value'] ?? '') : $feat }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <button @click="addToCart()"
                        :disabled="!cycle"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-3.5 rounded-xl text-sm transition">
                    Adicionar ao Carrinho
                </button>

                <p class="text-center text-xs text-gray-400 mt-3">
                    <i class="bi bi-shield-lock-fill text-green-500"></i>
                    Pagamento 100% seguro e criptografado
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const PRICING = @json($pricingMap->map(fn($p) => ['price' => (float)$p->price, 'setup_fee' => (float)($p->setup_fee ?? 0)]));
const CYCLE_LABELS = { monthly:'Mensal', quarterly:'Trimestral', semiannually:'Semestral', annually:'Anual', biennially:'Bienal', triennially:'Trienal', free:'Gratuito' };

function orderProduct(productId, defaultCycle, prices) {
    return {
        productId, cycle: defaultCycle,
        domainOption: 'new', domainName: '', domainTld: '{{ $tlds->first()?->tld ?? "com.br" }}', domainStatus: '',
        couponCode: '', couponLoading: false, couponValid: false, couponMsg: '', couponError: '', discount: 0,

        get cyclePrice()  { return PRICING[this.cycle]?.price ?? 0; },
        get setupFee()    { return PRICING[this.cycle]?.setup_fee ?? 0; },
        get cycleLabel()  { return CYCLE_LABELS[this.cycle] ?? this.cycle; },
        get total()       { return Math.max(0, this.cyclePrice + this.setupFee - this.discount); },

        formatPrice(v)    { return v.toFixed(2).replace('.', ','); },

        async checkDomain() {
            if (!this.domainName) return;
            this.domainStatus = 'checking';
            try {
                const r = await fetch(`/api/dominio/verificar?domain=${encodeURIComponent(this.domainName + '.' + this.domainTld)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const d = await r.json();
                this.domainStatus = d.available ? 'available' : 'taken';
            } catch { this.domainStatus = 'available'; }
        },

        async validateCoupon() {
            if (!this.couponCode) return;
            this.couponLoading = true; this.couponError = ''; this.couponValid = false; this.discount = 0;
            try {
                const r = await fetch('{{ route("cart.coupon.validate") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ code: this.couponCode, product_id: this.productId, cycle: this.cycle })
                });
                const d = await r.json();
                if (d.valid) { this.couponValid = true; this.couponMsg = d.message; this.discount = d.discount ?? 0; }
                else { this.couponError = d.message ?? 'Cupom inválido.'; }
            } catch { this.couponError = 'Erro ao validar cupom.'; }
            this.couponLoading = false;
        },

        addToCart() {
            const cart = JSON.parse(localStorage.getItem('hostpanel_cart') || '[]');
            const item = {
                id: Date.now(),
                product_id:    this.productId,
                product_name:  '{{ addslashes($product->name) }}',
                product_slug:  '{{ $product->slug }}',
                cycle:         this.cycle,
                cycle_label:   this.cycleLabel,
                price:         this.cyclePrice,
                setup_fee:     this.setupFee,
                total:         this.total,
                domain:        this.domainName ? (this.domainOption === 'new' ? this.domainName + '.' + this.domainTld : this.domainName) : '',
                domain_option: this.domainOption,
                coupon:        this.couponValid ? this.couponCode : '',
                discount:      this.discount,
            };
            cart.push(item);
            localStorage.setItem('hostpanel_cart', JSON.stringify(cart));
            window.location = '{{ route("cart") }}';
        }
    }
}
</script>
@endpush
