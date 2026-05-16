<?php
if (getenv('VERCEL')) {
    ini_set('display_errors', '0');
}
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

function db(): PDO {
    static $pdo = null;
    global $db_host, $db_port, $db_name, $db_user, $db_pass, $db_charset, $db_ssl_ca, $db_ssl_mode, $db_ssl_verify, $db_config_missing;
    if ($pdo === null) {
        if ($db_config_missing) {
            throw new RuntimeException('Database Vercel belum dikonfigurasi.');
        }
        $port = $db_port ? ';port=' . $db_port : '';
        $dsn = "mysql:host={$db_host}{$port};dbname={$db_name};charset={$db_charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (!$db_ssl_ca && in_array((string)$db_ssl_mode, ['1', 'true', 'on', 'yes', 'required', 'require', 'verify_ca', 'verify_identity'], true)) {
            foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/cert.pem'] as $caPath) {
                if (is_file($caPath)) {
                    $db_ssl_ca = $caPath;
                    break;
                }
            }
        }
        if ($db_ssl_ca && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $db_ssl_ca;
        }
        if ($db_ssl_verify !== '' && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = filter_var($db_ssl_verify, FILTER_VALIDATE_BOOLEAN);
        }
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    }
    return $pdo;
}

if (!function_exists('str_contains')) { function str_contains($haystack, $needle): bool { return $needle === '' || strpos($haystack, $needle) !== false; } }
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function url($page = 'dashboard', array $params = []): string { return 'index.php?' . http_build_query(array_merge(['p' => $page], $params)); }
function redirect($page = 'dashboard', array $params = []): never { header('Location: ' . url($page, $params)); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function logged_in(): bool { return current_user() !== null; }
function require_login(): void { if (!logged_in()) { header('Location: login.php'); exit; } }
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }
function get_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function verify_csrf(): void {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) { http_response_code(419); exit('CSRF token tidak valid. Refresh halaman lalu coba lagi.'); }
}

