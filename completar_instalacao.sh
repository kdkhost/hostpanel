#!/bin/bash
# =============================================================================
#  Script para completar instalação do HostPanel manualmente
#  Executa migrations e cria superadmin diretamente
# =============================================================================

echo "=========================================="
echo "  COMPLETAR INSTALAÇÃO HOSTPANEL"
echo "=========================================="
echo ""

cd /home/whmcsnano/public_html/

# Verificar se .env existe
if [ ! -f ".env" ]; then
    echo "❌ ERRO: Arquivo .env não encontrado!"
    exit 1
fi

echo "[1/5] Verificando .env..."
# Extrair dados do .env
DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2)
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2)

echo "      Banco: $DB_DATABASE"
echo "      Usuário: $DB_USERNAME"
echo ""

# Testar conexão MySQL
echo "[2/5] Testando conexão MySQL..."
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" 2>/dev/null
if [ $? -ne 0 ]; then
    echo "❌ ERRO: Não consegui conectar ao MySQL!"
    echo "      Verifique se a senha no .env está correta"
    echo ""
    echo "Para editar a senha: nano /home/whmcsnano/public_html/.env"
    exit 1
fi
echo "      ✓ Conexão OK"
echo ""

# Remover arquivo installed (se existir)
echo "[3/5] Resetando status de instalação..."
rm -f storage/installed
echo "      ✓ Resetado"
echo ""

# Rodar migrations
echo "[4/5] Criando tabelas no banco de dados..."
php artisan migrate --force 2>&1
if [ $? -ne 0 ]; then
    echo "❌ ERRO: Falha ao criar tabelas!"
    echo "      Verifique o erro acima"
    exit 1
fi
echo "      ✓ Tabelas criadas"
echo ""

# Rodar seeders
echo "[5/5] Populando dados iniciais..."
php artisan db:seed --force 2>&1
echo "      ✓ Dados iniciais inseridos"
echo ""

# Criar link do storage
php artisan storage:link 2>/dev/null || true

# Marcar como instalado
touch storage/installed
date > storage/installed

echo "=========================================="
echo "  ✅ INSTALAÇÃO COMPLETA!"
echo "=========================================="
echo ""
echo "Acesse o sistema:"
echo "  Admin: https://whmcs.nano-servidor.rio.br/admin/entrar"
echo "  Cliente: https://whmcs.nano-servidor.rio.br/cliente/entrar"
echo ""
echo "⚠️  Crie o superadmin manualmente no banco ou via tela de registro"
echo ""
