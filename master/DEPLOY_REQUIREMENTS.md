# Akti Master Admin — Requisitos de Deploy (VPS Debian)

## Visão Geral dos Módulos

| Módulo | Arquivos | Status |
|--------|----------|--------|
| Backup | `models/Backup.php`, `controllers/BackupController.php`, `views/backup/index.php` | ✅ Completo |
| Logs Nginx | `models/NginxLog.php`, `controllers/LogController.php`, `views/logs/index.php` | ✅ Completo |
| Git (Versionamento) | `models/GitVersion.php`, `controllers/GitController.php`, `views/git/index.php` | ✅ Completo |
| Cadastro de Usuários | `models/TenantClient.php`, `controllers/ClientController.php` | ✅ Corrigido |

---

## 1. Permissões e Sudoers

O PHP roda como o usuário `www-data` no Nginx. Os módulos de Backup, Logs e Git precisam de permissões especiais.

### 1.1 Backup — `/bin/bkp`

```bash
# Permitir www-data executar o script de backup sem senha
echo 'www-data ALL=(ALL) NOPASSWD: /bin/bkp' | sudo tee /etc/sudoers.d/akti-backup
sudo chmod 440 /etc/sudoers.d/akti-backup

# Tornar a pasta de backups legível
sudo chmod o+rX /bkp

# Permitir exclusão (se necessário)
# Opção A: Grupo compartilhado
sudo chgrp www-data /bkp
sudo chmod g+rw /bkp

# Opção B: Sudoers para rm (mais restrito)
echo 'www-data ALL=(ALL) NOPASSWD: /bin/rm /bkp/*' | sudo tee -a /etc/sudoers.d/akti-backup
```

### 1.2 Logs Nginx

```bash
# Adicionar www-data ao grupo adm (grupo padrão dos logs)
sudo usermod -aG adm www-data

# Ou definir permissão direta
sudo chmod o+r /var/log/nginx/error.log
sudo chmod o+r /var/log/nginx/access.log

# Para logs rotacionados (.gz)
sudo chmod -R o+r /var/log/nginx/
```

### 1.3 Git — Versionamento

```bash
# Permitir www-data executar git nos diretórios dos clientes
# O Git precisa de acesso de leitura/escrita nos repos
# Exemplo para /var/www/clientes/
sudo chgrp -R www-data /var/www/
sudo chmod -R g+rwX /var/www/

# Se necessário, sudoers para git:
echo 'www-data ALL=(ALL) NOPASSWD: /usr/bin/git' | sudo tee /etc/sudoers.d/akti-git
sudo chmod 440 /etc/sudoers.d/akti-git
```

---

## 2. Função `exec()` no PHP

Os módulos de Backup, Logs e Git dependem de `exec()`. Verificar que **não está desabilitado** no php.ini:

```ini
; php.ini (FPM ou CLI)
; Verificar que exec NÃO está na lista de disable_functions
disable_functions = 
```

Caminho típico no Debian:
```bash
# PHP-FPM
sudo nano /etc/php/8.2/fpm/php.ini
# Buscar "disable_functions" e remover "exec" se estiver listado
sudo systemctl restart php8.2-fpm
```

---

## 3. Configuração Opcional (`config.php`)

Constantes opcionais que podem ser definidas em `app/config/config.php`:

```php
// Pasta de backups (padrão: /bkp)
define('BACKUP_PATH', '/bkp');

// Comando de backup (padrão: sudo /bin/bkp)
define('BACKUP_COMMAND', 'sudo /bin/bkp');
```

---

## 4. Banco de Dados

- O banco `akti_master` deve existir com as tabelas definidas em `multi_tenant_master.sql`
- Tabela `admin_users` com campo `password` usando `password_hash()` (bcrypt)
- Tabela `admin_logs` para auditoria das ações de backup/logs

---

## 5. Funcionalidades por Módulo

### Backup
- ✅ Executar backup via `sudo /bin/bkp` (AJAX + POST)
- ✅ Listar arquivos em `/bkp` (scandir + fallback exec ls)
- ✅ Download de arquivos (chunked para > 50MB)
- ✅ Exclusão com confirmação dupla (nome do arquivo + senha admin)
- ✅ Diagnóstico de permissões
- ✅ Log de auditoria para todas as ações

### Logs Nginx
- ✅ Listar logs disponíveis
- ✅ Ler conteúdo do log (últimas N linhas)
- ✅ Busca por texto no log
- ✅ Análise de erros (agrupamento por tipo)
- ✅ Download de arquivo de log

### Git (Versionamento)
- ✅ Listar repos dos clientes com status
- ✅ Fetch / Fetch All
- ✅ Pull / Pull All
- ✅ Force Reset (hard reset ao remote)
- ✅ Detalhes do repositório (branch, último commit, status)
- ✅ Checkout de branch
- ✅ Diagnóstico

### Cadastro de Usuários
- ✅ Criação de usuário tenant com senha bcrypt
- ✅ Compatibilidade com estrutura SQL do template

---

## 6. Checklist de Deploy

- [ ] Configurar sudoers para `/bin/bkp`
- [ ] Criar pasta `/bkp` com permissões corretas
- [ ] Verificar `exec()` habilitado no PHP-FPM
- [ ] Adicionar `www-data` ao grupo `adm` para logs
- [ ] Verificar permissões dos repositórios Git
- [ ] Importar `multi_tenant_master.sql` no banco
- [ ] Testar página de backup (listar, executar, download, excluir)
- [ ] Testar página de logs (listar, ler, buscar, download)
- [ ] Testar página de Git (fetch, pull, status)
- [ ] Testar cadastro de usuário tenant
