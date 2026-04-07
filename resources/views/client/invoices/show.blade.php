@extends('client.layouts.app')
@section('title', "Fatura #{$invoice->number}")
@section('page-title', "Fatura #{$invoice->number}")

@section('content')
<div class="max-w-3xl mx-auto" x-data="invoiceShow()">

    {{-- Status Bar --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold
                {{ ['paid'=>'bg-green-100 text-green-700','overdue'=>'bg-red-100 text-red-700','pending'=>'bg-amber-100 text-amber-700','cancelled'=>'bg-gray-100 text-gray-600'][$invoice->status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ ['paid'=>'✅ Pago','overdue'=>'❌ Em Atraso','pending'=>'⏳ Pendente','cancelled'=>'🚫 Cancelado'][$invoice->status] ?? $invoice->status }}
            </span>
            <span class="text-sm text-gray-500">Vence em {{ \Carbon\Carbon::parse($invoice->date_due)->format('d/m/Y') }}</span>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('client.invoices.pdf', $invoice) }}" class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:bg-gray-50 font-semibold text-sm px-3 py-2 rounded-lg" target="_blank">
                <i class="bi bi-file-pdf"></i> PDF
            </a>
            @if(in_array($invoice->status, ['pending','overdue']))
            <button @click="openPayment()" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2 rounded-lg">
                <i class="bi bi-credit-card"></i> Pagar Agora
            </button>
            @endif
        </div>
    </div>

    {{-- Fatura --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-6">
        {{-- Header da Fatura --}}
        <div class="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white">
            <div class="flex justify-between items-start flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center"><i class="bi bi-server"></i></div>
                        <span class="font-bold text-lg">{{ config('app.name') }}</span>
                    </div>
                    <div class="text-slate-400 text-xs">{{ $companySettings['address'] ?? '' }}</div>
                    <div class="text-slate-400 text-xs">{{ $companySettings['cnpj'] ?? '' }}</div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black">FATURA</div>
                    <div class="text-slate-300 font-mono">#{{ $invoice->number }}</div>
                </div>
            </div>
        </div>

        {{-- Info Cliente --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 px-6 py-5 border-b border-gray-100">
            @php $client = auth('client')->user(); @endphp
            <div>
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Faturado Para</div>
                <div class="font-semibold text-gray-900">{{ $client->company_name ?? $client->name }}</div>
                <div class="text-sm text-gray-500">{{ $client->name }}</div>
                <div class="text-sm text-gray-500">{{ $client->email }}</div>
                @if($client->document_number) <div class="text-sm text-gray-500">{{ strtoupper($client->document_type ?? 'CPF') }}: {{ $client->document_number }}</div> @endif
            </div>
            <div class="sm:text-right">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Informações</div>
                <div class="text-sm text-gray-600">Emissão: {{ \Carbon\Carbon::parse($invoice->date_issued)->format('d/m/Y') }}</div>
                <div class="text-sm text-gray-600">Vencimento: {{ \Carbon\Carbon::parse($invoice->date_due)->format('d/m/Y') }}</div>
                @if($invoice->date_paid) <div class="text-sm text-green-600 font-medium">Pago em: {{ \Carbon\Carbon::parse($invoice->date_paid)->format('d/m/Y') }}</div> @endif
            </div>
        </div>

        {{-- Itens --}}
        <div class="px-6 py-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Descrição</th>
                        <th class="text-center pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Qtd.</th>
                        <th class="text-right pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Valor</th>
                        <th class="text-right pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="py-3 pr-4">
                            <div class="font-medium text-gray-900">{{ $item->description }}</div>
                            @if($item->period_start && $item->period_end)
                                <div class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($item->period_start)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($item->period_end)->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="py-3 text-center text-gray-600">{{ $item->quantity ?? 1 }}</td>
                        <td class="py-3 text-right text-gray-600">R$ {{ number_format($item->unit_price ?? $item->amount, 2, ',', '.') }}</td>
                        <td class="py-3 text-right font-semibold text-gray-900">R$ {{ number_format($item->amount, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @if($invoice->discount > 0)
                    <tr>
                        <td colspan="3" class="pt-3 text-right text-sm text-gray-500">Desconto</td>
                        <td class="pt-3 text-right text-sm text-green-600 font-semibold">- R$ {{ number_format($invoice->discount, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($invoice->late_fee > 0)
                    <tr>
                        <td colspan="3" class="pt-2 text-right text-sm text-gray-500">Multa/Juros</td>
                        <td class="pt-2 text-right text-sm text-red-600 font-semibold">+ R$ {{ number_format($invoice->late_fee, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="border-t border-gray-200">
                        <td colspan="3" class="pt-4 text-right font-bold text-gray-900">Total</td>
                        <td class="pt-4 text-right text-xl font-extrabold text-gray-900">R$ {{ number_format($invoice->total, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Transações --}}
        @if($invoice->transactions && $invoice->transactions->isNotEmpty())
        <div class="px-6 py-4 border-t border-gray-100 bg-green-50">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Pagamentos Registrados</div>
            @foreach($invoice->transactions as $tx)
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <i class="bi bi-check-circle-fill text-green-600"></i>
                    <span class="text-gray-700">{{ $tx->gateway_name }}</span>
                    <span class="text-gray-400 text-xs">{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}</span>
                    @if($tx->transaction_id) <span class="text-gray-400 text-xs font-mono">{{ $tx->transaction_id }}</span> @endif
                </div>
                <span class="font-semibold text-green-700">R$ {{ number_format($tx->amount, 2, ',', '.') }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Modal Pagamento --}}
    <div x-show="paymentOpen" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="!result && (paymentOpen=false)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

            {{-- Step 1: Escolher gateway --}}
            <div x-show="!result" class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-bold text-gray-900 text-lg">Pagar Fatura #{{ $invoice->number }}</h3>
                    <button @click="paymentOpen=false" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button>
                </div>

                <p class="text-sm text-gray-500 mb-4">Valor: <strong class="text-gray-900 text-base">R$ {{ number_format($invoice->amount_due, 2, ',', '.') }}</strong></p>

                {{-- Gateways --}}
                <div class="space-y-2 mb-5">
                    @foreach($gateways as $gw)
                    @php
                    $methods = [];
                    if(in_array($gw->driver, ['paghiper','efirpro','bancointer','bancobrasil'])) $methods[] = ['pix','⚡ PIX'];
                    if(in_array($gw->driver, ['paghiper','pagbank'])) $methods[] = ['billet','📄 Boleto'];
                    if(in_array($gw->driver, ['mercadopago','pagbank'])) $methods[] = ['pix','⚡ PIX'];
                    if(in_array($gw->driver, ['mercadopago','pagbank'])) $methods[] = ['credit_card','💳 Cartão'];
                    $methods = array_unique($methods, SORT_REGULAR);
                    @endphp
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button @click="toggleGateway('{{ $gw->driver }}')"
                                class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-800 text-sm">{{ $gw->name }}</span>
                            <i class="bi" :class="selectedGateway==='{{ $gw->driver }}' ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        </button>
                        <div x-show="selectedGateway==='{{ $gw->driver }}'" class="px-4 pb-4 pt-1 grid grid-cols-2 gap-2">
                            @foreach($methods as [$mVal, $mLabel])
                            <button @click="selectedMethod='{{ $mVal }}'"
                                    :class="selectedMethod==='{{ $mVal }}' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600'"
                                    class="border-2 rounded-xl py-3 text-sm font-semibold transition">
                                {{ $mLabel }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="flex gap-3">
                    <button @click="paymentOpen=false" class="flex-1 border border-gray-200 text-gray-600 font-semibold py-3 rounded-xl text-sm hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button @click="pay()" :disabled="!selectedGateway || !selectedMethod || paying"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-3 rounded-xl text-sm transition">
                        <span x-show="paying" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                        <span x-text="paying ? 'Gerando cobrança...' : 'Gerar Cobrança'"></span>
                    </button>
                </div>
            </div>

            {{-- Step 2: Resultado (PIX / Boleto / Link) --}}
            <div x-show="result" class="p-6">
                <div class="text-center mb-5">
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="bi bi-check-lg text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">Cobrança Gerada!</h3>
                    <p class="text-sm text-gray-500 mt-1">Realize o pagamento abaixo. A confirmação é automática.</p>
                </div>

                {{-- PIX --}}
                <div x-show="result?.pix_qrcode || result?.pix_emv" class="mb-5 text-center">
                    <div x-show="result?.pix_qrcode" class="flex justify-center mb-3">
                        <img :src="'data:image/png;base64,' + result?.pix_qrcode" alt="QR Code PIX"
                             class="w-48 h-48 border border-gray-200 rounded-xl p-2">
                    </div>
                    <p class="text-xs text-gray-500 mb-2 font-semibold">PIX Copia e Cola:</p>
                    <div class="relative">
                        <textarea readonly :value="result?.pix_emv"
                                  class="w-full border border-gray-200 rounded-xl px-4 py-3 text-xs font-mono bg-gray-50 resize-none h-20"></textarea>
                        <button @click="copyPix()"
                                class="mt-2 w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl text-sm transition">
                            <i class="bi bi-clipboard-check me-1"></i>
                            <span x-text="copied ? 'Copiado!' : 'Copiar Código PIX'"></span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="bi bi-clock me-1"></i>
                        Expira em: <span x-text="result?.expires_at ?? '—'"></span>
                    </p>
                </div>

                {{-- Boleto --}}
                <div x-show="result?.barcode_formatted || result?.barcode" class="mb-5">
                    <p class="text-xs text-gray-500 mb-2 font-semibold">Linha Digitável:</p>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-mono text-xs break-all mb-2"
                         x-text="result?.barcode_formatted || result?.barcode"></div>
                    <div class="flex gap-2">
                        <button @click="copyBarcode()"
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">
                            <i class="bi bi-clipboard me-1"></i> Copiar
                        </button>
                        <a :href="result?.payment_url" target="_blank" x-show="result?.payment_url"
                           class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm text-center">
                            <i class="bi bi-file-text me-1"></i> Ver Boleto
                        </a>
                    </div>
                </div>

                {{-- Link de pagamento --}}
                <div x-show="result?.payment_url && !result?.pix_emv && !result?.barcode" class="mb-5">
                    <a :href="result?.payment_url" target="_blank"
                       class="w-full block text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Ir para o pagamento
                    </a>
                </div>

                <button @click="closeResult()"
                        class="w-full border border-gray-200 text-gray-600 font-semibold py-3 rounded-xl text-sm hover:bg-gray-50">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function invoiceShow() {
    return {
        paymentOpen: false,
        selectedGateway: null,
        selectedMethod: null,
        paying: false,
        result: null,
        copied: false,

        openPayment() { this.paymentOpen = true; this.result = null; },

        toggleGateway(driver) {
            this.selectedGateway = this.selectedGateway === driver ? null : driver;
            this.selectedMethod  = null;
        },

        async pay() {
            if (!this.selectedGateway || !this.selectedMethod) return;
            this.paying = true;
            try {
                const d = await HostPanel.fetch('{{ route("client.invoices.pay", $invoice) }}', {
                    method: 'POST',
                    body: JSON.stringify({ gateway: this.selectedGateway, method: this.selectedMethod })
                });

                if (d.redirect) { window.location.href = d.redirect; return; }

                if (d.pix_emv || d.barcode || d.payment_url) {
                    this.result = d;
                } else if (d.message) {
                    HostPanel.toast(d.message);
                    this.paymentOpen = false;
                    setTimeout(() => window.location.reload(), 1500);
                }
            } catch (e) {
                HostPanel.toast('Erro ao gerar cobrança.', 'danger');
            }
            this.paying = false;
        },

        copyPix() {
            navigator.clipboard.writeText(this.result?.pix_emv ?? '');
            this.copied = true;
            setTimeout(() => this.copied = false, 3000);
        },

        copyBarcode() {
            navigator.clipboard.writeText(this.result?.barcode_formatted || this.result?.barcode || '');
        },

        closeResult() {
            this.paymentOpen = false;
            this.result = null;
            setTimeout(() => window.location.reload(), 300);
        }
    }
}
</script>
@endpush