function database_error_response(Throwable $e): never {
    global $db_config_missing, $db_host, $db_port, $db_name, $db_user, $db_ssl_mode, $db_url_parse_error;
    http_response_code(503);

    $code = (string)$e->getCode();
    $message = $e->getMessage();
    $title = 'Database belum siap';
    $detail = 'Aplikasi belum bisa terhubung ke database MySQL eksternal.';
    $steps = [
        'Buat database MySQL eksternal dan import file database/schema.sql.',
        'Tambahkan Environment Variables di Vercel: DATABASE_URL atau DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.',
        'Redeploy project setelah Environment Variables tersimpan. Aplikasi akan membuat tabel otomatis jika database masih kosong.',
    ];

    if ($db_config_missing) {
        $title = 'Database Vercel belum dikonfigurasi';
        $detail = 'Deployment ini belum memiliki environment variable database, jadi aplikasi tidak mencoba koneksi ke localhost.';
    } elseif (!empty($db_url_parse_error)) {
        $title = 'DATABASE_URL tidak sesuai';
        $detail = $db_url_parse_error;
    } elseif ($code === '42S02' || str_contains($message, 'Base table or view not found')) {
        $title = 'Schema database belum di-import';
        $detail = 'Koneksi database berhasil dijangkau, tetapi tabel aplikasi belum tersedia.';
        $steps = [
            'Import database/schema.sql ke database MySQL yang dipakai Vercel.',
            'Pastikan DB_NAME mengarah ke database yang sama.',
            'Refresh halaman setelah import selesai.',
        ];
    } elseif (str_contains($message, 'SQLSTATE[HY000] [2002]') || str_contains($message, 'Connection refused') || str_contains($message, 'No such file or directory')) {
        $title = 'Koneksi database gagal';
        $detail = 'Host database tidak bisa dijangkau dari Vercel. Pastikan host bukan localhost dan database menerima koneksi remote.';
    } elseif ($code === '1045') {
        $title = 'Login database ditolak';
        $detail = 'User atau password database di Environment Variables belum cocok.';
    } elseif ($code === '1049') {
        $title = 'Database tidak ditemukan';
        $detail = 'Nama database di DB_NAME atau DATABASE_URL belum sesuai.';
    }

    $requestPath = $_SERVER['REQUEST_URI'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($requestPath, '/api') || str_contains($accept, 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $title, 'detail' => $detail], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $safeTitle = h($title);
    $safeDetail = h($detail);
    $envExample = h("DATABASE_URL=mysql://user:password@host:3306/nama_database?ssl-mode=REQUIRED");
    $diagnostics = [
        'PDO code' => $code ?: '-',
        'DB host' => $db_host ?: '-',
        'DB port' => $db_port ?: '(default)',
        'DB name' => $db_name ?: '-',
        'DB user' => $db_user ? '(set)' : '(empty)',
        'SSL mode' => $db_ssl_mode ?: '(empty)',
    ];
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $safeTitle . '</title><style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:22px;background:#111827;color:#111827;font-family:Inter,Segoe UI,Arial,sans-serif}
        .panel{width:min(760px,100%);padding:26px;border-radius:10px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.24)}
        .kicker{margin:0 0 8px;color:#b42318;font-size:12px;font-weight:900;text-transform:uppercase}
        h1{margin:0 0 10px;font-size:28px;line-height:1.15}p{margin:0 0 16px;color:#667085;line-height:1.55}
        ol{margin:0 0 16px 20px;padding:0;color:#344054;line-height:1.65}code{display:block;overflow:auto;padding:12px;border-radius:8px;background:#f2f4f7;color:#344054}
        dl{display:grid;grid-template-columns:130px minmax(0,1fr);gap:8px 12px;margin:16px 0;padding:12px;border-radius:8px;background:#f8fafc}dt{color:#667085;font-weight:800}dd{margin:0;overflow-wrap:anywhere}
        .note{margin-top:16px;padding:12px;border:1px solid #fee4e2;border-radius:8px;background:#fff8f7;color:#7a271a}
    </style></head><body><main class="panel"><p class="kicker">Warehouse Pro</p><h1>' . $safeTitle . '</h1><p>' . $safeDetail . '</p><dl>';
    foreach ($diagnostics as $name => $value) echo '<dt>' . h($name) . '</dt><dd>' . h($value) . '</dd>';
    echo '</dl><ol>';
    foreach ($steps as $step) echo '<li>' . h($step) . '</li>';
    echo '</ol><code>' . $envExample . '</code><div class="note">Setelah env database di Vercel benar, halaman login akan aktif kembali.</div></main></body></html>';
    exit;
}

function q(string $sql, array $params = []): PDOStatement {
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (Throwable $e) {
        if (PHP_SAPI === 'cli') throw $e;
        database_error_response($e);
    }
}
function one(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r ?: null; }
function all_rows(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function scalar(string $sql, array $params = []) { return q($sql, $params)->fetchColumn(); }

function database_table_exists(string $table): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        if (PHP_SAPI === 'cli') throw $e;
        database_error_response($e);
    }
}

function database_split_sql(string $sql): array {
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql)), fn($stmt) => $stmt !== ''));
}

function database_auto_install_enabled(): bool {
    $setting = getenv('DB_AUTO_INSTALL');
    if ($setting !== false && $setting !== '') return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
    return getenv('VERCEL') !== false;
}

