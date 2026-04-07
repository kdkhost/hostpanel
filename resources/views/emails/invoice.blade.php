<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fatura #{{ $invoice->number }}</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; color: #334155; }
    .wrapper { max-width: 600px; margin: 0 auto; }
    .header { background: linear-gradient(135deg, #1e3a8a, #1a56db); color: white; padding: 32px 40px; border-radius: 12px 12px 0 0; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 800; }
    .header p { margin: 8px 0 0; opacity: .75; font-size: 14px; }
    .body { background: white; padding: 32px 40px; }
    .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; margin-bottom: 20px; }
    .status-paid    { background: #dcfce7; color: #15803d; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-overdue { background: #fee2e2; color: #991b1b; }
    h2 { font-size: 20px; color: #1e293b; margin: 0 0 8px; }
    p  { color: #475569; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #94a3b8; }
    .info-value { font-weight: 600; color: #1e293b; }
    .total-row { background: #f8fafc; padding: 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
    .total-label { font-size: 15px; color: #475569; font-weight: 600; }
    .total-value { font-size: 24px; font-weight: 900; color: #1e293b; }
    .btn { display: inline-block; background: #1a56db; color: white !important; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px; margin: 8px 0; }
    .footer { background: #f8fafc; padding: 24px 40px; border-radius: 0 0 12px 12px; border-top: 1px solid #e2e8f0; text-align: center; }
    .footer p { font-size: 13px; color: #94a3b8; margin: 0; }
    .items-table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 14px; }
    .items-table th { text-align: left; padding: 8px 12px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e2e8f0; }
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; color: #475569; }
    .items-table tr:last-child td { border-bottom: none; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>Sistema de Hospedagem</p>
    </div>
    <div class="body">
        <span class="status-badge status-{{ $invoice->status }}">
            {{ ['paid'=>'✅ PAGO','pending'=>'⏳ PENDENTE','overdue'=>'❌ EM ATRASO','cancelled'=>'🚫 CANCELADO'][$invoice->status] ?? strtoupper($invoice->status) }}
        </span>

        <h2>Fatura #{{ $invoice->number }}</h2>
        <p>Olá, {{ $invoice->client?->name }}! Aqui estão os detalhes da sua fatura.</p>

        <div class="info-row"><span class="info-label">Emissão</span><span class="info-value">{{ \Carbon\Carbon::parse($invoice->date_issued)->format('d/m/Y') }}</span></div>
        <div class="info-row"><span class="info-label">Vencimento</span><span class="info-value">{{ \Carbon\Carbon::parse($invoice->date_due)->format('d/m/Y') }}</span></div>
        @if($invoice->date_paid)
        <div class="info-row"><span class="info-label">Pago em</span><span class="info-value" style="color:#15803d">{{ \Carbon\Carbon::parse($invoice->date_paid)->format('d/m/Y') }}</span></div>
        @endif

        @if($invoice->items && $invoice->items->isNotEmpty())
        <table class="items-table">
            <thead><tr><th>Descrição</th><th style="text-align:right">Valor</th></tr></thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:right;font-weight:600">R$ {{ number_format($item->amount, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="total-row">
            <span class="total-label">Total</span>
            <span class="total-value">R$ {{ number_format($invoice->total, 2, ',', '.') }}</span>
        </div>

        @if($invoice->status === 'pending' || $invoice->status === 'overdue')
        <p style="text-align:center;margin:24px 0 8px">
            <a href="{{ url('/cliente/faturas/' . $invoice->id) }}" class="btn">Pagar Agora</a>
        </p>
        @elseif($invoice->status === 'paid')
        <p style="text-align:center;margin:24px 0 8px">
            <a href="{{ url('/cliente/faturas/' . $invoice->id) }}" class="btn">Ver Comprovante</a>
        </p>
        @endif
    </div>
    <div class="footer">
        <p>{{ config('app.name') }} &mdash; Hospedagem Profissional</p>
        <p style="margin-top:6px">Você recebeu este e-mail pois é cliente da {{ config('app.name') }}.</p>
    </div>
</div>
</body>
</html>
