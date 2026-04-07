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
                                'general'      => 'bi-gear',
                                'billing'      => 'bi-currency-dollar',
                                'email'        => 'bi-envelope',
                                'security'     => 'bi-shield-lock',
                                'support'      => 'bi-headset',
                                'modules'      => 'bi-puzzle',
                                'integrations' => 'bi-plug',
                                'appearance'   => 'bi-palette',
                                default        => 'bi-sliders'
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
                                <div class="{{ in_array($setting->type, ['text', 'textarea', 'encrypted']) ? 'col-12' : 'col-md-6' }}">
                                    <label class="form-label fw-semibold">
                                        @if($setting->type === 'encrypted')
                                            <i class="bi bi-lock-fill text-warning me-1" title="Armazenado criptografado no banco de dados"></i>
                                        @endif
                                        {{ $setting->label }}
                                    </label>
                                    @if($setting->type === 'boolean')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="{{ $setting->key }}" value="1" {{ $setting->value ? 'checked' : '' }}>
                                        </div>
                                    @elseif($setting->type === 'textarea')
                                        <textarea class="form-control" name="{{ $setting->key }}" rows="3">{{ $setting->value }}</textarea>
                                    @elseif($setting->type === 'encrypted')
                                        <div class="input-group">
                                            <input type="password"
                                                class="form-control font-monospace"
                                                name="{{ $setting->key }}"
                                                placeholder="{{ \App\Models\Setting::get($setting->key) ? '••••••••  (configurado — deixe em branco para manter)' : 'Não configurado' }}"
                                                autocomplete="new-password"
                                                data-encrypted="1">
                                            <button type="button" class="btn btn-outline-secondary" title="Mostrar/ocultar"
                                                onclick="const i=this.previousElementSibling; i.type=i.type==='password'?'text':'password'; this.querySelector('i').className=i.type==='password'?'bi bi-eye':'bi bi-eye-slash'">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <i class="bi bi-shield-check text-success me-1"></i>
                                            Valor armazenado criptografado. Deixe em branco para manter o atual.
                                        </div>
                                    @else
                                        <input type="{{ in_array($setting->type, ['integer', 'decimal']) ? 'number' : 'text' }}"
                                            class="form-control" name="{{ $setting->key }}"
                                            value="{{ $setting->value }}"
                                            {{ in_array($setting->type, ['decimal']) ? 'step=0.01' : '' }}>
                                    @endif
                                    @if($setting->description && $setting->type !== 'encrypted')
                                        <div class="form-text text-muted">{{ $setting->description }}</div>
                                    @endif
                                </div>
                                @endforeach

                                {{-- Botão de teste para o grupo integrations --}}
                                @if($group === 'integrations')
                                <div class="col-12 pt-2">
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <button type="button" class="btn btn-outline-success btn-sm" id="btnTestWa" onclick="testWhatsApp()">
                                            <i class="bi bi-whatsapp me-1"></i>Testar Conexão WhatsApp
                                        </button>
                                        <span id="waTestResult" class="small"></span>
                                    </div>
                                </div>
                                @endif
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
async function testWhatsApp() {
    const btn = document.getElementById('btnTestWa');
    const res = document.getElementById('waTestResult');
    btn.disabled = true;
    res.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Testando...</span>';
    try {
        const d = await HostPanel.fetch('{{ route("admin.settings.whatsapp.test") }}', { method: 'POST' });
        res.innerHTML = d.success
            ? `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>${d.message}</span>`
            : `<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>${d.message}</span>`;
    } catch(e) {
        res.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Erro ao conectar</span>`;
    }
    btn.disabled = false;
}

function settingsPage() {
    return {
        activeTab: '{{ $groups->keys()->first() ?? "general" }}',
        saving: false,

        async save() {
            this.saving = true;
            const form = document.getElementById('settings-form');
            const data = {};
            new FormData(form).forEach((v, k) => data[k] = v);

            // checkboxes não enviados = false
            document.querySelectorAll('input[type=checkbox]').forEach(el => {
                if (!el.checked) data[el.name] = '0';
            });

            // campos encrypted vazios = não enviar (manter valor atual no banco)
            document.querySelectorAll('input[data-encrypted]').forEach(el => {
                if (!el.value || el.value.trim() === '') delete data[el.name];
            });

            const res = await HostPanel.fetch('/admin/configuracoes', { method:'POST', body: JSON.stringify(data) });
            this.saving = false;
            HostPanel.toast(res.message || 'Configurações salvas!', res.message?.includes('sucesso') ? 'success' : 'danger');
        }
    }
}
</script>
@endpush
