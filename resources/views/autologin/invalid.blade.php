<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if($reason === 'expired') Link de Acesso Expirado
        @elseif($reason === 'not_found') Link Inválido
        @else Acesso Indisponível
        @endif
        — {{ config('app.name') }}
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #faf5ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #1e293b;
        }
        .card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 8px 40px rgba(0,0,0,.08);
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .icon-wrap {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        .icon-wrap.expired  { background: #fef3c7; color: #d97706; }
        .icon-wrap.invalid  { background: #fee2e2; color: #dc2626; }
        .icon-wrap.inactive { background: #f1f5f9; color: #64748b; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: .75rem; }
        p  { color: #64748b; line-height: 1.6; font-size: .9375rem; }
        .detail {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            padding: .875rem 1.25rem;
            margin: 1.5rem 0;
            text-align: left;
            font-size: .875rem;
        }
        .detail dt { color: #94a3b8; font-weight: 500; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; }
        .detail dd { color: #1e293b; font-weight: 600; margin-top: .125rem; }
        .btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .75rem 1.5rem;
            border-radius: .75rem;
            font-weight: 600; font-size: .9375rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-outline { background: transparent; border: 1.5px solid #e2e8f0; color: #475569; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: center; margin-top: 1.5rem; }
        .brand { margin-bottom: 2rem; font-size: .875rem; color: #94a3b8; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">{{ config('app.name') }}</div>

    @if($reason === 'expired')
        <div class="icon-wrap expired"><i class="bi bi-clock-history"></i></div>
        <h1>Link de Acesso Expirado</h1>
        <p>Este link de acesso automático atingiu sua data de validade e não pode mais ser utilizado.</p>
        @if($expires_at)
        <dl class="detail">
            <dt>Expirou em</dt>
            <dd>{{ \Carbon\Carbon::parse($expires_at)->format('d/m/Y \à\s H:i') }}</dd>
            @if($service)
            <dt style="margin-top:.75rem">Serviço</dt>
            <dd>{{ $service->domain ?? $service->product?->name ?? "#{$service->id}" }}</dd>
            @endif
        </dl>
        @endif
        <p>Para obter um novo link de acesso, entre em contato com o suporte ou faça login na área do cliente.</p>

    @elseif($reason === 'not_found')
        <div class="icon-wrap invalid"><i class="bi bi-link-45deg"></i></div>
        <h1>Link Inválido</h1>
        <p>Este link de acesso não existe ou já foi removido. Verifique se o link está correto ou solicite um novo acesso.</p>

    @elseif($reason === 'service_inactive')
        <div class="icon-wrap inactive"><i class="bi bi-pause-circle"></i></div>
        <h1>Serviço Indisponível</h1>
        <p>
            @if(($service->status ?? '') === 'suspended')
                Este serviço está <strong>suspenso</strong>. Regularize as faturas pendentes para reativar o acesso.
            @else
                Este serviço não está ativo no momento. Por favor, entre em contato com o suporte.
            @endif
        </p>

    @elseif($reason === 'panel_error')
        <div class="icon-wrap inactive"><i class="bi bi-exclamation-triangle"></i></div>
        <h1>Erro ao Acessar o Painel</h1>
        <p>Não foi possível gerar o link de acesso ao painel de hospedagem. Tente novamente em instantes ou contate o suporte.</p>
        @if($message && config('app.debug'))
        <div class="detail"><dt>Detalhe técnico</dt><dd>{{ $message }}</dd></div>
        @endif

    @else
        <div class="icon-wrap invalid"><i class="bi bi-shield-x"></i></div>
        <h1>Acesso Negado</h1>
        <p>Não foi possível processar este link de acesso.</p>
    @endif

    <div class="actions">
        <a href="{{ route('client.login') }}" class="btn btn-primary">
            <i class="bi bi-person-circle"></i> Área do Cliente
        </a>
        @if(Route::has('client.tickets.create'))
        <a href="{{ route('client.tickets.create') }}" class="btn btn-outline">
            <i class="bi bi-headset"></i> Abrir Suporte
        </a>
        @endif
    </div>
</div>
</body>
</html>
