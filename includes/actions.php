<?php
function handle_post_actions(): void {
    if (!is_post()) return;
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        match ($action) {
            'item_save' => action_item_save(),
            'item_delete' => action_item_delete(),
            'supplier_save' => action_supplier_save(),
            'supplier_delete' => action_supplier_delete(),
            'warehouse_save' => action_warehouse_save(),
            'warehouse_delete' => action_warehouse_delete(),
            'category_save' => action_category_save(),
            'category_delete' => action_category_delete(),
            'location_save' => action_location_save(),
            'location_delete' => action_location_delete(),
            'movement_save' => action_movement_save(),
            'movement_cancel' => action_movement_cancel(),
            'movement_delete' => action_movement_delete(),
            'opname_save' => action_opname_save(),
            'opname_delete' => action_opname_delete(),
            'approval_approve' => action_approval(true),
            'approval_reject' => action_approval(false),
            'purchase_save' => action_purchase_save(),
            'purchase_cancel' => action_purchase_cancel(),
            'purchase_delete' => action_purchase_delete(),
            'quality_save' => action_quality_save(),
            'quality_delete' => action_quality_delete(),
            'role_save' => action_role_save(),
            'role_delete' => action_role_delete(),
            'role_permissions_save' => action_role_permissions_save(),
            'user_save' => action_user_save(),
            'user_toggle' => action_user_toggle(),
            'user_reset_password' => action_user_reset_password(),
            'user_delete' => action_user_delete(),
            'password_change' => action_password_change(),
            default => (function_exists('handle_enterprise_action') && handle_enterprise_action($action)) ? null : flash('danger', 'Action tidak dikenal.'),
        };
    } catch (Throwable $e) {
        flash('danger', 'Error: ' . $e->getMessage());
    }
    $back = $_POST['_back'] ?? ($_GET['p'] ?? 'dashboard');
    redirect($back);
}

