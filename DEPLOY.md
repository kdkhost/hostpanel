# 🚀 Deploy do HostPanel

Guia de instalação para cPanel/WHM com suporte a instalação na raiz (public_html) ou subdiretório.

---

## 📋 Requisitos do Servidor

- **PHP 8.4+** (o sistema requer recursos do PHP 8.4)
- **MySQL 8.0+** ou **MariaDB 10.6+**
- **Apache** com mod_rewrite habilitado
- **Composer** (para instalar dependências)
- **Extensões PHP:** pdo, pdo_mysql, mbstring, xml, curl, gd, zip, bcmath, openssl, intl

---

## 📦 Opção 1: Instalação em Subdiretório (recomendado para testes)

Estrutura final:
```
/public_html/
└── hostpanel/           ← seu diretório de instalação
    ├── .htaccess        ← redireciona para public/
    ├── public/          ← document root virtual
    ├── app/
    ├── resources/
    └── ...
```

URL de acesso: `https://seudominio.com/hostpanel/`

### Passos:

1. **Upload do ZIP:**
   ```bash
   # Descompacte na pasta desejada
   cd /home/usuario/public_html/
   unzip hostpanel-deploy.zip -d hostpanel/
   ```

2. **Configurar .htaccess:**
   ```bash
   cd hostpanel/
   cp .htaccess.subdirectory.example .htaccess
   
   # EDITAR o RewriteBase se necessário:
   # Se a URL for https://dominio.com/hostpanel/
   # RewriteBase /hostpanel/
   ```

3. **Instalar dependências:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Permissões:**
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

5. **Acessar instalador:**
   ```
   https://seudominio.com/hostpanel/install
   ```

---

## 📦 Opção 2: Instalação na Raiz (public_html)

Use esta opção se o HostPanel for o único/site principal da hospedagem.

Estrutura final:
```
/public_html/              ← document root real
├── .htaccess             ← redireciona para public/
├── public/               ← pasta do Laravel (será a raiz virtual)
│   └── index.php
├── app/
├── resources/
└── ...
```

URL de acesso: `https://seudominio.com/`

### Passos:

1. **Upload do ZIP:**
   ```bash
   # ATENÇÃO: Isso sobrescreverá arquivos existentes em public_html!
   cd /home/usuario/public_html/
   rm -rf *  # Limpar primeiro (CUIDADO!)
   unzip ~/hostpanel-deploy.zip
   ```

2. **Configurar .htaccess:**
   ```bash
   cp .htaccess.root.example .htaccess
   ```

3. **Instalar dependências:**
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan storage:link
   ```

4. **Permissões:**
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

5. **Acessar instalador:**
   ```
   https://seudominio.com/install
   ```

---

## 🔧 Configurando a Versão do PHP

O `.htaccess` incluído já força PHP 8.4:
```apache
AddHandler application/x-httpd-ea-php84 .php
```

### Se precisar usar outra versão:

| Versão | Diretiva |
|--------|----------|
| PHP 8.3 | `AddHandler application/x-httpd-ea-php83 .php` |
| PHP 8.4 | `AddHandler application/x-httpd-ea-php84 .php` |
| PHP 8.5 | `AddHandler application/x-httpd-ea-php85 .php` |

### Via cPanel MultiPHP Manager:
1. Acesse **cPanel → MultiPHP Manager**
2. Selecione o domínio/subdomínio
3. Escolha **PHP 8.4** (ea-php84)
4. Clique em **Apply**

---

## ⚠️ Configurações Importantes

### SSL/HTTPS (recomendado)
No `.htaccess.root.example`, descomente:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

### WWW vs Não-WWW
Para forçar não-www, descomente:
```apache
RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]
```

---

## 🗂️ Arquivos Incluídos

| Arquivo | Descrição |
|---------|-----------|
| `.htaccess.subdirectory.example` | Para instalação em pasta (ex: /hostpanel/) |
| `.htaccess.root.example` | Para instalação na raiz (public_html) |

---

## 🆘 Solução de Problemas

### Erro 500 - Internal Server Error
- Verifique se o `mod_rewrite` está habilitado
- Confira permissões: `storage/` e `bootstrap/cache/` devem ser graváveis

### Página branca após instalação
- Verifique logs em `storage/logs/`
- Confirme se `.env` foi criado pelo instalador

### "Arquivo não encontrado" para assets CSS/JS
- Verifique se `public/build/` existe (contém o build do Vite)
- Confirme se o `RewriteBase` está correto no .htaccess

### Erro de versão do PHP
- Verifique via SSH: `php -v`
- Se mostrar 8.2, confirme se o `AddHandler` do .htaccess está funcionando
- Alternativa: use **MultiPHP Manager** do cPanel

---

## 📚 Comandos Úteis

```bash
# Verificar versão do PHP
php -v

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Limpar cache Laravel
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Criar link do storage
php artisan storage:link

# Executar migrations
php artisan migrate --force
```

---

**Pronto para instalar!** 🎉
