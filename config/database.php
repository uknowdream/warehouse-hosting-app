<?php
// Koneksi database bisa memakai env terpisah (DB_HOST, DB_USER, dst.)
// atau satu connection URL dari provider database cloud di Vercel.
if (!function_exists('env_first')) {
    function env_trim_quotes(string $value): string {
        $value = trim($value);
        if ($value === '') return '';

        $firstChar = substr($value, 0, 1);
        $lastChar = substr($value, -1);
        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $value = trim(substr($value, 1, -1));
        }

        return $value;
    }

    function env_extract_assignment(string $value, array $keys): ?string {
        $value = env_trim_quotes($value);
        foreach (preg_split('/\R/', $value) as $line) {
            $line = trim((string)$line);
            if ($line === '' || substr($line, 0, 1) === '#') continue;

            foreach ($keys as $key) {
                if (preg_match('/^(?:export\s+)?' . preg_quote($key, '/') . '\s*=\s*(.*)$/', $line, $match)) {
                    return env_trim_quotes($match[1]);
                }
            }
        }

        return null;
    }

    function env_normalize_value(string $value, array $keys = []): string {
        $value = env_trim_quotes($value);
        if ($value === '') return '';

        $assignedValue = $keys ? env_extract_assignment($value, $keys) : null;
        if ($assignedValue !== null) return env_normalize_value($assignedValue, []);

        if (strpos($value, "\n") !== false || strpos($value, "\r") !== false) {
            return preg_match('/^\s*(?:export\s+)?[A-Z0-9_]+\s*=/m', $value) ? '' : $value;
        }

        return $value;
    }

    function env_embedded_first(array $keys): ?string {
        $env = getenv();
        if (!is_array($env)) return null;

        foreach ($env as $value) {
            if (!is_string($value) || strpos($value, '=') === false) continue;

            $assignedValue = env_extract_assignment($value, $keys);
            if ($assignedValue !== null && trim($assignedValue) !== '') {
                return env_normalize_value($assignedValue, []);
            }
        }

        return null;
    }

    function env_first(array $keys, ?string $default = null): ?string {
        foreach ($keys as $key) {
            $value = getenv($key);
            if ($value !== false && trim((string)$value) !== '') return env_normalize_value((string)$value, $keys);
        }

        $embeddedValue = env_embedded_first($keys);
        return $embeddedValue !== null ? $embeddedValue : $default;
    }

    function env_is_placeholder(?string $value): bool {
        $value = strtolower(trim((string)$value));
        if ($value === '') return false;

        $normalized = preg_replace('/[^a-z0-9]+/', '_', $value);
        $placeholders = [
            'host',
            'db_host',
            'host_mysql_anda',
            'host_mysql_asli',
            'host_mysql_asli_dari_hosting',
            'isi_host_mysql_asli',
            'mysql_example_com',
            'db_name',
            'nama_database',
            'nama_database_asli',
            'isi_nama_database_asli',
            'user',
            'db_user',
            'user_database',
            'user_database_asli',
            'isi_user_database_asli',
            'password',
            'db_pass',
            'password_database',
            'password_database_asli',
            'isi_password_database_asli',
        ];

        if (in_array($normalized, $placeholders, true)) return true;

        return preg_match('/^(isi|ganti|masukkan|your|example|contoh)_/', $normalized) === 1
            || preg_match('/_(asli|anda|hosting)$/', $normalized) === 1;
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
$db_running_on_vercel = env_first(['VERCEL', 'NOW_REGION'], '') !== '';
$db_url_parse_error = '';
$db_placeholder_config = false;

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

$db_env_host = env_first(['DB_HOST', 'DATABASE_HOST', 'MYSQL_HOST', 'MYSQLHOST', 'TIDB_HOST', 'PLANETSCALE_DB_HOST'], '');
$db_env_name = env_first(['DB_NAME', 'DATABASE_NAME', 'MYSQL_DATABASE', 'MYSQLDATABASE', 'TIDB_DATABASE', 'PLANETSCALE_DB', 'PLANETSCALE_DATABASE_NAME'], '');
$db_env_user = env_first(['DB_USER', 'DATABASE_USER', 'MYSQL_USER', 'MYSQLUSER', 'TIDB_USER', 'PLANETSCALE_DB_USERNAME', 'PLANETSCALE_USERNAME'], '');
$db_config_missing = $db_running_on_vercel && !$db_url && ($db_env_host === '' || $db_env_name === '' || $db_env_user === '');

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
    } elseif (is_array($parts) && $scheme !== '') {
        $db_url_parse_error = 'DATABASE_URL harus memakai scheme mysql:// atau mariadb://, bukan ' . $scheme . '://';
    } else {
        $db_url_parse_error = 'DATABASE_URL tidak bisa dibaca. Pastikan formatnya mysql://user:password@host:3306/nama_database';
    }
}

$db_placeholder_config = env_is_placeholder($db_host)
    || env_is_placeholder($db_name)
    || env_is_placeholder($db_user)
    || env_is_placeholder($db_pass);
