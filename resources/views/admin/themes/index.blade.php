@extends('admin.layouts.app')
@section('title', 'Gerenciar Temas')

@section('content')
<div x-data="themeManager()" class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-palette me-2 text-primary"></i>Temas do Sistema</h4>
            <p class="text-muted small mb-0">Escolha o visual utilizado na loja, área do cliente e painel administrativo.</p>
        </div>
        <div>
            <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-gear me-1"></i>Configurações Gerais
            </a>
        </div>
    </div>

    {{-- Como instalar temas --}}
    <div class="alert alert-info d-flex gap-3 align-items-start border-0 rounded-3 mb-4" style="background:#eff6ff">
        <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
        <div class="small">
            <strong>Como instalar um tema personalizado:</strong><br>
            1. Crie uma pasta em <code>resources/themes/{nome-do-tema}/</code><br>
            2. Adicione um arquivo <code>theme.json</code> com as informações do tema<br>
            3. Coloque os arquivos de view em <code>views/</code> e assets em <code>assets/</code><br>
            4. As views do tema substituem as padrão — só precisa incluir as que deseja modificar<br>
            5. Use <code>&#64;themeAsset('css/estilo.css')</code> nas views do tema para referenciar assets
        </div>
    </div>

    {{-- Grid de temas --}}
    <div class="row g-4">
        @foreach($themes as $theme)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-2 {{ $theme['active'] ? 'border-primary' : 'border-light' }} shadow-sm rounded-3 overflow-hidden">

                {{-- Preview --}}
                <div class="position-relative" style="background:linear-gradient(135deg,#e0e7ff,#f0fdf4);height:160px;overflow:hidden;">
                    @if(!empty($theme['preview']))
                        <img src="{{ Str::startsWith($theme['preview'], 'http') ? $theme['preview'] : route('theme.asset', ['theme' => $theme['id'], 'path' => $theme['preview']]) }}"
                             class="w-100 h-100 object-fit-cover" alt="{{ $theme['name'] }}">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center text-muted">
                                <i class="bi bi-palette2" style="font-size:3rem;opacity:.3"></i>
                                <p class="small mt-2 mb-0 fw-semibold">{{ $theme['name'] }}</p>
                            </div>
                        </div>
                    @endif
                    @if($theme['active'])
                    <span class="badge bg-primary position-absolute top-0 end-0 m-2" style="font-size:.75rem">
                        <i class="bi bi-check-circle-fill me-1"></i>Ativo
                    </span>
                    @endif
                </div>

                {{-- Info --}}
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-1">
                        <h6 class="fw-bold mb-0">{{ $theme['name'] }}</h6>
                        <span class="badge bg-light text-secondary">v{{ $theme['version'] ?? '1.0' }}</span>
                    </div>
                    @if(!empty($theme['description']))
                    <p class="small text-muted mb-2">{{ $theme['description'] }}</p>
                    @endif
                    @if(!empty($theme['author']))
                    <p class="small text-muted mb-0"><i class="bi bi-person me-1"></i>{{ $theme['author'] }}</p>
                    @endif

                    {{-- Suporte --}}
                    @if(!empty($theme['supports']))
                    <div class="d-flex gap-1 flex-wrap mt-2">
                        @foreach($theme['supports'] as $support)
                        <span class="badge rounded-pill bg-light text-secondary border" style="font-size:.7rem">
                            {{ match($support) {
                                'store'  => '🛒 Loja',
                                'client' => '👤 Cliente',
                                'admin'  => '⚙️ Admin',
                                default  => $support,
                            } }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="card-footer bg-white border-0 pt-0">
                    @if($theme['active'])
                    <button class="btn btn-primary btn-sm w-100" disabled>
                        <i class="bi bi-check-circle-fill me-1"></i>Tema Ativo
                    </button>
                    @else
                    <button class="btn btn-outline-primary btn-sm w-100"
                            @click="activate('{{ $theme['id'] }}', '{{ $theme['name'] }}')"
                            :disabled="activating === '{{ $theme['id'] }}'">
                        <span x-show="activating === '{{ $theme['id'] }}'" class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="activating !== '{{ $theme['id'] }}'" class="bi bi-palette me-1"></i>
                        <span x-text="activating === '{{ $theme['id'] }}' ? 'Ativando...' : 'Ativar Este Tema'"></span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach

        {{-- Card: Adicionar Tema --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-2 border-dashed border-secondary rounded-3" style="border-style:dashed!important;min-height:280px">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center gap-3">
                    <i class="bi bi-folder-plus text-secondary" style="font-size:2.5rem;opacity:.4"></i>
                    <div>
                        <h6 class="fw-semibold text-muted">Adicionar Novo Tema</h6>
                        <p class="small text-muted mb-0">
                            Coloque o tema em:<br>
                            <code class="bg-light px-2 py-1 rounded">resources/themes/{nome}/</code>
                        </p>
                    </div>
                    <a href="https://docs.hostpanel.com.br/temas" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-book me-1"></i>Ver Documentação
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Estrutura de diretórios de referência --}}
    <div class="card mt-4 border-0 shadow-sm rounded-3">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-folder-tree me-2"></i>Estrutura de um Tema
        </div>
        <div class="card-body">
            <pre class="mb-0 small bg-light p-3 rounded" style="font-size:.8rem">resources/themes/
└── meu-tema/
    ├── theme.json          ← Manifesto do tema (obrigatório)
    ├── views/              ← Views que substituem as padrão (opcional)
    │   ├── layouts/
    │   │   ├── app.blade.php        ← Layout da área do cliente
    │   │   └── guest.blade.php      ← Layout de páginas públicas
    │   ├── home/
    │   │   ├── index.blade.php      ← Página inicial / loja
    │   │   ├── store.blade.php      ← Catálogo de produtos
    │   │   ├── cart.blade.php       ← Carrinho de compras
    │   │   └── order-product.blade.php
    │   └── client/
    │       └── dashboard.blade.php
    └── assets/             ← CSS, JS, imagens do tema (opcional)
        ├── css/
        │   └── theme.css
        ├── js/
        │   └── theme.js
        └── images/</pre>
        </div>
    </div>

    {{-- Exemplo theme.json --}}
    <div class="card mt-3 border-0 shadow-sm rounded-3">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-filetype-json me-2"></i>Exemplo de <code>theme.json</code>
        </div>
        <div class="card-body">
            <pre class="mb-0 small bg-light p-3 rounded" style="font-size:.8rem">{
    "name": "Meu Tema",
    "description": "Tema customizado para minha empresa",
    "author": "Seu Nome",
    "version": "1.0.0",
    "preview": "images/preview.png",
    "supports": ["store", "client"],
    "colors": {
        "primary": "#4f46e5",
        "secondary": "#7c3aed"
    }
}</pre>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function themeManager() {
    return {
        activating: null,

        async activate(themeId, themeName) {
            if (!(await HostPanel.confirm({ text: `Ativar o tema "${themeName}"?\n\nO visual do sistema sera alterado imediatamente.`, confirmButtonText: 'Sim, ativar' }))) return;

            this.activating = themeId;

            const d = await HostPanel.fetch(
                `/admin/temas/${themeId}/ativar`,
                { method: 'POST' }
            );

            this.activating = null;

            if (d.success) {
                HostPanel.toast(d.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                HostPanel.toast(d.message || 'Erro ao ativar tema.', 'danger');
            }
        }
    };
}
</script>
@endpush
