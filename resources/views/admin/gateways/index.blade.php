@extends('admin.layouts.app')
@section('title', 'Gateways de Pagamento')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Gateways de Pagamento
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info">{{ $gateways->where('active', true)->count() }} Ativos</span>
                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-4" id="gateways-container">
                        @foreach($gateways as $gateway)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 gateway-card {{ $gateway->active ? 'border-success' : 'border-secondary' }}" 
                                 data-gateway-id="{{ $gateway->id }}">
                                
                                <!-- Header do Gateway -->
                                <div class="card-header d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="gateway-icon">
                                            @switch($gateway->driver)
                                                @case('paghiper')
                                                    <i class="fas fa-qrcode text-warning" style="font-size: 1.5rem;"></i>
                                                    @break
                                                @case('mercadopago')
                                                    <i class="fas fa-credit-card text-primary" style="font-size: 1.5rem;"></i>
                                                    @break
                                                @case('pagbank')
                                                    <i class="fas fa-university text-info" style="font-size: 1.5rem;"></i>
                                                    @break
                                                @case('efirpro')
                                                    <i class="fas fa-bolt text-success" style="font-size: 1.5rem;"></i>
                                                    @break
                                                @case('bancointer')
                                                    <i class="fas fa-building text-orange" style="font-size: 1.5rem;"></i>
                                                    @break
                                                @case('bancobrasil')
                                                    <i class="fas fa-landmark text-warning" style="font-size: 1.5rem;"></i>
                                                    @break
                                                @default
                                                    <i class="fas fa-money-bill-wave text-secondary" style="font-size: 1.5rem;"></i>
                                            @endswitch
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{ $gateway->name }}</h6>
                                            <small class="text-muted">{{ ucfirst($gateway->driver) }}</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Toggle Ativo -->
                                    <div class="form-check form-switch">
                                        <input class="form-check-input gateway-toggle" 
                                               type="checkbox" 
                                               data-gateway-id="{{ $gateway->id }}"
                                               {{ $gateway->active ? 'checked' : '' }}>
                                    </div>
                                </div>

                                <!-- Corpo do Gateway -->
                                <div class="card-body">
                                    <!-- Status e Informações -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-4 text-center">
                                            <div class="small text-muted mb-1">Modo</div>
                                            <span class="badge {{ $gateway->test_mode ? 'bg-warning text-dark' : 'bg-success' }}">
                                                {{ $gateway->test_mode ? 'Teste' : 'Produção' }}
                                            </span>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="small text-muted mb-1">Taxa Fixa</div>
                                            <div class="fw-semibold small">
                                                {{ $gateway->fee_fixed ? 'R$ ' . number_format($gateway->fee_fixed, 2, ',', '.') : '—' }}
                                            </div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="small text-muted mb-1">Taxa %</div>
                                            <div class="fw-semibold small">
                                                {{ $gateway->fee_percentage ? number_format($gateway->fee_percentage, 2, ',', '.') . '%' : '—' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recursos Suportados -->
                                    <div class="mb-3">
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($gateway->supports_refund)
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-undo me-1"></i>Reembolso
                                                </span>
                                            @endif
                                            @if($gateway->supports_recurring)
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-sync me-1"></i>Recorrente
                                                </span>
                                            @endif
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-link me-1"></i>Webhook
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Configuração Status -->
                                    @php
                                        $settings = $gateway->getSettingsDecryptedAttribute();
                                        $isConfigured = $this->isGatewayConfigured($gateway->driver, $settings);
                                    @endphp
                                    
                                    <div class="alert {{ $isConfigured ? 'alert-success' : 'alert-warning' }} py-2 mb-3">
                                        <i class="fas {{ $isConfigured ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-1"></i>
                                        <small>
                                            {{ $isConfigured ? 'Configurado corretamente' : 'Necessita configuração' }}
                                        </small>
                                    </div>
                                </div>

                                <!-- Footer com Ações -->
                                <div class="card-footer bg-light">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.gateways.configure', $gateway) }}" 
                                           class="btn btn-sm btn-primary flex-grow-1">
                                            <i class="fas fa-cog me-1"></i>Configurar
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary test-gateway" 
                                                data-gateway-id="{{ $gateway->id }}"
                                                title="Testar Gateway">
                                            <i class="fas fa-vial"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="showWebhookUrl('{{ $gateway->driver }}')">
                                                        <i class="fas fa-link me-2"></i>URL Webhook
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="showLogs({{ $gateway->id }})">
                                                        <i class="fas fa-list me-2"></i>Ver Logs
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Indicador de Status -->
                                @if($gateway->active)
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i>
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if($gateways->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-credit-card text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">Nenhum gateway configurado</h5>
                        <p class="text-muted">Configure seus gateways de pagamento para começar a receber pagamentos.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para URL do Webhook -->
<div class="modal fade" id="webhookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">URL do Webhook</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Configure esta URL no painel do seu gateway de pagamento:</p>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace" id="webhookUrl" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyWebhookUrl()">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
                <div class="alert alert-info mt-3 py-2">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Esta URL é enviada automaticamente em cada cobrança. Não é necessário configurar manualmente.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.gateway-card {
    transition: all 0.3s ease;
    position: relative;
}

.gateway-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.gateway-card.border-success {
    border-left: 4px solid #28a745 !important;
}

.gateway-card.border-secondary {
    border-left: 4px solid #6c757d !important;
}

.text-orange {
    color: #fd7e14 !important;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.badge {
    font-size: 0.75em;
}

.gateway-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.05);
    border-radius: 8px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle ativo/inativo
    $('.gateway-toggle').change(function() {
        const gatewayId = $(this).data('gateway-id');
        const isActive = $(this).is(':checked');
        
        $.ajax({
            url: `/admin/gateways/${gatewayId}`,
            method: 'PUT',
            data: {
                active: isActive,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success(response.message);
                
                // Atualiza visual do card
                const card = $(`.gateway-card[data-gateway-id="${gatewayId}"]`);
                if (isActive) {
                    card.removeClass('border-secondary').addClass('border-success');
                } else {
                    card.removeClass('border-success').addClass('border-secondary');
                }
            },
            error: function(xhr) {
                toastr.error('Erro ao atualizar gateway');
                // Reverte o toggle
                $(this).prop('checked', !isActive);
            }
        });
    });

    // Testar gateway
    $('.test-gateway').click(function() {
        const gatewayId = $(this).data('gateway-id');
        const btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: `/admin/gateways/${gatewayId}/testar`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.warning(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Erro ao testar gateway');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-vial"></i>');
            }
        });
    });
});

function showWebhookUrl(driver) {
    const url = `{{ url('/webhook/gateway') }}/${driver}`;
    $('#webhookUrl').val(url);
    new bootstrap.Modal(document.getElementById('webhookModal')).show();
}

function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    document.execCommand('copy');
    toastr.success('URL copiada para a área de transferência!');
}

function showLogs(gatewayId) {
    // Implementar modal de logs se necessário
    toastr.info('Funcionalidade de logs em desenvolvimento');
}
</script>
@endpush
@endsection

@php
function isGatewayConfigured($driver, $settings) {
    switch ($driver) {
        case 'paghiper':
            return !empty($settings['api_key']) && !empty($settings['token']);
        case 'mercadopago':
            return !empty($settings['access_token']);
        case 'efirpro':
            return !empty($settings['client_id']) && !empty($settings['client_secret']) && !empty($settings['pix_key']);
        case 'bancointer':
            return !empty($settings['client_id']) && !empty($settings['client_secret']) && !empty($settings['pix_key']);
        case 'bancobrasil':
            return !empty($settings['client_id']) && !empty($settings['client_secret']) && !empty($settings['pix_key']);
        case 'pagbank':
            return !empty($settings['token']);
        default:
            return true;
    }
}
@endphp
