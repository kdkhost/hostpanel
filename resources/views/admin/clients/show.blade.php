@extends('admin.layouts.app')
@section('title', $client->name)
@section('page-title', $client->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@section('content')
<div x-data="adminClientShow()" class="row g-4">

    {{-- Sidebar: Info do Cliente --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                {{-- Avatar com drag-and-drop --}}
                <div class="position-relative mx-auto mb-3" style="width:88px;height:88px">
                    <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center fw-black text-white mx-auto"
                         style="width:88px;height:88px;background:linear-gradient(135deg,#1a56db,#7c3aed);font-size:2rem;cursor:pointer"
                         @click="$refs.avatarInput.click()"
                         @dragover.prevent="avatarDragOver=true"
                         @dragleave.prevent="avatarDragOver=false"
                         @drop.prevent="handleAvatarDrop($event)"
                         :class="{'ring-primary':avatarDragOver}"
                         :style="avatarDragOver ? 'box-shadow:0 0 0 4px rgba(26,86,219,.4)' : ''"
                         title="Clique ou arraste uma foto">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" class="w-100 h-100" style="object-fit:cover">
                        </template>
                        <template x-if="!avatarPreview">
                            <span>{{ strtoupper(substr($client->name, 0, 1)) }}</span>
                        </template>
                    </div>
                    <input type="file" x-ref="avatarInput" accept="image/*" class="d-none" @change="handleAvatarSelect($event)">
                    <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm"
                         style="width:28px;height:28px;cursor:pointer" @click="$refs.avatarInput.click()" title="Alterar foto">
                        <i class="bi bi-camera-fill" style="font-size:.75rem"></i>
                    </div>
                </div>
                {{-- Upload progress --}}
                <template x-if="avatarUploading">
                    <div class="mx-auto mb-2" style="max-width:200px">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Enviando...</span>
                            <span x-text="avatarProgress + '%'"></span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" :style="'width:'+avatarProgress+'%'"></div>
                        </div>
                        <div class="text-muted mt-1" style="font-size:.65rem" x-text="avatarEta"></div>
                    </div>
                </template>

                <h5 class="fw-bold mb-0">{{ $client->name }}</h5>
                @if($client->company_name) <small class="text-muted">{{ $client->company_name }}</small> @endif
                <div class="mt-2">
                    <span class="badge bg-{{ ['active'=>'success','suspended'=>'warning','banned'=>'danger','pending'=>'secondary'][$client->status] ?? 'secondary' }}">
                        {{ ucfirst($client->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body border-top py-3 px-4">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2 d-flex gap-2"><i class="bi bi-envelope text-muted"></i><span>{{ $client->email }}</span></li>
                    @if($client->phone) <li class="mb-2 d-flex gap-2"><i class="bi bi-telephone text-muted"></i><span>{{ $client->phone }}</span></li> @endif
                    @if($client->mobile) <li class="mb-2 d-flex gap-2"><i class="bi bi-phone text-muted"></i><span>{{ $client->mobile }}</span></li> @endif
                    @if($client->whatsapp) <li class="mb-2 d-flex gap-2"><i class="bi bi-whatsapp text-muted"></i><span>{{ $client->whatsapp }}</span></li> @endif
                    @if($client->document_number) <li class="mb-2 d-flex gap-2"><i class="bi bi-card-text text-muted"></i><span>{{ strtoupper($client->document_type ?? 'CPF') }}: {{ $client->document_number }}</span></li> @endif
                    @if($client->birth_date) <li class="mb-2 d-flex gap-2"><i class="bi bi-gift text-muted"></i><span>{{ \Carbon\Carbon::parse($client->birth_date)->format('d/m/Y') }}</span></li> @endif
                    @if($client->postcode)
                    <li class="mb-2 d-flex gap-2">
                        <i class="bi bi-geo-alt text-muted"></i>
                        <span>{{ implode(', ', array_filter([$client->address, $client->address_number, $client->address_complement])) }}<br>
                        {{ implode(' — ', array_filter([$client->neighborhood, $client->city . '/' . $client->state, $client->postcode])) }}</span>
                    </li>
                    @endif
                    <li class="d-flex gap-2"><i class="bi bi-calendar text-muted"></i><span>Desde {{ \Carbon\Carbon::parse($client->created_at)->format('d/m/Y H:i') }}</span></li>
                </ul>
            </div>
            <div class="card-footer bg-white py-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary flex-grow-1" @click="editClient()"><i class="bi bi-pencil me-1"></i>Editar</button>
                    <form method="POST" action="{{ route('admin.clients.impersonate', $client) }}">
                        @csrf <button type="submit" class="btn btn-sm btn-outline-warning flex-grow-1"><i class="bi bi-person-fill-gear me-1"></i>Impersonar</button>
                    </form>
                </div>
                @if($client->status === 'active')
                <button class="btn btn-sm btn-outline-danger w-100 mt-2" @click="changeStatus('suspended')"><i class="bi bi-pause-circle me-1"></i>Suspender Conta</button>
                @elseif($client->status === 'suspended')
                <button class="btn btn-sm btn-outline-success w-100 mt-2" @click="changeStatus('active')"><i class="bi bi-play-circle me-1"></i>Reativar Conta</button>
                @endif
            </div>
        </div>

        {{-- KPIs --}}
        <div class="card">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Serviços Ativos</span>
                        <span class="fw-bold text-primary">{{ $client->services->where('status','active')->count() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Faturas Pendentes</span>
                        <span class="fw-bold {{ $client->invoices->where('status','pending')->count() > 0 ? 'text-warning' : 'text-success' }}">
                            {{ $client->invoices->where('status','pending')->count() }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Fat. em Atraso</span>
                        <span class="fw-bold {{ $client->invoices->where('status','overdue')->count() > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $client->invoices->where('status','overdue')->count() }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Total Pago</span>
                        <span class="fw-bold text-success">R$ {{ number_format($client->invoices->where('status','paid')->sum('total'), 2, ',', '.') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Saldo de Crédito</span>
                        <span class="fw-bold text-info">R$ {{ number_format($client->credit_balance, 2, ',', '.') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted small">Tickets Abertos</span>
                        <span class="fw-bold">{{ $client->tickets->whereIn('status',['open','in_progress','customer_reply'])->count() }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Conteúdo Principal --}}
    <div class="col-lg-8">
        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='services'}" @click="tab='services'"><i class="bi bi-hdd me-1"></i>Serviços</button></li>
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='invoices'}" @click="tab='invoices'"><i class="bi bi-receipt me-1"></i>Faturas</button></li>
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='tickets'}" @click="tab='tickets'"><i class="bi bi-headset me-1"></i>Tickets</button></li>
            <li class="nav-item"><button class="nav-link" :class="{active:tab==='logs'}" @click="tab='logs'"><i class="bi bi-clock-history me-1"></i>Histórico</button></li>
        </ul>

        {{-- Serviços --}}
        <div x-show="tab==='services'">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span class="fw-semibold">Serviços</span>
                    <a href="{{ route('admin.services.index') }}?client_id={{ $client->id }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo Serviço</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Domínio/Produto</th><th>Status</th><th>Ciclo</th><th>Próx. Venc.</th><th>Valor</th><th class="text-center">Ações</th></tr></thead>
                        <tbody>
                            @forelse($client->services as $svc)
                            <tr>
                                <td><div class="fw-semibold">{{ $svc->domain ?? $svc->product?->name }}</div><small class="text-muted">{{ $svc->product?->name }}</small></td>
                                <td><span class="badge bg-{{ ['active'=>'success','suspended'=>'warning','pending'=>'secondary','terminated'=>'danger'][$svc->status] ?? 'secondary' }}">{{ ucfirst($svc->status) }}</span></td>
                                <td><small class="text-muted">{{ ['monthly'=>'Mensal','annually'=>'Anual'][$svc->billing_cycle] ?? $svc->billing_cycle }}</small></td>
                                <td><small>{{ $svc->next_due_date ? \Carbon\Carbon::parse($svc->next_due_date)->format('d/m/Y') : '—' }}</small></td>
                                <td class="fw-semibold">R$ {{ number_format($svc->price, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.services.show', $svc) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum serviço.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Faturas --}}
        <div x-show="tab==='invoices'">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span class="fw-semibold">Faturas</span>
                    <a href="{{ route('admin.invoices.index') }}?client_id={{ $client->id }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nova Fatura</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Número</th><th>Status</th><th>Emissão</th><th>Vencimento</th><th>Total</th><th class="text-center">Ações</th></tr></thead>
                        <tbody>
                            @forelse($client->invoices()->orderByDesc('date_issued')->take(20)->get() as $inv)
                            <tr>
                                <td class="fw-semibold">#{{ $inv->number }}</td>
                                <td><span class="badge bg-{{ ['paid'=>'success','overdue'=>'danger','pending'=>'warning','cancelled'=>'secondary'][$inv->status] ?? 'secondary' }}">{{ ['paid'=>'Pago','overdue'=>'Atrasado','pending'=>'Pendente','cancelled'=>'Cancelado'][$inv->status] ?? $inv->status }}</span></td>
                                <td><small>{{ \Carbon\Carbon::parse($inv->date_issued)->format('d/m/Y') }}</small></td>
                                <td><small>{{ \Carbon\Carbon::parse($inv->date_due)->format('d/m/Y') }}</small></td>
                                <td class="fw-semibold">R$ {{ number_format($inv->total, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma fatura.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tickets --}}
        <div x-show="tab==='tickets'">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Tickets de Suporte</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Número</th><th>Assunto</th><th>Status</th><th>Prioridade</th><th>Último Update</th><th class="text-center">Ações</th></tr></thead>
                        <tbody>
                            @forelse($client->tickets()->orderByDesc('last_reply_at')->take(20)->get() as $tkt)
                            <tr>
                                <td class="fw-semibold">#{{ $tkt->number }}</td>
                                <td>{{ Str::limit($tkt->subject, 40) }}</td>
                                <td><span class="badge bg-{{ ['open'=>'danger','in_progress'=>'primary','answered'=>'success','closed'=>'secondary'][$tkt->status] ?? 'secondary' }} bg-opacity-75">{{ ['open'=>'Aberto','answered'=>'Respondido','closed'=>'Fechado','in_progress'=>'Em Andamento'][$tkt->status] ?? $tkt->status }}</span></td>
                                <td><span class="badge bg-{{ ['urgent'=>'danger','high'=>'warning','medium'=>'primary','low'=>'secondary'][$tkt->priority] ?? 'secondary' }} bg-opacity-75">{{ ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Média','low'=>'Baixa'][$tkt->priority] ?? $tkt->priority }}</span></td>
                                <td><small class="text-muted">{{ $tkt->last_reply_at ? \Carbon\Carbon::parse($tkt->last_reply_at)->diffForHumans() : '—' }}</small></td>
                                <td class="text-center"><a href="{{ route('admin.tickets.show', $tkt) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum ticket.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Histórico de Logins --}}
        <div x-show="tab==='logs'">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Histórico de Acessos</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Data/Hora</th><th>IP</th><th>Status</th><th>User-Agent</th></tr></thead>
                        <tbody>
                            @forelse($client->loginLogs ?? [] as $log)
                            <tr>
                                <td><small>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</small></td>
                                <td><code class="small">{{ $log->ip_address }}</code></td>
                                <td><span class="badge {{ $log->success ? 'bg-success' : 'bg-danger' }} bg-opacity-75">{{ $log->success ? 'Sucesso' : 'Falhou' }}</span></td>
                                <td><small class="text-muted" title="{{ $log->user_agent }}">{{ Str::limit($log->user_agent, 50) }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum registro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Modal Editar Cliente --}}
    <div class="modal fade" id="editClientModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-person-gear me-2"></i>Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="updateClient">
                    <div class="modal-body" style="max-height:70vh;overflow-y:auto">
                        {{-- Seção 1: Dados Pessoais --}}
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 text-uppercase small"><i class="bi bi-person me-1"></i> Dados Pessoais</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" x-model="form.email" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo Documento</label>
                                <select class="form-select" x-model="form.document_type" @change="form.document_number=''">
                                    <option value="cpf">CPF (Pessoa Física)</option>
                                    <option value="cnpj">CNPJ (Pessoa Jurídica)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" x-text="form.document_type==='cnpj' ? 'CNPJ' : 'CPF'"></label>
                                <input type="text" class="form-control" x-model="form.document_number"
                                       x-mask:dynamic="form.document_type==='cnpj' ? '99.999.999/9999-99' : '999.999.999-99'"
                                       :placeholder="form.document_type==='cnpj' ? '00.000.000/0000-00' : '000.000.000-00'">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Data de Nascimento</label>
                                <input type="text" class="form-control" x-model="form.birth_date"
                                       x-mask="99/99/9999" placeholder="dd/mm/aaaa">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Senha</label>
                                <input type="password" class="form-control" x-model="form.password" placeholder="Em branco = manter atual">
                            </div>
                        </div>

                        {{-- Seção 2: Empresa --}}
                        <div x-show="form.document_type==='cnpj'" x-transition>
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 text-uppercase small"><i class="bi bi-building me-1"></i> Dados da Empresa</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Razão Social / Nome da Empresa</label>
                                    <input type="text" class="form-control" x-model="form.company_name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Cargo</label>
                                    <input type="text" class="form-control" x-model="form.company_position" placeholder="Ex: Diretor">
                                </div>
                            </div>
                        </div>

                        {{-- Seção 3: Contato --}}
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 text-uppercase small"><i class="bi bi-telephone me-1"></i> Contato</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Telefone Fixo</label>
                                <input type="text" class="form-control" x-model="form.phone"
                                       x-mask="(99) 9999-9999" placeholder="(00) 0000-0000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Celular</label>
                                <input type="text" class="form-control" x-model="form.mobile"
                                       x-mask="(99) 99999-9999" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp</label>
                                <input type="text" class="form-control" x-model="form.whatsapp"
                                       x-mask="(99) 99999-9999" placeholder="(00) 00000-0000">
                            </div>
                        </div>

                        {{-- Seção 4: Endereço com busca automática de CEP --}}
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 text-uppercase small"><i class="bi bi-geo-alt me-1"></i> Endereço</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">CEP</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" x-model="form.postcode"
                                           x-mask="99999-999" placeholder="00000-000"
                                           @input.debounce.600ms="lookupCep()"
                                           :class="{'border-success': cepFound, 'border-danger': cepNotFound}">
                                    <button class="btn btn-outline-secondary" type="button" @click="lookupCep()" :disabled="cepLoading">
                                        <span x-show="!cepLoading"><i class="bi bi-search"></i></span>
                                        <span x-show="cepLoading" class="spinner-border spinner-border-sm"></span>
                                    </button>
                                </div>
                                <small class="text-success" x-show="cepFound" x-cloak><i class="bi bi-check-circle me-1"></i>CEP encontrado</small>
                                <small class="text-danger" x-show="cepNotFound" x-cloak><i class="bi bi-x-circle me-1"></i>CEP não encontrado</small>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Endereço (Rua/Av.)</label>
                                <input type="text" class="form-control" x-model="form.address" placeholder="Rua, Avenida...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Número</label>
                                <input type="text" class="form-control" x-model="form.address_number" placeholder="Nº" x-ref="addressNumber">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Complemento</label>
                                <input type="text" class="form-control" x-model="form.address_complement" placeholder="Apto, Bloco...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Bairro</label>
                                <input type="text" class="form-control" x-model="form.neighborhood">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cidade</label>
                                <input type="text" class="form-control" x-model="form.city">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">UF</label>
                                <select class="form-select" x-model="form.state">
                                    <option value="">--</option>
                                    @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                    <option value="{{ $uf }}">{{ $uf }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Seção 5: Administrativo --}}
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 text-uppercase small"><i class="bi bi-gear me-1"></i> Administrativo</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select class="form-select" x-model="form.status">
                                    <option value="active">Ativo</option>
                                    <option value="inactive">Inativo</option>
                                    <option value="pending">Pendente</option>
                                    <option value="blocked">Bloqueado</option>
                                    <option value="suspended">Suspenso</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">País</label>
                                <select class="form-select" x-model="form.country">
                                    <option value="BR">Brasil</option>
                                    <option value="PT">Portugal</option>
                                    <option value="US">Estados Unidos</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Notas Internas</label>
                                <textarea class="form-control" rows="2" x-model="form.notes" placeholder="Observações internas sobre o cliente..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i class="bi bi-check-lg me-1" x-show="!saving"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminClientShow() {
    return {
        tab: 'services',
        saving: false,

        // Avatar upload
        avatarPreview: @js($client->avatar ? \Storage::url($client->avatar) : null),
        avatarUploading: false,
        avatarProgress: 0,
        avatarEta: '',
        avatarDragOver: false,

        // CEP lookup
        cepLoading: false,
        cepFound: false,
        cepNotFound: false,

        form: {
            name: @js($client->name),
            email: @js($client->email),
            document_type: @js($client->document_type ?? 'cpf'),
            document_number: @js($client->document_number),
            birth_date: @js($client->birth_date ? \Carbon\Carbon::parse($client->birth_date)->format('d/m/Y') : ''),
            phone: @js($client->phone),
            mobile: @js($client->mobile),
            whatsapp: @js($client->whatsapp),
            company_name: @js($client->company_name),
            company_position: @js($client->company_position),
            address: @js($client->address),
            address_number: @js($client->address_number),
            address_complement: @js($client->address_complement),
            neighborhood: @js($client->neighborhood),
            city: @js($client->city),
            state: @js($client->state),
            postcode: @js($client->postcode),
            country: @js($client->country ?? 'BR'),
            status: @js($client->status),
            notes: @js($client->notes),
            password: ''
        },

        editClient() {
            new bootstrap.Modal(document.getElementById('editClientModal')).show();
        },

        // ---- Avatar Upload com progresso e ETA ----
        handleAvatarDrop(e) {
            this.avatarDragOver = false;
            const file = e.dataTransfer?.files?.[0];
            if (file && file.type.startsWith('image/')) this.uploadAvatar(file);
        },
        handleAvatarSelect(e) {
            const file = e.target.files?.[0];
            if (file) this.uploadAvatar(file);
        },
        uploadAvatar(file) {
            if (file.size > 5 * 1024 * 1024) {
                HostPanel.toast('Imagem muito grande. Máximo 5MB.', 'danger');
                return;
            }
            // Preview imediato
            const reader = new FileReader();
            reader.onload = e => this.avatarPreview = e.target.result;
            reader.readAsDataURL(file);

            // Upload com XMLHttpRequest para progresso real
            const fd = new FormData();
            fd.append('avatar', file);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

            const xhr = new XMLHttpRequest();
            const startTime = Date.now();
            this.avatarUploading = true;
            this.avatarProgress = 0;
            this.avatarEta = 'Calculando...';

            xhr.upload.addEventListener('progress', (e) => {
                if (!e.lengthComputable) return;
                const pct = Math.round((e.loaded / e.total) * 100);
                this.avatarProgress = pct;
                const elapsed = (Date.now() - startTime) / 1000;
                if (pct > 0 && pct < 100) {
                    const totalEstimated = elapsed / (pct / 100);
                    const remaining = Math.max(0, totalEstimated - elapsed);
                    if (remaining < 60) {
                        this.avatarEta = `~${Math.ceil(remaining)}s restante${remaining > 1 ? 's' : ''}`;
                    } else {
                        this.avatarEta = `~${Math.ceil(remaining / 60)}min restante`;
                    }
                }
            });

            xhr.addEventListener('load', () => {
                this.avatarUploading = false;
                this.avatarProgress = 100;
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300) {
                        HostPanel.toast(res.message || 'Foto atualizada!');
                        // Testar se a URL do servidor é acessível, senão manter preview local
                        if (res.avatar_url) {
                            const img = new Image();
                            img.onload = () => { this.avatarPreview = res.avatar_url; };
                            img.onerror = () => { /* manter preview local do FileReader */ };
                            img.src = res.avatar_url;
                        }
                    } else {
                        HostPanel.toast(res.message || 'Erro no upload.', 'danger');
                    }
                } catch { HostPanel.toast('Erro no upload.', 'danger'); }
            });

            xhr.addEventListener('error', () => {
                this.avatarUploading = false;
                HostPanel.toast('Erro de rede ao enviar foto.', 'danger');
            });

            xhr.open('POST', '/admin/clientes/{{ $client->id }}/avatar');
            xhr.send(fd);
        },

        // ---- CEP auto-fill ----
        async lookupCep() {
            const raw = (this.form.postcode || '').replace(/\D/g, '');
            if (raw.length !== 8) return;

            this.cepLoading = true;
            this.cepFound = false;
            this.cepNotFound = false;

            try {
                const res = await fetch('/admin/clientes/cep/' + raw, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                if (!res.ok) { this.cepNotFound = true; return; }
                const data = await res.json();
                this.cepFound = true;
                if (data.address) this.form.address = data.address;
                if (data.neighborhood) this.form.neighborhood = data.neighborhood;
                if (data.city) this.form.city = data.city;
                if (data.state) this.form.state = data.state;
                // Focar no campo número após preencher
                this.$nextTick(() => this.$refs.addressNumber?.focus());
            } catch {
                this.cepNotFound = true;
            } finally {
                this.cepLoading = false;
            }
        },

        // ---- Save client ----
        async updateClient() {
            this.saving = true;
            try {
                // Converter data pt-BR → ISO para o backend
                const payload = { ...this.form };
                if (payload.birth_date) {
                    const parts = payload.birth_date.split('/');
                    if (parts.length === 3) {
                        payload.birth_date = `${parts[2]}-${parts[1]}-${parts[0]}`;
                    }
                }
                // Remover máscaras de documento e telefone para salvar limpo
                if (payload.document_number) payload.document_number = payload.document_number.replace(/\D/g, '');
                if (payload.phone) payload.phone = payload.phone.replace(/\D/g, '');
                if (payload.mobile) payload.mobile = payload.mobile.replace(/\D/g, '');
                if (payload.whatsapp) payload.whatsapp = payload.whatsapp.replace(/\D/g, '');
                if (payload.postcode) payload.postcode = payload.postcode.replace(/\D/g, '');

                const d = await HostPanel.fetch('{{ route("admin.clients.update", $client) }}', {
                    method: 'PUT',
                    body: JSON.stringify(payload)
                });
                if (d.client) {
                    HostPanel.toast('Cliente atualizado com sucesso!');
                    window.location.reload();
                } else {
                    const msg = typeof d.errors === 'object'
                        ? Object.values(d.errors).flat().join(', ')
                        : (d.message || 'Erro ao atualizar.');
                    HostPanel.toast(msg, 'danger');
                }
            } catch (e) {
                HostPanel.toast('Ocorreu um erro na requisição.', 'danger');
            } finally {
                this.saving = false;
            }
        },

        async changeStatus(status) {
            const labels = { suspended: 'suspender', active: 'reativar' };
            if (!(await HostPanel.confirm({ text: `Deseja ${labels[status]} a conta de {{ $client->name }}?`, confirmButtonText: 'Sim, continuar' }))) return;
            const d = await HostPanel.fetch('{{ route("admin.clients.status", $client) }}', {
                method: 'POST', body: JSON.stringify({ status })
            });
            HostPanel.toast(d.message);
            if (d.status) setTimeout(() => window.location.reload(), 1200);
        },

        init() {
            @if(session('open_edit'))
                this.editClient();
            @endif
        }
    }
}
</script>
@endpush
