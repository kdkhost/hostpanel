@extends('admin.layouts.app')
@section('title', 'Configurar ' . $gateway->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('admin.gateways.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <div>
                            <h5 class="mb-0">{{ $gateway->name }}</h5>
                            <small class="text-muted">{{ ucfirst($gateway->driver) }}</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-{{ $gateway->active ? 'success' : 'secondary' }}">
                            {{ $gateway->active ? 'Ativo' : 'Inativo' }}
                        </span>
                        <span class="badge bg-{{ $gateway->test_mode ? 'warning text-dark' : 'success' }}">
                            {{ $gateway->test_mode ? 'Sandbox' : 'Produção' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Formulário de Configuração -->
            <div class="card">
                <div class="card-body">
                    <form id="gatewayForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Hidden fields para checkboxes -->
                        <input type="hidden" name="active" value="0">
                        <input type="hidden" name="test_mode" value="0">
                        <input type="hidden" name="supports_recurring" value="0">
                        <input type="hidden" name="supports_refund" value="0">
                        <input type="hidden" name="settings[pass_fee]" value="0">
                        <input type="hidden" name="settings[late_fee_enabled]" value="0">

                        <!-- Navegação por Abas -->
                        <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="credentials-tab" data-bs-toggle="tab" 
                                        data-bs-target="#credentials" type="button" role="tab">
                                    <i class="fas fa-key me-2"></i>Credenciais
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="fees-tab" data-bs-toggle="tab" 
                                        data-bs-target="#fees" type="button" role="tab">
                                    <i class="fas fa-percentage me-2"></i>Taxas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="options-tab" data-bs-toggle="tab" 
                                        data-bs-target="#options" type="button" role="tab">
                                    <i class="fas fa-cog me-2"></i>Opções
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="webhook-tab" data-bs-toggle="tab" 
                                        data-bs-target="#webhook" type="button" role="tab">
                                    <i class="fas fa-link me-2"></i>Webhook
                                </button>
                            </li>
                        </ul>

                        <!-- Conteúdo das Abas -->
                        <div class="tab-content" id="configTabsContent">
                            
                            <!-- Aba Credenciais -->
                            <div class="tab-pane fade show active" id="credentials" role="tabpanel">
                                @php $settings = $gateway->getSettingsDecryptedAttribute(); @endphp
                                
                                <div class="row g-3">
                                    <!-- Modo Sandbox/Produção -->
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="test_mode" id="test_mode" value="1"
                                                   {{ $gateway->test_mode ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="test_mode">
                                                Modo Sandbox (Testes)
                                            </label>
                                        </div>
                                        <div class="alert alert-warning py-2 mt-2 small" id="sandbox-warning" 
                                             style="display: {{ $gateway->test_mode ? 'block' : 'none' }}">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Cobranças em sandbox <strong>não geram pagamentos reais</strong>.
                                        </div>
                                    </div>

                                    @if($gateway->driver === 'paghiper')
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">API Key *</label>
                                            <input class="form-control" name="settings[api_key]" 
                                                   value="{{ $settings['api_key'] ?? '' }}" 
                                                   placeholder="apk_...">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Token *</label>
                                            <input class="form-control" name="settings[token]" 
                                                   value="{{ $settings['token'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Método Padrão</label>
                                            <select class="form-select" name="settings[default_method]">
                                                <option value="pix" {{ ($settings['default_method'] ?? 'pix') === 'pix' ? 'selected' : '' }}>PIX</option>
                                                <option value="billet" {{ ($settings['default_method'] ?? '') === 'billet' ? 'selected' : '' }}>Boleto Bancário</option>
                                            </select>
                                        </div>

                                    @elseif($gateway->driver === 'mercadopago')
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Access Token (Produção) *</label>
                                            <input class="form-control" name="settings[access_token]" 
                                                   value="{{ $settings['access_token'] ?? '' }}" 
                                                   placeholder="APP_USR-...">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Access Token (Sandbox)</label>
                                            <input class="form-control" name="settings[access_token_sandbox]" 
                                                   value="{{ $settings['access_token_sandbox'] ?? '' }}" 
                                                   placeholder="TEST-...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Webhook Secret</label>
                                            <input class="form-control" name="settings[webhook_secret]" 
                                                   value="{{ $settings['webhook_secret'] ?? '' }}" 
                                                   placeholder="Secret para validação de webhook">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Expiração PIX (minutos)</label>
                                            <input type="number" class="form-control" name="settings[pix_expiration_minutes]" 
                                                   value="{{ $settings['pix_expiration_minutes'] ?? 1440 }}" 
                                                   placeholder="1440">
                                        </div>

                                    @elseif($gateway->driver === 'efirpro')
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Client ID *</label>
                                            <input class="form-control" name="settings[client_id]" 
                                                   value="{{ $settings['client_id'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Client Secret *</label>
                                            <input class="form-control" name="settings[client_secret]" 
                                                   value="{{ $settings['client_secret'] ?? '' }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Chave PIX *</label>
                                            <input class="form-control" name="settings[pix_key]" 
                                                   value="{{ $settings['pix_key'] ?? '' }}" 
                                                   placeholder="CPF, CNPJ, email ou chave aleatória">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Caminho do Certificado (.pem)</label>
                                            <input class="form-control" name="settings[cert_path]" 
                                                   value="{{ $settings['cert_path'] ?? '' }}" 
                                                   placeholder="storage/app/gateways/efi_cert.pem">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Expiração (horas)</label>
                                            <input type="number" class="form-control" name="settings[expiration_hours]" 
                                                   value="{{ $settings['expiration_hours'] ?? 24 }}" 
                                                   placeholder="24">
                                        </div>

                                    @elseif($gateway->driver === 'bancointer')
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Client ID *</label>
                                            <input class="form-control" name="settings[client_id]" 
                                                   value="{{ $settings['client_id'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Client Secret *</label>
                                            <input class="form-control" name="settings[client_secret]" 
                                                   value="{{ $settings['client_secret'] ?? '' }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Chave PIX *</label>
                                            <input class="form-control" name="settings[pix_key]" 
                                                   value="{{ $settings['pix_key'] ?? '' }}" 
                                                   placeholder="Chave PIX cadastrada no Inter">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Caminho do Certificado (.crt)</label>
                                            <input class="form-control" name="settings[cert_path]" 
                                                   value="{{ $settings['cert_path'] ?? '' }}" 
                                                   placeholder="storage/app/gateways/inter_cert.crt">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Caminho da Chave (.key)</label>
                                            <input class="form-control" name="settings[key_path]" 
                                                   value="{{ $settings['key_path'] ?? '' }}" 
                                                   placeholder="storage/app/gateways/inter_cert.key">
                                        </div>

                                    @elseif($gateway->driver === 'bancobrasil')
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Client ID *</label>
                                            <input class="form-control" name="settings[client_id]" 
                                                   value="{{ $settings['client_id'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Client Secret *</label>
                                            <input class="form-control" name="settings[client_secret]" 
                                                   value="{{ $settings['client_secret'] ?? '' }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Chave PIX *</label>
                                            <input class="form-control" name="settings[pix_key]" 
                                                   value="{{ $settings['pix_key'] ?? '' }}" 
                                                   placeholder="Chave PIX do Banco do Brasil">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Developer App Key (Sandbox)</label>
                                            <input class="form-control" name="settings[developer_app_key_sandbox]" 
                                                   value="{{ $settings['developer_app_key_sandbox'] ?? '' }}" 
                                                   placeholder="Somente para ambiente de testes">
                                        </div>

                                    @elseif($gateway->driver === 'pagbank')
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Token (Produção) *</label>
                                            <input class="form-control" name="settings[token]" 
                                                   value="{{ $settings['token'] ?? '' }}" 
                                                   placeholder="Token da conta PagBank">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Token (Sandbox)</label>
                                            <input class="form-control" name="settings[token_sandbox]" 
                                                   value="{{ $settings['token_sandbox'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Método Padrão</label>
                                            <select class="form-select" name="settings[default_method]">
                                                <option value="pix" {{ ($settings['default_method'] ?? 'pix') === 'pix' ? 'selected' : '' }}>PIX</option>
                                                <option value="boleto" {{ ($settings['default_method'] ?? '') === 'boleto' ? 'selected' : '' }}>Boleto</option>
                                                <option value="credit_card" {{ ($settings['default_method'] ?? '') === 'credit_card' ? 'selected' : '' }}>Cartão</option>
                                            </select>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Aba Taxas -->
                            <div class="tab-pane fade" id="fees" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Taxa Fixa (R$)</label>
                                        <input type="number" step="0.01" class="form-control" name="fee_fixed" 
                                               value="{{ $gateway->fee_fixed }}" placeholder="0.00">
                                        <small class="text-muted">Cobrada por transação.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Taxa Percentual (%)</label>
                                        <input type="number" step="0.0001" class="form-control" name="fee_percentage" 
                                               value="{{ $gateway->fee_percentage }}" placeholder="0.0000">
                                        <small class="text-muted">Ex: 1.99 = 1,99%</small>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="settings[pass_fee]" 
                                                   id="pass_fee" value="1" {{ ($settings['pass_fee'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pass_fee">
                                                Repassar taxa ao cliente
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">Se ativo, a taxa do gateway é adicionada ao valor da fatura.</small>
                                    </div>
                                    <div class="col-12"><hr class="my-2"></div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="settings[late_fee_enabled]" 
                                                   id="late_fee_enabled" value="1" {{ ($settings['late_fee_enabled'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="late_fee_enabled">
                                                Aplicar multa e juros por atraso no gateway
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">Envia multa e juros diretamente na cobrança PIX/boleto.</small>
                                    </div>
                                    <div class="col-md-6" id="late_fee_percent_group" style="display: {{ ($settings['late_fee_enabled'] ?? false) ? 'block' : 'none' }}">
                                        <label class="form-label fw-semibold">Multa por Atraso (%)</label>
                                        <input type="number" step="0.01" class="form-control" name="settings[late_fee_percent]" 
                                               value="{{ $settings['late_fee_percent'] ?? 2.00 }}" placeholder="2.00">
                                        <small class="text-muted">Cobrada uma única vez após o vencimento.</small>
                                    </div>
                                    <div class="col-md-6" id="interest_daily_group" style="display: {{ ($settings['late_fee_enabled'] ?? false) ? 'block' : 'none' }}">
                                        <label class="form-label fw-semibold">Juros Diários (%)</label>
                                        <input type="number" step="0.0001" class="form-control" name="settings[interest_daily]" 
                                               value="{{ $settings['interest_daily'] ?? 0.0330 }}" placeholder="0.0330">
                                        <small class="text-muted">0.0330% ao dia = ~1% ao mês.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Vencimento em (dias)</label>
                                        <input type="number" class="form-control" name="due_days" 
                                               value="{{ $gateway->due_days ?? 3 }}" placeholder="3">
                                        <small class="text-muted">Dias corridos para vencimento a partir da emissão.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Opções -->
                            <div class="tab-pane fade" id="options" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                                                   {{ $gateway->active ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="active">Gateway Ativo</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="supports_recurring" id="supports_recurring" value="1"
                                                   {{ $gateway->supports_recurring ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="supports_recurring">Suporta Recorrência</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="supports_refund" id="supports_refund" value="1"
                                                   {{ $gateway->supports_refund ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="supports_refund">Suporta Reembolso</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ordem de Exibição</label>
                                        <input type="number" class="form-control" name="sort_order" 
                                               value="{{ $gateway->sort_order }}" min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Webhook -->
                            <div class="tab-pane fade" id="webhook" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">URL do Webhook</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control font-monospace bg-light" 
                                                   id="webhookUrl" readonly
                                                   value="{{ url('/webhook/' . $gateway->driver . '/INVOICE_ID') }}">
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyWebhookUrl()">
                                                <i class="fas fa-copy"></i> Copiar
                                            </button>
                                        </div>
                                        <div class="alert alert-info mt-3 py-2">
                                            <small>
                                                <i class="fas fa-info-circle me-1"></i>
                                                Esta URL é enviada automaticamente em cada cobrança. Configure no painel do gateway se necessário.
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <h6>Instruções por Gateway:</h6>
                                        @switch($gateway->driver)
                                            @case('paghiper')
                                                <div class="alert alert-light">
                                                    <strong>PagHiper:</strong> Configure a URL acima em "Configurações → Notificações → URL de Retorno"
                                                </div>
                                                @break
                                            @case('mercadopago')
                                                <div class="alert alert-light">
                                                    <strong>Mercado Pago:</strong> Configure em "Integrações → Webhooks" no painel do desenvolvedor
                                                </div>
                                                @break
                                            @case('pagbank')
                                                <div class="alert alert-light">
                                                    <strong>PagBank:</strong> Configure em "Minha Conta → Preferências → Notificações de Transação"
                                                </div>
                                                @break
                                            @default
                                                <div class="alert alert-light">
                                                    Configure esta URL no painel do seu gateway de pagamento para receber notificações automáticas.
                                                </div>
                                        @endswitch
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">
                                <i class="fas fa-save me-2"></i>Salvar Configurações
                            </button>
                            <button type="button" class="btn btn-outline-success" id="testBtn">
                                <i class="fas fa-vial me-2"></i>Testar Gateway
                            </button>
                            <a href="{{ route('admin.gateways.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle sandbox warning
    $('#test_mode').change(function() {
        $('#sandbox-warning').toggle($(this).is(':checked'));
    });

    // Toggle late fee fields
    $('#late_fee_enabled').change(function() {
        $('#late_fee_percent_group, #interest_daily_group').toggle($(this).is(':checked'));
    });

    // Salvar configurações
    $('#gatewayForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $('#saveBtn');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Salvando...');
        
        // Serializa o formulário
        const formData = $(this).serialize();
        
        $.ajax({
            url: '{{ route("admin.gateways.configure.save", $gateway) }}',
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            success: function(response) {
                toastr.success(response.message);
            },
            error: function(xhr) {
                console.log('Erro:', xhr.responseText);
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(error => {
                        toastr.error(error[0]);
                    });
                } else {
                    toastr.error('Erro ao salvar configurações: ' + (xhr.responseJSON?.message || 'Erro desconhecido'));
                }
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Testar gateway
    $('#testBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Testando...');
        
        $.ajax({
            url: '{{ route("admin.gateways.test", $gateway) }}',
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
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Copiar URL do webhook
    window.copyWebhookUrl = function() {
        const input = document.getElementById('webhookUrl');
        input.select();
        document.execCommand('copy');
        toastr.success('URL copiada para a área de transferência!');
    };
});
</script>
@endpush
@endsection
                } else {
                    toastr.warning(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Erro ao testar gateway');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Copiar URL do webhook
    window.copyWebhookUrl = function() {
        const input = document.getElementById('webhookUrl');
        input.select();
        document.execCommand('copy');
        toastr.success('URL copiada para a área de transferência!');
    };
});
</script>
@endpush
@endsection