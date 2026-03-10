<?php

class TenantManager
{
    private static $tenantConfig = null;

    public static function bootstrap(): void
    {
        if (self::$tenantConfig !== null) {
            return;
        }

        $defaultConfig = self::getDefaultTenantConfig();
        $host = self::getRequestHost();

        if ($host === '' || self::isLocalHost($host)) {
            self::$tenantConfig = $defaultConfig;
            self::storeTenantSession($defaultConfig, $host ?: 'localhost', false);
            return;
        }

        $subdomain = self::extractSubdomain($host);
        if ($subdomain === null) {
            self::$tenantConfig = $defaultConfig;
            self::storeTenantSession($defaultConfig, $host, false);
            return;
        }

        $tenant = self::findTenantBySubdomain($subdomain);
        if ($tenant === null) {
            self::$tenantConfig = $defaultConfig;
            self::storeTenantSession($defaultConfig, $subdomain, true, 'Subdomínio não cadastrado ou inativo.');
            return;
        }

        self::$tenantConfig = [
            'host' => $tenant['db_host'],
            'port' => isset($tenant['db_port']) ? (int) $tenant['db_port'] : 3306,
            'db_name' => $tenant['db_name'],
            'username' => $tenant['db_user'],
            'password' => $tenant['db_password'],
            'charset' => $tenant['db_charset'] ?: 'utf8mb4',
            'max_users' => isset($tenant['max_users']) ? (int) $tenant['max_users'] : null,
            'max_products' => isset($tenant['max_products']) ? (int) $tenant['max_products'] : null,
            'max_warehouses' => isset($tenant['max_warehouses']) ? (int) $tenant['max_warehouses'] : null,
            'max_price_tables' => isset($tenant['max_price_tables']) ? (int) $tenant['max_price_tables'] : null,
            'max_sectors' => isset($tenant['max_sectors']) ? (int) $tenant['max_sectors'] : null,
            'enabled_modules' => $tenant['enabled_modules'] ?? null,
        ];

        self::storeTenantSession(self::$tenantConfig, $subdomain, false, null, (int) $tenant['id']);
    }

    public static function getTenantConfig(): array
    {
        self::bootstrap();
        return self::$tenantConfig;
    }

    private static function getDefaultTenantConfig(): array
    {
        return [
            'host' => getenv('AKTI_DB_HOST') ?: 'localhost',
            'port' => (int) (getenv('AKTI_DB_PORT') ?: 3306),
            'db_name' => getenv('AKTI_DB_NAME') ?: 'akti_teste',
            'username' => getenv('AKTI_DB_USER') ?: 'akti_sis_usr',
            'password' => getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$',
            'charset' => getenv('AKTI_DB_CHARSET') ?: 'utf8mb4',
            'max_users' => null,
            'max_products' => null,
            'max_warehouses' => null,
            'max_price_tables' => null,
            'max_sectors' => null,
            'enabled_modules' => null,
        ];
    }

    private static function getMasterConfig(): array
    {
        return [
            'host' => getenv('AKTI_MASTER_DB_HOST') ?: getenv('AKTI_DB_HOST') ?: 'localhost',
            'port' => (int) (getenv('AKTI_MASTER_DB_PORT') ?: getenv('AKTI_DB_PORT') ?: 3306),
            'db_name' => getenv('AKTI_MASTER_DB_NAME') ?: 'akti_master',
            'username' => getenv('AKTI_MASTER_DB_USER') ?: getenv('AKTI_DB_USER') ?: 'akti_sis_usr',
            'password' => getenv('AKTI_MASTER_DB_PASS') ?: getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$',
            'charset' => getenv('AKTI_MASTER_DB_CHARSET') ?: 'utf8mb4',
        ];
    }

    private static function findTenantBySubdomain(string $subdomain): ?array
    {
        $master = self::getMasterConfig();

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $master['host'],
                $master['port'],
                $master['db_name'],
                $master['charset']
            );

            $conn = new PDO($dsn, $master['username'], $master['password']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = 'SELECT id, subdomain, db_host, db_port, db_name, db_user, db_password, db_charset, max_users, max_products, max_warehouses, max_price_tables, max_sectors, enabled_modules
                    FROM tenant_clients
                    WHERE subdomain = :subdomain AND is_active = 1
                    LIMIT 1';

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':subdomain', $subdomain);
            $stmt->execute();

            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
            return $tenant ?: null;
        } catch (PDOException $exception) {
            self::$tenantConfig = self::getDefaultTenantConfig();
            self::storeTenantSession(self::$tenantConfig, $subdomain, true, 'Falha ao consultar o banco master: ' . $exception->getMessage());
            return null;
        }
    }

    private static function getRequestHost(): string
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return '';
        }

        $host = strtolower(trim($_SERVER['HTTP_HOST']));
        $host = preg_replace('/:\\d+$/', '', $host);
        return $host;
    }

    private static function extractSubdomain(string $host): ?string
    {
        $baseDomain = getenv('AKTI_BASE_DOMAIN') ?: 'useakti.com';

        if ($host === $baseDomain || $host === 'www.' . $baseDomain) {
            return null;
        }

        if (substr($host, -strlen('.' . $baseDomain)) === '.' . $baseDomain) {
            $subdomain = substr($host, 0, -strlen('.' . $baseDomain));
            if ($subdomain !== '' && $subdomain !== 'www') {
                return $subdomain;
            }
        }

        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }

    private static function isLocalHost(string $host): bool
    {
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    private static function storeTenantSession(array $tenantConfig, string $tenantKey, bool $hasError, ?string $errorMessage = null, ?int $tenantId = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['tenant'] = [
            'id' => $tenantId,
            'key' => $tenantKey,
            'database' => $tenantConfig['db_name'],
            'has_error' => $hasError,
            'error_message' => $errorMessage,
            'max_users' => isset($tenantConfig['max_users']) ? (int) $tenantConfig['max_users'] : null,
            'max_products' => isset($tenantConfig['max_products']) ? (int) $tenantConfig['max_products'] : null,
            'max_warehouses' => isset($tenantConfig['max_warehouses']) ? (int) $tenantConfig['max_warehouses'] : null,
            'max_price_tables' => isset($tenantConfig['max_price_tables']) ? (int) $tenantConfig['max_price_tables'] : null,
            'max_sectors' => isset($tenantConfig['max_sectors']) ? (int) $tenantConfig['max_sectors'] : null,
            'enabled_modules' => $tenantConfig['enabled_modules'] ?? null,
        ];
    }

    /**
     * Returns the base upload directory for the current tenant.
     * Example: "assets/uploads/akti_cliente1/"
     * All file uploads must be stored under this path to isolate tenant data.
     */
    public static function getTenantUploadBase(): string
    {
        self::bootstrap();
        $dbName = self::$tenantConfig['db_name'] ?? 'default';
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $dbName);
        return 'assets/uploads/' . $safe . '/';
    }

    public static function getTenantLimit(string $limitKey): ?int
    {
        self::bootstrap();
        if (!isset(self::$tenantConfig[$limitKey])) {
            return null;
        }

        $value = self::$tenantConfig[$limitKey];
        if ($value === null || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    public static function enforceTenantSession(): void
    {
        self::bootstrap();

        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $currentTenant = $_SESSION['tenant']['key'] ?? null;
        $lockedTenant = $_SESSION['user_tenant_key'] ?? null;

        if (!empty($lockedTenant) && !empty($currentTenant) && $lockedTenant !== $currentTenant) {
            session_unset();
            session_destroy();
            header('Location: ?page=login&tenant_changed=1');
            exit;
        }
    }

}
