<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_perm('maintenance_manage');

$file = basename((string)($_GET['file'] ?? ''));
if ($file === '' || !preg_match('/^warehouse_backup_[0-9]{8}_[0-9]{6}\.sql$/', $file)) {
    http_response_code(404);
    exit('File tidak valid.');
}
$path = __DIR__ . '/uploads/backups/' . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
