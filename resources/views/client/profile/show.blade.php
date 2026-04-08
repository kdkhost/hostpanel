@extends('client.layouts.app')
@section('title', 'Meu Perfil')
@section('page-title', 'Meu Perfil')

@section('content')
@php $client = auth('client')->user(); @endphp
<div class="max-w-3xl mx-auto space-y-6" x-data="profilePage()">

    {{-- Dados Pessoais --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Dados Pessoais</h2>
        </div>
        <form @submit.prevent="saveProfile" class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nome Completo *</label>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.name" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">E-mail *</label>
                    <input type="email" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 bg-gray-50" x-model="profile.email" disabled>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Telefone</label>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.phone" placeholder="(11) 99999-9999">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">WhatsApp</label>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.whatsapp" placeholder="(11) 99999-9999">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Empresa</label>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.company_name" placeholder="Nome da empresa (opcional)">
                </div>
            </div>

            {{-- Endereço --}}
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3 mt-2">Endereço</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="sm:col-span-2 flex gap-2">
                    <input type="text" class="flex-1 border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.postcode" placeholder="CEP" data-viacep>
                    <button type="button" class="border border-gray-200 text-gray-600 px-3 py-2.5 rounded-lg text-sm hover:bg-gray-50" @click="lookupCep()" :disabled="cepLoading">
                        <span x-show="cepLoading" class="inline-block w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></span>
                        <span x-show="!cepLoading"><i class="bi bi-search"></i></span>
                    </button>
                </div>
                <div class="sm:col-span-2">
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.address" placeholder="Logradouro" data-address>
                </div>
                <div>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.address_number" placeholder="Número">
                </div>
                <div>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.address_complement" placeholder="Complemento">
                </div>
                <div>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.city" placeholder="Cidade" data-city>
                </div>
                <div>
                    <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="profile.state" placeholder="Estado (UF)" maxlength="2" data-state>
                </div>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm" :disabled="saving">
                <span x-show="saving" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-1"></span>
                <span x-text="saving ? 'Salvando...' : 'Salvar Alterações'"></span>
            </button>
        </form>
    </div>

    {{-- Alterar Senha --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Alterar Senha</h2>
        </div>
        <form @submit.prevent="changePassword" class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Senha Atual *</label>
                    <input type="password" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="pwForm.current_password" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nova Senha *</label>
                    <input type="password" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="pwForm.password" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar *</label>
                    <input type="password" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500" x-model="pwForm.password_confirmation" required>
                </div>
            </div>
            <button type="submit" class="border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold px-6 py-2.5 rounded-lg text-sm" :disabled="pwSaving">
                <span x-text="pwSaving ? 'Alterando...' : 'Alterar Senha'"></span>
            </button>
        </form>
    </div>

    {{-- Histórico de Logins --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Histórico de Acessos</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($loginHistory ?? [] as $login)
            <div class="flex items-center gap-4 px-6 py-3">
                <div class="w-9 h-9 rounded-lg {{ $login->success ? 'bg-green-50' : 'bg-red-50' }} flex items-center justify-center flex-shrink-0">
                    <i class="bi {{ $login->success ? 'bi-check-circle text-green-600' : 'bi-x-circle text-red-600' }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-800">{{ $login->ip_address }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ $login->user_agent }}</div>
                </div>
                <div class="text-xs text-gray-500 flex-shrink-0">{{ \Carbon\Carbon::parse($login->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-gray-500 text-sm">Nenhum registro encontrado.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profilePage() {
    return {
        saving: false, pwSaving: false, cepLoading: false,
        profile: {
            name: '{{ addslashes($client->name) }}',
            email: '{{ $client->email }}',
            phone: '{{ $client->phone }}',
            whatsapp: '{{ $client->whatsapp }}',
            company_name: '{{ addslashes($client->company_name ?? "") }}',
            postcode: '{{ $client->postcode }}',
            address: '{{ addslashes($client->address ?? "") }}',
            address_number: '{{ $client->address_number ?? "" }}',
            address_complement: '{{ $client->address_complement ?? "" }}',
            city: '{{ $client->city ?? "" }}',
            state: '{{ $client->state ?? "" }}',
        },
        pwForm: { current_password: '', password: '', password_confirmation: '' },

        async saveProfile() {
            this.saving = true;
            const d = await HostPanel.fetch('{{ route("client.profile.update") }}', { method: 'PUT', body: JSON.stringify(this.profile) });
            this.saving = false;
            HostPanel.toast(d.message || 'Perfil atualizado!', d.ok ? 'success' : 'danger');
        },

        async changePassword() {
            this.pwSaving = true;
            const d = await HostPanel.fetch('{{ route("client.profile.password") }}', { method: 'PUT', body: JSON.stringify(this.pwForm) });
            this.pwSaving = false;
            HostPanel.toast(d.message, d.ok ? 'success' : 'danger');
            if (d.ok) { this.pwForm = { current_password:'', password:'', password_confirmation:'' }; }
        },

        async lookupCep() {
            const cep = this.profile.postcode?.replace(/\D/g, '');
            if (cep?.length !== 8) return;
            this.cepLoading = true;
            const d = await HostPanel.lookupCep(cep);
            this.cepLoading = false;
            if (d?.logradouro) {
                this.profile.address = d.logradouro;
                this.profile.city    = d.localidade;
                this.profile.state   = d.uf;
            }
        }
    }
}
</script>
@endpush
