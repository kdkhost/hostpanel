@extends('admin.layouts.app')
@section('title', 'Afiliados')
@section('page-title', 'Sistema de Afiliados')
@section('breadcrumb')
    <li class="breadcrumb-item active">Afiliados</li>
@endsection

@section('content')
<div x-data="affiliatesAdmin()">

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card stats-card"><div class="card-body text-center"><div class="fs-3 fw-bold text-primary" x-text="stats.total_affiliates">0</div><small class="text-muted">Total Afiliados</small></div></div></div>
        <div class="col-md-2"><div class="card stats-card"><div class="card-body text-center"><div class="fs-3 fw-bold text-success" x-text="stats.active_affiliates">0</div><small class="text-muted">Ativos</small></div></div></div>
        <div class="col-md-2"><div class="card stats-card"><div class="card-body text-center"><div class="fs-3 fw-bold" x-text="'R$ '+parseFloat(stats.total_commissions||0).toFixed(2)">R$ 0.00</div><small class="text-muted">Total Comissões</small></div></div></div>
        <div class="col-md-2"><div class="card stats-card"><div class="card-body text-center"><div class="fs-3 fw-bold text-warning" x-text="'R$ '+parseFloat(stats.pending_commissions||0).toFixed(2)">R$ 0.00</div><small class="text-muted">Pendentes</small></div></div></div>
        <div class="col-md-2"><div class="card stats-card"><div class="card-body text-center"><div class="fs-3 fw-bold text-info" x-text="'R$ '+parseFloat(stats.pending_payouts||0).toFixed(2)">R$ 0.00</div><small class="text-muted">Saques Pendentes</small></div></div></div>
        <div class="col-md-2"><div class="card stats-card"><div class="card-body text-center"><div class="fs-3 fw-bold text-success" x-text="'R$ '+parseFloat(stats.total_paid||0).toFixed(2)">R$ 0.00</div><small class="text-muted">Total Pago</small></div></div></div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link" :class="tab==='affiliates'?'active':''" @click="tab='affiliates';loadAffiliates()">Afiliados</button></li>
        <li class="nav-item"><button class="nav-link" :class="tab==='commissions'?'active':''" @click="tab='commissions';loadCommissions()">Comissões</button></li>
        <li class="nav-item"><button class="nav-link" :class="tab==='payouts'?'active':''" @click="tab='payouts';loadPayouts()">Saques</button></li>
    </ul>

    {{-- Affiliates List --}}
    <div x-show="tab==='affiliates'">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Cliente</th><th>Código</th><th>Taxa</th><th>Referidos</th><th>Conversões</th><th>Saldo</th><th>Total Ganho</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody>
                            <template x-for="a in affiliates.data||[]" :key="a.id">
                                <tr>
                                    <td><span class="fw-semibold" x-text="a.client?.name"></span><br><small class="text-muted" x-text="a.client?.email"></small></td>
                                    <td><code x-text="a.referral_code"></code></td>
                                    <td x-text="a.commission_rate+'%'"></td>
                                    <td x-text="a.referrals_count"></td>
                                    <td x-text="a.total_conversions"></td>
                                    <td class="fw-bold text-success" x-text="'R$ '+parseFloat(a.balance).toFixed(2)"></td>
                                    <td x-text="'R$ '+parseFloat(a.total_earned).toFixed(2)"></td>
                                    <td><span class="badge" :class="{'bg-success':a.status==='active','bg-secondary':a.status==='inactive','bg-danger':a.status==='suspended'}" x-text="a.status"></span></td>
                                    <td>
                                        <select class="form-select form-select-sm" style="width:120px" :value="a.status" @change="updateAffiliate(a.id,{status:$event.target.value})">
                                            <option value="active">Ativo</option>
                                            <option value="inactive">Inativo</option>
                                            <option value="suspended">Suspenso</option>
                                        </select>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Commissions --}}
    <div x-show="tab==='commissions'">
        <div class="card">
            <div class="card-header bg-white d-flex gap-2">
                <select class="form-select form-select-sm" style="width:150px" x-model="commFilter" @change="loadCommissions()">
                    <option value="">Todos</option>
                    <option value="pending">Pendentes</option>
                    <option value="approved">Aprovadas</option>
                    <option value="rejected">Rejeitadas</option>
                </select>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Afiliado</th><th>Referido</th><th>Fatura</th><th>Valor Fatura</th><th>Comissão</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody>
                            <template x-for="c in commissions.data||[]" :key="c.id">
                                <tr>
                                    <td x-text="c.affiliate?.client?.name"></td>
                                    <td x-text="c.referral?.referred_client?.name"></td>
                                    <td x-text="'#'+c.invoice_id"></td>
                                    <td x-text="'R$ '+parseFloat(c.invoice_amount).toFixed(2)"></td>
                                    <td class="fw-bold text-success" x-text="'R$ '+parseFloat(c.commission_amount).toFixed(2)"></td>
                                    <td><span class="badge" :class="{'bg-warning text-dark':c.status==='pending','bg-success':c.status==='approved','bg-danger':c.status==='rejected'}" x-text="c.status"></span></td>
                                    <td>
                                        <template x-if="c.status==='pending'">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success btn-sm" @click="approveComm(c.id)"><i class="bi bi-check"></i></button>
                                                <button class="btn btn-danger btn-sm" @click="rejectComm(c.id)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Payouts --}}
    <div x-show="tab==='payouts'">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Afiliado</th><th>Valor</th><th>Método</th><th>Detalhes</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody>
                            <template x-for="p in payouts.data||[]" :key="p.id">
                                <tr>
                                    <td x-text="p.affiliate?.client?.name"></td>
                                    <td class="fw-bold" x-text="'R$ '+parseFloat(p.amount).toFixed(2)"></td>
                                    <td><span class="badge bg-info text-dark" x-text="p.method"></span></td>
                                    <td><small x-text="p.payment_details"></small></td>
                                    <td><span class="badge" :class="{'bg-warning text-dark':p.status==='pending','bg-info':p.status==='processing','bg-success':p.status==='completed','bg-danger':p.status==='rejected'}" x-text="p.status"></span></td>
                                    <td>
                                        <template x-if="p.status==='pending'||p.status==='processing'">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success btn-sm" @click="processPayout(p.id,'completed')"><i class="bi bi-check-lg"></i> Pagar</button>
                                                <button class="btn btn-danger btn-sm" @click="processPayout(p.id,'rejected')"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function affiliatesAdmin(){
    return {
        tab:'affiliates', stats:{}, affiliates:{}, commissions:{}, payouts:{}, commFilter:'',
        async init(){ this.loadStats(); this.loadAffiliates(); },
        async loadStats(){ this.stats = await HostPanel.fetch('{{ route("admin.affiliates.stats") }}'); },
        async loadAffiliates(){ this.affiliates = await HostPanel.fetch('{{ route("admin.affiliates.index") }}'); },
        async loadCommissions(){ this.commissions = await HostPanel.fetch('{{ route("admin.affiliates.commissions") }}?status='+this.commFilter); },
        async loadPayouts(){ this.payouts = await HostPanel.fetch('{{ route("admin.affiliates.payouts") }}'); },
        async approveComm(id){ await HostPanel.fetch(`/admin/afiliados/comissoes/${id}/aprovar`,{method:'POST'}); this.loadCommissions(); this.loadStats(); HostPanel.toast('Comissão aprovada!'); },
        async rejectComm(id){ await HostPanel.fetch(`/admin/afiliados/comissoes/${id}/rejeitar`,{method:'POST'}); this.loadCommissions(); this.loadStats(); HostPanel.toast('Comissão rejeitada.','warning'); },
        async processPayout(id,status){ await HostPanel.fetch(`/admin/afiliados/saques/${id}/processar`,{method:'POST',body:JSON.stringify({status})}); this.loadPayouts(); this.loadStats(); HostPanel.toast(status==='completed'?'Pago!':'Rejeitado.'); },
        async updateAffiliate(id,data){ await HostPanel.fetch(`/admin/afiliados/${id}`,{method:'PUT',body:JSON.stringify(data)}); this.loadAffiliates(); HostPanel.toast('Atualizado!'); },
    }
}
</script>
@endpush
