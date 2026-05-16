<?php
// Koneksi database bisa memakai env terpisah (DB_HOST, DB_USER, dst.)
// atau satu connection URL dari provider database cloud di Vercel.
if (!function_exists('env_first')) {
    function env_first(array $keys, ?string $default = null): ?string {
        foreach ($keys as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') return $value;
        }
        return $default;
    }
}

$db_host = env_first(['DB_HOST', 'DATABASE_HOST', 'MYSQL_HOST', 'MYSQLHOST', 'TIDB_HOST', 'PLANETSCALE_DB_HOST'], 'localhost');
$db_port = env_first(['DB_PORT', 'DATABASE_PORT', 'MYSQL_PORT', 'MYSQLPORT', 'TIDB_PORT'], '');
$db_name = env_first(['DB_NAME', 'DATABASE_NAME', 'MYSQL_DATABASE', 'MYSQLDATABASE', 'TIDB_DATABASE', 'PLANETSCALE_DB', 'PLANETSCALE_DATABASE_NAME'], 'warehouse_inventory');
$db_user = env_first(['DB_USER', 'DATABASE_USER', 'MYSQL_USER', 'MYSQLUSER', 'TIDB_USER', 'PLANETSCALE_DB_USERNAME', 'PLANETSCALE_USERNAME'], 'root');
$db_pass = env_first(['DB_PASS', 'DB_PASSWORD', 'DATABASE_PASSWORD', 'MYSQL_PASSWORD', 'MYSQLPASSWORD', 'TIDB_PASSWORD', 'PLANETSCALE_DB_PASSWORD', 'PLANETSCALE_PASSWORD'], '');
$db_charset = env_first(['DB_CHARSET', 'MYSQL_CHARSET'], 'utf8mb4');
$db_ssl_ca = env_first(['DB_SSL_CA', 'MYSQL_ATTR_SSL_CA', 'MYSQL_SSL_CA', 'PLANETSCALE_SSL_CERT_PATH'], '');
$db_ssl_mode = strtolower((string) env_first(['DB_SSL_MODE', 'MYSQL_SSL_MODE'], ''));
$db_ssl_verify = env_first(['DB_SSL_VERIFY', 'MYSQL_SSL_VERIFY'], '');

$db_url = env_first([
    'DATABASE_URL',
    'MYSQL_URL',
    'MYSQL_DATABASE_URL',
    'MYSQL_PUBLIC_URL',
    'MYSQL_PRIVATE_URL',
    'TIDB_DATABASE_URL',
    'TIDB_URL',
    'PLANETSCALE_DATABASE_URL',
    'PLANETSCALE_DB_URL',
    'PLANETSCALE_URL',
    'JAWSDB_URL',
    'JAWSDB_MARIA_URL',
    'CLEARDB_DATABASE_URL',
], '');

if ($db_url) {
    $parts = parse_url($db_url);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if (is_array($parts) && in_array($scheme, ['mysql', 'mysqli', 'mysql2', 'mariadb'], true)) {
        if (!empty($parts['host'])) $db_host = rawurldecode($parts['host']);
        if (!empty($parts['port'])) $db_port = (string) $parts['port'];
        if (isset($parts['user'])) $db_user = rawurldecode((string) $parts['user']);
        if (isset($parts['pass'])) $db_pass = rawurldecode((string) $parts['pass']);

        $path = trim((string)($parts['path'] ?? ''), '/');
        if ($path !== '') $db_name = rawurldecode($path);

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['charset'])) $db_charset = (string) $query['charset'];
            if (!empty($query['encoding'])) $db_charset = (string) $query['encoding'];
            if (!empty($query['sslca'])) $db_ssl_ca = (string) $query['sslca'];
            if (!empty($query['ssl_ca'])) $db_ssl_ca = (string) $query['ssl_ca'];
            if (!empty($query['sslcert'])) $db_ssl_ca = (string) $query['sslcert'];

            $sslValue = $query['ssl-mode'] ?? $query['sslmode'] ?? $query['sslaccept'] ?? $query['ssl'] ?? '';
            if ($sslValue !== '') {
                $sslValue = strtolower((string) $sslValue);
                if (!in_array($sslValue, ['0', 'false', 'off', 'disabled', 'disable'], true)) {
                    $db_ssl_mode = $sslValue === 'strict' ? 'verify_identity' : (in_array($sslValue, ['required', 'require', 'verify_ca', 'verify_identity'], true) ? $sslValue : 'required');
                }
            }
        }
    }
}
