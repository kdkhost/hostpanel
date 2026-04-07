@extends('client.layouts.app')
@section('title', 'Finalizar Pedido')
@section('page-title', 'Finalizar Pedido')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('client.orders.catalog') }}" class="text-blue-600 hover:text-blue-700">← Voltar ao Catálogo</a>
    </div>

    {{-- Componente React de Checkout --}}
    <div
        data-react-component="OrderCheckout"
        data-props='@json([
            "items"         => $items,
            "gateways"      => $gateways,
            "creditBalance" => auth("client")->user()->credit_balance ?? 0,
        ])'
    ></div>
</div>
@endsection

@push('scripts')
{{-- Carregar React entry point para área do cliente --}}
@vite(['resources/js/client/main.jsx'])
@endpush
