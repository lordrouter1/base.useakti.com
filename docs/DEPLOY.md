# Akti — Guia de Deploy

> Documentação de deploy para ambientes de staging e produção.

## 1. Requisitos do Servidor

### Software

| Componente | Versão Mínima | Recomendado |
|---|---|---|
| PHP | 7.4 | 8.1+ |
| MySQL / MariaDB | 5.7 / 10.3 | 8.0 / 10.6+ |
| Node.js | 18 | 20 LTS |
| Apache / Nginx | 2.4 / 1.18 | 2.4 / 1.24+ |
| Composer | 2.0 | 2.7+ |

### Extensões PHP Obrigatórias

```
pdo_mysql, mbstring, json, openssl, curl, gd, zip, xml, bcmath
```

### Extensões PHP Recomendadas

```
opcache, intl, redis (se usar cache distribuído)
```

## 2. Variáveis de Ambiente

Copie o `.env.example` e configure:

```bash
cp .env.example .env
```

### Variáveis Obrigatórias

```env
# ── App ──
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.akti.com.br

# ── Banco de Dados ──
DB_HOST=localhost
DB_PORT=3306
DB_USER=akti_prod
DB_PASS=SENHA_FORTE_AQUI
DB_NAME=akti_production

# ── API Node.js ──
AKTI_API_URL=http://localhost:3000
API_JWT_SECRET=SEU_JWT_SECRET_LONGO_E_ALEATÓRIO

# ── Sessão ──
SESSION_LIFETIME=120  # minutos
```

### Variáveis Opcionais

```env
# ── Sentry (Error Tracking) ──
SENTRY_DSN=https://xxx@sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.1

# ── Email (SMTP) ──
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=noreply@akti.com.br
MAIL_PASS=app_password_here

# ── Backup ──
BACKUP_RETENTION_DAILY=7
BACKUP_RETENTION_WEEKLY=4
```

## 3. Processo de Deploy

### 3.1 Primeiro Deploy (Setup Inicial)

```bash
# 1. Clone o repositório
git clone git@github.com:akti/akti-gestao.git /var/www/akti
cd /var/www/akti

# 2. Instalar dependências PHP (sem dev)
composer install --no-dev --optimize-autoloader

# 3. Instalar dependências da API Node.js
cd api && npm install --production && cd ..

# 4. Configurar ambiente
cp .env.example .env
nano .env  # editar variáveis

# 5. Criar diretórios necessários
mkdir -p storage/logs storage/backups/daily storage/backups/weekly
chmod -R 775 storage assets/uploads

# 6. Executar migrations SQL
for f in sql/update_*.sql; do
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < "$f"
    echo "Executado: $f"
done

# 7. Configurar Apache/Nginx (ver seção 4)

# 8. Iniciar API Node.js (ver seção 5)
```

### 3.2 Deploy de Atualização

```bash
cd /var/www/akti

# 1. Pull das últimas alterações
git pull origin main

# 2. Atualizar dependências
composer install --no-dev --optimize-autoloader

# 3. Executar novas migrations
# (Verificar se há novos arquivos em /sql)
for f in sql/update_*.sql; do
    # Verificar se já foi executado (manter controle externo)
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < "$f" 2>/dev/null || true
done

# 4. Atualizar API Node.js (se necessário)
cd api && npm install --production && cd ..

# 5. Restart serviços
sudo systemctl restart apache2  # ou nginx
sudo systemctl restart akti-api  # serviço Node.js

# 6. Limpar cache de OPcache (se habilitado)
php -r "opcache_reset();" 2>/dev/null || true
```

## 4. Configuração do Servidor Web

### Apache (VirtualHost)

```apache
<VirtualHost *:80>
    ServerName akti.seu-dominio.com.br
    DocumentRoot /var/www/akti

    <Directory /var/www/akti>
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/akti-error.log
    CustomLog ${APACHE_LOG_DIR}/akti-access.log combined

    # Proxy para API Node.js
    ProxyPreserveHost On
    ProxyPass /api http://localhost:3000
    ProxyPassReverse /api http://localhost:3000
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name akti.seu-dominio.com.br;
    root /var/www/akti;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # API Node.js
    location /api {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Bloquear acesso a arquivos sensíveis
    location ~ /\.(env|git|github) {
        deny all;
    }

    location ~ /(storage|vendor|scripts|sql|tests|docs)/ {
        deny all;
    }
}
```

