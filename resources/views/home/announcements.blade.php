@extends('home.layouts.app')

@section('title', 'Avisos & Novidades — ' . config('app.name'))
@section('meta-description', 'Fique por dentro de tudo que acontece na ' . config('app.name'))

@section('content')
{{-- Hero --}}
<section class="bg-gradient-to-br from-slate-900 to-blue-900 text-white py-14">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold mb-2">Avisos & Novidades</h1>
        <p class="text-blue-200">Fique por dentro de tudo que acontece na {{ config('app.name') }}.</p>
    </div>
</section>

{{-- Anúncios --}}
<section class="py-12">
    <div class="max-w-3xl mx-auto px-4">
        @forelse($announcements as $ann)
        @php
            $typeColors = ['info'=>'blue','warning'=>'amber','danger'=>'red','success'=>'green'];
            $typeIcons  = ['info'=>'bi-info-circle','warning'=>'bi-exclamation-triangle','danger'=>'bi-x-octagon','success'=>'bi-check-circle'];
            $type   = $ann->type ?? 'info';
            $color  = $typeColors[$type] ?? 'blue';
            $icon   = $typeIcons[$type] ?? 'bi-megaphone';
        @endphp
        <article class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-4 overflow-hidden">
            <div class="border-l-4 border-{{ $color }}-500 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-{{ $color }}-50 flex items-center justify-center flex-shrink-0">
                        <i class="bi {{ $icon }} text-{{ $color }}-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            <h2 class="font-bold text-gray-900 text-lg">{{ $ann->title }}</h2>
                            <span class="text-xs bg-{{ $color }}-100 text-{{ $color }}-700 px-2 py-0.5 rounded-full font-semibold capitalize">{{ $type }}</span>
                        </div>
                        <div class="text-gray-600 text-sm leading-relaxed prose prose-sm max-w-none">
                            {!! nl2br(e($ann->content)) !!}
                        </div>
                        <div class="flex items-center gap-3 mt-3 text-xs text-gray-400">
                            <span><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($ann->created_at)->format('d/m/Y') }}</span>
                            @if($ann->expires_at)
                            <span><i class="bi bi-clock me-1"></i>Válido até {{ \Carbon\Carbon::parse($ann->expires_at)->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </article>
        @empty
        <div class="text-center py-20 text-gray-400">
            <i class="bi bi-megaphone text-4xl d-block mb-3"></i>
            <p class="text-lg font-medium">Nenhum aviso no momento.</p>
        </div>
        @endforelse

        @if($announcements->hasPages())
        <div class="mt-6">{{ $announcements->links() }}</div>
        @endif
    </div>
</section>
@endsection
