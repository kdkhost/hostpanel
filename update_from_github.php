<?php
/**
 * HostPanel — Script de Atualização Remota
 * 
 * Baixa o código mais recente do GitHub e atualiza os arquivos críticos.
 * Execute via SSH: php update_from_github.php
 * 
 * Após execução, acesse: https://seudominio.com/install
 */

echo "==========================================================\n";
echo "  HostPanel — Atualização do Código a partir do GitHub\n";
echo "==========================================================\n\n";

$baseDir = __DIR__;
$repo = 'kdkhost/hostpanel';
$branch = 'master';

// Arquivos que precisam ser atualizados
$files = [
    'app/Http/Controllers/InstallerController.php',
    'app/Services/ThemeManager.php',
    'app/Http/Middleware/InstallerCheck.php',
    'app/Providers/ThemeServiceProvider.php',
    '.env.example',
    'resources/views/installer/index.blade.php',
    'resources/views/installer/complete.blade.php',
    'bootstrap/app.php',
];

echo "[1/5] Baixando arquivos atualizados do GitHub...\n";

$updated = 0;
$errors = 0;

foreach ($files as $file) {
    $url = "https://raw.githubusercontent.com/{$repo}/{$branch}/{$file}";
    $targetPath = $baseDir . '/' . $file;
    
    // Garantir que o diretório existe
    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Backup
    if (file_exists($targetPath)) {
        copy($targetPath, $targetPath . '.bak');
    }
    
    // Download
    $content = @file_get_contents($url);
    if ($content === false) {
        // Tentar com curl
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'HostPanel-Updater/1.0');
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                $content = false;
            }
        }
    }
    
    if ($content !== false && strlen($content) > 10) {
        file_put_contents($targetPath, $content);
        echo "   ✓ {$file}\n";
        $updated++;
    } else {
        echo "   ✗ {$file} (falha no download)\n";
        $errors++;
    }
}

echo "\n   Atualizados: {$updated} | Erros: {$errors}\n\n";

echo "[2/5] Resetando estado de instalação...\n";
$installedFile = $baseDir . '/storage/installed';
if (file_exists($installedFile)) {
    unlink($installedFile);
    echo "   ✓ Arquivo storage/installed removido\n";
} else {
    echo "   ✓ Já estava limpo\n";
}

echo "\n[3/5] Criando .env a partir do .env.example...\n";
$envFile = $baseDir . '/.env';
$envExample = $baseDir . '/.env.example';

// Backup do .env atual
if (file_exists($envFile)) {
    copy($envFile, $baseDir . '/.env.backup.' . date('YmdHis'));
    echo "   ✓ Backup do .env atual criado\n";
}

// Copiar .env.example como novo .env
if (file_exists($envExample)) {
    copy($envExample, $envFile);
    
    // Gerar APP_KEY
    $key = 'base64:' . base64_encode(random_bytes(32));
    $env = file_get_contents($envFile);
    $env = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, $env);
    
    // Garantir que session e cache estão como file
    $env = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $env);
    $env = preg_replace('/^CACHE_STORE=.*/m', 'CACHE_STORE=file', $env);
    
    // Garantir installer habilitado
    $env = preg_replace('/^INSTALLER_ENABLED=.*/m', 'INSTALLER_ENABLED=true', $env);
    $env = preg_replace('/^INSTALLED=.*/m', 'INSTALLED=false', $env);
    
    file_put_contents($envFile, $env);
    echo "   ✓ .env criado com APP_KEY gerada\n";
} else {
    echo "   ✗ .env.example não encontrado!\n";
}

echo "\n[4/5] Verificando permissões...\n";
$dirs = ['storage', 'storage/framework', 'storage/framework/sessions', 'storage/framework/views', 'storage/framework/cache', 'storage/logs', 'bootstrap/cache'];
foreach ($dirs as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "   ✓ Criado: {$dir}/\n";
    }
    @chmod($path, 0755);
}

echo "\n[5/5] Limpando caches...\n";
// Limpar cache de views compiladas
$viewsDir = $baseDir . '/storage/framework/views';
if (is_dir($viewsDir)) {
    foreach (glob($viewsDir . '/*') as $f) {
        @unlink($f);
    }
}
// Limpar cache de config
$cacheDir = $baseDir . '/bootstrap/cache';
foreach (['config.php', 'routes-v7.php', 'services.php', 'packages.php'] as $cf) {
    @unlink($cacheDir . '/' . $cf);
}
echo "   ✓ Caches limpos\n";

echo "\n==========================================================\n";
echo "  ✅ ATUALIZAÇÃO CONCLUÍDA!\n";
echo "==========================================================\n\n";
echo "Agora acesse no navegador:\n";
echo "  → https://seudominio.com/install\n\n";
echo "O instalador vai pedir:\n";
echo "  1. Verificar requisitos\n";
echo "  2. Dados do banco de dados (host, porta, nome, usuário, senha)\n";
echo "  3. Nome e URL do sistema\n";
echo "  4. Dados do administrador (nome, email, senha)\n";
echo "  5. Clique em 'Instalar Agora'\n\n";
echo "Isso criará todas as tabelas, seeds e o superadmin automaticamente.\n\n";

// Auto-deletar este script por segurança
echo "⚠️  Este script será auto-deletado por segurança.\n";
@unlink(__FILE__);
