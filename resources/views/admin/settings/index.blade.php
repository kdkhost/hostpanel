@extends('admin.layouts.app')
@section('title', 'Configurações')
@section('page-title', 'Configurações do Sistema')
@section('breadcrumb')
    <li class="breadcrumb-item active">Configurações</li>
@endsection

@section('content')
<div x-data="settingsPage()">
    <div class="row g-3">
        {{-- Nav Lateral --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-body p-2">
                    <div class="nav flex-column nav-pills" id="settings-tabs">
                        @foreach($groups as $group => $settings)
                        <button class="nav-link text-start {{ $loop->first ? 'active' : '' }}" @click="activeTab='{{ $group }}'"
                            :class="activeTab === '{{ $group }}' ? 'active' : ''">
                            <i class="bi {{ match($group) {
                                'general' => 'bi-gear',
                                'billing' => 'bi-currency-dollar',
                                'email'   => 'bi-envelope',
                                'security'=> 'bi-shield',
                                'support' => 'bi-headset',
                                'modules' => 'bi-puzzle',
                                default   => 'bi-sliders'
                            } }} me-2"></i>
                            {{ ucfirst($group) }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Form de Configurações --}}
        <div class="col-md-9">
            <form id="settings-form" @submit.prevent="save">
                @foreach($groups as $group => $settings)
                <div x-show="activeTab === '{{ $group }}'">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0 fw-semibold">{{ ucfirst($group) }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($settings as $setting)
                                <div class="{{ in_array($setting->type, ['text', 'textarea']) ? 'col-12' : 'col-md-6' }}">
                                    <label class="form-label fw-semibold">{{ $setting->label }}</label>
                                    @if($setting->type === 'boolean')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="{{ $setting->key }}" value="1" {{ $setting->value ? 'checked' : '' }}>
                                        </div>
                                    @elseif($setting->type === 'textarea')
                                        <textarea class="form-control" name="{{ $setting->key }}" rows="3">{{ $setting->value }}</textarea>
                                    @else
                                        <input type="{{ in_array($setting->type, ['integer', 'decimal']) ? 'number' : 'text' }}"
                                            class="form-control" name="{{ $setting->key }}"
                                            value="{{ $setting->value }}"
                                            {{ in_array($setting->type, ['decimal']) ? 'step=0.01' : '' }}>
                                    @endif
                                    @if($setting->description)
                                        <div class="form-text text-muted">{{ $setting->description }}</div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Salvar Configurações
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function settingsPage() {
    return {
        activeTab: '{{ $groups->keys()->first() ?? "general" }}',
        saving: false,

        async save() {
            this.saving = true;
            const form   = document.getElementById('settings-form');
            const data   = {};
            new FormData(form).forEach((v, k) => data[k] = v);
            // checkboxes não enviados = false
            document.querySelectorAll('input[type=checkbox]').forEach(el => { if (!el.checked) data[el.name] = '0'; });

            const res = await HostPanel.fetch('/admin/configuracoes', { method:'POST', body: JSON.stringify(data) });
            this.saving = false;
            HostPanel.toast(res.message || 'Configurações salvas!', res.message?.includes('sucesso') ? 'success' : 'danger');
        }
    }
}
</script>
@endpush
