<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sem conexão — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 480px;
        }
        .icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
            opacity: .8;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: .8; }
            50% { opacity: .4; }
        }
        h1 { font-size: 2rem; font-weight: 800; margin-bottom: .75rem; }
        p { color: rgba(255,255,255,.7); font-size: 1.125rem; line-height: 1.6; margin-bottom: 2rem; }
        .btn {
            display: inline-block;
            background: white;
            color: #1a56db;
            font-weight: 700;
            font-size: 1rem;
            padding: .875rem 2rem;
            border-radius: .875rem;
            text-decoration: none;
            transition: opacity .2s;
            cursor: pointer;
            border: none;
        }
        .btn:hover { opacity: .9; }
        .cached-info {
            margin-top: 2rem;
            background: rgba(255,255,255,.08);
            border-radius: 1rem;
            padding: 1.25rem;
            font-size: .875rem;
            color: rgba(255,255,255,.6);
        }
        .cached-info strong { color: white; }
    </style>
</head>
<body>
    <div class="container">
        <span class="icon">📡</span>
        <h1>Sem Conexão</h1>
        <p>Você está offline no momento. Verifique sua conexão com a internet e tente novamente.</p>
        <button class="btn" onclick="window.location.reload()">Tentar Novamente</button>
        <div class="cached-info">
            <strong>{{ config('app.name') }}</strong><br>
            Algumas páginas podem estar disponíveis em modo offline graças ao cache do aplicativo.
            <div style="margin-top:.75rem">
                <a href="/cliente/dashboard" style="color:rgba(255,255,255,.8);text-decoration:underline;font-size:.8125rem">→ Tentar acessar o dashboard</a>
            </div>
        </div>
    </div>
    <script>
        // Auto-retry when connection is restored
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>
</html>
