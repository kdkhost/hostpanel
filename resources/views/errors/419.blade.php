<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 — Sessão expirada</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f0f9ff 0%, #dbeafe 100%); font-family: 'Segoe UI', sans-serif; }
        .error-code { font-size: 8rem; font-weight: 800; line-height: 1; color: #2563eb; }
        .error-card { max-width: 520px; text-align: center; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">419</div>
        <h2 class="fw-bold mb-3">Sessão Expirada</h2>
        <p class="text-muted mb-4">Sua sessão expirou. Por favor, recarregue a página e tente novamente.</p>
        <div class="d-flex gap-2 justify-content-center">
            <button onclick="location.reload()" class="btn btn-primary"><i class="bi bi-arrow-clockwise me-1"></i>Recarregar</button>
            <a href="{{ url('/') }}" class="btn btn-outline-secondary"><i class="bi bi-house me-1"></i>Página Inicial</a>
        </div>
    </div>
</body>
</html>
