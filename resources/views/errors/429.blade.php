<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 — Muitas requisições</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%); font-family: 'Segoe UI', sans-serif; }
        .error-code { font-size: 8rem; font-weight: 800; line-height: 1; color: #ea580c; }
        .error-card { max-width: 520px; text-align: center; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">429</div>
        <h2 class="fw-bold mb-3">Muitas Requisições</h2>
        <p class="text-muted mb-4">Você fez muitas requisições em pouco tempo. Aguarde alguns segundos e tente novamente.</p>
        <div class="d-flex gap-2 justify-content-center">
            <button onclick="setTimeout(()=>location.reload(),2000)" class="btn btn-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Aguardar e Recarregar</button>
        </div>
    </div>
</body>
</html>