function ensure_database_schema(): void {
    global $db_config_missing;
    if ($db_config_missing || !database_auto_install_enabled()) return;

    $schemaPath = __DIR__ . '/../database/schema.sql';
    if (!is_file($schemaPath)) return;

    try {
        $requiredTables = ['roles', 'permissions', 'users', 'items', 'stock_balances'];
        $missing = false;
        foreach ($requiredTables as $table) {
            if (!database_table_exists($table)) {
                $missing = true;
                break;
            }
        }
        if (!$missing) return;

        foreach (database_split_sql((string)file_get_contents($schemaPath)) as $statement) {
            if (preg_match('/^DROP\s+TABLE/i', $statement)) continue;

            if (preg_match('/^CREATE\s+TABLE\s+/i', $statement)) {
                $statement = preg_replace('/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $statement, 1);
                db()->exec($statement);
                continue;
            }

            if (preg_match('/^INSERT\s+INTO\s+/i', $statement)) {
                $statement = preg_replace('/^INSERT\s+INTO\s+/i', 'INSERT IGNORE INTO ', $statement, 1);
                db()->exec($statement);
                continue;
            }

            if (preg_match('/^SET\s+/i', $statement)) {
                db()->exec($statement);
            }
        }
    } catch (Throwable $e) {
        if (PHP_SAPI === 'cli') throw $e;
        database_error_response($e);
    }
}

if (is_file(__DIR__ . '/enterprise.php')) require_once __DIR__ . '/enterprise.php';

function core_permissions(): array {
    $permissions = [
        ['page_dashboard','Akses Dashboard','Halaman'],
        ['page_master','Akses Master Barang','Halaman'],
        ['item_write','Tambah/Edit Barang','Master Barang'],
        ['item_delete','Hapus Barang','Master Barang'],
        ['page_stock','Akses Data Stock','Halaman'],
        ['page_qr','Akses Generate QR','Halaman'],
        ['qr_generate','Generate/Cetak QR','QR'],
        ['page_movement','Akses Stock Masuk/Keluar','Halaman'],
        ['stock_movement','Input Transaksi Stock','Stock'],
        ['movement_cancel','Batalkan Transaksi Pending','Stock'],
        ['movement_delete','Hapus Transaksi Draft/Rejected','Stock'],
        ['page_opname','Akses Stock Opname QR','Halaman'],
        ['stock_opname','Input Stock Opname','Stock Opname'],
        ['opname_delete','Hapus Opname Belum Approved','Stock Opname'],
        ['page_transfer','Akses Transfer Gudang','Halaman'],
        ['page_quality','Akses Retur & Karantina','Halaman'],
        ['quality_write','Input Retur/Karantina','Quality'],
        ['quality_delete','Hapus/Reverse Quality','Quality'],
        ['page_purchase','Akses Purchase Request','Halaman'],
        ['purchase_write','Input Purchase Request','Purchase'],
        ['purchase_update','Edit/Batalkan Purchase Request','Purchase'],
        ['purchase_delete','Hapus Purchase Request','Purchase'],
        ['page_approval','Akses Approval','Halaman'],
        ['approval_manage','Approve/Reject Transaksi','Approval'],
        ['page_supplier','Akses Supplier','Halaman'],
        ['supplier_write','Tambah/Edit Supplier','Supplier'],
        ['supplier_delete','Hapus Supplier','Supplier'],
        ['page_reports','Akses Laporan','Halaman'],
        ['report_export','Export Laporan CSV','Laporan'],
        ['page_audit','Akses Audit Trail','Halaman'],
        ['page_roles','Akses Role Management','Halaman'],
        ['role_manage','Tambah/Edit/Hapus Role','Role Management'],
        ['user_manage','Tambah/Edit User','User'],
        ['user_delete','Hapus User','User'],
        ['page_settings','Akses Setting Data','Halaman'],
        ['settings_write','Tambah/Edit Setting Data','Setting'],
        ['settings_delete','Hapus Setting Data','Setting'],
    ];
    if (function_exists('enterprise_permissions')) $permissions = array_merge($permissions, enterprise_permissions());
    return $permissions;
}

function ensure_core_permissions(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        foreach (core_permissions() as $p) {
            q("INSERT INTO permissions(perm_key,label,group_name) VALUES(?,?,?) ON DUPLICATE KEY UPDATE label=VALUES(label), group_name=VALUES(group_name)", $p);
        }
        q("INSERT IGNORE INTO role_permissions(role_id, permission_id) SELECT 1, id FROM permissions");
        $defaults = [
            2 => ['page_dashboard','page_master','page_stock','page_qr','qr_generate','page_opname','page_quality','page_purchase','purchase_update','page_approval','approval_manage','page_supplier','supplier_write','page_reports','report_export','page_audit','page_roles','page_procurement','po_manage','receipt_manage','page_picking','picking_manage','page_lots','page_stock_card','page_cycle','cycle_manage','page_analytics','page_notifications','notification_manage','page_tools','import_manage','master_org_manage'],
            3 => ['page_dashboard','page_master','item_write','page_stock','page_qr','qr_generate','page_movement','stock_movement','movement_cancel','page_opname','stock_opname','page_transfer','page_quality','quality_write','page_purchase','purchase_write','page_procurement','receipt_manage','page_picking','picking_manage','page_lots','lot_manage','page_stock_card','page_cycle','page_notifications'],
            4 => ['page_dashboard','page_stock','page_reports','page_stock_card','page_analytics','page_notifications'],
        ];
        foreach ($defaults as $roleId => $keys) {
            foreach ($keys as $key) {
                q("INSERT IGNORE INTO role_permissions(role_id, permission_id) SELECT ?, id FROM permissions WHERE perm_key=?", [$roleId, $key]);
            }
        }
    } catch (Throwable $e) {
        // Fresh installs may not have imported the schema yet.
    }
}

