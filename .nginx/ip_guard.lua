-- ============================================================================
-- ip_guard.lua — Script Lua para OpenResty/Nginx
-- Verifica a blacklist de IPs no banco akti_master antes de processar a request.
-- Se o IP estiver bloqueado (is_active=1 e não expirado), retorna 403 imediato.
--
-- Instalação:
--   1. Copiar para /etc/nginx/conf.d/ip_guard.lua
--   2. Adicionar no bloco server do Nginx:
--        access_by_lua_file /etc/nginx/conf.d/ip_guard.lua;
--   3. Configurar o bloco upstream mysql (ver snippet no nginx config)
--
-- Requisitos:
--   - OpenResty (Nginx compilado com ngx_http_lua_module)
--   - lua-resty-mysql (já incluído no OpenResty)
--   - Usuário MySQL akti_guard (somente leitura em ip_blacklist)
--
-- Arquivo de migração: sql/update_20260309_ip_blacklist.sql
-- ============================================================================

local mysql  = require "resty.mysql"

-- ─── Configuração ──────────────────────────────────────────────────
local DB_HOST     = os.getenv("AKTI_GUARD_DB_HOST")     or "127.0.0.1"
local DB_PORT     = tonumber(os.getenv("AKTI_GUARD_DB_PORT") or "3306")
local DB_NAME     = os.getenv("AKTI_GUARD_DB_NAME")     or "akti_master"
local DB_USER     = os.getenv("AKTI_GUARD_DB_USER")     or "akti_guard"
local DB_PASSWORD = os.getenv("AKTI_GUARD_DB_PASSWORD") or "GuardR3ad0nly!@2026"

-- Timeout em milissegundos para conexão e query
local DB_TIMEOUT  = 1000  -- 1 segundo

-- Cache local em shared dict (evita consultar o banco a cada request)
-- Requer no nginx.conf: lua_shared_dict ip_blacklist_cache 10m;
local CACHE_TTL   = 60    -- segundos de cache para resultado positivo (bloqueado)
local CACHE_NEG_TTL = 10  -- segundos de cache para resultado negativo (não bloqueado)

-- ─── IPs que nunca devem ser bloqueados (whitelist) ────────────────
local WHITELIST = {
    ["127.0.0.1"] = true,
    ["::1"]       = true,
}

-- ─── Funções auxiliares ────────────────────────────────────────────

--- Obtém o IP real do visitante (respeita headers de proxy reverso)
local function get_client_ip()
    -- CloudFlare
    local ip = ngx.var.http_cf_connecting_ip
    if ip and ip ~= "" then return ip end

    -- Proxy genérico (X-Forwarded-For: primeiro IP da cadeia)
    local xff = ngx.var.http_x_forwarded_for
    if xff and xff ~= "" then
        ip = xff:match("^([^,]+)")
        if ip then return ip:match("^%s*(.-)%s*$") end  -- trim
    end

    -- X-Real-IP
    ip = ngx.var.http_x_real_ip
    if ip and ip ~= "" then return ip end

    -- Fallback
    return ngx.var.remote_addr
end

--- Consulta o cache local (shared dict)
local function check_cache(ip)
    local cache = ngx.shared.ip_blacklist_cache
    if not cache then return nil end

    local val, flags = cache:get(ip)
    if val ~= nil then
        -- val = 1 → bloqueado, val = 0 → não bloqueado
        return val == 1
    end
    return nil  -- cache miss
end

--- Grava no cache local
local function set_cache(ip, is_blocked)
    local cache = ngx.shared.ip_blacklist_cache
    if not cache then return end

    local ttl = is_blocked and CACHE_TTL or CACHE_NEG_TTL
    cache:set(ip, is_blocked and 1 or 0, ttl)
end

--- Consulta o banco de dados
local function check_database(ip)
    local db, err = mysql:new()
    if not db then
        ngx.log(ngx.ERR, "[IpGuard/Lua] Falha ao criar instância MySQL: ", err)
        return false  -- fail-open
    end

    db:set_timeout(DB_TIMEOUT)

    local ok, err, errcode, sqlstate = db:connect({
        host     = DB_HOST,
        port     = DB_PORT,
        database = DB_NAME,
        user     = DB_USER,
        password = DB_PASSWORD,
        charset  = "utf8mb4",
        max_packet_size = 1024 * 1024,
    })

    if not ok then
        ngx.log(ngx.ERR, "[IpGuard/Lua] Falha ao conectar ao MySQL: ", err)
        return false  -- fail-open
    end

    -- Usa ngx.quote_sql_str para prevenir SQL injection
    local quoted_ip = ngx.quote_sql_str(ip)

    local query = string.format(
        "SELECT 1 FROM ip_blacklist WHERE ip_address = %s AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1",
        quoted_ip
    )

    local res, err, errcode, sqlstate = db:query(query)
    if not res then
        ngx.log(ngx.ERR, "[IpGuard/Lua] Falha na query: ", err)
        db:close()
        return false  -- fail-open
    end

    -- Devolve a conexão para o pool (keepalive)
    db:set_keepalive(10000, 10)  -- 10s idle, máx 10 conexões no pool

    return #res > 0
end

-- ─── Execução principal ────────────────────────────────────────────

local ip = get_client_ip()

-- Whitelist: nunca bloqueia
if WHITELIST[ip] then
    return
end

-- 1. Verifica cache
local cached = check_cache(ip)
if cached ~= nil then
    if cached then
        ngx.log(ngx.WARN, "[IpGuard/Lua] IP bloqueado (cache): ", ip)
        ngx.header["Retry-After"] = "3600"
        return ngx.exit(ngx.HTTP_FORBIDDEN)
    end
    return  -- não bloqueado (cache)
end

-- 2. Consulta banco
local is_blocked = check_database(ip)
set_cache(ip, is_blocked)

if is_blocked then
    ngx.log(ngx.WARN, "[IpGuard/Lua] IP bloqueado (db): ", ip)
    ngx.header["Retry-After"] = "3600"
    return ngx.exit(ngx.HTTP_FORBIDDEN)
end
