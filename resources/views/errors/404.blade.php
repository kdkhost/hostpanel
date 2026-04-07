<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página não encontrada</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); font-family: 'Segoe UI', sans-serif; }
        .error-code { font-size: 8rem; font-weight: 800; line-height: 1; background: linear-gradient(135deg, #1a56db, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .error-card { max-width: 520px; text-align: center; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">404</div>
        <h2 class="fw-bold mb-3">Página não encontrada</h2>
        <p class="text-muted mb-4">A página que você procura não existe ou foi movida para outro endereço.</p>
        <div class="d-flex gap-2 justify-content-center">
            <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house me-1"></i>Página Inicial</a>
            <button onclick="history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</button>
        </div>
    </div>
</body>
</html>
