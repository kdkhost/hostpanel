@extends('admin.layouts.app')
@section('title', "Fatura #{$invoice->number}")
@section('page-title', "Fatura #{$invoice->number}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Faturas</a></li>
    <li class="breadcrumb-item active">#{{ $invoice->number }}</li>
@endsection

@section('content')
<div x-data="adminInvoiceShow()" class="row g-4">

    {{-- Coluna Principal --}}
    <div class="col-lg-8">
        <div class="card">
            {{-- Header da Fatura --}}
            <div class="card-header p-0 overflow-hidden">
                <div style="background:linear-gradient(135deg,#0f172a,#1e3a8a);color:white;padding:1.5rem 2rem">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#1a56db">
                                    <i class="bi bi-server text-white"></i>
                                </div>
                                <span class="fw-bold fs-5">{{ config('app.name') }}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fs-3 fw-black">FATURA</div>
                            <div class="font-monospace opacity-75">#{{ $invoice->number }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Info Cliente/Emissão --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="text-uppercase fw-semibold text-muted small mb-2 letter-spacing-1">Faturado Para</div>
                        <div class="fw-bold">{{ $invoice->client?->company_name ?? $invoice->client?->name }}</div>
                        <div class="text-muted small">{{ $invoice->client?->name }}</div>
                        <div class="text-muted small">{{ $invoice->client?->email }}</div>
                        @if($invoice->client?->document_number)
                            <div class="text-muted small">{{ strtoupper($invoice->client?->document_type ?? 'CPF') }}: {{ $invoice->client?->document_number }}</div>
                        @endif
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="text-uppercase fw-semibold text-muted small mb-2">Informações</div>
                        <div class="small text-muted">Emissão: <strong>{{ \Carbon\Carbon::parse($invoice->date_issued)->format('d/m/Y') }}</strong></div>
                        <div class="small text-muted">Vencimento: <strong>{{ \Carbon\Carbon::parse($invoice->date_due)->format('d/m/Y') }}</strong></div>
                        @if($invoice->date_paid)
                            <div class="small text-success fw-semibold">Pago em: {{ \Carbon\Carbon::parse($invoice->date_paid)->format('d/m/Y') }}</div>
                        @endif
                        <span class="badge mt-2 bg-{{ ['paid'=>'success','overdue'=>'danger','pending'=>'warning','cancelled'=>'secondary'][$invoice->status] ?? 'secondary' }}">
                            {{ ['paid'=>'Pago','overdue'=>'Atrasado','pending'=>'Pendente','cancelled'=>'Cancelado'][$invoice->status] ?? $invoice->status }}
                        </span>
                    </div>
                </div>

                {{-- Itens --}}
                <div class="table-responsive mb-3">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Descrição</th><th class="text-center">Qtd.</th><th class="text-end">Valor Unit.</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->description }}</div>
                                    @if($item->period_start && $item->period_end)
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($item->period_start)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($item->period_end)->format('d/m/Y') }}</small>
                                    @endif
                                </td>
                                <td class="text-center text-muted">{{ $item->quantity ?? 1 }}</td>
                                <td class="text-end text-muted">R$ {{ number_format($item->unit_price ?? $item->amount, 2, ',', '.') }}</td>
                                <td class="text-end fw-semibold">R$ {{ number_format($item->amount, 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if($invoice->discount > 0)
                            <tr><td colspan="3" class="text-end text-muted">Desconto</td><td class="text-end text-success fw-semibold">- R$ {{ number_format($invoice->discount, 2, ',', '.') }}</td></tr>
                            @endif
                            @if($invoice->late_fee > 0)
                            <tr><td colspan="3" class="text-end text-muted">Multa/Juros</td><td class="text-end text-danger fw-semibold">+ R$ {{ number_format($invoice->late_fee, 2, ',', '.') }}</td></tr>
                            @endif
                            <tr class="border-top">
                                <td colspan="3" class="text-end fw-bold fs-6">Total</td>
                                <td class="text-end fw-black fs-5">R$ {{ number_format($invoice->total, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Transações --}}
                @if($invoice->transactions && $invoice->transactions->isNotEmpty())
                <div class="alert alert-success py-3 mb-0">
                    <div class="fw-semibold small text-uppercase mb-2">Pagamentos Registrados</div>
                    @foreach($invoice->transactions as $tx)
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span>
                            <i class="bi bi-check-circle-fill me-1"></i>
                            <strong>{{ $tx->gateway }}</strong> — {{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}
                            <span class="badge bg-{{ $tx->status === 'completed' ? 'success' : 'secondary' }} ms-1">{{ $tx->status_label }}</span>
                            @if($tx->gateway_transaction_id) <code class="small d-block text-muted">{{ $tx->gateway_transaction_id }}</code> @endif
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold">R$ {{ number_format($tx->amount, 2, ',', '.') }}</span>
                            @if($tx->canRefund())
                            <button class="btn btn-outline-danger btn-xs py-0 px-2" style="font-size:.7rem"
                                    onclick="openRefund({{ $tx->id }}, {{ $tx->amount }}, '{{ $tx->gateway }}')"
                                    title="Reembolsar">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar: Ações --}}
    <div class="col-lg-4">
        {{-- Ações --}}
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Ações</div>
            <div class="card-body d-grid gap-2">
                @if(in_array($invoice->status, ['pending','overdue']))
                <button class="btn btn-success" @click="markPaid()"><i class="bi bi-check-circle me-2"></i>Marcar como Pago</button>
                <button class="btn btn-outline-danger" @click="cancelInvoice()"><i class="bi bi-x-circle me-2"></i>Cancelar Fatura</button>
                @endif
                <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-file-pdf me-2"></i>Baixar PDF</a>
                <a href="{{ route('admin.invoices.send', $invoice) }}" class="btn btn-outline-primary"><i class="bi bi-envelope me-2"></i>Reenviar por E-mail</a>
            </div>
        </div>

        {{-- Info Cliente --}}
        <div class="card">
            <div class="card-header bg-white fw-semibold">Cliente</div>
            <div class="card-body">
                <h6 class="fw-bold">{{ $invoice->client?->name }}</h6>
                <p class="small text-muted mb-1"><i class="bi bi-envelope me-1"></i>{{ $invoice->client?->email }}</p>
                @if($invoice->client?->phone) <p class="small text-muted mb-2"><i class="bi bi-telephone me-1"></i>{{ $invoice->client?->phone }}</p> @endif
                <a href="{{ route('admin.clients.show', $invoice->client) }}" class="btn btn-sm btn-outline-primary w-100">Ver Perfil do Cliente</a>
            </div>
        </div>

        {{-- Modal Reembolso --}}
        <div x-show="showRefundModal" x-cloak class="position-fixed d-flex align-items-center justify-content-center" style="background:rgba(0,0,0,.5);top:0;left:0;right:0;bottom:0;z-index:9999">
            <div class="card shadow-lg" style="max-width:420px;width:90%">
                <div class="card-header fw-semibold text-danger"><i class="bi bi-arrow-counterclockwise me-2"></i>Solicitar Reembolso</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Reembolso</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" x-model="refundForm.type" value="full" id="rfFull">
                                <label class="form-check-label" for="rfFull">Total</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" x-model="refundForm.type" value="partial" id="rfPartial">
                                <label class="form-check-label" for="rfPartial">Parcial</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" x-show="refundForm.type === 'partial'">
                        <label class="form-label fw-semibold">Valor do Reembolso</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" x-model="refundForm.amount"
                                   :max="refundForm.maxAmount" placeholder="0.00">
                        </div>
                        <small class="text-muted">Máximo: R$ <span x-text="parseFloat(refundForm.maxAmount).toFixed(2)"></span></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo (opcional)</label>
                        <textarea class="form-control" rows="2" x-model="refundForm.reason" placeholder="Ex: Cliente solicitou cancelamento"></textarea>
                    </div>
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        O reembolso será processado via <strong x-text="refundForm.gateway"></strong>. Esta ação não pode ser desfeita.
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-secondary flex-grow-1" @click="showRefundModal=false">Cancelar</button>
                    <button class="btn btn-danger flex-grow-1" @click="confirmRefund()" :disabled="refunding">
                        <span x-show="refunding" class="spinner-border spinner-border-sm me-1"></span>
                        Confirmar Reembolso
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Pagamento --}}
        <div x-show="showPaidModal" x-cloak class="position-fixed inset-0 d-flex align-items-center justify-content-center" style="background:rgba(0,0,0,.5);top:0;left:0;right:0;bottom:0;z-index:9999">
            <div class="card shadow-lg" style="max-width:400px;width:90%">
                <div class="card-header fw-semibold">Registrar Pagamento</div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label fw-semibold">Gateway</label>
                        <select class="form-select" x-model="payForm.gateway">
                            @foreach($gateways as $gw)
                                <option value="{{ $gw->slug }}">{{ $gw->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Valor Pago</label>
                        <div class="input-group"><span class="input-group-text">R$</span><input type="number" step="0.01" class="form-control" x-model="payForm.amount"></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">ID da Transação</label>
                        <input type="text" class="form-control" x-model="payForm.transaction_id" placeholder="Opcional">
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-secondary flex-grow-1" @click="showPaidModal=false">Cancelar</button>
                    <button class="btn btn-success flex-grow-1" @click="confirmPaid()" :disabled="paying">
                        <span x-show="paying" class="spinner-border spinner-border-sm me-1"></span>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminInvoiceShow() {
    return {
        showPaidModal: false, paying: false,
        showRefundModal: false, refunding: false,
        payForm: { gateway: '{{ $gateways->first()?->slug ?? "manual" }}', amount: {{ $invoice->total }}, transaction_id: '' },
        refundForm: { transactionId: null, type: 'full', amount: 0, maxAmount: 0, gateway: '', reason: '' },

        markPaid() { this.showPaidModal = true; },

        async confirmPaid() {
            this.paying = true;
            const d = await HostPanel.fetch('{{ route("admin.invoices.pay", $invoice) }}', { method: 'POST', body: JSON.stringify(this.payForm) });
            this.paying = false;
            this.showPaidModal = false;
            HostPanel.toast(d.message);
            if (d.invoice) setTimeout(() => window.location.reload(), 1200);
        },

        async cancelInvoice() {
            if (!(await HostPanel.confirm({ text: 'Cancelar esta fatura? Esta acao nao pode ser desfeita.', confirmButtonText: 'Sim, cancelar' }))) return;
            const d = await HostPanel.fetch('{{ route("admin.invoices.cancel", $invoice) }}', { method: 'POST' });
            HostPanel.toast(d.message);
            if (d.invoice) setTimeout(() => window.location.reload(), 1200);
        },

        async confirmRefund() {
            this.refunding = true;
            const payload = {
                type:   this.refundForm.type,
                amount: this.refundForm.type === 'partial' ? this.refundForm.amount : this.refundForm.maxAmount,
                reason: this.refundForm.reason,
            };
            const d = await HostPanel.fetch(
                `{{ url('admin/gateways/reembolso') }}/${this.refundForm.transactionId}`,
                { method: 'POST', body: JSON.stringify(payload) }
            );
            this.refunding = false;
            this.showRefundModal = false;
            HostPanel.toast(d.message, d.success ? 'success' : 'danger');
            if (d.success) setTimeout(() => window.location.reload(), 1500);
        }
    }
}

function openRefund(txId, amount, gateway) {
    const comp = Alpine.$data(document.querySelector('[x-data]'));
    if (!comp) return;
    comp.refundForm = { transactionId: txId, type: 'full', amount: 0, maxAmount: amount, gateway, reason: '' };
    comp.showRefundModal = true;
}
</script>
@endpush
