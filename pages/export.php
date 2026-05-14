<?php
$type = $_GET['export'] ?? 'stock';
$filename = 'report-' . $type . '-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
$out = fopen('php://output', 'w');
function csv_rows($out, array $rows){ if(!$rows){fputcsv($out,['Tidak ada data']);return;} fputcsv($out, array_keys($rows[0])); foreach($rows as $r) fputcsv($out,$r); }
if ($type === 'stock') {
    $rows = all_rows("SELECT i.sku, i.name, c.name category, s.name supplier, w.name warehouse, l.code location, sb.stock_status, sb.qty, i.unit, i.min_stock, i.price, (sb.qty*i.price) stock_value, i.batch_no, i.expired_date FROM stock_balances sb JOIN items i ON i.id=sb.item_id LEFT JOIN categories c ON c.id=i.category_id LEFT JOIN suppliers s ON s.id=i.supplier_id JOIN locations l ON l.id=sb.location_id JOIN warehouses w ON w.id=l.warehouse_id ORDER BY i.sku");
    csv_rows($out,$rows);
} elseif ($type === 'movements') {
    $rows = all_rows("SELECT sm.id, sm.created_at, sm.movement_type, i.sku, i.name, sm.qty, sm.status, sm.department, sm.cost_center, sm.reference_no, sm.lot_no, sm.serial_no, c.name customer, p.code project, wo.wo_no, sm.note FROM stock_movements sm JOIN items i ON i.id=sm.item_id LEFT JOIN customers c ON c.id=sm.customer_id LEFT JOIN projects p ON p.id=sm.project_id LEFT JOIN work_orders wo ON wo.id=sm.work_order_id ORDER BY sm.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'opname') {
    $rows = all_rows("SELECT sc.id, sc.created_at, i.sku, i.name, w.name warehouse, l.code location, sc.system_qty, sc.physical_qty, sc.variance, sc.status, sc.note FROM stock_counts sc JOIN items i ON i.id=sc.item_id JOIN locations l ON l.id=sc.location_id JOIN warehouses w ON w.id=l.warehouse_id ORDER BY sc.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'purchase') {
    $rows = all_rows("SELECT pr.id, pr.created_at, i.sku, i.name item_name, s.name supplier, pr.qty, pr.department, pr.priority, pr.expected_date, pr.reason, pr.status, u.name requester FROM purchase_requests pr JOIN items i ON i.id=pr.item_id LEFT JOIN suppliers s ON s.id=pr.supplier_id LEFT JOIN users u ON u.id=pr.requested_by ORDER BY pr.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'quality') {
    $rows = all_rows("SELECT qr.id, qr.created_at, qr.type, i.sku, i.name item_name, w.name warehouse, l.code location, qr.qty, qr.note, u.name user_name FROM quality_records qr JOIN items i ON i.id=qr.item_id JOIN locations l ON l.id=qr.location_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN users u ON u.id=qr.created_by ORDER BY qr.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'audit') {
    $rows = all_rows("SELECT a.id, a.created_at, u.name user_name, a.action, a.detail, a.ip_address FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'po') {
    $rows = all_rows("SELECT po.id, po.po_no, po.created_at, s.name supplier, i.sku, i.name item_name, po.qty_ordered, po.qty_received, po.unit_price, po.expected_date, po.status, po.notes FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN items i ON i.id=po.item_id ORDER BY po.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'receipts') {
    $rows = all_rows("SELECT gr.id, gr.receipt_no, gr.received_date, po.po_no, s.name supplier, i.sku, i.name item_name, w.name warehouse, l.code location, gr.qty_received, gr.accepted_qty, gr.rejected_qty, gr.lot_no, gr.serial_no, gr.supplier_doc_no, gr.invoice_no, gr.note FROM goods_receipts gr LEFT JOIN purchase_orders po ON po.id=gr.po_id LEFT JOIN suppliers s ON s.id=gr.supplier_id JOIN items i ON i.id=gr.item_id JOIN locations l ON l.id=gr.location_id JOIN warehouses w ON w.id=l.warehouse_id ORDER BY gr.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'lots') {
    $rows = all_rows("SELECT il.id, i.sku, i.name item_name, w.name warehouse, l.code location, il.stock_status, il.lot_no, il.serial_no, il.qty, il.production_date, il.expired_date, il.source_table, il.source_id FROM item_lots il JOIN items i ON i.id=il.item_id JOIN locations l ON l.id=il.location_id JOIN warehouses w ON w.id=l.warehouse_id ORDER BY i.sku, il.lot_no, il.serial_no");
    csv_rows($out,$rows);
} elseif ($type === 'picking') {
    $rows = all_rows("SELECT ps.id, ps.slip_no, ps.created_at, i.sku, i.name item_name, w.name warehouse, l.code location, ps.qty, ps.lot_no, ps.serial_no, d.name department, cc.code cost_center, c.name customer, p.code project, wo.wo_no, ps.status, ps.note FROM picking_slips ps JOIN items i ON i.id=ps.item_id JOIN locations l ON l.id=ps.location_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN departments d ON d.id=ps.department_id LEFT JOIN cost_centers cc ON cc.id=ps.cost_center_id LEFT JOIN customers c ON c.id=ps.customer_id LEFT JOIN projects p ON p.id=ps.project_id LEFT JOIN work_orders wo ON wo.id=ps.work_order_id ORDER BY ps.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'cycle') {
    $rows = all_rows("SELECT cp.id, cp.plan_no, cp.created_at, w.name warehouse, l.code location, c.name category, u.name assigned_to, cp.due_date, cp.status, cp.note FROM cycle_count_plans cp LEFT JOIN locations l ON l.id=cp.location_id LEFT JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN categories c ON c.id=cp.category_id LEFT JOIN users u ON u.id=cp.assigned_to ORDER BY cp.id DESC");
    csv_rows($out,$rows);
} elseif ($type === 'reorder') {
    $rows = all_rows("SELECT i.sku, i.name item_name, i.unit, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty, i.min_stock, i.safety_stock, i.reorder_point, i.max_stock, COALESCE(NULLIF(i.default_lead_time_days,0), s.lead_time_days, 0) lead_time_days FROM items i LEFT JOIN stock_balances sb ON sb.item_id=i.id LEFT JOIN suppliers s ON s.id=i.supplier_id GROUP BY i.id HAVING available_qty <= GREATEST(i.min_stock, i.reorder_point) ORDER BY i.sku");
    csv_rows($out,$rows);
}
fclose($out);
