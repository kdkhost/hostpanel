@extends('client.layouts.app')
@section('title', $service->domain ?? "Serviço #{$service->id}")
@section('page-title', $service->domain ?? ($service->product?->name ?? "Serviço #{$service->id}"))

@section('content')
@php
    $statusColors = ['active'=>'green','suspended'=>'amber','pending'=>'gray','terminated'=>'red','provisioning'=>'blue','failed'=>'red'];
    $statusLabels = ['active'=>'Ativo','suspended'=>'Suspenso','pending'=>'Pendente','terminated'=>'Encerrado','provisioning'=>'Provisionando','failed'=>'Falhou'];
    $color = $statusColors[$service->status] ?? 'gray';
    $cycleLabels = ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','triennially'=>'Trienal','free'=>'Grátis'];
@endphp

<div x-data="serviceShow()">

    {{-- ===== MODAL: Alterar Senha ===== --}}
    <div x-show="showPwModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showPwModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-900"><i class="bi bi-key-fill me-2 text-blue-600"></i>Alterar Senha do Painel</h3>
                <button @click="showPwModal=false" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form @submit.prevent="doChangePassword()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nova Senha *</label>
                    <input type="password" x-model="pwForm.password" required minlength="8"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500"
                           placeholder="Mínimo 8 caracteres">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar Senha *</label>
                    <input type="password" x-model="pwForm.password_confirmation" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <p class="text-red-500 text-sm" x-show="pwError" x-text="pwError"></p>
                <button type="submit" :disabled="pwLoading" class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold py-2.5 rounded-xl text-sm transition">
                    <span x-show="pwLoading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                    Alterar Senha
                </button>
            </form>
        </div>
    </div>

    {{-- ===== MODAL: Upgrade/Downgrade ===== --}}
    @if($upgradeProducts->count())
    <div x-show="showUpgradeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showUpgradeModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-900"><i class="bi bi-arrow-up-circle-fill me-2 text-indigo-600"></i>Solicitar Upgrade / Downgrade</h3>
                <button @click="showUpgradeModal=false" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form @submit.prevent="doUpgrade()" class="p-6 space-y-4">
                <p class="text-sm text-gray-500">Plano atual: <strong class="text-gray-800">{{ $service->product?->name }}</strong></p>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Novo Plano *</label>
                    <div class="space-y-2">
                        @foreach($upgradeProducts as $up)
                        @php $lowestPricing = $up->pricing->first(); @endphp
                        <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer hover:border-indigo-400 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                            <input type="radio" name="upgrade_product" value="{{ $up->id }}" x-model="upgradeForm.requested_product_id" class="accent-indigo-600">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900 text-sm">{{ $up->name }}</div>
                                @if($lowestPricing)
                                <div class="text-xs text-gray-400">R$ {{ number_format($lowestPricing->price, 2, ',', '.') }}/{{ ['monthly'=>'mês','annually'=>'ano','quarterly'=>'trim.'][$lowestPricing->billing_cycle] ?? $lowestPricing->billing_cycle }}</div>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mensagem adicional</label>
                    <textarea x-model="upgradeForm.message" rows="3"
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 resize-none"
                              placeholder="Informações adicionais para nossa equipe..."></textarea>
                </div>
                <p class="text-red-500 text-sm" x-show="upgradeError" x-text="upgradeError"></p>
                <button type="submit" :disabled="upgradeLoading || !upgradeForm.requested_product_id"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-bold py-2.5 rounded-xl text-sm transition">
                    <span x-show="upgradeLoading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                    Enviar Solicitação
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ===== MODAL: Cancelar Serviço ===== --}}
    <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showCancelModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-red-50">
                <h3 class="font-bold text-red-700"><i class="bi bi-x-circle-fill me-2"></i>Cancelar Serviço</h3>
                <button @click="showCancelModal=false" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>
            <form @submit.prevent="doCancel()" class="p-6 space-y-4">
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Atenção:</strong> O cancelamento imediato encerrará o serviço agora e todos os dados serão apagados do servidor.
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Cancelamento *</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer hover:border-gray-300 transition has-[:checked]:border-gray-500 has-[:checked]:bg-gray-50">
                            <input type="radio" name="cancel_type" value="end_of_period" x-model="cancelForm.cancel_type" class="accent-gray-600">
                            <div>
                                <div class="font-semibold text-sm text-gray-800">Fim do Período Atual</div>
                                <div class="text-xs text-gray-400">O serviço continuará ativo até o próximo vencimento.</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer hover:border-red-300 transition has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                            <input type="radio" name="cancel_type" value="immediate" x-model="cancelForm.cancel_type" class="accent-red-600">
                            <div>
                                <div class="font-semibold text-sm text-red-700">Cancelamento Imediato</div>
                                <div class="text-xs text-gray-400">O serviço será encerrado imediatamente. Sem reembolso.</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Motivo do Cancelamento</label>
                    <textarea x-model="cancelForm.reason" rows="3"
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-red-400 resize-none"
                              placeholder="Descreva o motivo do cancelamento..."></textarea>
                </div>
                <p class="text-red-500 text-sm" x-show="cancelError" x-text="cancelError"></p>
                <button type="submit" :disabled="cancelLoading"
                        class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold py-2.5 rounded-xl text-sm transition">
                    <span x-show="cancelLoading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                    Confirmar Cancelamento
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Coluna Principal --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Status Card --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between flex-wrap gap-4">
                    <div>
                        <h2 class="font-bold text-gray-900 text-xl">{{ $service->domain ?? $service->product?->name }}</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $service->product?->name }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-{{ $color }}-100 text-{{ $color }}-700">
                        {{ $statusLabels[$service->status] ?? $service->status }}
                    </span>
                </div>

                @if($service->status === 'active')
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($service->username)
                    <a href="{{ route('client.services.autologin', $service) }}" target="_blank"
                       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2 rounded-lg">
                        <i class="bi bi-box-arrow-up-right"></i>
                        @php
                            $panelLabel = \App\Services\ServerModules\ServerModuleManager::panelLabel(
                                $service->server?->module,
                                'Painel'
                            );
                        @endphp
                        {{ 'Acessar ' . $panelLabel }}
                    </a>
                    @endif
                    @if($service->username)
                    <button @click="showPwModal=true"
                            class="inline-flex items-center gap-2 border border-blue-200 text-blue-700 hover:bg-blue-50 font-semibold text-sm px-4 py-2 rounded-lg">
                        <i class="bi bi-key"></i> Alterar Senha
                    </button>
                    <button @click="requestAccess()"
                            class="inline-flex items-center gap-2 border border-indigo-200 text-indigo-700 hover:bg-indigo-50 font-semibold text-sm px-4 py-2 rounded-lg"
                            :disabled="requestingAccess">
                        <span x-show="requestingAccess" class="inline-block w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></span>
                        <i x-show="!requestingAccess" class="bi bi-envelope-arrow-up"></i>
                        <span x-text="requestingAccess ? 'Enviando...' : 'Enviar Link de Acesso'"></span>
                    </button>
                    @endif
                    <a href="{{ route('client.tickets.create') }}?service={{ $service->id }}" class="inline-flex items-center gap-2 border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold text-sm px-4 py-2 rounded-lg">
                        <i class="bi bi-headset"></i> Abrir Suporte
                    </a>
                </div>
                {{-- Feedback envio de link --}}
                <div x-show="accessLinkSent" x-transition class="mt-3 flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    <i class="bi bi-check-circle-fill text-green-500"></i>
                    <span>Link enviado para seu email<template x-if="accessLinkExpires"> com validade até <strong x-text="accessLinkExpires"></strong></template>.</span>
                </div>
                @endif
            </div>

            {{-- Informações de Acesso --}}
            @if($service->username && $service->status === 'active')
            @php
                $moduleLabel = \App\Services\ServerModules\ServerModuleManager::panelLabel(
                    $service->server?->module,
                    'Painel'
                );
            @endphp
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <span class="font-semibold text-gray-900"><i class="bi bi-key me-2 text-blue-600"></i>Acesso {{ $moduleLabel }}</span>
                    @if($service->username)
                    <a href="{{ route('client.services.autologin', $service) }}" target="_blank"
                       class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                        <i class="bi bi-box-arrow-up-right"></i> Login automático
                    </a>
                    @endif
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach([
                        ['Usuário', $service->username, true],
                        ['Domínio Principal', $service->domain, false],
                        ['Servidor', $service->server?->hostname, false],
                        ['IP do Servidor', $service->server?->ip_address, false],
                        ['NS1', $service->server?->nameserver1, false],
                        ['NS2', $service->server?->nameserver2, false],
                    ] as [$label, $value, $isMono])
                    @if($value)
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm text-gray-500">{{ $label }}</span>
                        <span class="text-sm font-medium {{ $isMono ? 'font-mono text-blue-700' : 'text-gray-800' }}">{{ $value }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Uso de Recursos --}}
            @if($service->status === 'active' && $service->username)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="usageStats()" x-init="load()">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <span class="font-semibold text-gray-900"><i class="bi bi-bar-chart me-2 text-blue-600"></i>Uso de Recursos</span>
                    <button @click="load()" class="text-gray-400 hover:text-blue-600" title="Atualizar">
                        <i class="bi bi-arrow-clockwise" :class="loading && 'animate-spin'"></i>
                    </button>
                </div>
                <div class="p-5">
                    <div x-show="loading" class="text-center py-4 text-gray-400 text-sm"><i class="bi bi-hourglass-split me-1"></i>Carregando...</div>
                    <div x-show="!loading && error" class="text-center py-4 text-red-500 text-sm" x-text="error"></div>
                    <div x-show="!loading && !error" class="space-y-4">
                        <template x-for="r in resources" :key="r.label">
                            <div>
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="font-medium text-gray-700" x-text="r.label"></span>
                                    <span class="text-gray-500" x-text="r.value"></span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="rounded-full h-2 transition-all"
                                         :class="r.pct > 90 ? 'bg-red-500' : r.pct > 70 ? 'bg-amber-400' : 'bg-blue-500'"
                                         :style="'width:' + r.pct + '%'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            @endif

            {{-- Faturas do Serviço --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                    <span class="font-semibold text-gray-900"><i class="bi bi-receipt me-2 text-blue-600"></i>Faturas</span>
                    <a href="{{ route('client.invoices.index') }}" class="text-blue-600 text-sm font-medium hover:text-blue-700">Ver todas →</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($service->invoices ?? [] as $inv)
                    <a href="{{ route('client.invoices.show', $inv) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition">
                        <div>
                            <div class="font-medium text-gray-900">#{{ $inv->number }}</div>
                            <div class="text-xs text-gray-400">Venc. {{ \Carbon\Carbon::parse($inv->date_due)->format('d/m/Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-gray-900">R$ {{ number_format($inv->total, 2, ',', '.') }}</div>
                            <span class="text-xs font-semibold {{ ['paid'=>'text-green-600','overdue'=>'text-red-600','pending'=>'text-amber-600'][$inv->status] ?? 'text-gray-500' }}">
                                {{ ['paid'=>'Pago','overdue'=>'Atrasado','pending'=>'Pendente','cancelled'=>'Cancelado'][$inv->status] ?? $inv->status }}
                            </span>
                        </div>
                    </a>
                    @empty
                    <div class="px-5 py-6 text-center text-gray-500 text-sm">Nenhuma fatura encontrada.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            {{-- Detalhes do Plano --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 font-semibold text-gray-900">Detalhes do Plano</div>
                <div class="divide-y divide-gray-50 text-sm">
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Plano</span>
                        <span class="font-medium text-gray-800">{{ $service->product?->name }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Ciclo</span>
                        <span class="font-medium text-gray-800">{{ $cycleLabels[$service->billing_cycle] ?? $service->billing_cycle }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Valor</span>
                        <span class="font-bold text-gray-900">R$ {{ number_format($service->price, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Ativação</span>
                        <span class="font-medium text-gray-800">{{ $service->created_at ? \Carbon\Carbon::parse($service->created_at)->format('d/m/Y') : '—' }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Próx. Vencimento</span>
                        <span class="font-medium {{ $service->next_due_date && \Carbon\Carbon::parse($service->next_due_date)->isPast() ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $service->next_due_date ? \Carbon\Carbon::parse($service->next_due_date)->format('d/m/Y') : '—' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Alertas --}}
            @if($service->status === 'suspended')
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-amber-500 mt-0.5"></i>
                    <div>
                        <div class="font-semibold text-amber-800 text-sm">Serviço Suspenso</div>
                        <div class="text-amber-700 text-xs mt-1">Pague as faturas em aberto para reativar seu serviço.</div>
                        <a href="{{ route('client.invoices.index', ['status'=>'pending']) }}" class="inline-block mt-2 text-amber-800 font-semibold text-xs underline">Ver faturas pendentes →</a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Ações do Serviço --}}
            @if(!in_array($service->status, ['terminated','cancelled']))
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 font-semibold text-gray-900 text-sm">Gerenciar Serviço</div>
                <div class="p-4 space-y-2">
                    @if($upgradeProducts->count() && $service->status === 'active')
                    <button @click="showUpgradeModal=true"
                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition">
                        <i class="bi bi-arrow-up-circle"></i> Solicitar Upgrade / Downgrade
                    </button>
                    @endif
                    <button @click="showCancelModal=true"
                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition">
                        <i class="bi bi-x-circle"></i> Solicitar Cancelamento
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function serviceShow() {
    return {
        // Access link
        requestingAccess:  false,
        accessLinkSent:    false,
        accessLinkExpires: null,

        // Change password modal
        showPwModal: false,
        pwLoading:   false,
        pwError:     '',
        pwForm:      { password: '', password_confirmation: '' },

        // Upgrade modal
        showUpgradeModal: false,
        upgradeLoading:   false,
        upgradeError:     '',
        upgradeForm:      { requested_product_id: '', message: '' },

        // Cancel modal
        showCancelModal: false,
        cancelLoading:   false,
        cancelError:     '',
        cancelForm:      { cancel_type: 'end_of_period', reason: '' },

        async requestAccess() {
            this.requestingAccess = true;
            this.accessLinkSent   = false;
            try {
                const d = await HostPanel.fetch('{{ route("client.services.request.access", $service) }}', { method: 'POST' });
                if (d.success) {
                    this.accessLinkSent    = true;
                    this.accessLinkExpires = d.expires_at ?? null;
                    HostPanel.toast(d.message, 'success');
                } else {
                    HostPanel.toast(d.message || 'Não foi possível enviar o link.', 'danger');
                }
            } catch(e) {
                HostPanel.toast('Erro ao solicitar link de acesso.', 'danger');
            }
            this.requestingAccess = false;
        },

        async doChangePassword() {
            this.pwLoading = true; this.pwError = '';
            try {
                const d = await HostPanel.fetch('{{ route("client.services.change.password", $service) }}', {
                    method: 'POST',
                    body: JSON.stringify(this.pwForm),
                });
                if (d.message && !d.errors) {
                    HostPanel.toast(d.message, 'success');
                    this.showPwModal = false;
                    this.pwForm = { password: '', password_confirmation: '' };
                } else {
                    this.pwError = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'Erro ao alterar senha.');
                }
            } catch(e) {
                this.pwError = 'Erro de conexão. Tente novamente.';
            }
            this.pwLoading = false;
        },

        async doUpgrade() {
            this.upgradeLoading = true; this.upgradeError = '';
            try {
                const d = await HostPanel.fetch('{{ route("client.services.upgrade", $service) }}', {
                    method: 'POST',
                    body: JSON.stringify(this.upgradeForm),
                });
                if (d.message && !d.errors) {
                    HostPanel.toast(d.message, 'success');
                    this.showUpgradeModal = false;
                } else {
                    this.upgradeError = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'Erro ao enviar solicitação.');
                }
            } catch(e) {
                this.upgradeError = 'Erro de conexão. Tente novamente.';
            }
            this.upgradeLoading = false;
        },

        async doCancel() {
            this.cancelLoading = true; this.cancelError = '';
            try {
                const d = await HostPanel.fetch('{{ route("client.services.cancel", $service) }}', {
                    method: 'POST',
                    body: JSON.stringify(this.cancelForm),
                });
                if (d.message && !d.errors) {
                    HostPanel.toast(d.message, 'success');
                    this.showCancelModal = false;
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.cancelError = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'Erro ao cancelar serviço.');
                }
            } catch(e) {
                this.cancelError = 'Erro de conexão. Tente novamente.';
            }
            this.cancelLoading = false;
        },
    };
}

function usageStats() {
    return {
        loading: false, error: null, resources: [],
        async load() {
            this.loading = true; this.error = null;
            try {
                const d = await HostPanel.fetch('{{ route("client.services.usage", $service) }}');
                if (d.message) { this.error = d.message; return; }
                this.resources = [
                    { label: 'Disco',    value: fmt(d.disk_used_bytes) + ' / ' + fmt(d.disk_total_bytes), pct: pct(d.disk_used_bytes, d.disk_total_bytes) },
                    { label: 'Memória', value: fmt(d.mem_used_bytes)  + ' / ' + fmt(d.mem_total_bytes),  pct: pct(d.mem_used_bytes, d.mem_total_bytes) },
                ].filter(r => r.value !== ' / ');
            } catch(e) { this.error = 'Não foi possível carregar as métricas.'; }
            this.loading = false;
        }
    };
}

function fmt(bytes) {
    if (!bytes) return '—';
    if (bytes >= 1073741824) return (bytes/1073741824).toFixed(1)+' GB';
    if (bytes >= 1048576)    return (bytes/1048576).toFixed(1)+' MB';
    return (bytes/1024).toFixed(0)+' KB';
}
function pct(used, total) { return total > 0 ? Math.min(100, Math.round(used/total*100)) : 0; }
</script>
@endpush