ensure_database_schema();
ensure_core_permissions();
if (function_exists('ensure_enterprise_schema')) ensure_enterprise_schema();

function has_perm(string $key): bool {
    $u = current_user();
    if (!$u) return false;
    if (($u['role_name'] ?? '') === 'Admin') return true;
    return (bool) scalar("SELECT 1 FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ? AND p.perm_key = ? LIMIT 1", [$u['role_id'], $key]);
}
function require_perm(string $key): void {
    if (!has_perm($key)) { flash('danger', 'Akses ditolak untuk fitur ini.'); redirect('dashboard'); }
}
function user_can_edit_role(int $roleId): bool {
    $u = current_user();
    if (!$u) return false;
    $role = one("SELECT name FROM roles WHERE id = ?", [$roleId]);
    if (!$role || $role['name'] === 'Admin') return false;
    if ($u['role_name'] === 'Admin') return true;
    if ($u['role_name'] === 'Manager') return in_array($role['name'], ['Staff Gudang','Viewer'], true);
    return false;
}

function menu_items(): array {
    return [
        ['dashboard','Dashboard','DB','page_dashboard','Utama'],

        ['master','Master Barang','MB','page_master','Master Data'],
        ['supplier','Supplier','SP','page_supplier','Master Data'],
        ['settings','Setting Data','DT','page_settings','Master Data'],
        ['qr','Generate QR','QR','page_qr','Master Data'],
        ['lots','Lot & Serial','LS','page_lots','Master Data'],

        ['stock','Data Stock','SK','page_stock','Operasional Stock'],
        ['movement','Stock Masuk/Keluar','MV','page_movement','Operasional Stock'],
        ['transfer','Transfer Gudang','TR','page_transfer','Operasional Stock'],
        ['opname','Stock Opname QR','OP','page_opname','Operasional Stock'],
        ['cycle','Cycle Count','CC','page_cycle','Operasional Stock'],

        ['purchase','Purchase Request','PR','page_purchase','Purchasing'],
        ['procurement','PO & Penerimaan','PO','page_procurement','Purchasing'],

        ['picking','Picking Slip','PK','page_picking','Pengeluaran & Quality'],
        ['quality','Retur & Karantina','QC','page_quality','Pengeluaran & Quality'],

        ['approval','Approval','AP','page_approval','Monitoring & Laporan'],
        ['stock_card','Kartu Stock','KS','page_stock_card','Monitoring & Laporan'],
        ['analytics','Analitik Stock','AN','page_analytics','Monitoring & Laporan'],
        ['reports','Laporan','RP','page_reports','Monitoring & Laporan'],

        ['notifications','Notifikasi','NT','page_notifications','Administrasi'],
        ['audit','Audit Trail','AU','page_audit','Administrasi'],
        ['roles','Role Management','RL','page_roles','Administrasi'],
        ['tools','Import Backup API','TL','page_tools','Administrasi'],
    ];
}
function page_allowed(string $page): bool {
    if ($page === 'dashboard') return has_perm('page_dashboard');
    foreach (menu_items() as $m) if ($m[0] === $page) return has_perm($m[3]);
    return false;
}

