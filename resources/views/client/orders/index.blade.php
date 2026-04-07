@extends('client.layouts.app')
@section('title', 'Meus Pedidos')
@section('page-title', 'Meus Pedidos')

@section('content')
<div x-data="ordersIndex()">
    {{-- Cabeçalho --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex gap-2">
            <input type="text" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" style="width:220px"
                   placeholder="Buscar pedido..." x-model.debounce.400="search" @input="page=1;load()">
        </div>
        <a href="{{ route('client.orders.catalog') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg">
            <i class="bi bi-plus-lg"></i> Novo Pedido
        </a>
    </div>

    {{-- Tabela --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pedido</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Itens</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="loading">
                        <tr><td colspan="6" class="text-center py-10">
                            <div class="inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        </td></tr>
                    </template>
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <span class="font-mono font-semibold text-gray-900" x-text="'#' + order.number"></span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-gray-700" x-text="order.items_summary || `${order.items_count ?? 0} item(s)`"></div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-gray-900" x-text="'R$ ' + fmt(order.total)"></span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="{
                                        'bg-green-100 text-green-700': order.status === 'completed',
                                        'bg-amber-100 text-amber-700': order.status === 'pending',
                                        'bg-blue-100 text-blue-700':  order.status === 'processing',
                                        'bg-red-100 text-red-700':    order.status === 'cancelled',
                                        'bg-gray-100 text-gray-600':  !['completed','pending','processing','cancelled'].includes(order.status),
                                    }"
                                    x-text="{ completed:'Concluído', pending:'Pendente', processing:'Processando', cancelled:'Cancelado' }[order.status] || order.status">
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-500" x-text="fmtDate(order.created_at)"></td>
                            <td class="px-5 py-4 text-center">
                                <a :href="`/cliente/pedidos/${order.id}`"
                                   class="inline-flex items-center gap-1 border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium text-xs px-3 py-1.5 rounded-lg">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && orders.length === 0">
                        <tr><td colspan="6" class="text-center py-16">
                            <div class="text-gray-300 text-5xl mb-3">🛒</div>
                            <p class="text-gray-500 font-medium">Nenhum pedido encontrado.</p>
                            <a href="{{ route('client.orders.catalog') }}" class="inline-block mt-3 text-blue-600 font-semibold text-sm hover:text-blue-700">
                                Explorar planos →
                            </a>
                        </td></tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div class="px-5 py-3 border-t border-gray-100 flex justify-between items-center text-sm" x-show="meta && meta.total > 0">
            <span class="text-gray-500" x-text="`${meta?.from ?? 0}–${meta?.to ?? 0} de ${meta?.total ?? 0} pedidos`"></span>
            <div class="flex gap-1">
                <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 disabled:opacity-40" :disabled="page === 1" @click="page--;load()">←</button>
                <span class="px-3 py-1.5 bg-blue-600 text-white rounded-lg font-semibold" x-text="page"></span>
                <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 disabled:opacity-40" :disabled="page >= (meta?.last_page ?? 1)" @click="page++;load()">→</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ordersIndex() {
    return {
        orders: [], meta: null, loading: false, search: '', page: 1,

        async load() {
            this.loading = true;
            const p = new URLSearchParams({ search: this.search, page: this.page });
            const d  = await HostPanel.fetch(`/api/v1/orders?${p}`);
            this.orders = d.data || [];
            this.meta   = d.meta || null;
            this.loading = false;
        },

        fmt(v)  { return parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }); },
        fmtDate(d) { return d ? new Date(d).toLocaleDateString('pt-BR') : '—'; },
        init() { this.load(); }
    }
}
</script>
@endpush
