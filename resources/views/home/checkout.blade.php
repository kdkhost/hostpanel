@extends('home.layouts.app')

@section('title', 'Checkout — ' . config('app.name'))
@section('meta-description', 'Finalize seu pedido. Crie sua conta ou faça login para continuar.')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none}</style>
@endpush

@section('content')
@php
$cycles = ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','triennially'=>'Trienal','free'=>'Grátis'];
$isLoggedIn = auth('client')->check();
@endphp

<div class="bg-gray-50 min-h-screen py-8" x-data="checkoutFlow({{ $isLoggedIn ? 'true' : 'false' }}, {{ json_encode($cartItems) }})">
    <div class="max-w-6xl mx-auto px-4">

        {{-- Steps --}}
        <div class="mb-8">
            <div class="flex items-center justify-center gap-0 text-sm">
                <div class="flex items-center gap-2 text-green-600">
                    <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center font-bold">✓</div>
                    <span class="hidden sm:inline font-semibold">Carrinho</span>
                </div>
                <div class="w-16 sm:w-24 h-1 bg-blue-600 mx-2"></div>
                <div class="flex items-center gap-2 text-blue-600">
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">2</div>
                    <span class="hidden sm:inline font-bold">Checkout</span>
                </div>
                <div class="w-16 sm:w-24 h-1 bg-gray-200 mx-2"></div>
                <div class="flex items-center gap-2 text-gray-400">
                    <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold">3</div>
                    <span class="hidden sm:inline">Confirmação</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna Principal --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Erros --}}
                @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="bi bi-exclamation-circle text-red-500 text-xl"></i>
                        <div>
                            <h4 class="font-semibold text-red-800">Corrija os seguintes erros:</h4>
                            <ul class="mt-2 text-sm text-red-600 space-y-1">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Carrinho Vazio --}}
                @if(empty($cartItems))
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-cart-x text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Seu carrinho está vazio</h3>
                    <p class="text-gray-500 mb-4">Adicione produtos para continuar</p>
                    <a href="{{ route('store') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl">
                        Ver Produtos
                    </a>
                </div>
                @else

                {{-- Resumo do Pedido --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Resumo do Pedido</h2>
                    <div class="space-y-4">
                        @foreach($cartItems as $index => $item)
                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                <i class="bi bi-server text-blue-600 text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900">{{ $item['product']->name }}</h4>
                                <p class="text-sm text-gray-500">{{ $cycles[$item['cycle']] ?? $item['cycle'] }}</p>

                                @if($item['requires_domain'])
                                <div class="mt-2">
                                    <label class="text-xs font-semibold text-gray-600">Domínio:</label>
                                    <input type="text"
                                           name="items[{{ $index }}][domain]"
                                           x-model="items[{{ $index }}].domain"
                                           class="w-full mt-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                                           placeholder="meudominio.com.br"
                                           required>
                                    @if($item['requires_domain'] && empty($item['domain']))
                                    <p class="text-amber-600 text-xs mt-1"><i class="bi bi-exclamation-triangle"></i> Este produto requer um domínio</p>
                                    @endif
                                </div>
                                @endif

                                {{-- Inputs hidden para o form --}}
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product']->id }}">
                                <input type="hidden" name="items[{{ $index }}][billing_cycle]" value="{{ $item['cycle'] }}">
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900">R$ {{ number_format($item['total'], 2, ',', '.') }}</div>
                                @if($item['setup_fee'] > 0)
                                <div class="text-xs text-gray-500">Setup: R$ {{ number_format($item['setup_fee'], 2, ',', '.') }}</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Cupom --}}
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex gap-2">
                            <input type="text" x-model="couponCode"
                                   class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm uppercase focus:border-blue-500 focus:outline-none"
                                   placeholder="Cupom de desconto">
                            <button @click="validateCoupon()" type="button"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2 rounded-lg text-sm">
                                Aplicar
                            </button>
                        </div>
                        <p class="text-green-600 text-sm mt-2" x-show="couponValid" x-text="couponMsg"></p>
                        <p class="text-red-500 text-sm mt-2" x-show="couponError" x-text="couponError"></p>
                    </div>
                </div>

                {{-- Dados da Conta --}}
                @if(!$isLoggedIn)
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Dados da Conta</h2>

                    {{-- Abas Login / Criar Conta --}}
                    <div class="flex gap-4 mb-6 border-b border-gray-200">
                        <button type="button"
                                @click="accountType = 'existing'"
                                :class="accountType === 'existing' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500'"
                                class="pb-2 px-4 transition">
                            Já tenho conta
                        </button>
                        <button type="button"
                                @click="accountType = 'new'"
                                :class="accountType === 'new' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500'"
                                class="pb-2 px-4 transition">
                            Criar nova conta
                        </button>
                    </div>

                    <form id="checkout-form" action="{{ route('checkout.submit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="account_type" x-model="accountType">
                        <input type="hidden" name="coupon_code" x-model="couponCode">

                        {{-- Campos dos itens --}}
                        @foreach($cartItems as $index => $item)
                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product']->id }}">
                        <input type="hidden" name="items[{{ $index }}][billing_cycle]" value="{{ $item['cycle'] }}">
                        <input type="hidden" name="items[{{ $index }}][domain]" :value="items[{{ $index }}].domain">
                        @endforeach

                        {{-- Form Login --}}
                        <div x-show="accountType === 'existing'" x-cloak>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                                    <input type="email" name="email" required
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none"
                                           placeholder="seu@email.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Senha</label>
                                    <input type="password" name="password" required
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none"
                                           placeholder="••••••••">
                                </div>
                                <p class="text-sm text-gray-500">
                                    <a href="{{ route('client.password.forgot') }}" class="text-blue-600 hover:underline">Esqueceu a senha?</a>
                                </p>
                            </div>
                        </div>

                        {{-- Form Criar Conta --}}
                        <div x-show="accountType === 'new'" x-cloak x-data="{ cep: '', cepLoading: false, cepError: '', address: { logradouro: '', bairro: '', cidade: '', uf: '', ibge: '' }, cpfCnpj: '' }">
                            <div class="space-y-4">
                                {{-- Nome e Sobrenome --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nome <span class="text-red-500">*</span></label>
                                        <input type="text" name="first_name" required
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                               placeholder="João">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Sobrenome <span class="text-red-500">*</span></label>
                                        <input type="text" name="last_name" required
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                               placeholder="Silva">
                                    </div>
                                </div>

                                {{-- Email e CPF/CNPJ --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                        <input type="email" name="email" required
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                               placeholder="seu@email.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">CPF ou CNPJ <span class="text-red-500">*</span></label>
                                        <input type="text" name="document" required
                                               x-model="cpfCnpj"
                                               @input="cpfCnpj = formatCpfCnpj($event.target.value)"
                                               maxlength="18"
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                               placeholder="000.000.000-00 ou 00.000.000/0000-00">
                                    </div>
                                </div>

                                {{-- Telefone e Senha --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Telefone <span class="text-red-500">*</span></label>
                                        <input type="tel" name="phone" required
                                               x-model="phone"
                                               @input="phone = formatPhone($event.target.value)"
                                               maxlength="15"
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                               placeholder="(11) 99999-9999">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Senha <span class="text-red-500">*</span></label>
                                        <input type="password" name="password" required minlength="6"
                                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                               placeholder="Mínimo 6 caracteres">
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 pt-4 mt-4">
                                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                        <i class="bi bi-geo-alt text-blue-600"></i> Endereço de Cobrança
                                    </h4>

                                    {{-- CEP --}}
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div class="md:col-span-1">
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">CEP <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <input type="text" name="postal_code" required
                                                       x-model="cep"
                                                       @input="cep = formatCep($event.target.value)"
                                                       @blur="searchCep()"
                                                       maxlength="9"
                                                       class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                                       placeholder="00000-000">
                                                <div x-show="cepLoading" class="absolute right-3 top-3">
                                                    <i class="bi bi-arrow-repeat animate-spin text-blue-600"></i>
                                                </div>
                                            </div>
                                            <p x-show="cepError" x-text="cepError" class="text-red-500 text-xs mt-1"></p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Logradouro <span class="text-red-500">*</span></label>
                                            <input type="text" name="address1" required
                                                   x-model="address.logradouro"
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition bg-gray-50"
                                                   placeholder="Rua, Avenida, etc.">
                                        </div>
                                    </div>

                                    {{-- Número e Complemento --}}
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Número <span class="text-red-500">*</span></label>
                                            <input type="text" name="address2" required
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                                   placeholder="123">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Complemento <span class="text-gray-400 font-normal">(opcional)</span></label>
                                            <input type="text" name="address3"
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition"
                                                   placeholder="Apto, Sala, Bloco, etc.">
                                        </div>
                                    </div>

                                    {{-- Bairro, Cidade e Estado --}}
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Bairro <span class="text-red-500">*</span></label>
                                            <input type="text" name="neighborhood" required
                                                   x-model="address.bairro"
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition bg-gray-50"
                                                   placeholder="Bairro">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Cidade <span class="text-red-500">*</span></label>
                                            <input type="text" name="city" required
                                                   x-model="address.cidade"
                                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition bg-gray-50"
                                                   placeholder="Cidade">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Estado <span class="text-red-500">*</span></label>
                                            <select name="state" required
                                                    x-model="address.uf"
                                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 transition bg-gray-50">
                                                <option value="">Selecione</option>
                                                <option value="AC">Acre</option>
                                                <option value="AL">Alagoas</option>
                                                <option value="AP">Amapá</option>
                                                <option value="AM">Amazonas</option>
                                                <option value="BA">Bahia</option>
                                                <option value="CE">Ceará</option>
                                                <option value="DF">Distrito Federal</option>
                                                <option value="ES">Espírito Santo</option>
                                                <option value="GO">Goiás</option>
                                                <option value="MA">Maranhão</option>
                                                <option value="MT">Mato Grosso</option>
                                                <option value="MS">Mato Grosso do Sul</option>
                                                <option value="MG">Minas Gerais</option>
                                                <option value="PA">Pará</option>
                                                <option value="PB">Paraíba</option>
                                                <option value="PR">Paraná</option>
                                                <option value="PE">Pernambuco</option>
                                                <option value="PI">Piauí</option>
                                                <option value="RJ">Rio de Janeiro</option>
                                                <option value="RN">Rio Grande do Norte</option>
                                                <option value="RS">Rio Grande do Sul</option>
                                                <option value="RO">Rondônia</option>
                                                <option value="RR">Roraima</option>
                                                <option value="SC">Santa Catarina</option>
                                                <option value="SP">São Paulo</option>
                                                <option value="SE">Sergipe</option>
                                                <option value="TO">Tocantins</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Método de Pagamento --}}
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="font-semibold text-gray-900 mb-3">Forma de Pagamento</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @forelse($gateways as $gateway)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="payment_method" value="{{ $gateway->slug }}" required
                                           class="sr-only peer"
                                           @if($loop->first) checked @endif>
                                    <div class="border-2 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300 transition">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                                                <i class="bi bi-credit-card text-2xl text-gray-600"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">{{ $gateway->name }}</div>
                                                @if($gateway->test_mode)
                                                <div class="text-xs text-amber-600 font-medium">Modo Teste</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 hidden peer-checked:block">
                                        <div class="w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                            <i class="bi bi-check text-white text-xs"></i>
                                        </div>
                                    </div>
                                </label>
                                @empty
                                <p class="text-gray-500">Nenhum gateway configurado</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Termos --}}
                        <div class="mt-6 flex items-start gap-2">
                            <input type="checkbox" id="terms" required class="mt-1 w-4 h-4 text-blue-600 rounded">
                            <label for="terms" class="text-sm text-gray-600">
                                Concordo com os <a href="{{ route('page', 'termos-de-uso') }}" class="text-blue-600 hover:underline" target="_blank">Termos de Serviço</a>
                                e <a href="{{ route('page', 'privacidade') }}" class="text-blue-600 hover:underline" target="_blank">Política de Privacidade</a>
                            </label>
                        </div>

                        {{-- Botão Finalizar --}}
                        <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl text-lg transition">
                            Finalizar Pedido — R$ {{ number_format($total, 2, ',', '.') }}
                        </button>

                        <a href="{{ route('cart') }}" class="block text-center text-gray-500 hover:text-gray-700 text-sm mt-3">
                            ← Voltar ao Carrinho
                        </a>
                    </form>
                </div>
                @else

                {{-- Usuário Logado --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Dados do Cliente</h2>
                    <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-lg">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                            {{ substr(auth('client')->user()->first_name, 0, 1) }}{{ substr(auth('client')->user()->last_name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">{{ auth('client')->user()->first_name }} {{ auth('client')->user()->last_name }}</div>
                            <div class="text-sm text-gray-500">{{ auth('client')->user()->email }}</div>
                        </div>
                        <a href="{{ route('client.logout') }}" class="ml-auto text-sm text-red-500 hover:text-red-700">
                            Sair
                        </a>
                    </div>

                    <form id="checkout-form" action="{{ route('checkout.submit') }}" method="POST" class="mt-6">
                        @csrf
                        <input type="hidden" name="coupon_code" x-model="couponCode">

                        @foreach($cartItems as $index => $item)
                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product']->id }}">
                        <input type="hidden" name="items[{{ $index }}][billing_cycle]" value="{{ $item['cycle'] }}">
                        <input type="hidden" name="items[{{ $index }}][domain]" :value="items[{{ $index }}].domain">
                        @endforeach

                        {{-- Método de Pagamento --}}
                        <h3 class="font-semibold text-gray-900 mb-3">Forma de Pagamento</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                            @forelse($gateways as $gateway)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="{{ $gateway->slug }}" required
                                       class="sr-only peer" @if($loop->first) checked @endif>
                                <div class="border-2 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300 transition">
                                    <div class="font-semibold text-gray-900">{{ $gateway->name }}</div>
                                    @if($gateway->test_mode)
                                    <div class="text-xs text-amber-600 font-medium">Modo Teste</div>
                                    @endif
                                </div>
                            </label>
                            @empty
                            <p class="text-gray-500">Nenhum gateway configurado</p>
                            @endforelse
                        </div>

                        {{-- Termos --}}
                        <div class="flex items-start gap-2 mb-4">
                            <input type="checkbox" id="terms" required class="mt-1 w-4 h-4 text-blue-600 rounded">
                            <label for="terms" class="text-sm text-gray-600">
                                Concordo com os <a href="{{ route('page', 'termos-de-uso') }}" class="text-blue-600 hover:underline">Termos de Serviço</a>
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl text-lg transition">
                            Pagar R$ {{ number_format($total, 2, ',', '.') }}
                        </button>

                        <a href="{{ route('cart') }}" class="block text-center text-gray-500 hover:text-gray-700 text-sm mt-3">
                            ← Voltar ao Carrinho
                        </a>
                    </form>
                </div>
                @endif

                @endif
            </div>

            {{-- Coluna Lateral --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4">Resumo</h3>

                    @if(!empty($cartItems))
                    <div class="space-y-3 mb-4">
                        @foreach($cartItems as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $item['product']->name }}</span>
                            <span class="font-semibold">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-200 pt-4 space-y-2">
                        <div class="flex justify-between" x-show="discount > 0">
                            <span class="text-green-600">Desconto</span>
                            <span class="font-semibold text-green-600" x-text="'- R$ ' + discount.toFixed(2).replace('.', ',')"></span>
                        </div>
                        <div class="flex justify-between items-center text-lg">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="font-extrabold text-blue-700" x-text="'R$ ' + ({{ $total }} - discount).toFixed(2).replace('.', ',')"></span>
                        </div>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        <p><i class="bi bi-shield-check text-green-500"></i> Pagamento seguro e criptografado</p>
                        <p class="mt-1"><i class="bi bi-arrow-repeat text-blue-500"></i> 7 dias de garantia</p>
                    </div>
                    @else
                    <p class="text-gray-500 text-center py-4">Nenhum item no carrinho</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Funções auxiliares de formatação
function formatCpfCnpj(value) {
    value = value.replace(/\D/g, '');
    if (value.length <= 11) {
        // CPF
        return value.replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        // CNPJ
        return value.replace(/(\d{2})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1/$2')
                    .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }
}

function formatPhone(value) {
    value = value.replace(/\D/g, '');
    if (value.length <= 10) {
        // Fixo
        return value.replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        // Celular
        return value.replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{5})(\d)/, '$1-$2');
    }
}

function formatCep(value) {
    return value.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2');
}

async function searchCep() {
    const rawCep = this.cep.replace(/\D/g, '');
    if (rawCep.length !== 8) return;

    this.cepLoading = true;
    this.cepError = '';

    try {
        const response = await fetch(`{{ route('viacep') }}/${rawCep}`);
        const data = await response.json();

        if (data.erro) {
            this.cepError = 'CEP não encontrado';
            return;
        }

        this.address = {
            logradouro: data.logradouro || '',
            bairro: data.bairro || '',
            cidade: data.localidade || '',
            uf: data.uf || '',
            ibge: data.ibge || ''
        };
    } catch (e) {
        this.cepError = 'Erro ao buscar CEP';
    } finally {
        this.cepLoading = false;
    }
}

function checkoutFlow(isLoggedIn, items) {
    return {
        accountType: 'existing',
        items: items.map(item => ({ ...item, domain: item.domain || '' })),
        couponCode: '',
        couponValid: false,
        couponMsg: '',
        couponError: '',
        discount: 0,

        async validateCoupon() {
            if (!this.couponCode) return;
            this.couponError = '';
            this.couponValid = false;
            this.couponMsg = '';

            try {
                const response = await fetch('{{ route("cart.coupon.validate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        code: this.couponCode,
                        items: this.items
                    })
                });

                const data = await response.json();

                if (data.valid) {
                    this.couponValid = true;
                    this.couponMsg = data.message;
                    this.discount = data.discount || 0;
                } else {
                    this.couponError = data.message || 'Cupom inválido';
                }
            } catch (e) {
                this.couponError = 'Erro ao validar cupom';
            }
        }
    }
}
</script>
@endpush
