<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function api_fail(string $message, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function api_token_from_request(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $m)) return trim($m[1]);
    return trim((string)($_GET['token'] ?? ''));
}

$token = api_token_from_request();
if ($token === '') api_fail('Token tidak ditemukan.', 401);
$row = one("SELECT * FROM api_tokens WHERE token_hash=? AND is_active=1", [hash('sha256', $token)]);
if (!$row) api_fail('Token tidak valid.', 401);
q("UPDATE api_tokens SET last_used_at=NOW() WHERE id=?", [$row['id']]);

$resource = $_GET['resource'] ?? 'stock';
$limit = min(500, max(1, (int)($_GET['limit'] ?? 100)));

if ($resource === 'items') {
    $rows = all_rows("SELECT i.*, c.name category_name, s.name supplier_name FROM items i LEFT JOIN categories c ON c.id=i.category_id LEFT JOIN suppliers s ON s.id=i.supplier_id ORDER BY i.sku LIMIT $limit");
} elseif ($resource === 'stock') {
    $rows = all_rows("SELECT i.sku, i.name item_name, i.unit, w.name warehouse_name, l.code location_code, sb.stock_status, sb.qty, i.min_stock, i.safety_stock, i.reorder_point FROM stock_balances sb JOIN items i ON i.id=sb.item_id JOIN locations l ON l.id=sb.location_id JOIN warehouses w ON w.id=l.warehouse_id ORDER BY i.sku,w.name,l.code LIMIT $limit");
} elseif ($resource === 'movements') {
    $rows = all_rows("SELECT sm.id, sm.created_at, sm.movement_type, sm.qty, sm.status, sm.department, sm.cost_center, sm.reference_no, sm.lot_no, sm.serial_no, i.sku, i.name item_name FROM stock_movements sm JOIN items i ON i.id=sm.item_id ORDER BY sm.id DESC LIMIT $limit");
} elseif ($resource === 'reorder') {
    $rows = all_rows("SELECT i.sku, i.name item_name, i.unit, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty, i.min_stock, i.safety_stock, i.reorder_point FROM items i LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY i.id HAVING available_qty <= GREATEST(i.min_stock, i.reorder_point) ORDER BY i.sku LIMIT $limit");
} elseif ($resource === 'purchase_orders') {
    $rows = all_rows("SELECT po.*, s.name supplier_name, i.sku, i.name item_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN items i ON i.id=po.item_id ORDER BY po.id DESC LIMIT $limit");
} else {
    api_fail('Resource tidak dikenal.', 404);
}

echo json_encode(['ok' => true, 'resource' => $resource, 'count' => count($rows), 'data' => $rows], JSON_UNESCAPED_UNICODE);
