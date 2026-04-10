@extends('admin.layouts.app')
@section('title', 'Gateways de Pagamento')
@section('page-title', 'Gateways de Pagamento')
@section('breadcrumb')
    <li class="breadcrumb-item active">Gateways</li>
@endsection

@section('content')
<div x-data="gatewaysIndex()">

    <div class="row g-4" x-show="!loading">
        <template x-for="gw in gateways" :key="gw.id">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border" :class="gw.active ? 'border-success' : 'border-0'">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center gap-3">
                            <!-- Logo/Ícone do Gateway -->
                            <div class="gateway-logo">
                                <template x-if="gw.driver === 'paghiper'">
                                    <img src="https://paghiper.com/assets/img/logo-paghiper-horizontal.png" alt="PagHiper" class="gateway-img">
                                </template>
                                <template x-if="gw.driver === 'mercadopago'">
                                    <img src="https://http2.mlstatic.com/frontend-assets/ml-web-navigation/ui-navigation/5.21.22/mercadolibre/logo__large_plus.png" alt="Mercado Pago" class="gateway-img">
                                </template>
                                <template x-if="gw.driver === 'pagbank'">
                                    <img src="https://assets.pagseguro.com.br/ps-sdk-web/2.0.0/assets/images/logos/pagbank.svg" alt="PagBank" class="gateway-img">
                                </template>
                                <template x-if="gw.driver === 'efirpro'">
                                    <img src="https://sejaefi.com.br/wp-content/uploads/2021/03/logo-efi-pay.png" alt="Efí Pro" class="gateway-img">
                                </template>
                                <template x-if="gw.driver === 'bancointer'">
                                    <div class="gateway-icon bg-orange">
                                        <i class="fas fa-university text-white"></i>
                                    </div>
                                </template>
                                <template x-if="gw.driver === 'bancobrasil'">
                                    <div class="gateway-icon bg-warning">
                                        <i class="fas fa-landmark text-dark"></i>
                                    </div>
                                </template>
                                <template x-if="!['paghiper','mercadopago','pagbank','efirpro','bancointer','bancobrasil'].includes(gw.driver)">
                                    <div class="gateway-icon bg-primary">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <div class="fw-bold" x-text="gw.name"></div>
                                <small class="text-muted" x-text="gw.driver"></small>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" :checked="gw.active" @change="toggleActive(gw)">
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Status de Configuração -->
                        <div class="mb-3">
                            <template x-if="isConfigured(gw)">
                                <div class="alert alert-success py-2 mb-2">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    <small>Configurado corretamente</small>
                                </div>
                            </template>
                            <template x-if="!isConfigured(gw)">
                                <div class="alert alert-warning py-2 mb-2">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    <small>Necessita configuração</small>
                                </div>
                            </template>
                        </div>

                        <div class="row g-2 text-center mb-3">
                            <div class="col-4">
                                <div class="small text-muted">Modo</div>
                                <span class="badge" :class="gw.test_mode ? 'bg-warning text-dark' : 'bg-success'" x-text="gw.test_mode ? 'Teste' : 'Produção'"></span>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Taxa Fixa</div>
                                <div class="fw-semibold small" x-text="gw.fee_fixed ? 'R$ ' + parseFloat(gw.fee_fixed).toFixed(2) : '—'"></div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Taxa %</div>
                                <div class="fw-semibold small" x-text="gw.fee_percentage ? gw.fee_percentage + '%' : '—'"></div>
                            </div>
                        </div>

                        <!-- Recursos Suportados -->
                        <div class="mb-3">
                            <div class="d-flex flex-wrap gap-1">
                                <template x-if="gw.supports_refund">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reembolso
                                    </span>
                                </template>
                                <template x-if="gw.supports_recurring">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-arrow-repeat me-1"></i>Recorrente
                                    </span>
                                </template>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-link-45deg me-1"></i>Webhook
                                </span>
                                <!-- Métodos de Pagamento -->
                                <template x-if="['paghiper','efirpro','bancointer','bancobrasil'].includes(gw.driver)">
                                    <span class="badge bg-success text-white">PIX</span>
                                </template>
                                <template x-if="['paghiper','efirpro','pagbank'].includes(gw.driver)">
                                    <span class="badge bg-info text-white">Boleto</span>
                                </template>
                                <template x-if="['mercadopago','pagbank'].includes(gw.driver)">
                                    <span class="badge bg-primary text-white">Cartão</span>
                                </template>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a :href="`{{ url('admin/gateways') }}/${gw.id}/configurar`"
                               class="btn btn-sm btn-primary flex-grow-1">
                                <i class="bi bi-gear me-1"></i>Configurar
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" @click="testGateway(gw)" title="Testar Gateway">
                                <i class="bi bi-wifi"></i>
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                        type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" @click="showWebhookUrl(gw.driver)">
                                            <i class="bi bi-link me-2"></i>URL Webhook
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" @click="openEdit(gw)">
                                            <i class="bi bi-pencil me-2"></i>Edição Rápida
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-2 small text-center" x-show="gw.active">
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Gateway Ativo</span>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!loading && gateways.length === 0">
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-credit-card" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Nenhum gateway configurado</h5>
                <p>Configure seus gateways de pagamento para começar a receber pagamentos.</p>
            </div>
        </template>
    </div>

    <div x-show="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>

    <!-- Modal Edição Rápida -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" x-show="editing">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="'Edição Rápida - ' + (editing?.name ?? '')"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Modo</label>
                            <select class="form-select" x-model="form.test_mode">
                                <option :value="false">Produção</option>
                                <option :value="true">Teste/Sandbox</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Taxa Fixa (R$)</label>
                            <input type="number" step="0.01" class="form-control" x-model="form.fee_fixed">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Taxa (%)</label>
                            <input type="number" step="0.01" class="form-control" x-model="form.fee_percentage">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ordem de Exibição</label>
                            <input type="number" class="form-control" x-model="form.sort_order">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" x-model="form.active" id="modal_active">
                                <label class="form-check-label fw-semibold" for="modal_active">Gateway Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveGateway()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Webhook URL -->
    <div class="modal fade" id="webhookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">URL do Webhook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Configure esta URL no painel do seu gateway:</p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="webhookUrl" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyWebhookUrl()">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>
                    <div class="alert alert-info mt-3 py-2">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            Esta URL é enviada automaticamente em cada cobrança.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.gateway-logo {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.gateway-img {
    max-width: 45px;
    max-height: 45px;
    object-fit: contain;
}

.gateway-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 1.2rem;
}