function audit_log(string $action, string $detail = ''): void {
    $u = current_user();
    q("INSERT INTO audit_logs(user_id, action, detail, ip_address, created_at) VALUES(?,?,?,?,NOW())", [$u['id'] ?? null, $action, $detail, $_SERVER['REMOTE_ADDR'] ?? null]);
}
function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function status_badge(string $status): string {
    $s = strtolower($status);
    $cls = 'badge-muted';
    if (str_contains($s, 'approved') || str_contains($s, 'selesai') || str_contains($s, 'sesuai') || str_contains($s, 'available') || str_contains($s, 'active')) $cls = 'badge-success';
    if (str_contains($s, 'pending') || str_contains($s, 'reserved') || str_contains($s, 'quarantine') || str_contains($s, 'menipis')) $cls = 'badge-warning';
    if (str_contains($s, 'reject') || str_contains($s, 'ditolak') || str_contains($s, 'selisih') || str_contains($s, 'damaged') || str_contains($s, 'expired') || str_contains($s, 'inactive')) $cls = 'badge-danger';
    if (str_contains($s, 'cancel')) $cls = 'badge-muted';
    if (str_contains($s, 'transfer') || str_contains($s, 'draft')) $cls = 'badge-info';
    return '<span class="badge ' . $cls . '">' . h($status) . '</span>';
}
function stock_qty(int $itemId, int $locationId, string $status = 'available'): float {
    return (float) scalar("SELECT qty FROM stock_balances WHERE item_id=? AND location_id=? AND stock_status=?", [$itemId, $locationId, $status]);
}
function add_stock(int $itemId, int $locationId, float $qty, string $status = 'available'): void {
    q("INSERT INTO stock_balances(item_id, location_id, stock_status, qty) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)", [$itemId, $locationId, $status, $qty]);
}
function subtract_stock(int $itemId, int $locationId, float $qty, string $status = 'available'): bool {
    $available = stock_qty($itemId, $locationId, $status);
    if ($available < $qty) return false;
    q("UPDATE stock_balances SET qty = qty - ? WHERE item_id=? AND location_id=? AND stock_status=?", [$qty, $itemId, $locationId, $status]);
    q("DELETE FROM stock_balances WHERE item_id=? AND location_id=? AND stock_status=? AND ABS(qty) < 0.00001", [$itemId, $locationId, $status]);
    return true;
}
function set_stock(int $itemId, int $locationId, float $qty, string $status = 'available'): void {
    q("INSERT INTO stock_balances(item_id, location_id, stock_status, qty) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE qty = VALUES(qty)", [$itemId, $locationId, $status, $qty]);
}
function item_name(int $id): string { return (string) scalar("SELECT CONCAT(sku, ' - ', name) FROM items WHERE id=?", [$id]); }
function location_name(int $id): string { return (string) scalar("SELECT CONCAT(w.name, ' / ', l.code) FROM locations l JOIN warehouses w ON w.id=l.warehouse_id WHERE l.id=?", [$id]); }
function qr_payload(array $item, ?array $loc = null): string {
    return json_encode([
        'type' => 'WAREHOUSE_ITEM',
        'id' => (int)$item['id'],
        'sku' => $item['sku'],
        'name' => $item['name'],
        'location_id' => $loc['id'] ?? null,
        'location' => $loc ? ($loc['warehouse_name'] . ' / ' . $loc['code']) : '',
        'batch' => $item['batch_no'] ?? '',
        'expired' => $item['expired_date'] ?? '',
    ], JSON_UNESCAPED_UNICODE);
}
