<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status da Rede — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, sans-serif; }
        .status-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
        .status-dot.online   { background: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,.2); }
        .status-dot.degraded { background: #f59e0b; box-shadow: 0 0 0 4px rgba(245,158,11,.2); }
        .status-dot.offline  { background: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,.2); animation: pulse-red .8s infinite; }
        .status-dot.unknown  { background: #94a3b8; }
        @keyframes pulse-red {
            0%, 100% { box-shadow: 0 0 0 4px rgba(239,68,68,.2); }
            50%       { box-shadow: 0 0 0 8px rgba(239,68,68,.05); }
        }
        .hero-banner { border-radius: 16px; padding: 2.5rem; color: #fff; }
        .hero-banner.operational  { background: linear-gradient(135deg, #16a34a, #15803d); }
        .hero-banner.degraded     { background: linear-gradient(135deg, #d97706, #b45309); }
        .hero-banner.partial_outage { background: linear-gradient(135deg, #ea580c, #c2410c); }
        .hero-banner.outage       { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        .hero-banner.unknown      { background: linear-gradient(135deg, #475569, #334155); }
        .server-card { border-radius: 12px; transition: box-shadow .2s; }
        .server-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .metric-badge { font-size: .75rem; padding: .2rem .55rem; border-radius: 20px; white-space: nowrap; }
        .latency-bar { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
        .latency-bar-fill { height: 100%; border-radius: 3px; transition: width .5s; }
    </style>
</head>
<body>

@php
    $heroCopy = [
        'operational'   => ['icon' => 'bi-check-circle-fill', 'title' => 'Todos os sistemas operacionais', 'sub' => 'Toda a infraestrutura está funcionando normalmente.'],
        'degraded'      => ['icon' => 'bi-exclamation-circle-fill', 'title' => 'Desempenho degradado', 'sub' => 'Alguns servidores estão com latência elevada ou perda de pacotes.'],
        'partial_outage'=> ['icon' => 'bi-exclamation-triangle-fill', 'title' => 'Interrupção parcial', 'sub' => 'Um ou mais servidores estão offline. Estamos investigando.'],
        'outage'        => ['icon' => 'bi-x-circle-fill', 'title' => 'Interrupção total', 'sub' => 'Todos os servidores estão offline. Trabalhando para restaurar.'],
        'unknown'       => ['icon' => 'bi-question-circle-fill', 'title' => 'Status desconhecido', 'sub' => 'Aguardando dados do primeiro monitoramento.'],
    ];
    $hero = $heroCopy[$overall] ?? $heroCopy['unknown'];
@endphp

<div class="container py-5" style="max-width:900px">

    {{-- Logo + nome --}}
    <div class="text-center mb-4">
        <a href="{{ url('/') }}" class="text-decoration-none">
            <span class="fw-bold fs-4 text-dark">{{ config('app.name') }}</span>
        </a>
        <div class="text-muted small">Página de Status da Infraestrutura</div>
    </div>

    {{-- Banner de status geral --}}
    <div class="hero-banner {{ $overall }} mb-5 d-flex align-items-center gap-4">
        <i class="bi {{ $hero['icon'] }} fs-1 opacity-90"></i>
        <div>
            <div class="fs-4 fw-bold">{{ $hero['title'] }}</div>
            <div class="opacity-80">{{ $hero['sub'] }}</div>
        </div>
        <div class="ms-auto text-end opacity-70 small">
            <div>Atualizado automaticamente</div>
            <div id="lastUpdated">a cada 30s</div>
        </div>
    </div>

    {{-- Lista de Servidores --}}
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-muted mb-0 text-uppercase" style="font-size:.75rem;letter-spacing:.08em">Servidores ({{ count($servers) }})</h6>
        <small class="text-muted" id="refreshCountdown">Próxima atualização em <span id="cdSecs">30</span>s</small>
    </div>

    <div id="serverList">
        @foreach($servers as $srv)
        @php
            $statusColors = ['online' => 'success', 'degraded' => 'warning', 'offline' => 'danger', 'unknown' => 'secondary'];
            $sc = $statusColors[$srv['status']] ?? 'secondary';
            $latPct = $srv['latency_ms'] ? min(100, ($srv['latency_ms'] / 1000) * 100) : 0;
            $latColor = !$srv['latency_ms'] ? '#94a3b8' : ($srv['latency_ms'] > 500 ? '#ef4444' : ($srv['latency_ms'] > 200 ? '#f59e0b' : '#22c55e'));
        @endphp
        <div class="card server-card border-0 shadow-sm mb-3" data-server-id="{{ $srv['id'] }}">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="status-dot {{ $srv['status'] }}" title="{{ ucfirst($srv['status']) }}"></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $srv['name'] }}</div>
                        @if($srv['location'])
                        <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $srv['location'] }}</div>
                        @endif
                    </div>

                    {{-- Latência --}}
                    <div class="text-center" style="min-width:90px">
                        <div class="text-muted" style="font-size:.7rem">LATÊNCIA</div>
                        <div class="fw-bold" style="color:{{ $latColor }}">
                            {{ $srv['latency_ms'] ? $srv['latency_ms'] . ' ms' : '—' }}
                        </div>
                        <div class="latency-bar mt-1">
                            <div class="latency-bar-fill" style="width:{{ $latPct }}%;background:{{ $latColor }}"></div>
                        </div>
                    </div>

                    {{-- Packet Loss --}}
                    <div class="text-center" style="min-width:80px">
                        <div class="text-muted" style="font-size:.7rem">PACKET LOSS</div>
                        <div class="fw-bold {{ ($srv['packet_loss'] ?? 0) > 10 ? 'text-danger' : 'text-muted' }}">
                            {{ $srv['packet_loss'] !== null ? number_format($srv['packet_loss'], 1) . '%' : '—' }}
                        </div>
                    </div>

                    {{-- CPU/RAM (compacto) --}}
                    <div class="text-center d-none d-md-block" style="min-width:70px">
                        <div class="text-muted" style="font-size:.7rem">CPU / RAM</div>
                        <div class="fw-semibold small">
                            {{ $srv['cpu'] !== null ? round($srv['cpu']) . '%' : '—' }}
                            /
                            {{ $srv['ram'] !== null ? round($srv['ram']) . '%' : '—' }}
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <span class="badge bg-{{ $sc }} bg-opacity-{{ $srv['status'] === 'online' ? '100' : '75' }} rounded-pill"
                          style="font-size:.75rem">
                        {{ ['online' => 'Operacional', 'degraded' => 'Degradado', 'offline' => 'Offline', 'unknown' => 'Desconhecido'][$srv['status']] ?? ucfirst($srv['status']) }}
                    </span>
                </div>

                @if($srv['uptime'] || $srv['checked_at'])
                <div class="text-muted mt-2 d-flex gap-3 flex-wrap" style="font-size:.72rem">
                    @if($srv['uptime'])
                    <span><i class="bi bi-clock me-1"></i>Uptime: {{ $srv['uptime'] }}</span>
                    @endif
                    @if($srv['checked_at'])
                    <span><i class="bi bi-arrow-repeat me-1"></i>Verificado {{ $srv['checked_at'] }}</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach

        @if($servers->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-server fs-1 d-block mb-2 opacity-25"></i>
            Nenhum servidor monitorado no momento.
        </div>
        @endif
    </div>

    {{-- Rodapé --}}
    <div class="text-center mt-5 text-muted small">
        <p class="mb-1">&copy; {{ date('Y') }} {{ config('app.name') }} — Todos os direitos reservados</p>
        <p class="mb-0">
            <a href="{{ url('/') }}" class="text-muted text-decoration-none me-3">Início</a>
            <a href="{{ url('/cliente') }}" class="text-muted text-decoration-none me-3">Área do Cliente</a>
            <a href="{{ route('status.api') }}" class="text-muted text-decoration-none">API JSON</a>
        </p>
    </div>
</div>

<script>
// Auto-refresh a cada 30s
let cd = 30;
const cdEl = document.getElementById('cdSecs');
const luEl = document.getElementById('lastUpdated');

setInterval(() => {
    cd--;
    if (cdEl) cdEl.textContent = cd;
    if (cd <= 0) {
        cd = 30;
        fetch('{{ route("status.api") }}')
            .then(r => r.json())
            .then(data => {
                if (luEl) luEl.textContent = 'Atualizado agora';
                data.servers.forEach(srv => {
                    const card = document.querySelector('[data-server-id="' + srv.id + '"]');
                    if (!card) return;
                    const dot = card.querySelector('.status-dot');
                    if (dot) { dot.className = 'status-dot ' + srv.status; }
                });
            })
            .catch(() => {});
    }
}, 1000);
</script>
</body>
</html>
