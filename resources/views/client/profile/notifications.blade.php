@extends('client.layouts.app')
@section('title', 'Notificações')
@section('page-title', 'Notificações e Acessos')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6" x-data="profileNotifications()">
    <section class="xl:col-span-2 space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Central de notificações</h2>
                    <p class="text-sm text-gray-500 mt-1">Atualizações do sistema, tickets e avisos da sua conta.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" x-model="unreadOnly" @change="page = 1; loadNotifications()">
                        Mostrar apenas não lidas
                    </label>
                    <button type="button" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" @click="markAllAsRead" :disabled="markingAll || unreadCount === 0">
                        <span x-show="markingAll" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
                        Marcar tudo como lido
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div x-show="loading" class="py-16 text-center">
                <div class="inline-block h-8 w-8 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
            </div>

            <div x-show="!loading">
                <template x-for="notification in notifications" :key="notification.id">
                    <article class="border-b border-gray-100 px-5 py-4 transition hover:bg-gray-50">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm" :class="iconClass(notification.color)">
                                <i :class="iconName(notification.icon)"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="truncate text-sm font-semibold text-gray-900" x-text="notification.title"></h3>
                                            <span x-show="!notification.read" class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Não lida</span>
                                        </div>
                                        <p class="mt-1 whitespace-pre-wrap break-words text-sm text-gray-600" x-text="notification.message"></p>
                                    </div>
                                    <div class="text-xs text-gray-400 sm:text-right" x-text="formatDate(notification.created_at)"></div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-if="notification.action_url">
                                        <a :href="notification.action_url" class="inline-flex items-center justify-center rounded-xl border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50" x-text="notification.action_label || 'Abrir'"></a>
                                    </template>
                                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" @click="markAsRead(notification)" :disabled="notification.read || busyId === notification.id">
                                        <span x-show="busyId === notification.id" class="inline-block w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin mr-2"></span>
                                        Marcar como lida
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>

                <template x-if="!loading && notifications.length === 0">
                    <div class="px-6 py-16 text-center">
                        <i class="bi bi-bell-slash block text-5xl text-gray-200 mb-3"></i>
                        <p class="text-sm text-gray-500">Nenhuma notificação encontrada para o filtro atual.</p>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-between" x-show="meta && meta.last_page > 1">
            <span class="text-sm text-gray-500" x-text="`${meta?.from ?? 0}-${meta?.to ?? 0} de ${meta?.total ?? 0}`"></span>
            <div class="flex gap-2">
                <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" :disabled="page <= 1" @click="page--; loadNotifications()">Anterior</button>
                <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" :disabled="page >= (meta?.last_page ?? 1)" @click="page++; loadNotifications()">Próxima</button>
            </div>
        </div>
    </section>

    <aside class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-lg font-semibold text-gray-900">Resumo</h2>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-blue-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Não lidas</div>
                    <div class="mt-2 text-2xl font-bold text-blue-900" x-text="unreadCount"></div>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Carregadas</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-900" x-text="meta?.total ?? notifications.length"></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Histórico de acesso</h2>
                    <p class="text-sm text-gray-500 mt-1">Últimas autenticações registradas.</p>
                </div>
                <button type="button" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50" @click="loadHistory">Atualizar</button>
            </div>

            <div x-show="historyLoading" class="py-10 text-center">
                <div class="inline-block h-7 w-7 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
            </div>

            <div class="mt-4 space-y-3" x-show="!historyLoading">
                <template x-for="log in loginHistory" :key="log.id">
                    <div class="rounded-2xl border border-gray-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900" x-text="log.device || 'Dispositivo não identificado'"></div>
                                <div class="mt-1 text-xs text-gray-500" x-text="`${log.browser || 'Navegador'} • ${log.platform || 'Plataforma'}`"></div>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="log.success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'" x-text="log.success ? 'Sucesso' : 'Falha'"></span>
                        </div>
                        <div class="mt-3 space-y-1 text-xs text-gray-500">
                            <div x-text="`IP: ${log.ip_address || 'Não informado'}`"></div>
                            <div x-text="`${log.city || 'Cidade não informada'}${log.country ? ' • ' + log.country : ''}`"></div>
                            <div x-text="formatDate(log.created_at)"></div>
                        </div>
                    </div>
                </template>

                <template x-if="!historyLoading && loginHistory.length === 0">
                    <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-500">
                        Nenhum acesso recente encontrado.
                    </div>
                </template>
            </div>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
function profileNotifications() {
    return {
        notifications: [],
        loginHistory: [],
        meta: null,
        page: 1,
        unreadOnly: false,
        loading: false,
        historyLoading: false,
        markingAll: false,
        busyId: null,
        unreadCount: {{ (int) ($unreadNotifications ?? 0) }},

        async loadNotifications() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.page,
                unread_only: this.unreadOnly ? 1 : 0,
            });

            const data = await HostPanel.fetch(`{{ route('client.profile.notifications') }}?${params.toString()}`);
            this.notifications = data.data || [];
            this.meta = data.meta || data;
            this.loading = false;
        },

        async loadHistory() {
            this.historyLoading = true;
            const data = await HostPanel.fetch('{{ route('client.profile.login.history') }}');
            this.loginHistory = Array.isArray(data) ? data : (data.data || []);
            this.historyLoading = false;
        },

        async markAsRead(notification) {
            if (!notification || notification.read) {
                return;
            }

            this.busyId = notification.id;
            const response = await HostPanel.fetch(`{{ url('/cliente/perfil/notificacoes') }}/${notification.id}/lida`, {
                method: 'POST',
            });
            this.busyId = null;

            if (!response.ok) {
                HostPanel.toast(response.message || 'Não foi possível marcar a notificação como lida.', 'danger');
                return;
            }

            notification.read = true;
            notification.read_at = new Date().toISOString();
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            HostPanel.toast(response.message || 'Notificação atualizada.');
        },

        async markAllAsRead() {
            if (this.unreadCount === 0) {
                return;
            }

            this.markingAll = true;
            const response = await HostPanel.fetch('{{ route('client.profile.notifications.read.all') }}', {
                method: 'POST',
            });
            this.markingAll = false;

            if (!response.ok) {
                HostPanel.toast(response.message || 'Não foi possível marcar todas as notificações.', 'danger');
                return;
            }

            this.notifications = this.notifications.map((notification) => ({
                ...notification,
                read: true,
                read_at: notification.read_at || new Date().toISOString(),
            }));
            this.unreadCount = 0;
            HostPanel.toast(response.message || 'Todas as notificações foram marcadas como lidas.');
        },

        formatDate(value) {
            if (!value) {
                return 'Agora mesmo';
            }

            return new Date(value).toLocaleString('pt-BR', {
                dateStyle: 'short',
                timeStyle: 'short',
            });
        },

        iconName(icon) {
            return {
                bell: 'bi bi-bell-fill',
                ticket: 'bi bi-headset',
                invoice: 'bi bi-receipt',
                success: 'bi bi-check-circle-fill',
                warning: 'bi bi-exclamation-triangle-fill',
            }[icon] || 'bi bi-bell-fill';
        },

        iconClass(color) {
            return {
                blue: 'bg-blue-600',
                green: 'bg-emerald-600',
                red: 'bg-red-600',
                amber: 'bg-amber-500',
                purple: 'bg-purple-600',
            }[color] || 'bg-slate-700';
        },

        init() {
            this.loadNotifications();
            this.loadHistory();
        },
    };
}
</script>
@endpush
