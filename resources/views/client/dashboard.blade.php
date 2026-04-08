@extends('client.layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Bem-vindo, ' . auth('client')->user()->name . '!')

@section('content')

{{-- Alerta de faturas em atraso --}}
@if($overdueInvoices->isNotEmpty())
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-start gap-3">
    <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg mt-0.5"></i>
    <div>
        <div class="font-semibold text-red-800">{{ $overdueInvoices->count() }} fatura(s) em atraso</div>
        <div class="text-red-700 text-sm mt-1">Regularize para evitar a suspensão dos seus serviços.</div>
        <div class="mt-2 flex gap-2 flex-wrap">
            @foreach($overdueInvoices as $inv)
                <a href="{{ route('client.invoices.show', $inv) }}"
                   class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                    <i class="bi bi-receipt"></i> Fatura #{{ $inv->number }} — R$ {{ number_format($inv->total, 2, ',', '.') }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- KPI Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <a href="{{ route('client.services.index') }}" class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Serviços Ativos</span>
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition">
                <i class="bi bi-hdd-stack text-blue-600"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ $activeServices }}</div>
    </a>
    <a href="{{ route('client.invoices.index', ['status'=>'pending']) }}" class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fat. Pendentes</span>
            <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition">
                <i class="bi bi-receipt text-amber-600"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ $pendingInvoices }}</div>
    </a>
    <a href="{{ route('client.tickets.index', ['status'=>'open']) }}" class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tickets Abertos</span>
            <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition">
                <i class="bi bi-headset text-purple-600"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ $openTickets }}</div>
    </a>
    @php $client = auth('client')->user(); @endphp
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo</span>
            <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                <i class="bi bi-wallet2 text-green-600"></i>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900">R$ {{ number_format($client->credit_balance, 2, ',', '.') }}</div>
    </div>
</div>

{{-- Serviços + Faturas --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Serviços Recentes --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Meus Serviços</h2>
            <a href="{{ route('client.services.index') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Ver todos →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentServices as $service)
            <a href="{{ route('client.services.show', $service) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-hdd text-blue-600"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-gray-900 truncate">{{ $service->domain ?? $service->product?->name ?? "Serviço #{$service->id}" }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $service->product?->name }}</div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $service->status === 'active' ? 'bg-green-100 text-green-700' : ($service->status === 'suspended' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                    {{ ['active'=>'Ativo','suspended'=>'Suspenso','pending'=>'Pendente','terminated'=>'Encerrado'][$service->status] ?? $service->status }}
                </span>
            </a>
            @empty
            <div class="px-5 py-8 text-center">
                <i class="bi bi-hdd-stack text-4xl text-gray-200 block mb-2"></i>
                <p class="text-gray-500 text-sm">Você não possui serviços ativos.</p>
                <a href="{{ route('client.orders.catalog') }}" class="mt-3 inline-flex items-center gap-1.5 bg-primary-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-primary-700">
                    <i class="bi bi-plus-lg"></i> Contratar Agora
                </a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Faturas Recentes --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Faturas Recentes</h2>
            <a href="{{ route('client.invoices.index') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Ver todas →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentInvoices as $invoice)
            <a href="{{ route('client.invoices.show', $invoice) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition">
                <div class="w-10 h-10 rounded-lg {{ $invoice->status === 'paid' ? 'bg-green-50' : ($invoice->status === 'overdue' ? 'bg-red-50' : 'bg-amber-50') }} flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-receipt {{ $invoice->status === 'paid' ? 'text-green-600' : ($invoice->status === 'overdue' ? 'text-red-600' : 'text-amber-600') }}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-gray-900">#{{ $invoice->number }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Vence em {{ \Carbon\Carbon::parse($invoice->date_due)->format('d/m/Y') }}</div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="font-semibold text-gray-900">R$ {{ number_format($invoice->total, 2, ',', '.') }}</div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ ['paid'=>'bg-green-100 text-green-700','overdue'=>'bg-red-100 text-red-700','pending'=>'bg-amber-100 text-amber-700','cancelled'=>'bg-gray-100 text-gray-600'][$invoice->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ['paid'=>'Pago','overdue'=>'Atrasado','pending'=>'Pendente','cancelled'=>'Cancelado'][$invoice->status] ?? $invoice->status }}
                    </span>
                </div>
            </a>
            @empty
            <div class="px-5 py-8 text-center text-gray-500 text-sm">Nenhuma fatura encontrada.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Tickets Recentes + Anúncios --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">Tickets de Suporte</h2>
        <a href="{{ route('client.tickets.create') }}" class="inline-flex items-center gap-1.5 bg-primary-600 text-white text-sm font-semibold px-3 py-1.5 rounded-lg hover:bg-primary-700">
            <i class="bi bi-plus-lg"></i> Novo Ticket
        </a>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($recentTickets as $ticket)
        <a href="{{ route('client.tickets.show', $ticket) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition">
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-chat-dots text-purple-600"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="font-medium text-gray-900 truncate">#{{ $ticket->number }} — {{ $ticket->subject }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $ticket->department?->name }}</div>
            </div>
            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ ['open'=>'bg-red-100 text-red-700','answered'=>'bg-green-100 text-green-700','closed'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','customer_reply'=>'bg-amber-100 text-amber-700'][$ticket->status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ ['open'=>'Aberto','answered'=>'Respondido','closed'=>'Fechado','in_progress'=>'Em Andamento','customer_reply'=>'Aguardando'][$ticket->status] ?? $ticket->status }}
            </span>
        </a>
        @empty
        <div class="px-5 py-8 text-center text-gray-500 text-sm">Nenhum ticket aberto.</div>
        @endforelse
    </div>
</div>
@endsection
