@extends('client.layouts.app')
@section('title', 'Programa de Afiliados')

@section('content')
<div x-data="affiliatePanel()" x-init="load()">

    {{-- Not enrolled --}}
    <template x-if="!enrolled && !loading">
        <div class="max-w-2xl mx-auto text-center py-16">
            <div class="text-6xl mb-4">🤝</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-3">Programa de Afiliados</h2>
            <p class="text-gray-500 mb-6">Indique amigos e ganhe comissões em cada compra realizada por eles. Compartilhe seu link exclusivo e acompanhe seus ganhos em tempo real.</p>
            <div class="bg-blue-50 rounded-xl p-4 mb-6 text-sm text-blue-700">
                <i class="bi bi-info-circle me-1"></i> Você recebe comissão sobre cada fatura paga pelos clientes que se cadastrarem usando seu link de indicação.
            </div>
            <button @click="enroll()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition">
                <i class="bi bi-rocket-takeoff me-2"></i>Quero ser Afiliado
            </button>
        </div>
    </template>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="text-center py-16 text-gray-400"><div class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full mb-3"></div><p>Carregando...</p></div>
    </template>

    {{-- Enrolled Dashboard --}}
    <template x-if="enrolled && !loading">
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="bi bi-people me-2"></i>Painel de Afiliado</h2>

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600" x-text="stats.total_referrals">0</div>
                    <div class="text-xs text-gray-500">Indicados</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
                    <div class="text-2xl font-bold text-green-600" x-text="stats.total_conversions">0</div>
                    <div class="text-xs text-gray-500">Conversões</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600" x-text="'R$ '+(parseFloat(stats.pending)||0).toFixed(2)">R$ 0.00</div>
                    <div class="text-xs text-gray-500">Pendente</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
                    <div class="text-2xl font-bold text-green-600" x-text="'R$ '+(parseFloat(stats.balance)||0).toFixed(2)">R$ 0.00</div>
                    <div class="text-xs text-gray-500">Saldo Disponível</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600" x-text="'R$ '+(parseFloat(stats.total_earned)||0).toFixed(2)">R$ 0.00</div>
                    <div class="text-xs text-gray-500">Total Ganho</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
                    <div class="text-2xl font-bold text-gray-600" x-text="'R$ '+(parseFloat(stats.total_withdrawn)||0).toFixed(2)">R$ 0.00</div>
                    <div class="text-xs text-gray-500">Total Sacado</div>
                </div>
            </div>

            {{-- Referral Link --}}
            <div class="bg-white rounded-xl shadow-sm border p-4 mb-6">
                <h3 class="font-semibold text-gray-700 mb-2"><i class="bi bi-link-45deg me-1"></i>Seu Link de Indicação</h3>
                <div class="flex gap-2">
                    <input type="text" class="flex-1 bg-gray-50 border rounded-lg px-3 py-2 text-sm font-mono" :value="stats.referral_url" readonly id="refLink">
                    <button @click="copyLink()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                        <i class="bi bi-clipboard me-1"></i>Copiar
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex gap-2 mb-4 border-b">
                <button class="px-4 py-2 text-sm font-semibold border-b-2 transition" :class="tab==='referrals'?'border-blue-600 text-blue-600':'border-transparent text-gray-500'" @click="tab='referrals'">Indicados</button>
                <button class="px-4 py-2 text-sm font-semibold border-b-2 transition" :class="tab==='commissions'?'border-blue-600 text-blue-600':'border-transparent text-gray-500'" @click="tab='commissions'">Comissões</button>
                <button class="px-4 py-2 text-sm font-semibold border-b-2 transition" :class="tab==='payouts'?'border-blue-600 text-blue-600':'border-transparent text-gray-500'" @click="tab='payouts'">Saques</button>
                <button class="px-4 py-2 text-sm font-semibold border-b-2 transition" :class="tab==='withdraw'?'border-blue-600 text-blue-600':'border-transparent text-gray-500'" @click="tab='withdraw'">Solicitar Saque</button>
            </div>

            {{-- Referrals --}}
            <div x-show="tab==='referrals'" class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500 font-medium">Cliente</th><th class="px-4 py-3 text-left text-gray-500 font-medium">Data</th><th class="px-4 py-3 text-left text-gray-500 font-medium">Converteu</th></tr></thead>
                    <tbody>
                        <template x-for="r in affiliate.referrals||[]" :key="r.id">
                            <tr class="border-t">
                                <td class="px-4 py-3"><span class="font-semibold" x-text="r.referred_client?.name"></span><br><small class="text-gray-400" x-text="r.referred_client?.email"></small></td>
                                <td class="px-4 py-3 text-gray-500" x-text="new Date(r.created_at).toLocaleDateString('pt-BR')"></td>
                                <td class="px-4 py-3"><span class="inline-block px-2 py-1 rounded text-xs font-semibold" :class="r.converted?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500'" x-text="r.converted?'Sim':'Não'"></span></td>
                            </tr>
                        </template>
                        <template x-if="!affiliate.referrals?.length">
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Nenhum indicado ainda. Compartilhe seu link!</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Commissions --}}
            <div x-show="tab==='commissions'" class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Descrição</th><th class="px-4 py-3 text-left">Valor</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Data</th></tr></thead>
                    <tbody>
                        <template x-for="c in affiliate.commissions||[]" :key="c.id">
                            <tr class="border-t">
                                <td class="px-4 py-3" x-text="c.description"></td>
                                <td class="px-4 py-3 font-bold text-green-600" x-text="'R$ '+parseFloat(c.commission_amount).toFixed(2)"></td>
                                <td class="px-4 py-3"><span class="inline-block px-2 py-1 rounded text-xs font-semibold" :class="{'bg-yellow-100 text-yellow-700':c.status==='pending','bg-green-100 text-green-700':c.status==='approved','bg-red-100 text-red-700':c.status==='rejected','bg-blue-100 text-blue-700':c.status==='paid'}" x-text="c.status"></span></td>
                                <td class="px-4 py-3 text-gray-500" x-text="new Date(c.created_at).toLocaleDateString('pt-BR')"></td>
                            </tr>
                        </template>
                        <template x-if="!affiliate.commissions?.length">
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Nenhuma comissão ainda.</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Payouts --}}
            <div x-show="tab==='payouts'" class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Valor</th><th class="px-4 py-3 text-left">Método</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Data</th></tr></thead>
                    <tbody>
                        <template x-for="p in affiliate.payouts||[]" :key="p.id">
                            <tr class="border-t">
                                <td class="px-4 py-3 font-bold" x-text="'R$ '+parseFloat(p.amount).toFixed(2)"></td>
                                <td class="px-4 py-3" x-text="p.method.toUpperCase()"></td>
                                <td class="px-4 py-3"><span class="inline-block px-2 py-1 rounded text-xs font-semibold" :class="{'bg-yellow-100 text-yellow-700':p.status==='pending','bg-blue-100 text-blue-700':p.status==='processing','bg-green-100 text-green-700':p.status==='completed','bg-red-100 text-red-700':p.status==='rejected'}" x-text="p.status"></span></td>
                                <td class="px-4 py-3 text-gray-500" x-text="new Date(p.created_at).toLocaleDateString('pt-BR')"></td>
                            </tr>
                        </template>
                        <template x-if="!affiliate.payouts?.length">
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Nenhum saque solicitado.</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Withdraw Form --}}
            <div x-show="tab==='withdraw'" class="bg-white rounded-xl shadow-sm border p-6 max-w-lg">
                <h3 class="font-semibold text-gray-700 mb-4"><i class="bi bi-cash-stack me-1"></i>Solicitar Saque</h3>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Valor (R$)</label>
                    <input type="number" step="0.01" min="1" class="w-full border rounded-lg px-3 py-2" x-model="payout.amount" :max="stats.balance">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Método</label>
                    <select class="w-full border rounded-lg px-3 py-2" x-model="payout.method">
                        <option value="pix">PIX</option>
                        <option value="bank_transfer">Transferência Bancária</option>
                        <option value="credit">Crédito na Conta</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Dados para Pagamento</label>
                    <textarea class="w-full border rounded-lg px-3 py-2" rows="3" placeholder="Chave PIX, dados bancários, etc." x-model="payout.payment_details"></textarea>
                </div>
                <button @click="requestPayout()" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg transition" :disabled="!payout.amount||!payout.payment_details">
                    <i class="bi bi-send me-1"></i>Solicitar Saque
                </button>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function affiliatePanel(){
    return {
        loading:true, enrolled:false, affiliate:{}, stats:{}, tab:'referrals',
        payout:{amount:'',method:'pix',payment_details:''},
        async load(){
            const r = await HostPanel.fetch('{{ route("client.affiliates.index") }}');
            this.enrolled = r.enrolled;
            this.affiliate = r.affiliate||{};
            this.stats = r.stats||{};
            this.loading = false;
        },
        async enroll(){
            const r = await HostPanel.fetch('{{ route("client.affiliates.enroll") }}',{method:'POST'});
            HostPanel.toast(r.message);
            this.load();
        },
        copyLink(){
            navigator.clipboard.writeText(document.getElementById('refLink').value);
            HostPanel.toast('Link copiado!','info');
        },
        async requestPayout(){
            try {
                const r = await HostPanel.fetch('{{ route("client.affiliates.payout") }}',{method:'POST',body:JSON.stringify(this.payout)});
                if(r.message) HostPanel.toast(r.message);
                this.payout={amount:'',method:'pix',payment_details:''};
                this.load();
            } catch(e){ HostPanel.toast('Erro ao solicitar saque.','danger'); }
        }
    }
}
</script>
@endpush