.bg-orange {
    background-color: #ff6900 !important;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.border-success {
    border-left: 4px solid #28a745 !important;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}
</style>
@endpush

@push('scripts')
<script>
function gatewaysIndex() {
    return {
        gateways: [], 
        loading: false, 
        editing: null, 
        saving: false,
        form: {},

        async load() {
            this.loading = true;
            try {
                this.gateways = await HostPanel.fetch('{{ route("admin.gateways.index") }}');
            } catch (error) {
                HostPanel.toast('Erro ao carregar gateways', 'error');
            }
            this.loading = false;
        },

        isConfigured(gw) {
            const settings = gw.settings_decrypted || {};
            switch (gw.driver) {
                case 'paghiper':
                    return !!(settings.api_key && settings.token);
                case 'mercadopago':
                    return !!settings.access_token;
                case 'efirpro':
                    return !!(settings.client_id && settings.client_secret && settings.pix_key);
                case 'bancointer':
                    return !!(settings.client_id && settings.client_secret && settings.pix_key);
                case 'bancobrasil':
                    return !!(settings.client_id && settings.client_secret && settings.pix_key);
                case 'pagbank':
                    return !!settings.token;
                default:
                    return true;
            }
        },

        openEdit(gw) {
            this.editing = gw;
            this.form = { 
                test_mode: gw.test_mode, 
                fee_fixed: gw.fee_fixed, 
                fee_percentage: gw.fee_percentage, 
                sort_order: gw.sort_order ?? 0,
                active: gw.active
            };
            new bootstrap.Modal(document.getElementById('editModal')).show();
        },

        async saveGateway() {
            this.saving = true;
            try {
                const response = await HostPanel.fetch(`{{ url('admin/gateways') }}/${this.editing.id}`, { 
                    method:'PUT', 
                    body: JSON.stringify(this.form) 
                });
                HostPanel.toast(response.message);
                if (response.gateway) { 
                    await this.load(); 
                    bootstrap.Modal.getInstance(document.getElementById('editModal'))?.hide(); 
                }
            } catch (error) {
                HostPanel.toast('Erro ao salvar gateway', 'error');
            }
            this.saving = false;
        },

        async toggleActive(gw) {
            try {
                const response = await HostPanel.fetch(`{{ url('admin/gateways') }}/${gw.id}`, { 
                    method:'PUT', 
                    body: JSON.stringify({ active: !gw.active }) 
                });
                HostPanel.toast(response.message);
                if (response.gateway) gw.active = response.gateway.active;
            } catch (error) {
                HostPanel.toast('Erro ao atualizar gateway', 'error');
            }
        },

        async testGateway(gw) {
            try {
                const response = await HostPanel.fetch(`{{ url('admin/gateways') }}/${gw.id}/testar`, { method:'POST' });
                HostPanel.toast(response.message, response.success ? 'success' : 'warning');
            } catch (error) {
                HostPanel.toast('Erro ao testar gateway', 'error');
            }
        },

        showWebhookUrl(driver) {
            const url = `{{ url('/webhook') }}/${driver}/INVOICE_ID`;
            document.getElementById('webhookUrl').value = url;
            new bootstrap.Modal(document.getElementById('webhookModal')).show();
        },

        init() { 
            this.load(); 
        }
    }
}

function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    document.execCommand('copy');
    HostPanel.toast('URL copiada para a área de transferência!', 'success');
}
</script>
@endpush
@endsection