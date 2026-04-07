<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none}</style>
</head>
<body class="bg-gray-50 text-gray-900">

{{-- Navbar --}}
<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-gray-900">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center"><i class="bi bi-server text-white text-sm"></i></div>
            {{ config('app.name') }}
        </a>
        <a href="{{ route('store') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-900">
            <i class="bi bi-arrow-left me-1"></i> Continuar comprando
        </a>
    </div>
</nav>

{{-- Steps --}}
<div class="bg-white border-b border-gray-100">
    <div class="max-w-5xl mx-auto px-4 py-3">
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

<div class="max-w-5xl mx-auto px-4 py-8" x-data="cartPage()">

    {{-- Carrinho vazio --}}
    <div x-show="items.length === 0" x-cloak class="text-center py-24">
        <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <i class="bi bi-cart3 text-4xl text-gray-300"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Seu carrinho está vazio</h2>
        <p class="text-gray-400 text-sm mb-6">Adicione produtos para continuar.</p>
        <a href="{{ route('store') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3 rounded-xl text-sm">
            Ver Planos e Hospedagem
        </a>
    </div>

    {{-- Carrinho com itens --}}
    <div x-show="items.length > 0" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Itens --}}
            <div class="lg:col-span-2 space-y-4">
                <h2 class="font-bold text-gray-900 text-lg">
                    Itens no Carrinho
                    <span class="text-gray-400 font-normal text-base" x-text="'(' + items.length + ')'"></span>
                </h2>

                <template x-for="(item, index) in items" :key="item.id">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4 flex-1">
                                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                                    <i class="bi bi-server text-blue-600 text-lg"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-900" x-text="item.product_name"></div>
                                    <div class="text-sm text-gray-500 mt-0.5" x-text="item.cycle_label + ' · R$ ' + formatPrice(item.price) + '/período'"></div>
                                    <div class="text-sm text-gray-500 mt-0.5" x-show="item.domain" x-text="'Domínio: ' + item.domain"></div>
                                    <div class="text-xs text-amber-600 font-semibold mt-1" x-show="item.setup_fee > 0" x-text="'+ R$ ' + formatPrice(item.setup_fee) + ' de setup'"></div>
                                    <div class="text-xs text-green-600 font-semibold mt-1" x-show="item.discount > 0" x-text="'Desconto: - R$ ' + formatPrice(item.discount)"></div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-lg font-extrabold text-blue-700" x-text="'R$ ' + formatPrice(item.total)"></div>
                                <button @click="removeItem(index)" class="text-xs text-red-400 hover:text-red-600 mt-1 font-semibold">
                                    <i class="bi bi-trash"></i> Remover
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <a href="{{ route('store') }}" class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700 font-semibold mt-2">
                    <i class="bi bi-plus-circle"></i> Adicionar outro produto
                </a>
            </div>

            {{-- Resumo + Checkout --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-20">
                    <h3 class="font-bold text-gray-900 mb-5">Resumo</h3>

                    <div class="space-y-2 text-sm mb-5">
                        <template x-for="item in items" :key="item.id">
                            <div class="flex justify-between">
                                <span class="text-gray-500 truncate max-w-32" x-text="item.product_name"></span>
                                <span class="font-semibold ml-2" x-text="'R$ ' + formatPrice(item.price)"></span>
                            </div>
                        </template>
                        <div class="border-t border-gray-100 pt-3">
                            <div class="flex justify-between text-xs text-gray-400 mb-1" x-show="totalSetup > 0">
                                <span>Setup total</span>
                                <span x-text="'R$ ' + formatPrice(totalSetup)"></span>
                            </div>
                            <div class="flex justify-between text-xs text-green-600 mb-1" x-show="totalDiscount > 0">
                                <span>Descontos</span>
                                <span x-text="'- R$ ' + formatPrice(totalDiscount)"></span>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="font-bold text-gray-900">Total</span>
                                <span class="text-xl font-extrabold text-blue-700" x-text="'R$ ' + formatPrice(grandTotal)"></span>
                            </div>
                        </div>
                    </div>

                    <button @click="proceed()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl text-sm transition mb-3">
                        Finalizar Pedido →
                    </button>

                    <p class="text-center text-xs text-gray-400">
                        <i class="bi bi-shield-lock-fill text-green-500"></i>
                        Ambiente seguro e criptografado
                    </p>

                    <div class="flex justify-center gap-3 mt-4 opacity-40">
                        <i class="bi bi-credit-card text-2xl"></i>
                        <i class="bi bi-bank text-2xl"></i>
                        <i class="bi bi-qr-code text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de checkout: Login ou Cadastro --}}
    <div x-show="showCheckout" x-cloak
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="showCheckout=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-extrabold text-gray-900">Finalizar Pedido</h2>
                    <button @click="showCheckout=false" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>

                {{-- Tabs login/cadastro --}}
                <div class="flex border-b border-gray-200 mb-6">
                    <button @click="authTab='login'"
                            :class="authTab==='login' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500'"
                            class="flex-1 py-3 text-sm font-semibold transition">
                        Já tenho conta
                    </button>
                    <button @click="authTab='register'"
                            :class="authTab==='register' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500'"
                            class="flex-1 py-3 text-sm font-semibold transition">
                        Criar conta
                    </button>
                </div>

                {{-- Login --}}
                <form @submit.prevent="doLogin()" x-show="authTab==='login'">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">E-mail</label>
                            <input type="email" x-model="loginForm.email" required
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                                   placeholder="seu@email.com">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Senha</label>
                            <input type="password" x-model="loginForm.password" required
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                                   placeholder="Sua senha">
                        </div>
                        <p class="text-red-500 text-sm" x-show="loginError" x-text="loginError"></p>
                        <button type="submit" :disabled="authLoading"
                                class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold py-3 rounded-xl text-sm transition">
                            <span x-show="authLoading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                            Entrar e Finalizar Pedido
                        </button>
                        <a href="{{ route('client.password.forgot') }}" class="block text-center text-sm text-blue-600 hover:text-blue-700">Esqueci minha senha</a>
                    </div>
                </form>

                {{-- Cadastro --}}
                <form @submit.prevent="doRegister()" x-show="authTab==='register'">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nome Completo *</label>
                            <input type="text" x-model="regForm.name" required
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">E-mail *</label>
                            <input type="email" x-model="regForm.email" required
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Senha *</label>
                            <input type="password" x-model="regForm.password" required minlength="8"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar Senha *</label>
                            <input type="password" x-model="regForm.password_confirmation" required
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Telefone</label>
                            <input type="text" x-model="regForm.phone"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                                   placeholder="(11) 99999-9999">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">CPF / CNPJ</label>
                            <input type="text" x-model="regForm.document_number"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500"
                                   placeholder="000.000.000-00">
                        </div>
                    </div>
                    <p class="text-red-500 text-sm mt-3" x-show="regError" x-text="regError"></p>
                    <button type="submit" :disabled="authLoading"
                            class="w-full mt-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold py-3 rounded-xl text-sm transition">
                        <span x-show="authLoading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                        Criar Conta e Finalizar Pedido
                    </button>
                    <p class="text-xs text-gray-400 text-center mt-3">
                        Ao criar uma conta você concorda com nossos
                        <a href="{{ route('page', 'termos-de-uso') }}" class="text-blue-600 underline" target="_blank">Termos de Uso</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<footer class="bg-slate-900 text-slate-500 py-8 text-center text-sm mt-8">
    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
</footer>

<script>
function cartPage() {
    return {
        items: [],
        showCheckout: false,
        authTab: 'login',
        authLoading: false,
        loginForm:  { email: '', password: '' },
        regForm:    { name:'', email:'', password:'', password_confirmation:'', phone:'', document_number:'' },
        loginError: '',
        regError:   '',

        get totalSetup()    { return this.items.reduce((s, i) => s + (i.setup_fee ?? 0), 0); },
        get totalDiscount() { return this.items.reduce((s, i) => s + (i.discount ?? 0), 0); },
        get grandTotal()    { return this.items.reduce((s, i) => s + (i.total ?? 0), 0); },

        formatPrice(v) { return Number(v).toFixed(2).replace('.', ','); },

        removeItem(index) {
            this.items.splice(index, 1);
            localStorage.setItem('hostpanel_cart', JSON.stringify(this.items));
        },

        proceed() {
            @auth('client')
            this.placeOrder();
            @else
            this.showCheckout = true;
            @endauth
        },

        async doLogin() {
            this.authLoading = true; this.loginError = '';
            try {
                const r = await fetch('{{ route("client.login.post") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.loginForm),
                });
                const d = await r.json();
                if (r.ok && d.redirect) { await this.placeOrder(); }
                else { this.loginError = d.message ?? 'Credenciais inválidas.'; }
            } catch { this.loginError = 'Erro de conexão.'; }
            this.authLoading = false;
        },

        async doRegister() {
            this.authLoading = true; this.regError = '';
            try {
                const r = await fetch('{{ route("client.register.post") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.regForm),
                });
                const d = await r.json();
                if (r.ok) { await this.placeOrder(); }
                else {
                    const errs = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message ?? 'Erro no cadastro.');
                    this.regError = errs;
                }
            } catch { this.regError = 'Erro de conexão.'; }
            this.authLoading = false;
        },

        async placeOrder() {
            const r = await fetch('{{ route("client.orders.place.cart") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ items: this.items }),
            });
            const d = await r.json();
            if (r.ok && d.order) {
                localStorage.removeItem('hostpanel_cart');
                window.location = '/cliente/pedidos/' + d.order.id;
            } else if (r.status === 401) {
                this.showCheckout = true;
            } else {
                alert(d.message ?? 'Erro ao criar pedido.');
            }
        },

        init() {
            this.items = JSON.parse(localStorage.getItem('hostpanel_cart') || '[]');
        }
    }
}
</script>
</body>
</html>