## 5. API Node.js como Serviço

Criar systemd service (`/etc/systemd/system/akti-api.service`):

```ini
[Unit]
Description=Akti API Node.js
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/akti/api
ExecStart=/usr/bin/node src/app.js
Restart=on-failure
RestartSec=10
Environment=NODE_ENV=production
Environment=PORT=3000

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable akti-api
sudo systemctl start akti-api
```

## 6. Backup Automatizado

```bash
# Tornar executável
chmod +x scripts/backup.sh

# Configurar cron (2h da manhã, todos os dias)
crontab -e
# Adicionar:
0 2 * * * /var/www/akti/scripts/backup.sh >> /var/www/akti/storage/logs/backup.log 2>&1
```

## 7. Checklist Pós-Deploy

- [ ] Acessar a URL e verificar se a tela de login carrega
- [ ] Fazer login com conta admin
- [ ] Verificar se o dashboard carrega sem erros
- [ ] Abrir DevTools e verificar console (sem erros JS)
- [ ] Verificar `storage/logs/` — sem erros críticos recentes
- [ ] Testar criação de pedido
- [ ] Verificar se a API Node.js responde: `curl http://localhost:3000/health`
- [ ] Verificar HTTPS (certificado SSL válido)
- [ ] Confirmar que `/storage`, `/vendor`, `/sql` não são acessíveis externamente
- [ ] Testar health check: `curl https://seu-dominio/?page=health&action=check`
- [ ] Verificar se o Sentry está recebendo eventos (se configurado)
- [ ] Verificar se o backup cron está agendado: `crontab -l`

## 8. Monitoramento

### 8.1 Health Check Endpoint

O sistema inclui endpoints de health check para monitoramento:

```bash
# Ping simples (para UptimeRobot, Pingdom, etc.)
curl https://seu-dominio/?page=health&action=ping
# Retorna: {"status":"ok","timestamp":"2026-03-31T10:00:00-03:00"}

# Health check detalhado (status de todos os componentes)
curl https://seu-dominio/?page=health&action=check
# Retorna: status de DB, filesystem, backup, disco, extensões PHP
```

**Código de resposta:**
- `200` — Tudo saudável
- `503` — Algum componente degradado

### 8.2 Sentry (Error Tracking)

Para ativar o rastreamento de erros com Sentry:

```bash
# 1. Instalar SDK
composer require sentry/sentry

# 2. Configurar no .env
SENTRY_DSN=https://seu-dsn@sentry.io/projeto
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
```

O middleware `SentryMiddleware` captura automaticamente:
- Exceções não tratadas
- Erros fatais do PHP
- Erros de runtime (warnings, notices)

### 8.3 Logging Estruturado

Logs são gravados em JSON em `storage/logs/`:

```
storage/logs/
  general-2026-03-31.log
  security-2026-03-31.log
  financial-2026-03-31.log
  api-2026-03-31.log
```

Cada linha é um JSON com: `timestamp`, `level`, `channel`, `message`, `context`, `tenant_id`, `user_id`.

### 8.4 Verificação de Backup

O health check verifica automaticamente se há backups nas últimas 48h.
Para verificar manualmente:

```bash
ls -la storage/backups/daily/
```

## 9. Troubleshooting

| Problema | Solução |
|---|---|
| Tela branca | Verificar `storage/logs/error_*.log` e PHP error log |
| 500 Internal Server Error | Verificar permissões de `storage/` e `.htaccess` |
| API não conecta | Verificar se o serviço `akti-api` está rodando |
| Sessão expira rápido | Ajustar `SESSION_LIFETIME` no `.env` |
| CSS/JS desatualizado | Limpar cache do navegador (Ctrl+Shift+R) |
| Erro de permissão | `chown -R www-data:www-data storage assets/uploads` |
