@extends('admin.layouts.app')
@section('title', 'Editar Serviço #' . $service->id)
@section('page-title', 'Editar Serviço')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Serviços</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.services.show', $service) }}">#{{ $service->id }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
<div x-data="serviceEdit()" class="max-w-3xl">

    <form @submit.prevent="save">
        <div class="card mb-4">
            <div class="card-header bg-white fw-semibold">Informações do Serviço</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Produto</label>
                        <select class="form-select" x-model="form.product_id">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ $service->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Servidor</label>
                        <select class="form-select" x-model="form.server_id">
                            <option value="">Nenhum</option>
                            @foreach($servers as $server)
                            <option value="{{ $server->id }}" {{ $service->server_id == $server->id ? 'selected' : '' }}>{{ $server->hostname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Domínio / Hostname</label>
                        <input type="text" class="form-control" x-model="form.domain" value="{{ $service->domain }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" x-model="form.status">
                            <option value="pending">Pendente</option>
                            <option value="active">Ativo</option>
                            <option value="suspended">Suspenso</option>
                            <option value="terminated">Encerrado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Próximo Vencimento</label>
                        <input type="date" class="form-control" x-model="form.next_due_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ciclo de Cobrança</label>
                        <select class="form-select" x-model="form.billing_cycle">
                            <option value="monthly">Mensal</option>
                            <option value="quarterly">Trimestral</option>
                            <option value="semiannually">Semestral</option>
                            <option value="annually">Anual</option>
                            <option value="biennially">Bienal</option>
                            <option value="free">Grátis</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Preço de Renovação (R$)</label>
                        <input type="number" step="0.01" class="form-control" x-model="form.amount">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nome do Usuário cPanel</label>
                        <input type="text" class="form-control" x-model="form.username">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Senha cPanel</label>
                        <input type="text" class="form-control" x-model="form.password" placeholder="Deixe em branco para não alterar">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">IP Dedicado</label>
                        <input type="text" class="form-control" x-model="form.dedicated_ip" placeholder="000.000.000.000">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white fw-semibold">Notas Internas</div>
            <div class="card-body">
                <textarea class="form-control" rows="4" x-model="form.admin_notes" placeholder="Notas visíveis apenas para administradores..."></textarea>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" :disabled="saving">
                <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                Salvar Alterações
            </button>
            <a href="{{ route('admin.services.show', $service) }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function serviceEdit() {
    return {
        saving: false,
        form: {
            product_id:    {{ $service->product_id ?? 'null' }},
            server_id:     {{ $service->server_id ?? 'null' }},
            domain:        '{{ addslashes($service->domain ?? '') }}',
            status:        '{{ $service->status }}',
            next_due_date: '{{ $service->next_due_date?->format('Y-m-d') ?? '' }}',
            billing_cycle: '{{ $service->billing_cycle ?? 'monthly' }}',
            amount:        {{ $service->amount ?? 0 }},
            username:      '{{ addslashes($service->username ?? '') }}',
            password:      '',
            dedicated_ip:  '{{ addslashes($service->dedicated_ip ?? '') }}',
            admin_notes:   '{{ addslashes($service->admin_notes ?? '') }}',
        },

        async save() {
            this.saving = true;
            const d = await HostPanel.fetch('{{ route("admin.services.update", $service) }}', {
                method: 'PUT',
                body: JSON.stringify(this.form)
            });
            this.saving = false;
            HostPanel.toast(d.message);
            if (d.service || d.message?.includes('sucesso')) {
                window.location = '{{ route("admin.services.show", $service) }}';
            }
        }
    }
}
</script>
@endpush
