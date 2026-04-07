@extends('admin.layouts.app')
@section('title', $product->name)
@section('page-title', $product->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produtos</a></li>
    <li class="breadcrumb-item active">{{ $product->name }}</li>
@endsection

@section('content')
<div x-data="productShow()" class="pb-4">

    <div class="row g-4">
        {{-- Detalhes --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Informações do Produto</span>
                    <button class="btn btn-sm btn-outline-primary" @click="editing=!editing" x-text="editing?'Cancelar':'Editar'"></button>
                </div>
                <div class="card-body">
                    <div x-show="!editing">
                        <div class="row g-3 small">
                            <div class="col-md-6"><div class="text-muted">Nome</div><div class="fw-semibold">{{ $product->name }}</div></div>
                            <div class="col-md-6"><div class="text-muted">Grupo</div><div class="fw-semibold">{{ $product->group?->name ?? '—' }}</div></div>
                            <div class="col-md-6"><div class="text-muted">Slug</div><div><code>{{ $product->slug }}</code></div></div>
                            <div class="col-md-6"><div class="text-muted">Tipo</div><div class="fw-semibold text-capitalize">{{ $product->type }}</div></div>
                            <div class="col-md-6">
                                <div class="text-muted">Status</div>
                                <span class="badge" style="font-size:.75rem" :class="''">
                                    @if($product->active) <span class="badge bg-success">Ativo</span> @else <span class="badge bg-secondary">Inativo</span> @endif
                                    @if($product->hidden) <span class="badge bg-warning text-dark ms-1">Oculto</span> @endif
                                    @if($product->featured) <span class="badge bg-info ms-1">Destaque</span> @endif
                                </span>
                            </div>
                            <div class="col-md-6"><div class="text-muted">Servidor Padrão</div><div class="fw-semibold">{{ $product->serverGroup?->name ?? 'Nenhum' }}</div></div>
                            <div class="col-12"><div class="text-muted mb-1">Descrição</div><div class="text-secondary">{{ $product->description ?: '—' }}</div></div>
                        </div>
                    </div>
                    <div x-show="editing">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nome *</label>
                                <input class="form-control" x-model="form.name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tagline</label>
                                <input class="form-control" x-model="form.tagline">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descrição</label>
                                <textarea class="form-control" rows="3" x-model="form.description"></textarea>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" x-model="form.active">
                                    <label class="form-check-label">Ativo</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" x-model="form.hidden">
                                    <label class="form-check-label">Oculto</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" x-model="form.featured">
                                    <label class="form-check-label">Destaque</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-sm" @click="save()" :disabled="saving">
                                    <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>Salvar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preços --}}
            <div class="card mb-4">
                <div class="card-header bg-white fw-semibold">Preços por Ciclo</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Ciclo</th><th class="text-end">Preço</th><th class="text-end">Setup</th><th class="text-center">Ativo</th></tr>
                        </thead>
                        <tbody>
                            @forelse($product->allPricing ?? [] as $price)
                            @php $labels = ['monthly'=>'Mensal','quarterly'=>'Trimestral','semiannually'=>'Semestral','annually'=>'Anual','biennially'=>'Bienal','triennially'=>'Trienal','free'=>'Grátis']; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $labels[$price->billing_cycle] ?? $price->billing_cycle }}</td>
                                <td class="text-end">R$ {{ number_format($price->price, 2, ',', '.') }}</td>
                                <td class="text-end text-muted">R$ {{ number_format($price->setup_fee ?? 0, 2, ',', '.') }}</td>
                                <td class="text-center"><span class="badge" :class="''">@if($price->active)<span class="badge bg-success">Sim</span>@else<span class="badge bg-secondary">Não</span>@endif</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhum preço configurado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white fw-semibold">Estatísticas</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Serviços Ativos</span>
                        <span class="fw-bold">{{ $product->services()->where('status','active')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">Total de Serviços</span>
                        <span class="fw-bold">{{ $product->services()->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Criado em</span>
                        <span class="small text-muted">{{ $product->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white fw-semibold">Ações</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('plans') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Ver Página Pública
                    </a>
                    <button class="btn btn-outline-danger btn-sm" @click="destroy()">
                        <i class="bi bi-trash me-1"></i>Excluir Produto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productShow() {
    return {
        editing: false, saving: false,
        form: {
            name:        '{{ addslashes($product->name) }}',
            tagline:     '{{ addslashes($product->tagline ?? '') }}',
            description: '{{ addslashes($product->description ?? '') }}',
            active:      {{ $product->active ? 'true' : 'false' }},
            hidden:      {{ $product->hidden ? 'true' : 'false' }},
            featured:    {{ $product->featured ? 'true' : 'false' }},
        },

        async save() {
            this.saving = true;
            const d = await HostPanel.fetch('{{ route("admin.products.update", $product) }}', {
                method: 'PUT', body: JSON.stringify(this.form)
            });
            this.saving = false;
            HostPanel.toast(d.message);
            if (d.product) this.editing = false;
        },

        async destroy() {
            if (!confirm('Excluir o produto "{{ $product->name }}"? Esta ação não pode ser desfeita.')) return;
            const d = await HostPanel.fetch('{{ route("admin.products.destroy", $product) }}', { method: 'DELETE' });
            HostPanel.toast(d.message);
            if (d.message) setTimeout(() => window.location = '{{ route("admin.products.index") }}', 1000);
        }
    }
}
</script>
@endpush
