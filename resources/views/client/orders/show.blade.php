@extends('client.layouts.app')
@section('title', "Pedido #{$order->number}")
@section('page-title', "Pedido #{$order->number}")

@section('content')
@php
    $statusColors = ['completed'=>'green','pending'=>'amber','processing'=>'blue','cancelled'=>'red','fraud'=>'red'];
    $statusLabels = ['completed'=>'Concluído','pending'=>'Pendente','processing'=>'Processando','cancelled'=>'Cancelado','fraud'=>'Fraude'];
    $color  = $statusColors[$order->status] ?? 'gray';
    $label  = $statusLabels[$order->status] ?? ucfirst($order->status);
@endphp

<div class="max-w-3xl mx-auto space-y-5">
    {{-- Cabeçalho --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <a href="{{ route('client.orders.index') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">← Meus Pedidos</a>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-{{ $color }}-100 text-{{ $color }}-700">
            {{ $label }}
        </span>
    </div>

    {{-- Resumo --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex justify-between items-start flex-wrap gap-4">
                <div>
                    <h2 class="font-bold text-gray-900 text-xl">Pedido #{{ $order->number }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Realizado em {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y \à\s H:i') }}</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-extrabold text-gray-900">R$ {{ number_format($order->total, 2, ',', '.') }}</div>
                    @if($order->discount > 0)
                        <div class="text-xs text-green-600 font-medium">Desconto aplicado: R$ {{ number_format($order->discount, 2, ',', '.') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Itens do Pedido --}}
        <div class="divide-y divide-gray-50">
            @foreach($order->items as $item)
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-xl">📦</div>
                    <div>
                        <div class="font-semibold text-gray-900">{{ $item->product?->name ?? $item->description }}</div>
                        <div class="text-xs text-gray-500">
                            Ciclo: {{ ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','free'=>'Grátis'][$item->billing_cycle ?? ''] ?? ($item->billing_cycle ?? '—') }}
                            @if($item->domain) &bull; {{ $item->domain }} @endif
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-gray-900">R$ {{ number_format($item->amount, 2, ',', '.') }}</div>
                    @if($item->service_id)
                        <a href="{{ route('client.services.show', $item->service_id) }}" class="text-xs text-blue-600 hover:text-blue-700">Ver serviço →</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Fatura Gerada --}}
    @if($order->invoice)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                    <i class="bi bi-receipt text-green-600"></i>
                </div>
                <div>
                    <div class="font-semibold text-gray-900">Fatura #{{ $order->invoice->number }}</div>
                    <div class="text-xs text-gray-500">Venc. {{ \Carbon\Carbon::parse($order->invoice->date_due)->format('d/m/Y') }}</div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                    {{ ['paid'=>'bg-green-100 text-green-700','overdue'=>'bg-red-100 text-red-700','pending'=>'bg-amber-100 text-amber-700'][$order->invoice->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ ['paid'=>'Pago','overdue'=>'Em Atraso','pending'=>'Pendente','cancelled'=>'Cancelado'][$order->invoice->status] ?? $order->invoice->status }}
                </span>
                <a href="{{ route('client.invoices.show', $order->invoice) }}"
                   class="border border-gray-200 text-gray-600 hover:bg-gray-50 font-semibold text-sm px-4 py-2 rounded-lg">
                    {{ $order->invoice->status === 'pending' ? 'Pagar Agora' : 'Ver Fatura' }}
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Timeline do Pedido --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Histórico do Pedido</h3>
        <div class="space-y-4">
            @php
                $events = [
                    ['icon'=>'🛒','label'=>'Pedido realizado','date'=>$order->created_at,'show'=>true],
                    ['icon'=>'💳','label'=>'Pagamento confirmado','date'=>$order->invoice?->date_paid,'show'=>$order->invoice?->status === 'paid'],
                    ['icon'=>'⚙️','label'=>'Serviço sendo provisionado','date'=>$order->created_at,'show'=>$order->status === 'processing'],
                    ['icon'=>'✅','label'=>'Pedido concluído','date'=>$order->updated_at,'show'=>$order->status === 'completed'],
                    ['icon'=>'❌','label'=>'Pedido cancelado','date'=>$order->updated_at,'show'=>$order->status === 'cancelled'],
                ];
            @endphp
            @foreach(array_filter($events, fn($e) => $e['show']) as $event)
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-sm flex-shrink-0">{{ $event['icon'] }}</div>
                <div>
                    <div class="font-medium text-gray-900 text-sm">{{ $event['label'] }}</div>
                    @if($event['date'])
                    <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Ações --}}
    <div class="flex flex-wrap gap-3">
        @if($order->status === 'pending' && $order->invoice?->status !== 'paid')
        <a href="{{ route('client.invoices.show', $order->invoice) }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm">
            <i class="bi bi-credit-card me-1"></i> Pagar Fatura
        </a>
        @endif
        <a href="{{ route('client.tickets.create') }}?order={{ $order->id }}"
           class="border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold px-5 py-2.5 rounded-lg text-sm">
            <i class="bi bi-headset me-1"></i> Suporte
        </a>
    </div>
</div>
@endsection