function action_item_save(): void {
    require_perm('item_write');
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        trim($_POST['sku'] ?? ''), trim($_POST['name'] ?? ''), (int)($_POST['category_id'] ?? 0) ?: null,
        (int)($_POST['supplier_id'] ?? 0) ?: null, trim($_POST['unit'] ?? 'PCS'), (float)($_POST['min_stock'] ?? 0),
        (float)($_POST['safety_stock'] ?? 0), (float)($_POST['reorder_point'] ?? 0), (float)($_POST['max_stock'] ?? 0), (int)($_POST['default_lead_time_days'] ?? 0),
        (float)($_POST['price'] ?? 0), trim($_POST['barcode'] ?? ''), trim($_POST['batch_no'] ?? ''),
        $_POST['production_date'] ?: null, $_POST['expired_date'] ?: null, $_POST['issue_method'] ?? 'FIFO'
    ];
    if ($data[0] === '' || $data[1] === '') throw new Exception('SKU dan nama barang wajib diisi.');
    if ($id) {
        q("UPDATE items SET sku=?, name=?, category_id=?, supplier_id=?, unit=?, min_stock=?, safety_stock=?, reorder_point=?, max_stock=?, default_lead_time_days=?, price=?, barcode=?, batch_no=?, production_date=?, expired_date=?, issue_method=?, updated_at=NOW() WHERE id=?", [...$data, $id]);
        audit_log('Update Barang', $data[0] . ' - ' . $data[1]);
        flash('success', 'Barang berhasil diperbarui.');
    } else {
        q("INSERT INTO items(sku, name, category_id, supplier_id, unit, min_stock, safety_stock, reorder_point, max_stock, default_lead_time_days, price, barcode, batch_no, production_date, expired_date, issue_method, created_at, updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())", $data);
        $itemId = (int)db()->lastInsertId();
        $loc = (int)($_POST['initial_location_id'] ?? 0);
        $qty = (float)($_POST['initial_qty'] ?? 0);
        if ($loc && $qty > 0) add_stock($itemId, $loc, $qty, 'available');
        audit_log('Tambah Barang', $data[0] . ' - ' . $data[1]);
        flash('success', 'Barang berhasil ditambahkan.');
    }
}
function action_item_delete(): void {
    require_perm('item_delete');
    $id = (int)($_POST['id'] ?? 0);
    $name = item_name($id);
    q("DELETE FROM items WHERE id=?", [$id]);
    audit_log('Hapus Barang', $name);
    flash('success', 'Barang berhasil dihapus.');
}
function action_supplier_save(): void {
    require_perm('supplier_write');
    $id = (int)($_POST['id'] ?? 0);
    $data = [trim($_POST['name'] ?? ''), trim($_POST['contact_name'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['address'] ?? ''), (int)($_POST['lead_time_days'] ?? 0), (float)($_POST['last_price'] ?? 0), trim($_POST['notes'] ?? '')];
    if ($data[0] === '') throw new Exception('Nama supplier wajib diisi.');
    if ($id) q("UPDATE suppliers SET name=?, contact_name=?, phone=?, email=?, address=?, lead_time_days=?, last_price=?, notes=? WHERE id=?", [...$data, $id]);
    else q("INSERT INTO suppliers(name, contact_name, phone, email, address, lead_time_days, last_price, notes) VALUES(?,?,?,?,?,?,?,?)", $data);
    audit_log($id ? 'Update Supplier' : 'Tambah Supplier', $data[0]);
    flash('success', 'Supplier tersimpan.');
}
function action_supplier_delete(): void {
    require_perm('supplier_delete');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) throw new Exception('Supplier tidak valid.');
    $used = (int)scalar("SELECT COUNT(*) FROM items WHERE supplier_id=?", [$id]);
    if ($used > 0) throw new Exception('Supplier masih dipakai oleh master barang.');
    q("DELETE FROM suppliers WHERE id=?", [$id]);
    audit_log('Hapus Supplier', 'ID ' . $id);
    flash('success', 'Supplier dihapus.');
}
function action_warehouse_save(): void {
    require_perm('settings_write');
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name === '') throw new Exception('Nama gudang wajib diisi.');
    if ($id) {
        q("UPDATE warehouses SET name=?, address=? WHERE id=?", [$name, $address, $id]);
        audit_log('Update Gudang', $name);
        flash('success', 'Gudang diperbarui.');
    } else {
        q("INSERT INTO warehouses(name,address) VALUES(?,?)", [$name, $address]);
        audit_log('Tambah Gudang', $name);
        flash('success', 'Gudang ditambahkan.');
    }
}
function action_warehouse_delete(): void {
    require_perm('settings_delete');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) throw new Exception('Gudang tidak valid.');
    $used = (int)scalar("SELECT COUNT(*) FROM locations WHERE warehouse_id=?", [$id]);
    if ($used > 0) throw new Exception('Gudang masih memiliki lokasi/rak.');
    $name = (string)scalar("SELECT name FROM warehouses WHERE id=?", [$id]);
    q("DELETE FROM warehouses WHERE id=?", [$id]);
    audit_log('Hapus Gudang', $name ?: ('ID ' . $id));
    flash('success', 'Gudang dihapus.');
}
function action_category_save(): void {
    require_perm('settings_write');
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($name === '') throw new Exception('Nama kategori wajib diisi.');
    if ($id) {
        q("UPDATE categories SET name=? WHERE id=?", [$name, $id]);
        audit_log('Update Kategori', $name);
        flash('success', 'Kategori diperbarui.');
    } else {
        q("INSERT INTO categories(name) VALUES(?)", [$name]);
        audit_log('Tambah Kategori', $name);
        flash('success', 'Kategori ditambahkan.');
    }
}
function action_category_delete(): void {
    require_perm('settings_delete');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) throw new Exception('Kategori tidak valid.');
    $used = (int)scalar("SELECT COUNT(*) FROM items WHERE category_id=?", [$id]);
    if ($used > 0) throw new Exception('Kategori masih dipakai oleh master barang.');
    $name = (string)scalar("SELECT name FROM categories WHERE id=?", [$id]);
    q("DELETE FROM categories WHERE id=?", [$id]);
    audit_log('Hapus Kategori', $name ?: ('ID ' . $id));
    flash('success', 'Kategori dihapus.');
}
function action_location_save(): void {
    require_perm('settings_write');
    $id = (int)($_POST['id'] ?? 0);
    $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    if (!$warehouseId || $code === '') throw new Exception('Gudang dan kode rak wajib diisi.');
    $description = trim($_POST['description'] ?? '');
    if ($id) {
        q("UPDATE locations SET warehouse_id=?, code=?, description=? WHERE id=?", [$warehouseId, $code, $description, $id]);
        audit_log('Update Lokasi', location_name($id));
        flash('success', 'Lokasi diperbarui.');
    } else {
        q("INSERT INTO locations(warehouse_id, code, description) VALUES(?,?,?)", [$warehouseId, $code, $description]);
        audit_log('Tambah Lokasi', location_name((int)db()->lastInsertId()));
        flash('success', 'Lokasi ditambahkan.');
    }
}
function action_location_delete(): void {
    require_perm('settings_delete');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) throw new Exception('Lokasi tidak valid.');
    $used = (int)scalar("SELECT COUNT(*) FROM stock_balances WHERE location_id=? AND qty <> 0", [$id]);
    if ($used > 0) throw new Exception('Lokasi masih memiliki saldo stock.');
    $movementUsed = (int)scalar("SELECT COUNT(*) FROM stock_movements WHERE from_location_id=? OR to_location_id=?", [$id, $id]);
    $countUsed = (int)scalar("SELECT COUNT(*) FROM stock_counts WHERE location_id=?", [$id]);
    $qualityUsed = (int)scalar("SELECT COUNT(*) FROM quality_records WHERE location_id=?", [$id]);
    if (($movementUsed + $countUsed + $qualityUsed) > 0) throw new Exception('Lokasi masih dipakai oleh transaksi historis.');
    $name = location_name($id);
    q("DELETE FROM locations WHERE id=?", [$id]);
    audit_log('Hapus Lokasi', $name);
    flash('success', 'Lokasi dihapus.');
}
function action_movement_save(): void {
    require_perm('stock_movement');
    $type = $_POST['movement_type'] ?? 'in';
    $itemId = (int)($_POST['item_id'] ?? 0);
    $from = (int)($_POST['from_location_id'] ?? 0) ?: null;
    $to = (int)($_POST['to_location_id'] ?? 0) ?: null;
    $qty = (float)($_POST['qty'] ?? 0);
    if (!$itemId || $qty <= 0) throw new Exception('Barang dan qty wajib diisi.');
    if ($type === 'in' && !$to) throw new Exception('Lokasi tujuan wajib diisi untuk stock masuk.');
    if (in_array($type, ['out','reserve','adjust'], true) && !$from) throw new Exception('Lokasi asal wajib diisi.');
    if ($type === 'transfer' && (!$from || !$to)) throw new Exception('Lokasi asal dan tujuan wajib diisi untuk transfer.');

    $departmentId = function_exists('enterprise_fk_or_null') ? enterprise_fk_or_null('department_id') : null;
    $costCenterId = function_exists('enterprise_fk_or_null') ? enterprise_fk_or_null('cost_center_id') : null;
    $customerId = function_exists('enterprise_fk_or_null') ? enterprise_fk_or_null('customer_id') : null;
    $projectId = function_exists('enterprise_fk_or_null') ? enterprise_fk_or_null('project_id') : null;
    $workOrderId = function_exists('enterprise_fk_or_null') ? enterprise_fk_or_null('work_order_id') : null;
    $department = $departmentId && function_exists('enterprise_entity_name') ? enterprise_entity_name('departments', $departmentId) : trim($_POST['department'] ?? '');
    $costCenter = $costCenterId && function_exists('enterprise_entity_name') ? enterprise_entity_name('cost_centers', $costCenterId, 'code') : trim($_POST['cost_center'] ?? '');
    $lotNo = trim($_POST['lot_no'] ?? '');
    $serialNo = trim($_POST['serial_no'] ?? '');
    $needsApproval = in_array($type, ['out','reserve','adjust','transfer'], true);
    $status = $needsApproval ? 'pending' : 'completed';
    q("INSERT INTO stock_movements(movement_type, item_id, from_location_id, to_location_id, qty, status, department, department_id, cost_center, cost_center_id, customer_id, project_id, work_order_id, reference_no, lot_no, serial_no, note, created_by, created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())", [
        $type, $itemId, $from, $to, $qty, $status, $department, $departmentId, $costCenter, $costCenterId, $customerId, $projectId, $workOrderId, trim($_POST['reference_no'] ?? ''), $lotNo, $serialNo, trim($_POST['note'] ?? ''), current_user()['id']
    ]);
    $movementId = (int)db()->lastInsertId();
    if ($needsApproval) {
        q("INSERT INTO approvals(type, ref_table, ref_id, payload, status, requester_id, notes, created_at) VALUES('stock_movement','stock_movements',?,?,?,?,?,NOW())", [$movementId, json_encode(['movement_id'=>$movementId]), 'pending', current_user()['id'], 'Approval transaksi stock ' . strtoupper($type)]);
        audit_log('Request Approval Stock', item_name($itemId) . ' qty ' . $qty . ' tipe ' . $type);
        flash('success', 'Transaksi masuk ke approval.');
    } else {
        add_stock($itemId, $to, $qty, 'available');
        if (function_exists('enterprise_add_lot_stock')) enterprise_add_lot_stock($itemId, (int)$to, $qty, 'available', $lotNo, $serialNo, null, null, 'stock_movements', $movementId);
        audit_log('Stock Masuk', item_name($itemId) . ' qty ' . $qty . ' ke ' . location_name($to));
        flash('success', 'Stock masuk berhasil disimpan.');
    }
}
function action_movement_cancel(): void {
    require_perm('movement_cancel');
    $id = (int)($_POST['id'] ?? 0);
    $movement = one("SELECT * FROM stock_movements WHERE id=?", [$id]);
    if (!$movement) throw new Exception('Transaksi tidak ditemukan.');
    if ($movement['status'] !== 'pending') throw new Exception('Hanya transaksi pending yang dapat dibatalkan.');
    q("UPDATE stock_movements SET status='cancelled' WHERE id=?", [$id]);
    q("UPDATE approvals SET status='rejected', approved_by=?, approved_at=NOW(), notes=CONCAT(COALESCE(notes,''),' | Dibatalkan requester') WHERE ref_table='stock_movements' AND ref_id=? AND status='pending'", [current_user()['id'], $id]);
    audit_log('Batalkan Transaksi Stock', 'Movement #' . $id);
    flash('success', 'Transaksi pending dibatalkan.');
}
function action_movement_delete(): void {
    require_perm('movement_delete');
    $id = (int)($_POST['id'] ?? 0);
    $movement = one("SELECT * FROM stock_movements WHERE id=?", [$id]);
    if (!$movement) throw new Exception('Transaksi tidak ditemukan.');
    if (!in_array($movement['status'], ['rejected','cancelled'], true)) throw new Exception('Hanya transaksi rejected/cancelled yang dapat dihapus.');
    q("DELETE FROM approvals WHERE ref_table='stock_movements' AND ref_id=?", [$id]);
    q("DELETE FROM stock_movements WHERE id=?", [$id]);
    audit_log('Hapus Transaksi Stock', 'Movement #' . $id);
    flash('success', 'Transaksi dihapus.');
}
function action_opname_save(): void {
    require_perm('stock_opname');
    $itemId = (int)($_POST['item_id'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $physical = (float)($_POST['physical_qty'] ?? 0);
    if (!$itemId || !$locationId) throw new Exception('Barang dan lokasi wajib diisi.');
    $system = stock_qty($itemId, $locationId, 'available');
    $variance = $physical - $system;
    $status = abs($variance) < 0.00001 ? 'sesuai' : 'selisih';
    q("INSERT INTO stock_counts(item_id, location_id, system_qty, physical_qty, variance, status, counted_by, note, created_at) VALUES(?,?,?,?,?,?,?,?,NOW())", [$itemId, $locationId, $system, $physical, $variance, $status, current_user()['id'], trim($_POST['note'] ?? '')]);
    $countId = (int)db()->lastInsertId();
    if ($status === 'selisih') {
        q("INSERT INTO approvals(type, ref_table, ref_id, payload, status, requester_id, notes, created_at) VALUES('stock_adjustment','stock_counts',?,?,?,?,?,NOW())", [
            $countId, json_encode(['count_id'=>$countId, 'item_id'=>$itemId, 'location_id'=>$locationId, 'target_qty'=>$physical]), 'pending', current_user()['id'], 'Koreksi stock dari hasil opname'
        ]);
    }
    audit_log('Stock Opname', item_name($itemId) . ' sistem ' . $system . ', fisik ' . $physical . ', selisih ' . $variance);
    flash('success', $status === 'sesuai' ? 'Opname sesuai dan tersimpan.' : 'Opname selisih tersimpan dan masuk approval koreksi.');
}
function action_opname_delete(): void {
    require_perm('opname_delete');
    $id = (int)($_POST['id'] ?? 0);
    $count = one("SELECT * FROM stock_counts WHERE id=?", [$id]);
    if (!$count) throw new Exception('Data opname tidak ditemukan.');
    if ($count['status'] === 'approved') throw new Exception('Opname approved tidak boleh dihapus karena sudah memengaruhi stock.');
    q("DELETE FROM approvals WHERE ref_table='stock_counts' AND ref_id=? AND status='pending'", [$id]);
    q("DELETE FROM stock_counts WHERE id=?", [$id]);
    audit_log('Hapus Opname', 'Count #' . $id);
    flash('success', 'Data opname dihapus.');
}
function action_approval(bool $approve): void {
    require_perm('approval_manage');
    $id = (int)($_POST['id'] ?? 0);
    $approval = one("SELECT * FROM approvals WHERE id=?", [$id]);
    if (!$approval || $approval['status'] !== 'pending') throw new Exception('Approval tidak ditemukan atau sudah diproses.');
    if (!$approve) {
        q("UPDATE approvals SET status='rejected', approved_by=?, approved_at=NOW() WHERE id=?", [current_user()['id'], $id]);
        if ($approval['ref_table'] === 'stock_movements') q("UPDATE stock_movements SET status='rejected' WHERE id=?", [$approval['ref_id']]);
        if ($approval['ref_table'] === 'purchase_requests') q("UPDATE purchase_requests SET status='rejected' WHERE id=?", [$approval['ref_id']]);
        audit_log('Approval Ditolak', $approval['type'] . ' #' . $id);
        flash('success', 'Approval ditolak.');
        return;
    }

    db()->beginTransaction();
    try {
        if ($approval['type'] === 'stock_movement') apply_stock_movement((int)$approval['ref_id']);
        if ($approval['type'] === 'stock_adjustment') {
            $payload = json_decode($approval['payload'], true) ?: [];
            set_stock((int)$payload['item_id'], (int)$payload['location_id'], (float)$payload['target_qty'], 'available');
            q("UPDATE stock_counts SET status='approved' WHERE id=?", [(int)$payload['count_id']]);
        }
        if ($approval['type'] === 'purchase_request') q("UPDATE purchase_requests SET status='approved' WHERE id=?", [$approval['ref_id']]);
        q("UPDATE approvals SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?", [current_user()['id'], $id]);
        db()->commit();
        audit_log('Approval Disetujui', $approval['type'] . ' #' . $id);
        flash('success', 'Approval disetujui dan efek stock diterapkan.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}
function apply_stock_movement(int $movementId): void {
    $m = one("SELECT * FROM stock_movements WHERE id=?", [$movementId]);
    if (!$m) throw new Exception('Movement tidak ditemukan.');
    $item = (int)$m['item_id']; $qty = (float)$m['qty'];
    $lotNo = (string)($m['lot_no'] ?? '');
    $serialNo = (string)($m['serial_no'] ?? '');
    if ($m['movement_type'] === 'out') {
        if (!subtract_stock($item, (int)$m['from_location_id'], $qty, 'available')) throw new Exception('Stock tidak cukup untuk barang keluar.');
        if (function_exists('enterprise_subtract_lot_stock') && !enterprise_subtract_lot_stock($item, (int)$m['from_location_id'], $qty, 'available', $lotNo, $serialNo)) throw new Exception('Saldo lot/serial tidak cukup untuk barang keluar.');
    } elseif ($m['movement_type'] === 'reserve') {
        if (!subtract_stock($item, (int)$m['from_location_id'], $qty, 'available')) throw new Exception('Stock tidak cukup untuk reservasi.');
        add_stock($item, (int)$m['from_location_id'], $qty, 'reserved');
        if (function_exists('enterprise_subtract_lot_stock') && !enterprise_subtract_lot_stock($item, (int)$m['from_location_id'], $qty, 'available', $lotNo, $serialNo)) throw new Exception('Saldo lot/serial tidak cukup untuk reservasi.');
        if (function_exists('enterprise_add_lot_stock')) enterprise_add_lot_stock($item, (int)$m['from_location_id'], $qty, 'reserved', $lotNo, $serialNo, null, null, 'stock_movements', $movementId);
    } elseif ($m['movement_type'] === 'transfer') {
        if (!subtract_stock($item, (int)$m['from_location_id'], $qty, 'available')) throw new Exception('Stock tidak cukup untuk transfer.');
        add_stock($item, (int)$m['to_location_id'], $qty, 'available');
        if (function_exists('enterprise_subtract_lot_stock') && !enterprise_subtract_lot_stock($item, (int)$m['from_location_id'], $qty, 'available', $lotNo, $serialNo)) throw new Exception('Saldo lot/serial tidak cukup untuk transfer.');
        if (function_exists('enterprise_add_lot_stock')) enterprise_add_lot_stock($item, (int)$m['to_location_id'], $qty, 'available', $lotNo, $serialNo, null, null, 'stock_movements', $movementId);
    } elseif ($m['movement_type'] === 'adjust') {
        set_stock($item, (int)$m['from_location_id'], $qty, 'available');
    }
    q("UPDATE stock_movements SET status='completed', approved_by=?, approved_at=NOW() WHERE id=?", [current_user()['id'], $movementId]);
}
function action_purchase_save(): void {
    $id = (int)($_POST['id'] ?? 0);
    require_perm($id ? 'purchase_update' : 'purchase_write');
    $itemId = (int)($_POST['item_id'] ?? 0); $qty = (float)($_POST['qty'] ?? 0);
    if (!$itemId || $qty <= 0) throw new Exception('Barang dan qty wajib diisi.');
    $supplierId = function_exists('enterprise_fk_or_null') ? enterprise_fk_or_null('supplier_id') : null;
    $data = [$itemId, $supplierId, $qty, trim($_POST['department'] ?? ''), $_POST['priority'] ?? 'Normal', $_POST['expected_date'] ?: null, trim($_POST['reason'] ?? '')];
    if ($id) {
        $pr = one("SELECT * FROM purchase_requests WHERE id=?", [$id]);
        if (!$pr) throw new Exception('Purchase request tidak ditemukan.');
        if ($pr['status'] !== 'pending') throw new Exception('Hanya PR pending yang dapat diedit.');
        q("UPDATE purchase_requests SET item_id=?, supplier_id=?, qty=?, department=?, priority=?, expected_date=?, reason=? WHERE id=?", [...$data, $id]);
        q("UPDATE approvals SET notes=? WHERE ref_table='purchase_requests' AND ref_id=? AND status='pending'", ['Approval purchase request ' . item_name($itemId), $id]);
        audit_log('Update Purchase Request', 'PR #' . $id . ' ' . item_name($itemId) . ' qty ' . $qty);
        flash('success', 'Purchase request diperbarui.');
    } else {
        q("INSERT INTO purchase_requests(item_id, supplier_id, qty, department, priority, expected_date, reason, status, requested_by, created_at) VALUES(?,?,?,?,?,?,?,'pending',?,NOW())", [...$data, current_user()['id']]);
        $prId = (int)db()->lastInsertId();
        q("INSERT INTO approvals(type, ref_table, ref_id, payload, status, requester_id, notes, created_at) VALUES('purchase_request','purchase_requests',?,?,?,?,?,NOW())", [$prId, json_encode(['pr_id'=>$prId]), 'pending', current_user()['id'], 'Approval purchase request ' . item_name($itemId)]);
        audit_log('Purchase Request', item_name($itemId) . ' qty ' . $qty);
        flash('success', 'Purchase request masuk approval.');
    }
}
function action_purchase_cancel(): void {
    require_perm('purchase_update');
    $id = (int)($_POST['id'] ?? 0);
    $pr = one("SELECT * FROM purchase_requests WHERE id=?", [$id]);
    if (!$pr) throw new Exception('Purchase request tidak ditemukan.');
    if ($pr['status'] !== 'pending') throw new Exception('Hanya PR pending yang dapat dibatalkan.');
    q("UPDATE purchase_requests SET status='cancelled' WHERE id=?", [$id]);
    q("UPDATE approvals SET status='rejected', approved_by=?, approved_at=NOW(), notes=CONCAT(COALESCE(notes,''),' | PR dibatalkan') WHERE ref_table='purchase_requests' AND ref_id=? AND status='pending'", [current_user()['id'], $id]);
    audit_log('Batalkan Purchase Request', 'PR #' . $id);
    flash('success', 'Purchase request dibatalkan.');
}
function action_purchase_delete(): void {
    require_perm('purchase_delete');
    $id = (int)($_POST['id'] ?? 0);
    $pr = one("SELECT * FROM purchase_requests WHERE id=?", [$id]);
    if (!$pr) throw new Exception('Purchase request tidak ditemukan.');
    if (!in_array($pr['status'], ['pending','rejected','cancelled'], true)) throw new Exception('PR approved tidak boleh dihapus.');
    q("DELETE FROM approvals WHERE ref_table='purchase_requests' AND ref_id=?", [$id]);
    q("DELETE FROM purchase_requests WHERE id=?", [$id]);
    audit_log('Hapus Purchase Request', 'PR #' . $id);
    flash('success', 'Purchase request dihapus.');
}
function action_quality_save(): void {
    require_perm('quality_write');
    $type = $_POST['type'] ?? 'quarantine'; $itemId = (int)($_POST['item_id'] ?? 0); $loc = (int)($_POST['location_id'] ?? 0); $qty = (float)($_POST['qty'] ?? 0);
    if (!$itemId || !$loc || $qty <= 0) throw new Exception('Barang, lokasi, dan qty wajib diisi.');
    if (in_array($type, ['quarantine','damaged'], true)) {
        if (!subtract_stock($itemId, $loc, $qty, 'available')) throw new Exception('Stock tidak cukup.');
        add_stock($itemId, $loc, $qty, $type);
    } elseif ($type === 'return_customer') {
        add_stock($itemId, $loc, $qty, 'available');
    } else {
        if (!subtract_stock($itemId, $loc, $qty, 'available')) throw new Exception('Stock tidak cukup.');
    }
    q("INSERT INTO quality_records(type, item_id, location_id, qty, note, created_by, created_at) VALUES(?,?,?,?,?,?,NOW())", [$type, $itemId, $loc, $qty, trim($_POST['note'] ?? ''), current_user()['id']]);
    audit_log('Quality / Retur', strtoupper($type) . ' ' . item_name($itemId) . ' qty ' . $qty);
    flash('success', 'Data retur/karantina tersimpan.');
}
function action_quality_delete(): void {
    require_perm('quality_delete');
    $id = (int)($_POST['id'] ?? 0);
    $record = one("SELECT * FROM quality_records WHERE id=?", [$id]);
    if (!$record) throw new Exception('Data quality tidak ditemukan.');
    $itemId = (int)$record['item_id'];
    $loc = (int)$record['location_id'];
    $qty = (float)$record['qty'];
    db()->beginTransaction();
    try {
        if (in_array($record['type'], ['quarantine','damaged'], true)) {
            if (!subtract_stock($itemId, $loc, $qty, $record['type'])) throw new Exception('Saldo ' . $record['type'] . ' tidak cukup untuk reverse.');
            add_stock($itemId, $loc, $qty, 'available');
        } elseif ($record['type'] === 'return_customer') {
            if (!subtract_stock($itemId, $loc, $qty, 'available')) throw new Exception('Saldo available tidak cukup untuk reverse retur customer.');
        } else {
            add_stock($itemId, $loc, $qty, 'available');
        }
        q("DELETE FROM quality_records WHERE id=?", [$id]);
        db()->commit();
        audit_log('Hapus/Reverse Quality', strtoupper($record['type']) . ' ' . item_name($itemId) . ' qty ' . $qty);
        flash('success', 'Data quality dihapus dan efek stock direverse.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}
function action_role_save(): void {
    $id = (int)($_POST['id'] ?? 0);
    $syncPermissions = isset($_POST['sync_permissions']);
    if ($id && $syncPermissions && !has_perm('role_manage')) {
        sync_role_permissions($id, (array)($_POST['permissions'] ?? []));
        audit_log('Update Role Permission', 'Role ID ' . $id);
        flash('success', 'Permission role berhasil diperbarui.');
        return;
    }

    require_perm('role_manage');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($name === '') throw new Exception('Nama role wajib diisi.');
    if (strcasecmp($name, 'Admin') === 0 && $id !== 1) throw new Exception('Nama Admin sudah dipakai role sistem.');
    if ($id) {
        if ($id === 1) throw new Exception('Role Admin sistem tidak boleh diubah.');
        q("UPDATE roles SET name=?, description=? WHERE id=?", [$name, $description, $id]);
        if ($syncPermissions) sync_role_permissions($id, (array)($_POST['permissions'] ?? []));
        audit_log('Update Role', $name);
        flash('success', 'Role diperbarui.');
    } else {
        q("INSERT INTO roles(name, description) VALUES(?,?)", [$name, $description]);
        $id = (int)db()->lastInsertId();
        if ($syncPermissions) sync_role_permissions($id, (array)($_POST['permissions'] ?? []));
        audit_log('Tambah Role', $name);
        flash('success', 'Role ditambahkan.');
    }
}
function action_role_delete(): void {
    require_perm('role_manage');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id || $id === 1) throw new Exception('Role sistem tidak boleh dihapus.');
    $used = (int)scalar("SELECT COUNT(*) FROM users WHERE role_id=?", [$id]);
    if ($used > 0) throw new Exception('Role masih dipakai user.');
    $name = (string)scalar("SELECT name FROM roles WHERE id=?", [$id]);
    q("DELETE FROM roles WHERE id=?", [$id]);
    audit_log('Hapus Role', $name ?: ('ID ' . $id));
    flash('success', 'Role dihapus.');
}
function action_role_permissions_save(): void {
    $roleId = (int)($_POST['role_id'] ?? 0);
    sync_role_permissions($roleId, (array)($_POST['permissions'] ?? []));
    audit_log('Update Role Permission', 'Role ID ' . $roleId);
    flash('success', 'Permission role berhasil diperbarui.');
}
function sync_role_permissions(int $roleId, array $inputPermissions): void {
    if (!user_can_edit_role($roleId)) throw new Exception('Anda tidak boleh mengubah role ini.');
    $perms = array_values(array_filter(array_unique(array_map('intval', $inputPermissions)), fn($pid) => $pid > 0));
    if ((current_user()['role_name'] ?? '') !== 'Admin') {
        $allowed = all_rows("SELECT permission_id FROM role_permissions WHERE role_id=?", [current_user()['role_id']]);
        $allowedIds = array_map('intval', array_column($allowed, 'permission_id'));
        $perms = array_values(array_intersect($perms, $allowedIds));
        if (!$allowedIds) return;
        $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
        q("DELETE FROM role_permissions WHERE role_id=? AND permission_id IN ($placeholders)", array_merge([$roleId], $allowedIds));
        foreach ($perms as $pid) q("INSERT IGNORE INTO role_permissions(role_id, permission_id) VALUES(?,?)", [$roleId, $pid]);
        return;
    }
    q("DELETE FROM role_permissions WHERE role_id=?", [$roleId]);
    foreach ($perms as $pid) q("INSERT IGNORE INTO role_permissions(role_id, permission_id) VALUES(?,?)", [$roleId, $pid]);
}
function action_user_save(): void {
    require_perm('user_manage');
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $roleId = (int)($_POST['role_id'] ?? 0);
    if (!$name || !$email || !$roleId) throw new Exception('Nama, email, dan role wajib diisi.');
    $role = one("SELECT * FROM roles WHERE id=?", [$roleId]);
    if (!$role) throw new Exception('Role tidak valid.');
    $password = $_POST['password'] ?? '';
    if ($id) {
        $target = one("SELECT * FROM users WHERE id=?", [$id]);
        if (!$target) throw new Exception('User tidak ditemukan.');
        if ((int)$target['id'] === (int)current_user()['id'] && (int)$target['role_id'] !== $roleId) throw new Exception('Anda tidak boleh mengubah role akun sendiri.');
        if ($password !== '') {
            if (strlen($password) < 6) throw new Exception('Password minimal 6 karakter.');
            q("UPDATE users SET name=?, email=?, role_id=?, password=? WHERE id=?", [$name, $email, $roleId, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            q("UPDATE users SET name=?, email=?, role_id=? WHERE id=?", [$name, $email, $roleId, $id]);
        }
        audit_log('Update User', $email);
        flash('success', 'User diperbarui.');
    } else {
        if ($password === '') $password = 'password123';
        if (strlen($password) < 6) throw new Exception('Password minimal 6 karakter.');
        q("INSERT INTO users(name, email, password, role_id, is_active, created_at) VALUES(?,?,?,?,1,NOW())", [$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleId]);
        audit_log('Tambah User', $email);
        flash('success', 'User ditambahkan. Password awal: ' . $password);
    }
}
function action_user_toggle(): void {
    require_perm('user_manage');
    $id = (int)($_POST['id'] ?? 0);
    $user = one("SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?", [$id]);
    if (!$user) throw new Exception('User tidak ditemukan.');
    if ((int)$user['id'] === (int)current_user()['id']) throw new Exception('Anda tidak boleh menonaktifkan akun sendiri.');
    if ($user['role_name'] === 'Admin' && (int)$user['is_active'] === 1) {
        $activeAdmins = (int)scalar("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='Admin' AND u.is_active=1");
        if ($activeAdmins <= 1) throw new Exception('Minimal harus ada satu Admin aktif.');
    }
    $newStatus = (int)$user['is_active'] ? 0 : 1;
    q("UPDATE users SET is_active=? WHERE id=?", [$newStatus, $id]);
    audit_log($newStatus ? 'Aktifkan User' : 'Nonaktifkan User', $user['email']);
    flash('success', $newStatus ? 'User diaktifkan.' : 'User dinonaktifkan.');
}
function action_user_reset_password(): void {
    require_perm('user_manage');
    $id = (int)($_POST['id'] ?? 0);
    $password = trim($_POST['password'] ?? '');
    if (strlen($password) < 6) throw new Exception('Password reset minimal 6 karakter.');
    $user = one("SELECT * FROM users WHERE id=?", [$id]);
    if (!$user) throw new Exception('User tidak ditemukan.');
    q("UPDATE users SET password=? WHERE id=?", [password_hash($password, PASSWORD_DEFAULT), $id]);
    audit_log('Reset Password User', $user['email']);
    flash('success', 'Password user direset.');
}
function action_user_delete(): void {
    require_perm('user_delete');
    $id = (int)($_POST['id'] ?? 0);
    $user = one("SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?", [$id]);
    if (!$user) throw new Exception('User tidak ditemukan.');
    if ((int)$user['id'] === (int)current_user()['id']) throw new Exception('Anda tidak boleh menghapus akun sendiri.');
    if ($user['role_name'] === 'Admin') {
        $admins = (int)scalar("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='Admin'");
        if ($admins <= 1) throw new Exception('Minimal harus ada satu Admin.');
    }
    q("DELETE FROM users WHERE id=?", [$id]);
    audit_log('Hapus User', $user['email']);
    flash('success', 'User dihapus.');
}
function action_password_change(): void {
    $new = $_POST['new_password'] ?? '';
    if (strlen($new) < 6) throw new Exception('Password minimal 6 karakter.');
    q("UPDATE users SET password=? WHERE id=?", [password_hash($new, PASSWORD_DEFAULT), current_user()['id']]);
    audit_log('Ganti Password', 'User mengganti password');
    flash('success', 'Password berhasil diganti.');
}
