<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manutenção — {{ config('app.name', 'HostPanel') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%); font-family: 'Segoe UI', sans-serif; color: #fff; }
        .maint-card { max-width: 560px; text-align: center; }
        .gear-icon { font-size: 5rem; animation: spin 4s linear infinite; display: inline-block; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .countdown { font-size: 1.5rem; font-weight: 700; }
    </style>
</head>
<body>
    <div class="maint-card">
        <div class="gear-icon mb-3"><i class="bi bi-gear-wide-connected"></i></div>
        <h1 class="fw-bold mb-3">Em Manutenção</h1>
        <p class="text-white-50 mb-4 fs-5">
            {{ $exception->getMessage() ?: 'Estamos realizando melhorias no sistema. Voltaremos em breve!' }}
        </p>
        <div class="bg-white bg-opacity-10 rounded-3 p-3 mb-4">
            <p class="mb-1 small text-white-50">Previsão de retorno:</p>
            <div class="countdown" id="countdown">Calculando...</div>
        </div>
        <div class="d-flex gap-2 justify-content-center">
            <button onclick="location.reload()" class="btn btn-light"><i class="bi bi-arrow-clockwise me-1"></i>Tentar Novamente</button>
        </div>
        <p class="mt-4 text-white-50 small">
            <i class="bi bi-envelope me-1"></i>Dúvidas? Entre em contato com o suporte.
        </p>
    </div>
    <script>
        // Countdown simples — estima 30min a partir do carregamento
        (function(){
            const end = new Date(Date.now() + 30*60*1000);
            const el = document.getElementById('countdown');
            function tick(){
                const diff = end - Date.now();
                if(diff<=0){el.textContent='A qualquer momento!';return;}
                const m=Math.floor(diff/60000),s=Math.floor((diff%60000)/1000);
                el.textContent=`${m}min ${s}s`;
                requestAnimationFrame(tick);
            }
            tick();
        })();
    </script>
</body>
</html>
