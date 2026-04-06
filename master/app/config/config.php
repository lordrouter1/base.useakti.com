<?php
/**
 * Akti Master Admin - Configuração do Banco de Dados
 */

// Configurações do banco master
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'akti_master');
define('DB_USER', 'akti_master_user');
define('DB_PASS', '%7m5ns8d$UJe');
define('DB_CHARSET', 'utf8mb4');

// Domínio base
define('BASE_DOMAIN', 'useakti.com');
define('BASE_URL', '/');

// Configurações de sessão
define('SESSION_NAME', 'akti_master_admin');
define('SESSION_LIFETIME', 3600); // 1 hora

// Banco de dados base para clonagem de novos tenants
define('CLIENT_DB_INIT_BASE', 'akti_init_base');

// ── Detecção automática dos binários MySQL (cross-platform) ──
// Em Windows (XAMPP), busca nos caminhos comuns; em Linux, usa PATH do sistema.
// Para forçar um caminho manual, defina as constantes antes deste bloco.
if (!defined('MYSQLDUMP_PATH') || !defined('MYSQL_PATH')) {
    $isWindows = (PHP_OS_FAMILY === 'Windows');

    if ($isWindows) {
        // Caminhos típicos do XAMPP e MySQL no Windows
        $winPaths = [
            'd:/xampp/mysql/bin',
            'c:/xampp/mysql/bin',
            'c:/Program Files/MySQL/MySQL Server 8.0/bin',
            'c:/Program Files/MySQL/MySQL Server 5.7/bin',
            'c:/Program Files/MariaDB 10.6/bin',
        ];
        $foundDump = '';
        $foundMysql = '';
        foreach ($winPaths as $dir) {
            $dir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR);
            if (!$foundDump && file_exists($dir . DIRECTORY_SEPARATOR . 'mysqldump.exe')) {
                $foundDump = $dir . DIRECTORY_SEPARATOR . 'mysqldump.exe';
            }
            if (!$foundMysql && file_exists($dir . DIRECTORY_SEPARATOR . 'mysql.exe')) {
                $foundMysql = $dir . DIRECTORY_SEPARATOR . 'mysql.exe';
            }
            if ($foundDump && $foundMysql) break;
        }
        // Fallback: tentar via where (PATH do Windows)
        if (!$foundDump) {
            $foundDump = trim(shell_exec('where mysqldump 2>NUL') ?: '');
            if ($foundDump) $foundDump = explode("\n", $foundDump)[0];
        }
        if (!$foundMysql) {
            $foundMysql = trim(shell_exec('where mysql 2>NUL') ?: '');
            if ($foundMysql) $foundMysql = explode("\n", $foundMysql)[0];
        }
        if (!defined('MYSQLDUMP_PATH')) define('MYSQLDUMP_PATH', $foundDump ?: 'mysqldump');
        if (!defined('MYSQL_PATH'))     define('MYSQL_PATH', $foundMysql ?: 'mysql');
    } else {
        // Linux/macOS: usar which para localizar no PATH
        $foundDump  = trim(shell_exec('which mysqldump 2>/dev/null') ?: '');
        $foundMysql = trim(shell_exec('which mysql 2>/dev/null') ?: '');
        if (!defined('MYSQLDUMP_PATH')) define('MYSQLDUMP_PATH', $foundDump ?: 'mysqldump');
        if (!defined('MYSQL_PATH'))     define('MYSQL_PATH', $foundMysql ?: 'mysql');
    }
    // Limpar variáveis temporárias
    unset($isWindows, $winPaths, $dir, $foundDump, $foundMysql);
}
