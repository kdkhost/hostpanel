@extends('client.layouts.app')
@section('title', 'Meus Serviços')
@section('page-title', 'Meus Serviços')

@section('content')
<div x-data="servicesList()">
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
        <div class="flex gap-3 flex-wrap">
            <input type="text" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Buscar domínio ou produto..." x-model.debounce.400="search" @input="page=1;load()">
            <select class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" x-model="status" @change="page=1;load()">
                <option value="">Todos os status</option>
                <option value="active">Ativo</option>
                <option value="suspended">Suspenso</option>
                <option value="pending">Pendente</option>
                <option value="terminated">Encerrado</option>
            </select>
        </div>
        <a href="{{ route('client.orders.catalog') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            <i class="bi bi-plus-lg"></i> Contratar Novo
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" x-show="!loading">
        <template x-for="s in services" :key="s.id">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div class="px-5 py-4 border-b border-gray-50 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900 truncate" x-text="s.domain || s.product?.name || `Serviço #${s.id}`"></div>
                        <div class="text-sm text-gray-500 mt-0.5" x-text="s.product?.name"></div>
                    </div>
                    <span :class="`px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0 ${statusClass(s.status)}`" x-text="statusLabel(s.status)"></span>
                </div>
                <div class="px-5 py-4 space-y-2.5">
                    <div class="flex justify-between text-sm" x-show="s.username">
                        <span class="text-gray-500">Usuário cPanel</span>
                        <span class="font-medium font-mono text-gray-800" x-text="s.username"></span>
                    </div>
                    <div class="flex justify-between text-sm" x-show="s.server">
                        <span class="text-gray-500">Servidor</span>
                        <span class="font-medium text-gray-800" x-text="s.server?.hostname"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Ciclo</span>
                        <span class="font-medium text-gray-800" x-text="billingLabel(s.billing_cycle)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Próx. Vencimento</span>
                        <span :class="`font-medium ${isExpiring(s.next_due_date) ? 'text-red-600' : 'text-gray-800'}`" x-text="formatDate(s.next_due_date)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Valor</span>
                        <span class="font-semibold text-gray-900" x-text="`R$ ${fmt(s.price)}`"></span>
                    </div>
                </div>
                <div class="px-5 pb-4 flex gap-2">
                    <a :href="`/cliente/servicos/${s.id}`" class="flex-1 text-center border border-gray-200 text-gray-700 font-semibold py-2 rounded-lg text-sm hover:bg-gray-50">Gerenciar</a>
                    <button x-show="s.status === 'active' && s.cpanel_url" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg text-sm" @click="cpanelLogin(s)">
                        <i class="bi bi-box-arrow-up-right me-1"></i>cPanel
                    </button>
                </div>
            </div>
        </template>
        <template x-if="!loading && services.length === 0">
            <div class="col-span-full py-16 text-center">
                <i class="bi bi-hdd-stack text-5xl text-gray-200 block mb-3"></i>
                <p class="text-gray-500 mb-4">Você não possui serviços ativos.</p>
                <a href="{{ route('client.orders.catalog') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white font-semibold px-5 py-2.5 rounded-lg hover:bg-blue-700">
                    <i class="bi bi-cart3"></i> Ver Planos
                </a>
            </div>
        </template>
    </div>

    <div x-show="loading" class="py-12 text-center">
        <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div class="flex justify-between items-center mt-6" x-show="meta && meta.last_page > 1">
        <span class="text-sm text-gray-500" x-text="`${meta?.from??0}–${meta?.to??0} de ${meta?.total??0}`"></span>
        <div class="flex gap-2">
            <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm disabled:opacity-40" :disabled="page===1" @click="page--;load()">← Anterior</button>
            <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm disabled:opacity-40" :disabled="page>=meta?.last_page" @click="page++;load()">Próxima →</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function servicesList() {
    return {
        services: [], meta: null, loading: false, search: '', status: '', page: 1,
        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, status: this.status, page: this.page });
            const d = await HostPanel.fetch(`/cliente/servicos?${p}`);
            this.services = d.data || [];
            this.meta = d.meta || d;
            this.loading = false;
        },
        async cpanelLogin(s) {
            const d = await HostPanel.fetch(`/cliente/servicos/${s.id}/cpanel-login`, { method: 'POST' });
            if (d.url) window.open(d.url, '_blank');
            else HostPanel.toast(d.message || 'Erro ao obter link do cPanel.', 'danger');
        },
        statusClass(s)  { return {active:'bg-green-100 text-green-700',suspended:'bg-amber-100 text-amber-700',pending:'bg-gray-100 text-gray-600',terminated:'bg-red-100 text-red-700'}[s]||'bg-gray-100 text-gray-600'; },
        statusLabel(s)  { return {active:'Ativo',suspended:'Suspenso',pending:'Pendente',terminated:'Encerrado',provisioning:'Provisionando',failed:'Falhou'}[s]||s; },
        billingLabel(b) { return {monthly:'Mensal',quarterly:'Trimestral',semiannually:'Semestral',annually:'Anual',biennially:'Bienal',triennially:'Trienal',free:'Grátis'}[b]||b||'—'; },
        formatDate(d)   { return d ? new Date(d+'T00:00:00').toLocaleDateString('pt-BR') : '—'; },
        isExpiring(d)   { return d && new Date(d+'T00:00:00') < new Date(Date.now() + 7*864e5); },
        fmt(v)          { return parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2}); },
        init()          { this.load(); }
    }
}
</script>
@endpush
