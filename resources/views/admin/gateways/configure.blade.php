@extends('admin.layouts.app')
@section('title', 'Configurar ' . $gateway->name)
@section('page-title', 'Configurar Gateway')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.gateways.index') }}">Gateways</a></li>
    <li class="breadcrumb-item active">{{ $gateway->name }}</li>
@endsection

@section('content')
<div x-data="gatewayConfig()" class="max-w-3xl">

    <div class="card mb-4">
        <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
            <span class="badge bg-{{ $gateway->active ? 'success' : 'secondary' }}">
                {{ $gateway->active ? 'Ativo' : 'Inativo' }}
            </span>
            {{ $gateway->name }}
            <span class="badge bg-{{ $gateway->test_mode ? 'warning text-dark' : 'success' }} ms-1">
                {{ $gateway->test_mode ? 'Sandbox' : 'Produção' }}
            </span>
        </div>
        <div class="card-body">

            {{-- Tabs --}}
            <ul class="nav nav-tabs mb-4" x-data="{ tab: 'credentials' }">
                <li class="nav-item">
                    <button class="nav-link" :class="tab==='credentials'?'active':''" @click="tab='credentials'">Credenciais</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="tab==='fees'?'active':''" @click="tab='fees'">Taxas e Juros</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="tab==='options'?'active':''" @click="tab='options'">Opções</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="tab==='whatsapp'?'active':''" @click="tab='whatsapp'">WhatsApp</button>
                </li>
            </ul>

            @php
            $s = $gateway->getSettingsDecryptedAttribute();
            $driver = $gateway->driver;
            @endphp

            {{-- Credenciais --}}
            <div x-show="tab==='credentials'" x-data="{ tab: 'credentials' }">
                <div class="row g-3">
                    {{-- Modo Sandbox/Produção --}}
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" x-model="form.test_mode" id="test_mode">
                            <label class="form-check-label fw-semibold" for="test_mode">
                                Modo Sandbox (Testes)
                            </label>
                        </div>
                        <div class="alert alert-warning py-2 mt-2 small" x-show="form.test_mode">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Cobranças em sandbox <strong>não geram pagamentos reais</strong>.
                        </div>
                    </div>

                    @if($driver === 'paghiper')
                    <div class="col-12">
                        <label class="form-label fw-semibold">API Key *</label>
                        <input class="form-control" x-model="form.settings.api_key" placeholder="apk_...">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Token *</label>
                        <input class="form-control" x-model="form.settings.token">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Método Padrão</label>
                        <select class="form-select" x-model="form.settings.default_method">
                            <option value="pix">PIX</option>
                            <option value="billet">Boleto Bancário</option>
                        </select>
                    </div>

                    @elseif($driver === 'mercadopago')
                    <div class="col-12">
                        <label class="form-label fw-semibold">Access Token (Produção) *</label>
                        <input class="form-control" x-model="form.settings.access_token" placeholder="APP_USR-...">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Access Token (Sandbox)</label>
                        <input class="form-control" x-model="form.settings.access_token_sandbox" placeholder="TEST-...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Método Padrão</label>
                        <select class="form-select" x-model="form.settings.default_method">
                            <option value="pix">PIX</option>
                            <option value="credit_card">Cartão de Crédito</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Expiração PIX (minutos)</label>
                        <input type="number" class="form-control" x-model="form.settings.pix_expiration_minutes" placeholder="1440">
                    </div>

                    @elseif($driver === 'efirpro')
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Client ID *</label>
                        <input class="form-control" x-model="form.settings.client_id">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Client Secret *</label>
                        <input class="form-control" x-model="form.settings.client_secret">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Chave PIX *</label>
                        <input class="form-control" x-model="form.settings.pix_key" placeholder="CPF, CNPJ, email ou chave aleatória">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Caminho do Certificado (.pem)</label>
                        <input class="form-control" x-model="form.settings.cert_path" placeholder="storage/app/gateways/efi_cert.pem">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Expiração (horas)</label>
                        <input type="number" class="form-control" x-model="form.settings.expiration_hours" placeholder="24">
                    </div>

                    @elseif($driver === 'bancointer')
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Client ID *</label>
                        <input class="form-control" x-model="form.settings.client_id">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Client Secret *</label>
                        <input class="form-control" x-model="form.settings.client_secret">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Chave PIX *</label>
                        <input class="form-control" x-model="form.settings.pix_key" placeholder="Chave PIX cadastrada no Inter">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Caminho do Certificado (.crt)</label>
                        <input class="form-control" x-model="form.settings.cert_path" placeholder="storage/app/gateways/inter_cert.crt">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Caminho da Chave (.key)</label>
                        <input class="form-control" x-model="form.settings.key_path" placeholder="storage/app/gateways/inter_cert.key">
                    </div>

                    @elseif($driver === 'bancobrasil')
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Client ID *</label>
                        <input class="form-control" x-model="form.settings.client_id">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Client Secret *</label>
                        <input class="form-control" x-model="form.settings.client_secret">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Chave PIX *</label>
                        <input class="form-control" x-model="form.settings.pix_key" placeholder="Chave PIX do Banco do Brasil">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Developer App Key (Sandbox)</label>
                        <input class="form-control" x-model="form.settings.developer_app_key_sandbox" placeholder="Somente para ambiente de testes">
                    </div>

                    @elseif($driver === 'pagbank')
                    <div class="col-12">
                        <label class="form-label fw-semibold">Token (Produção) *</label>
                        <input class="form-control" x-model="form.settings.token" placeholder="Token da conta PagBank">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Token (Sandbox)</label>
                        <input class="form-control" x-model="form.settings.token_sandbox">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Método Padrão</label>
                        <select class="form-select" x-model="form.settings.default_method">
                            <option value="pix">PIX</option>
                            <option value="boleto">Boleto</option>
                            <option value="credit_card">Cartão</option>
                        </select>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Taxas e Juros --}}
            <div x-show="tab==='fees'">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Taxa Fixa (R$)</label>
                        <input type="number" step="0.01" class="form-control" x-model="form.fee_fixed" placeholder="0.00">
                        <small class="text-muted">Cobrada por transação.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Taxa Percentual (%)</label>
                        <input type="number" step="0.0001" class="form-control" x-model="form.fee_percentage" placeholder="0.0000">
                        <small class="text-muted">Ex: 0.0199 = 1,99%</small>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" x-model="form.settings.pass_fee" id="pass_fee">
                            <label class="form-check-label fw-semibold" for="pass_fee">
                                Repassar taxa ao cliente
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Se ativo, a taxa do gateway é adicionada ao valor da fatura.</small>
                    </div>
                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" x-model="form.settings.late_fee_enabled" id="late_fee_enabled">
                            <label class="form-check-label fw-semibold" for="late_fee_enabled">
                                Aplicar multa e juros por atraso no gateway
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Envia multa e juros diretamente na cobrança PIX/boleto.</small>
                    </div>
                    <div class="col-md-6" x-show="form.settings.late_fee_enabled">
                        <label class="form-label fw-semibold">Multa por Atraso (%)</label>
                        <input type="number" step="0.01" class="form-control" x-model="form.settings.late_fee_percent" placeholder="2.00">
                        <small class="text-muted">Cobrada uma única vez após o vencimento.</small>
                    </div>
                    <div class="col-md-6" x-show="form.settings.late_fee_enabled">
                        <label class="form-label fw-semibold">Juros Diários (%)</label>
                        <input type="number" step="0.0001" class="form-control" x-model="form.settings.interest_daily" placeholder="0.0330">
                        <small class="text-muted">0.0330% ao dia = ~1% ao mês.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Vencimento em (dias)</label>
                        <input type="number" class="form-control" x-model="form.due_days" placeholder="3">
                        <small class="text-muted">Dias corridos para vencimento a partir da emissão.</small>
                    </div>
                </div>
            </div>

            {{-- Opções --}}
            <div x-show="tab==='options'">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" x-model="form.active" id="active">
                            <label class="form-check-label fw-semibold" for="active">Gateway Ativo</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" x-model="form.supports_recurring" id="supports_recurring">
                            <label class="form-check-label fw-semibold" for="supports_recurring">Suporta Recorrência</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" x-model="form.supports_refund" id="supports_refund">
                            <label class="form-check-label fw-semibold" for="supports_refund">Suporta Reembolso</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ordem de Exibição</label>
                        <input type="number" class="form-control" x-model="form.sort_order" min="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">URL do Webhook <small class="text-muted fw-normal">(gerada automaticamente por fatura)</small></label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace bg-light" readonly
                                   value="{{ url('/webhook/' . $gateway->driver . '/{invoice_id}') }}">
                            <span class="input-group-text">
                                <i class="bi bi-info-circle text-primary" title="Enviada automaticamente em cada cobrança. Não é necessário configurar no painel do gateway."></i>
                            </span>
                        </div>
                        <small class="text-success"><i class="bi bi-check-circle-fill"></i> Enviada automaticamente em cada requisição de cobrança.</small>
                    </div>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div x-show="tab==='whatsapp'">
                <div class="alert alert-info small py-2 mb-4">
                    <i class="bi bi-whatsapp me-1"></i>
                    Configuração global do WhatsApp via <strong>Evolution API</strong>.
                    Aplica-se a todos os gateways.
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" x-model="wapp.enabled" id="wapp_enabled">
                            <label class="form-check-label fw-semibold" for="wapp_enabled">Habilitar WhatsApp</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">URL da Evolution API</label>
                        <input class="form-control" x-model="wapp.url" placeholder="https://evolution.meudominio.com">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">API Key</label>
                        <input class="form-control" x-model="wapp.api_key" placeholder="Chave de autenticação da API">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nome da Instância</label>
                        <input class="form-control" x-model="wapp.instance" placeholder="hostpanel">
                    </div>
                    <div class="col-12">
                        <button @click="testWhatsApp()" class="btn btn-sm btn-outline-success" :disabled="testing">
                            <span x-show="testing" class="spinner-border spinner-border-sm me-1"></span>
                            <i class="bi bi-whatsapp" x-show="!testing"></i>
                            Testar Conexão
                        </button>
                        <span x-show="testMsg" class="ms-2 small" :class="testOk?'text-success':'text-danger'" x-text="testMsg"></span>
                    </div>
                    <div class="col-12">
                        <button @click="saveWhatsApp()" class="btn btn-success btn-sm" :disabled="savingWapp">
                            <span x-show="savingWapp" class="spinner-border spinner-border-sm me-1"></span>
                            Salvar Configurações WhatsApp
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-5 d-flex gap-2">
                <button @click="save()" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                    Salvar Gateway
                </button>
                <a href="{{ route('admin.gateways.index') }}" class="btn btn-outline-secondary">Voltar</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function gatewayConfig() {
    return {
        saving: false, savingWapp: false, testing: false,
        testMsg: '', testOk: false,

        form: {
            active:             {{ $gateway->active ? 'true' : 'false' }},
            test_mode:          {{ $gateway->test_mode ? 'true' : 'false' }},
            fee_fixed:          {{ (float)$gateway->fee_fixed }},
            fee_percentage:     {{ (float)$gateway->fee_percentage }},
            sort_order:         {{ (int)$gateway->sort_order }},
            supports_recurring: {{ $gateway->supports_recurring ? 'true' : 'false' }},
            supports_refund:    {{ $gateway->supports_refund ? 'true' : 'false' }},
            due_days:           {{ (int)($gateway->due_days ?? 3) }},
            settings: @json($gateway->getSettingsDecryptedAttribute()),
        },

        wapp: {
            enabled:  {{ config('hostpanel.whatsapp.enabled', false) ? 'true' : 'false' }},
            url:      '{{ config('hostpanel.whatsapp.url', '') }}',
            api_key:  '{{ config('hostpanel.whatsapp.api_key', '') }}',
            instance: '{{ config('hostpanel.whatsapp.instance', '') }}',
        },

        async save() {
            this.saving = true;
            const d = await HostPanel.fetch('{{ route("admin.gateways.configure.save", $gateway) }}', {
                method: 'PUT',
                body: JSON.stringify(this.form)
            });
            this.saving = false;
            HostPanel.toast(d.message ?? 'Salvo!');
        },

        async saveWhatsApp() {
            this.savingWapp = true;
            const d = await HostPanel.fetch('{{ route("admin.settings.whatsapp.save") }}', {
                method: 'POST',
                body: JSON.stringify(this.wapp)
            });
            this.savingWapp = false;
            HostPanel.toast(d.message ?? 'WhatsApp configurado!');
        },

        async testWhatsApp() {
            this.testing = true; this.testMsg = '';
            const d = await HostPanel.fetch('{{ route("admin.settings.whatsapp.test") }}', {
                method: 'POST',
                body: JSON.stringify(this.wapp)
            });
            this.testing = false;
            this.testOk  = !!d.success;
            this.testMsg = d.message ?? (d.success ? 'Conectado!' : 'Falha na conexão.');
        }
    }
}
</script>
@endpush
