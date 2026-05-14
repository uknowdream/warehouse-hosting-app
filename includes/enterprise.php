<?php

function enterprise_permissions(): array {
    return [
        ['page_procurement','Akses PO & Penerimaan','Halaman'],
        ['po_manage','Kelola Purchase Order','Procurement'],
        ['receipt_manage','Input Penerimaan Barang','Procurement'],
        ['receipt_delete','Hapus/Reverse Penerimaan','Procurement'],
        ['page_picking','Akses Picking Slip','Halaman'],
        ['picking_manage','Kelola Picking & Issue Slip','Picking'],
        ['page_lots','Akses Lot & Serial','Halaman'],
        ['lot_manage','Kelola Lot & Serial','Lot Serial'],
        ['page_stock_card','Akses Kartu Stock','Halaman'],
        ['page_cycle','Akses Cycle Count','Halaman'],
        ['cycle_manage','Kelola Jadwal Cycle Count','Cycle Count'],
        ['page_analytics','Akses Analitik Stock','Halaman'],
        ['page_notifications','Akses Notifikasi','Halaman'],
        ['notification_manage','Kelola Notifikasi','Notifikasi'],
        ['page_tools','Akses Tools Import/Backup/API','Halaman'],
        ['import_manage','Import Data CSV/Excel','Tools'],
        ['maintenance_manage','Backup Restore Database','Tools'],
        ['api_manage','Kelola API Token','Tools'],
        ['master_org_manage','Kelola Master Dept/Project/Customer','Master Organisasi'],
    ];
}

function enterprise_menu_items(): array {
    return [
        ['procurement','PO & Penerimaan','PO','page_procurement'],
        ['picking','Picking Slip','PK','page_picking'],
        ['lots','Lot & Serial','LS','page_lots'],
        ['stock_card','Kartu Stock','KS','page_stock_card'],
        ['cycle','Cycle Count','CC','page_cycle'],
        ['analytics','Analitik Stock','AN','page_analytics'],
        ['notifications','Notifikasi','NT','page_notifications'],
        ['tools','Import Backup API','TL','page_tools'],
    ];
}

function enterprise_titles(): array {
    return [
        'procurement' => 'PO & Penerimaan',
        'picking' => 'Picking Slip',
        'lots' => 'Lot & Serial',
        'stock_card' => 'Kartu Stock',
        'cycle' => 'Cycle Count',
        'analytics' => 'Analitik Stock',
        'notifications' => 'Notifikasi',
        'tools' => 'Import Backup API',
    ];
}

function enterprise_valid_identifier(string $name): bool {
    return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
}

function enterprise_table_exists(string $table): bool {
    if (!enterprise_valid_identifier($table)) return false;
    return (bool) scalar("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]);
}

function enterprise_column_exists(string $table, string $column): bool {
    if (!enterprise_valid_identifier($table) || !enterprise_valid_identifier($column)) return false;
    return (bool) scalar("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $column]);
}

function enterprise_index_exists(string $table, string $index): bool {
    if (!enterprise_valid_identifier($table) || !enterprise_valid_identifier($index)) return false;
    return (bool) scalar("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?", [$table, $index]);
}

function enterprise_add_column(string $table, string $column, string $definition): void {
    if (!enterprise_column_exists($table, $column)) q("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}

function ensure_enterprise_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        q("CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(40) NULL,
            name VARCHAR(120) NOT NULL,
            description VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uq_department_name(name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS cost_centers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(60) NOT NULL,
            name VARCHAR(120) NOT NULL,
            department_id INT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uq_cost_code(code),
            CONSTRAINT fk_cc_department FOREIGN KEY(department_id) REFERENCES departments(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            contact_name VARCHAR(120) NULL,
            phone VARCHAR(50) NULL,
            email VARCHAR(150) NULL,
            address TEXT NULL,
            UNIQUE KEY uq_customer_name(name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(60) NOT NULL,
            name VARCHAR(160) NOT NULL,
            customer_id INT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            UNIQUE KEY uq_project_code(code),
            CONSTRAINT fk_project_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS work_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            wo_no VARCHAR(80) NOT NULL,
            project_id INT NULL,
            customer_id INT NULL,
            department_id INT NULL,
            description TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'open',
            due_date DATE NULL,
            UNIQUE KEY uq_wo_no(wo_no),
            CONSTRAINT fk_wo_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE SET NULL,
            CONSTRAINT fk_wo_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,
            CONSTRAINT fk_wo_department FOREIGN KEY(department_id) REFERENCES departments(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            po_no VARCHAR(80) NOT NULL,
            pr_id INT NULL,
            supplier_id INT NOT NULL,
            item_id INT NOT NULL,
            qty_ordered DECIMAL(18,2) NOT NULL,
            qty_received DECIMAL(18,2) NOT NULL DEFAULT 0,
            unit_price DECIMAL(18,2) NOT NULL DEFAULT 0,
            expected_date DATE NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'ordered',
            notes TEXT NULL,
            created_by INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_po_no(po_no),
            CONSTRAINT fk_po_pr FOREIGN KEY(pr_id) REFERENCES purchase_requests(id) ON DELETE SET NULL,
            CONSTRAINT fk_po_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id),
            CONSTRAINT fk_po_item FOREIGN KEY(item_id) REFERENCES items(id),
            CONSTRAINT fk_po_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS goods_receipts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            receipt_no VARCHAR(80) NOT NULL,
            po_id INT NULL,
            supplier_id INT NULL,
            item_id INT NOT NULL,
            location_id INT NOT NULL,
            qty_received DECIMAL(18,2) NOT NULL,
            accepted_qty DECIMAL(18,2) NOT NULL DEFAULT 0,
            rejected_qty DECIMAL(18,2) NOT NULL DEFAULT 0,
            lot_no VARCHAR(120) NULL,
            serial_no VARCHAR(120) NULL,
            supplier_doc_no VARCHAR(120) NULL,
            invoice_no VARCHAR(120) NULL,
            received_date DATE NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'completed',
            note TEXT NULL,
            created_by INT NULL,
            created_at DATETIME NULL,
            UNIQUE KEY uq_receipt_no(receipt_no),
            CONSTRAINT fk_gr_po FOREIGN KEY(po_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
            CONSTRAINT fk_gr_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
            CONSTRAINT fk_gr_item FOREIGN KEY(item_id) REFERENCES items(id),
            CONSTRAINT fk_gr_location FOREIGN KEY(location_id) REFERENCES locations(id),
            CONSTRAINT fk_gr_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS item_lots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            location_id INT NOT NULL,
            stock_status VARCHAR(30) NOT NULL DEFAULT 'available',
            lot_no VARCHAR(120) NOT NULL DEFAULT '',
            serial_no VARCHAR(120) NOT NULL DEFAULT '',
            production_date DATE NULL,
            expired_date DATE NULL,
            qty DECIMAL(18,2) NOT NULL DEFAULT 0,
            source_table VARCHAR(80) NULL,
            source_id INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_lot_lookup(item_id, location_id, stock_status, lot_no, serial_no),
            CONSTRAINT fk_lot_item FOREIGN KEY(item_id) REFERENCES items(id) ON DELETE CASCADE,
            CONSTRAINT fk_lot_location FOREIGN KEY(location_id) REFERENCES locations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ref_table VARCHAR(80) NOT NULL,
            ref_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) NULL,
            size_bytes INT NOT NULL DEFAULT 0,
            uploaded_by INT NULL,
            created_at DATETIME NULL,
            KEY idx_attachment_ref(ref_table, ref_id),
            CONSTRAINT fk_attachment_user FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            role_id INT NULL,
            type VARCHAR(40) NOT NULL DEFAULT 'info',
            title VARCHAR(160) NOT NULL,
            message TEXT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            KEY idx_notification_target(user_id, role_id, is_read),
            CONSTRAINT fk_notification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_notification_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS notification_outbox (
            id INT AUTO_INCREMENT PRIMARY KEY,
            channel VARCHAR(30) NOT NULL,
            recipient VARCHAR(160) NOT NULL,
            subject VARCHAR(180) NULL,
            message TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            ref_table VARCHAR(80) NULL,
            ref_id INT NULL,
            created_at DATETIME NULL,
            sent_at DATETIME NULL,
            error_message TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS picking_slips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slip_no VARCHAR(80) NOT NULL,
            item_id INT NOT NULL,
            location_id INT NOT NULL,
            qty DECIMAL(18,2) NOT NULL,
            lot_no VARCHAR(120) NULL,
            serial_no VARCHAR(120) NULL,
            department_id INT NULL,
            cost_center_id INT NULL,
            customer_id INT NULL,
            project_id INT NULL,
            work_order_id INT NULL,
            requested_by INT NULL,
            picked_by INT NULL,
            issued_by INT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'requested',
            note TEXT NULL,
            created_at DATETIME NULL,
            picked_at DATETIME NULL,
            issued_at DATETIME NULL,
            UNIQUE KEY uq_slip_no(slip_no),
            CONSTRAINT fk_pick_item FOREIGN KEY(item_id) REFERENCES items(id),
            CONSTRAINT fk_pick_location FOREIGN KEY(location_id) REFERENCES locations(id),
            CONSTRAINT fk_pick_department FOREIGN KEY(department_id) REFERENCES departments(id) ON DELETE SET NULL,
            CONSTRAINT fk_pick_cost FOREIGN KEY(cost_center_id) REFERENCES cost_centers(id) ON DELETE SET NULL,
            CONSTRAINT fk_pick_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,
            CONSTRAINT fk_pick_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE SET NULL,
            CONSTRAINT fk_pick_wo FOREIGN KEY(work_order_id) REFERENCES work_orders(id) ON DELETE SET NULL,
            CONSTRAINT fk_pick_requested FOREIGN KEY(requested_by) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_pick_picked FOREIGN KEY(picked_by) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_pick_issued FOREIGN KEY(issued_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS cycle_count_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_no VARCHAR(80) NOT NULL,
            location_id INT NULL,
            category_id INT NULL,
            assigned_to INT NULL,
            due_date DATE NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'open',
            note TEXT NULL,
            created_by INT NULL,
            created_at DATETIME NULL,
            closed_at DATETIME NULL,
            UNIQUE KEY uq_plan_no(plan_no),
            CONSTRAINT fk_cycle_location FOREIGN KEY(location_id) REFERENCES locations(id) ON DELETE SET NULL,
            CONSTRAINT fk_cycle_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
            CONSTRAINT fk_cycle_assigned FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_cycle_created FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS api_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token_hash CHAR(64) NOT NULL,
            token_prefix VARCHAR(16) NOT NULL,
            name VARCHAR(120) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT NULL,
            last_used_at DATETIME NULL,
            created_at DATETIME NULL,
            UNIQUE KEY uq_api_hash(token_hash),
            CONSTRAINT fk_api_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        q("CREATE TABLE IF NOT EXISTS backup_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            size_bytes INT NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'created',
            created_by INT NULL,
            created_at DATETIME NULL,
            note TEXT NULL,
            CONSTRAINT fk_backup_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        enterprise_add_column('items', 'safety_stock', "DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER min_stock");
        enterprise_add_column('items', 'reorder_point', "DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER safety_stock");
        enterprise_add_column('items', 'max_stock', "DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER reorder_point");
        enterprise_add_column('items', 'default_lead_time_days', "INT NOT NULL DEFAULT 0 AFTER max_stock");
        enterprise_add_column('users', 'phone', "VARCHAR(50) NULL AFTER email");
        enterprise_add_column('purchase_requests', 'supplier_id', "INT NULL AFTER item_id");
        enterprise_add_column('purchase_requests', 'expected_date', "DATE NULL AFTER priority");
        enterprise_add_column('stock_movements', 'lot_no', "VARCHAR(120) NULL AFTER reference_no");
        enterprise_add_column('stock_movements', 'serial_no', "VARCHAR(120) NULL AFTER lot_no");
        enterprise_add_column('stock_movements', 'department_id', "INT NULL AFTER department");
        enterprise_add_column('stock_movements', 'cost_center_id', "INT NULL AFTER cost_center");
        enterprise_add_column('stock_movements', 'customer_id', "INT NULL AFTER cost_center_id");
        enterprise_add_column('stock_movements', 'project_id', "INT NULL AFTER customer_id");
        enterprise_add_column('stock_movements', 'work_order_id', "INT NULL AFTER project_id");

        q("INSERT IGNORE INTO departments(id,code,name,description) VALUES
            (1,'WH','Warehouse','Operasional gudang'),
            (2,'PRD','Produksi','Pemakaian produksi'),
            (3,'MTC','Maintenance','Perawatan mesin')");
        q("INSERT IGNORE INTO cost_centers(id,code,name,department_id) VALUES
            (1,'WH-001','Warehouse Utama',1),
            (2,'PRD-001','Produksi Line 1',2),
            (3,'MTC-001','Maintenance Umum',3)");
    } catch (Throwable $e) {
        // Fresh installs may not have the base schema yet.
    }
}

function enterprise_next_no(string $prefix, string $table, string $column): string {
    if (!enterprise_valid_identifier($table) || !enterprise_valid_identifier($column)) return $prefix . date('YmdHis');
    $ym = date('Ym');
    $like = $prefix . '-' . $ym . '-%';
    $last = (string) scalar("SELECT `$column` FROM `$table` WHERE `$column` LIKE ? ORDER BY id DESC LIMIT 1", [$like]);
    $seq = 1;
    if ($last && preg_match('/-(\d+)$/', $last, $m)) $seq = ((int)$m[1]) + 1;
    return $prefix . '-' . $ym . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
}

function enterprise_fk_or_null(string $key): ?int {
    $v = (int)($_POST[$key] ?? 0);
    return $v > 0 ? $v : null;
}

function enterprise_selected($a, $b): string {
    return (string)$a === (string)$b ? 'selected' : '';
}

function enterprise_entity_name(string $table, int $id, string $field = 'name'): string {
    if (!$id || !enterprise_valid_identifier($table) || !enterprise_valid_identifier($field)) return '';
    return (string) scalar("SELECT `$field` FROM `$table` WHERE id=?", [$id]);
}

function enterprise_create_notification(string $title, string $message, string $type = 'info', ?int $roleId = null, ?int $userId = null): void {
    if (!enterprise_table_exists('notifications')) return;
    q("INSERT INTO notifications(user_id, role_id, type, title, message, is_read, created_at) VALUES(?,?,?,?,?,0,NOW())", [$userId, $roleId, $type, $title, $message]);
    if ($userId) {
        $user = one("SELECT email, phone FROM users WHERE id=?", [$userId]);
        if ($user && !empty($user['email'])) q("INSERT INTO notification_outbox(channel, recipient, subject, message, status, created_at) VALUES('email',?,?,?,?,NOW())", [$user['email'], $title, $message, 'pending']);
        if ($user && !empty($user['phone'])) q("INSERT INTO notification_outbox(channel, recipient, subject, message, status, created_at) VALUES('whatsapp',?,?,?,?,NOW())", [$user['phone'], $title, $message, 'pending']);
    }
}

function enterprise_notify_role(string $roleName, string $title, string $message, string $type = 'info'): void {
    $roleId = (int) scalar("SELECT id FROM roles WHERE name=?", [$roleName]);
    enterprise_create_notification($title, $message, $type, $roleId ?: null, null);
}

function enterprise_lot_qty(int $itemId, int $locationId, string $status, string $lotNo = '', string $serialNo = ''): float {
    return (float) scalar(
        "SELECT COALESCE(SUM(qty),0) FROM item_lots WHERE item_id=? AND location_id=? AND stock_status=? AND lot_no=? AND serial_no=?",
        [$itemId, $locationId, $status, $lotNo, $serialNo]
    );
}

function enterprise_add_lot_stock(int $itemId, int $locationId, float $qty, string $status = 'available', string $lotNo = '', string $serialNo = '', ?string $prodDate = null, ?string $expDate = null, ?string $sourceTable = null, ?int $sourceId = null): void {
    $lotNo = trim($lotNo);
    $serialNo = trim($serialNo);
    if ($qty <= 0 || ($lotNo === '' && $serialNo === '')) return;
    $row = one("SELECT id FROM item_lots WHERE item_id=? AND location_id=? AND stock_status=? AND lot_no=? AND serial_no=? LIMIT 1", [$itemId, $locationId, $status, $lotNo, $serialNo]);
    if ($row) {
        q("UPDATE item_lots SET qty=qty+?, production_date=COALESCE(?, production_date), expired_date=COALESCE(?, expired_date), source_table=COALESCE(?, source_table), source_id=COALESCE(?, source_id), updated_at=NOW() WHERE id=?", [$qty, $prodDate, $expDate, $sourceTable, $sourceId, $row['id']]);
    } else {
        q("INSERT INTO item_lots(item_id, location_id, stock_status, lot_no, serial_no, production_date, expired_date, qty, source_table, source_id, created_at, updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),NOW())", [$itemId, $locationId, $status, $lotNo, $serialNo, $prodDate, $expDate, $qty, $sourceTable, $sourceId]);
    }
}

function enterprise_subtract_lot_stock(int $itemId, int $locationId, float $qty, string $status = 'available', string $lotNo = '', string $serialNo = ''): bool {
    $lotNo = trim($lotNo);
    $serialNo = trim($serialNo);
    if ($qty <= 0 || ($lotNo === '' && $serialNo === '')) return true;
    $available = enterprise_lot_qty($itemId, $locationId, $status, $lotNo, $serialNo);
    if ($available + 0.00001 < $qty) return false;
    q("UPDATE item_lots SET qty=qty-?, updated_at=NOW() WHERE item_id=? AND location_id=? AND stock_status=? AND lot_no=? AND serial_no=? LIMIT 1", [$qty, $itemId, $locationId, $status, $lotNo, $serialNo]);
    q("DELETE FROM item_lots WHERE ABS(qty) < 0.00001");
    return true;
}

function enterprise_save_attachment(string $refTable, int $refId, string $field = 'attachment'): void {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload lampiran gagal.');
    $dir = __DIR__ . '/../uploads/attachments';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $original = basename((string)$_FILES[$field]['name']);
    $ext = pathinfo($original, PATHINFO_EXTENSION);
    $safeExt = $ext ? ('.' . preg_replace('/[^A-Za-z0-9]/', '', $ext)) : '';
    $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(5)) . $safeExt;
    $target = $dir . '/' . $fileName;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) throw new Exception('File lampiran tidak bisa disimpan.');
    $relPath = 'uploads/attachments/' . $fileName;
    q("INSERT INTO attachments(ref_table, ref_id, original_name, file_name, file_path, mime_type, size_bytes, uploaded_by, created_at) VALUES(?,?,?,?,?,?,?,?,NOW())", [
        $refTable, $refId, $original, $fileName, $relPath, $_FILES[$field]['type'] ?? null, (int)($_FILES[$field]['size'] ?? 0), current_user()['id'] ?? null
    ]);
}

function handle_enterprise_action(string $action): bool {
    return match ($action) {
        'po_save' => enterprise_action_po_save(),
        'po_close' => enterprise_action_po_close(),
        'po_delete' => enterprise_action_po_delete(),
        'receipt_save' => enterprise_action_receipt_save(),
        'receipt_delete' => enterprise_action_receipt_delete(),
        'department_save' => enterprise_action_department_save(),
        'department_delete' => enterprise_action_department_delete(),
        'cost_center_save' => enterprise_action_cost_center_save(),
        'cost_center_delete' => enterprise_action_cost_center_delete(),
        'customer_save' => enterprise_action_customer_save(),
        'customer_delete' => enterprise_action_customer_delete(),
        'project_save' => enterprise_action_project_save(),
        'project_delete' => enterprise_action_project_delete(),
        'work_order_save' => enterprise_action_work_order_save(),
        'work_order_delete' => enterprise_action_work_order_delete(),
        'lot_save' => enterprise_action_lot_save(),
        'picking_save' => enterprise_action_picking_save(),
        'picking_pick' => enterprise_action_picking_pick(),
        'picking_issue' => enterprise_action_picking_issue(),
        'picking_cancel' => enterprise_action_picking_cancel(),
        'cycle_save' => enterprise_action_cycle_save(),
        'cycle_status' => enterprise_action_cycle_status(),
        'notification_read' => enterprise_action_notification_read(),
        'notification_read_all' => enterprise_action_notification_read_all(),
        'import_csv' => enterprise_action_import_csv(),
        'backup_create' => enterprise_action_backup_create(),
        'backup_restore' => enterprise_action_backup_restore(),
        'api_token_create' => enterprise_action_api_token_create(),
        'api_token_toggle' => enterprise_action_api_token_toggle(),
        default => false,
    };
}

function enterprise_action_po_save(): bool {
    require_perm('po_manage');
    $id = (int)($_POST['id'] ?? 0);
    $prId = enterprise_fk_or_null('pr_id');
    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $itemId = (int)($_POST['item_id'] ?? 0);
    $qty = (float)($_POST['qty_ordered'] ?? 0);
    $unitPrice = (float)($_POST['unit_price'] ?? 0);
    if ($prId) {
        $pr = one("SELECT * FROM purchase_requests WHERE id=?", [$prId]);
        if ($pr) {
            $itemId = $itemId ?: (int)$pr['item_id'];
            $qty = $qty > 0 ? $qty : (float)$pr['qty'];
            $supplierId = $supplierId ?: (int)($pr['supplier_id'] ?? 0);
        }
    }
    if (!$supplierId || !$itemId || $qty <= 0) throw new Exception('Supplier, barang, dan qty PO wajib diisi.');
    $expected = $_POST['expected_date'] ?: null;
    $notes = trim($_POST['notes'] ?? '');
    if ($id) {
        q("UPDATE purchase_orders SET pr_id=?, supplier_id=?, item_id=?, qty_ordered=?, unit_price=?, expected_date=?, notes=?, updated_at=NOW() WHERE id=? AND status <> 'closed'", [$prId, $supplierId, $itemId, $qty, $unitPrice, $expected, $notes, $id]);
        audit_log('Update Purchase Order', 'PO #' . $id);
        flash('success', 'Purchase order diperbarui.');
    } else {
        $poNo = trim($_POST['po_no'] ?? '') ?: enterprise_next_no('PO', 'purchase_orders', 'po_no');
        q("INSERT INTO purchase_orders(po_no, pr_id, supplier_id, item_id, qty_ordered, unit_price, expected_date, status, notes, created_by, created_at, updated_at) VALUES(?,?,?,?,?,?,?,'ordered',?,?,NOW(),NOW())", [$poNo, $prId, $supplierId, $itemId, $qty, $unitPrice, $expected, $notes, current_user()['id']]);
        if ($prId) q("UPDATE purchase_requests SET status='ordered' WHERE id=? AND status='approved'", [$prId]);
        audit_log('Tambah Purchase Order', $poNo . ' ' . item_name($itemId));
        enterprise_notify_role('Manager', 'PO baru dibuat', $poNo . ' untuk ' . item_name($itemId), 'procurement');
        flash('success', 'Purchase order dibuat.');
    }
    return true;
}

function enterprise_action_po_close(): bool {
    require_perm('po_manage');
    $id = (int)($_POST['id'] ?? 0);
    q("UPDATE purchase_orders SET status='closed', updated_at=NOW() WHERE id=?", [$id]);
    audit_log('Tutup Purchase Order', 'PO #' . $id);
    flash('success', 'PO ditutup.');
    return true;
}

function enterprise_action_po_delete(): bool {
    require_perm('po_manage');
    $id = (int)($_POST['id'] ?? 0);
    $used = (int)scalar("SELECT COUNT(*) FROM goods_receipts WHERE po_id=?", [$id]);
    if ($used > 0) throw new Exception('PO sudah memiliki penerimaan dan tidak boleh dihapus.');
    q("DELETE FROM purchase_orders WHERE id=?", [$id]);
    audit_log('Hapus Purchase Order', 'PO #' . $id);
    flash('success', 'PO dihapus.');
    return true;
}

function enterprise_action_receipt_save(): bool {
    require_perm('receipt_manage');
    $poId = enterprise_fk_or_null('po_id');
    $po = $poId ? one("SELECT * FROM purchase_orders WHERE id=?", [$poId]) : null;
    $supplierId = $po ? (int)$po['supplier_id'] : enterprise_fk_or_null('supplier_id');
    $itemId = $po ? (int)$po['item_id'] : (int)($_POST['item_id'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $qtyReceived = (float)($_POST['qty_received'] ?? 0);
    $acceptedQty = (float)($_POST['accepted_qty'] ?? $qtyReceived);
    $rejectedQty = (float)($_POST['rejected_qty'] ?? 0);
    if (!$itemId || !$locationId || $qtyReceived <= 0 || $acceptedQty < 0 || $rejectedQty < 0) throw new Exception('Data penerimaan belum lengkap.');
    if ($acceptedQty + $rejectedQty > $qtyReceived + 0.00001) throw new Exception('Accepted + rejected tidak boleh melebihi qty diterima.');
    $receiptNo = trim($_POST['receipt_no'] ?? '') ?: enterprise_next_no('GR', 'goods_receipts', 'receipt_no');
    $lotNo = trim($_POST['lot_no'] ?? '');
    $serialNo = trim($_POST['serial_no'] ?? '');
    $receivedDate = $_POST['received_date'] ?: date('Y-m-d');

    db()->beginTransaction();
    try {
        q("INSERT INTO goods_receipts(receipt_no, po_id, supplier_id, item_id, location_id, qty_received, accepted_qty, rejected_qty, lot_no, serial_no, supplier_doc_no, invoice_no, received_date, status, note, created_by, created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,'completed',?,?,NOW())", [
            $receiptNo, $poId, $supplierId, $itemId, $locationId, $qtyReceived, $acceptedQty, $rejectedQty, $lotNo, $serialNo,
            trim($_POST['supplier_doc_no'] ?? ''), trim($_POST['invoice_no'] ?? ''), $receivedDate, trim($_POST['note'] ?? ''), current_user()['id']
        ]);
        $receiptId = (int)db()->lastInsertId();
        if ($acceptedQty > 0) {
            add_stock($itemId, $locationId, $acceptedQty, 'available');
            enterprise_add_lot_stock($itemId, $locationId, $acceptedQty, 'available', $lotNo, $serialNo, null, null, 'goods_receipts', $receiptId);
        }
        if ($rejectedQty > 0) {
            add_stock($itemId, $locationId, $rejectedQty, 'quarantine');
            enterprise_add_lot_stock($itemId, $locationId, $rejectedQty, 'quarantine', $lotNo, $serialNo, null, null, 'goods_receipts', $receiptId);
        }
        if ($poId) {
            q("UPDATE purchase_orders SET qty_received=qty_received+?, status=CASE WHEN qty_received + ? >= qty_ordered THEN 'received' ELSE 'partial' END, updated_at=NOW() WHERE id=?", [$acceptedQty, $acceptedQty, $poId]);
        }
        enterprise_save_attachment('goods_receipts', $receiptId);
        db()->commit();
        audit_log('Penerimaan Barang', $receiptNo . ' ' . item_name($itemId) . ' accepted ' . $acceptedQty);
        enterprise_notify_role('Manager', 'Barang diterima', $receiptNo . ' accepted ' . $acceptedQty . ' ' . item_name($itemId), 'receipt');
        flash('success', 'Penerimaan barang tersimpan dan stock diperbarui.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return true;
}

function enterprise_action_receipt_delete(): bool {
    require_perm('receipt_delete');
    $id = (int)($_POST['id'] ?? 0);
    $r = one("SELECT * FROM goods_receipts WHERE id=?", [$id]);
    if (!$r) throw new Exception('Penerimaan tidak ditemukan.');
    db()->beginTransaction();
    try {
        if ((float)$r['accepted_qty'] > 0) {
            if (!subtract_stock((int)$r['item_id'], (int)$r['location_id'], (float)$r['accepted_qty'], 'available')) throw new Exception('Saldo available tidak cukup untuk reverse receipt.');
            if (!enterprise_subtract_lot_stock((int)$r['item_id'], (int)$r['location_id'], (float)$r['accepted_qty'], 'available', (string)$r['lot_no'], (string)$r['serial_no'])) throw new Exception('Saldo lot available tidak cukup untuk reverse.');
        }
        if ((float)$r['rejected_qty'] > 0) {
            if (!subtract_stock((int)$r['item_id'], (int)$r['location_id'], (float)$r['rejected_qty'], 'quarantine')) throw new Exception('Saldo quarantine tidak cukup untuk reverse receipt.');
            if (!enterprise_subtract_lot_stock((int)$r['item_id'], (int)$r['location_id'], (float)$r['rejected_qty'], 'quarantine', (string)$r['lot_no'], (string)$r['serial_no'])) throw new Exception('Saldo lot quarantine tidak cukup untuk reverse.');
        }
        if ($r['po_id']) q("UPDATE purchase_orders SET qty_received=GREATEST(qty_received-?,0), status=CASE WHEN GREATEST(qty_received-?,0)=0 THEN 'ordered' ELSE 'partial' END, updated_at=NOW() WHERE id=?", [(float)$r['accepted_qty'], (float)$r['accepted_qty'], (int)$r['po_id']]);
        q("DELETE FROM goods_receipts WHERE id=?", [$id]);
        db()->commit();
        audit_log('Reverse Penerimaan', $r['receipt_no']);
        flash('success', 'Penerimaan dihapus dan stock direverse.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return true;
}

function enterprise_action_department_save(): bool {
    require_perm('master_org_manage');
    $id = (int)($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($name === '') throw new Exception('Nama department wajib diisi.');
    if ($id) q("UPDATE departments SET code=?, name=?, description=?, is_active=? WHERE id=?", [$code, $name, trim($_POST['description'] ?? ''), isset($_POST['is_active']) ? 1 : 0, $id]);
    else q("INSERT INTO departments(code,name,description,is_active) VALUES(?,?,?,1)", [$code, $name, trim($_POST['description'] ?? '')]);
    audit_log($id ? 'Update Department' : 'Tambah Department', $name);
    flash('success', 'Department tersimpan.');
    return true;
}

function enterprise_action_department_delete(): bool {
    require_perm('master_org_manage');
    q("DELETE FROM departments WHERE id=?", [(int)($_POST['id'] ?? 0)]);
    flash('success', 'Department dihapus.');
    return true;
}

function enterprise_action_cost_center_save(): bool {
    require_perm('master_org_manage');
    $id = (int)($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($code === '' || $name === '') throw new Exception('Kode dan nama cost center wajib diisi.');
    $departmentId = enterprise_fk_or_null('department_id');
    if ($id) q("UPDATE cost_centers SET code=?, name=?, department_id=?, is_active=? WHERE id=?", [$code, $name, $departmentId, isset($_POST['is_active']) ? 1 : 0, $id]);
    else q("INSERT INTO cost_centers(code,name,department_id,is_active) VALUES(?,?,?,1)", [$code, $name, $departmentId]);
    audit_log($id ? 'Update Cost Center' : 'Tambah Cost Center', $code);
    flash('success', 'Cost center tersimpan.');
    return true;
}

function enterprise_action_cost_center_delete(): bool {
    require_perm('master_org_manage');
    q("DELETE FROM cost_centers WHERE id=?", [(int)($_POST['id'] ?? 0)]);
    flash('success', 'Cost center dihapus.');
    return true;
}

function enterprise_action_customer_save(): bool {
    require_perm('master_org_manage');
    $id = (int)($_POST['id'] ?? 0);
    $data = [trim($_POST['name'] ?? ''), trim($_POST['contact_name'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['address'] ?? '')];
    if ($data[0] === '') throw new Exception('Nama customer wajib diisi.');
    if ($id) q("UPDATE customers SET name=?, contact_name=?, phone=?, email=?, address=? WHERE id=?", [...$data, $id]);
    else q("INSERT INTO customers(name,contact_name,phone,email,address) VALUES(?,?,?,?,?)", $data);
    flash('success', 'Customer tersimpan.');
    return true;
}

function enterprise_action_customer_delete(): bool {
    require_perm('master_org_manage');
    q("DELETE FROM customers WHERE id=?", [(int)($_POST['id'] ?? 0)]);
    flash('success', 'Customer dihapus.');
    return true;
}

function enterprise_action_project_save(): bool {
    require_perm('master_org_manage');
    $id = (int)($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($code === '' || $name === '') throw new Exception('Kode dan nama project wajib diisi.');
    $customerId = enterprise_fk_or_null('customer_id');
    $status = $_POST['status'] ?? 'active';
    if ($id) q("UPDATE projects SET code=?, name=?, customer_id=?, status=? WHERE id=?", [$code, $name, $customerId, $status, $id]);
    else q("INSERT INTO projects(code,name,customer_id,status) VALUES(?,?,?,?)", [$code, $name, $customerId, $status]);
    flash('success', 'Project tersimpan.');
    return true;
}

function enterprise_action_project_delete(): bool {
    require_perm('master_org_manage');
    q("DELETE FROM projects WHERE id=?", [(int)($_POST['id'] ?? 0)]);
    flash('success', 'Project dihapus.');
    return true;
}

function enterprise_action_work_order_save(): bool {
    require_perm('master_org_manage');
    $id = (int)($_POST['id'] ?? 0);
    $woNo = trim($_POST['wo_no'] ?? '');
    if ($woNo === '') throw new Exception('Nomor work order wajib diisi.');
    $data = [$woNo, enterprise_fk_or_null('project_id'), enterprise_fk_or_null('customer_id'), enterprise_fk_or_null('department_id'), trim($_POST['description'] ?? ''), $_POST['status'] ?? 'open', $_POST['due_date'] ?: null];
    if ($id) q("UPDATE work_orders SET wo_no=?, project_id=?, customer_id=?, department_id=?, description=?, status=?, due_date=? WHERE id=?", [...$data, $id]);
    else q("INSERT INTO work_orders(wo_no,project_id,customer_id,department_id,description,status,due_date) VALUES(?,?,?,?,?,?,?)", $data);
    flash('success', 'Work order tersimpan.');
    return true;
}

function enterprise_action_work_order_delete(): bool {
    require_perm('master_org_manage');
    q("DELETE FROM work_orders WHERE id=?", [(int)($_POST['id'] ?? 0)]);
    flash('success', 'Work order dihapus.');
    return true;
}

function enterprise_action_lot_save(): bool {
    require_perm('lot_manage');
    $itemId = (int)($_POST['item_id'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $qty = (float)($_POST['qty'] ?? 0);
    $status = trim($_POST['stock_status'] ?? 'available') ?: 'available';
    $mode = $_POST['mode'] ?? 'add';
    $lotNo = trim($_POST['lot_no'] ?? '');
    $serialNo = trim($_POST['serial_no'] ?? '');
    if (!$itemId || !$locationId || $qty < 0 || ($lotNo === '' && $serialNo === '')) throw new Exception('Barang, lokasi, qty, dan lot/serial wajib diisi.');
    if ($mode === 'set') {
        $old = enterprise_lot_qty($itemId, $locationId, $status, $lotNo, $serialNo);
        $diff = $qty - $old;
        q("DELETE FROM item_lots WHERE item_id=? AND location_id=? AND stock_status=? AND lot_no=? AND serial_no=?", [$itemId, $locationId, $status, $lotNo, $serialNo]);
        if ($qty > 0) enterprise_add_lot_stock($itemId, $locationId, $qty, $status, $lotNo, $serialNo, $_POST['production_date'] ?: null, $_POST['expired_date'] ?: null, 'manual_lot', null);
        if ($diff > 0) add_stock($itemId, $locationId, $diff, $status);
        if ($diff < 0 && !subtract_stock($itemId, $locationId, abs($diff), $status)) throw new Exception('Saldo stock tidak cukup untuk set lot.');
    } else {
        enterprise_add_lot_stock($itemId, $locationId, $qty, $status, $lotNo, $serialNo, $_POST['production_date'] ?: null, $_POST['expired_date'] ?: null, 'manual_lot', null);
        add_stock($itemId, $locationId, $qty, $status);
    }
    audit_log('Kelola Lot Serial', item_name($itemId) . ' lot ' . $lotNo . ' serial ' . $serialNo);
    flash('success', 'Lot/serial tersimpan.');
    return true;
}

function enterprise_action_picking_save(): bool {
    require_perm('picking_manage');
    $itemId = (int)($_POST['item_id'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $qty = (float)($_POST['qty'] ?? 0);
    if (!$itemId || !$locationId || $qty <= 0) throw new Exception('Barang, lokasi, dan qty wajib diisi.');
    $slipNo = trim($_POST['slip_no'] ?? '') ?: enterprise_next_no('ISS', 'picking_slips', 'slip_no');
    q("INSERT INTO picking_slips(slip_no,item_id,location_id,qty,lot_no,serial_no,department_id,cost_center_id,customer_id,project_id,work_order_id,requested_by,status,note,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'requested',?,NOW())", [
        $slipNo, $itemId, $locationId, $qty, trim($_POST['lot_no'] ?? ''), trim($_POST['serial_no'] ?? ''), enterprise_fk_or_null('department_id'), enterprise_fk_or_null('cost_center_id'), enterprise_fk_or_null('customer_id'), enterprise_fk_or_null('project_id'), enterprise_fk_or_null('work_order_id'), current_user()['id'], trim($_POST['note'] ?? '')
    ]);
    audit_log('Buat Picking Slip', $slipNo);
    enterprise_notify_role('Staff Gudang', 'Picking slip baru', $slipNo . ' untuk ' . item_name($itemId), 'picking');
    flash('success', 'Picking slip dibuat.');
    return true;
}

function enterprise_action_picking_pick(): bool {
    require_perm('picking_manage');
    $id = (int)($_POST['id'] ?? 0);
    $p = one("SELECT * FROM picking_slips WHERE id=?", [$id]);
    if (!$p || $p['status'] !== 'requested') throw new Exception('Picking slip tidak valid.');
    db()->beginTransaction();
    try {
        if (!subtract_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'available')) throw new Exception('Stock tidak cukup untuk picking.');
        add_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'reserved');
        if (!enterprise_subtract_lot_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'available', (string)$p['lot_no'], (string)$p['serial_no'])) throw new Exception('Saldo lot tidak cukup untuk picking.');
        enterprise_add_lot_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'reserved', (string)$p['lot_no'], (string)$p['serial_no'], null, null, 'picking_slips', $id);
        q("UPDATE picking_slips SET status='picked', picked_by=?, picked_at=NOW() WHERE id=?", [current_user()['id'], $id]);
        db()->commit();
        flash('success', 'Barang ditandai picked dan stock direservasi.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return true;
}

function enterprise_action_picking_issue(): bool {
    require_perm('picking_manage');
    $id = (int)($_POST['id'] ?? 0);
    $p = one("SELECT * FROM picking_slips WHERE id=?", [$id]);
    if (!$p || $p['status'] !== 'picked') throw new Exception('Picking slip belum picked.');
    db()->beginTransaction();
    try {
        if (!subtract_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'reserved')) throw new Exception('Stock reserved tidak cukup untuk issue.');
        if (!enterprise_subtract_lot_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'reserved', (string)$p['lot_no'], (string)$p['serial_no'])) throw new Exception('Saldo lot reserved tidak cukup untuk issue.');
        q("INSERT INTO stock_movements(movement_type,item_id,from_location_id,to_location_id,qty,status,department,department_id,cost_center,cost_center_id,customer_id,project_id,work_order_id,reference_no,lot_no,serial_no,note,created_by,approved_by,created_at,approved_at) VALUES('out',?,?,?,?, 'completed',?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())", [
            (int)$p['item_id'], (int)$p['location_id'], null, (float)$p['qty'],
            enterprise_entity_name('departments', (int)$p['department_id']), $p['department_id'],
            enterprise_entity_name('cost_centers', (int)$p['cost_center_id'], 'code'), $p['cost_center_id'],
            $p['customer_id'], $p['project_id'], $p['work_order_id'], $p['slip_no'], $p['lot_no'], $p['serial_no'], $p['note'], current_user()['id'], current_user()['id']
        ]);
        q("UPDATE picking_slips SET status='issued', issued_by=?, issued_at=NOW() WHERE id=?", [current_user()['id'], $id]);
        db()->commit();
        audit_log('Issue Picking Slip', $p['slip_no']);
        flash('success', 'Picking slip issued dan stock reserved dikeluarkan.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return true;
}

function enterprise_action_picking_cancel(): bool {
    require_perm('picking_manage');
    $id = (int)($_POST['id'] ?? 0);
    $p = one("SELECT * FROM picking_slips WHERE id=?", [$id]);
    if (!$p || $p['status'] === 'issued') throw new Exception('Picking slip tidak dapat dibatalkan.');
    db()->beginTransaction();
    try {
        if ($p['status'] === 'picked') {
            if (!subtract_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'reserved')) throw new Exception('Stock reserved tidak cukup untuk cancel.');
            add_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'available');
            if (!enterprise_subtract_lot_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'reserved', (string)$p['lot_no'], (string)$p['serial_no'])) throw new Exception('Saldo lot reserved tidak cukup untuk cancel.');
            enterprise_add_lot_stock((int)$p['item_id'], (int)$p['location_id'], (float)$p['qty'], 'available', (string)$p['lot_no'], (string)$p['serial_no'], null, null, 'picking_slips', $id);
        }
        q("UPDATE picking_slips SET status='cancelled' WHERE id=?", [$id]);
        db()->commit();
        flash('success', 'Picking slip dibatalkan.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return true;
}

function enterprise_action_cycle_save(): bool {
    require_perm('cycle_manage');
    $planNo = trim($_POST['plan_no'] ?? '') ?: enterprise_next_no('CC', 'cycle_count_plans', 'plan_no');
    q("INSERT INTO cycle_count_plans(plan_no,location_id,category_id,assigned_to,due_date,status,note,created_by,created_at) VALUES(?,?,?,?,?,'open',?,?,NOW())", [
        $planNo, enterprise_fk_or_null('location_id'), enterprise_fk_or_null('category_id'), enterprise_fk_or_null('assigned_to'), $_POST['due_date'] ?: null, trim($_POST['note'] ?? ''), current_user()['id']
    ]);
    flash('success', 'Jadwal cycle count dibuat.');
    return true;
}

function enterprise_action_cycle_status(): bool {
    require_perm('cycle_manage');
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'closed';
    if (!in_array($status, ['open','closed','cancelled'], true)) throw new Exception('Status cycle tidak valid.');
    q("UPDATE cycle_count_plans SET status=?, closed_at=CASE WHEN ?='closed' THEN NOW() ELSE closed_at END WHERE id=?", [$status, $status, $id]);
    flash('success', 'Status cycle count diperbarui.');
    return true;
}

function enterprise_action_notification_read(): bool {
    require_perm('page_notifications');
    q("UPDATE notifications SET is_read=1 WHERE id=? AND (user_id=? OR user_id IS NULL OR role_id=?)", [(int)($_POST['id'] ?? 0), current_user()['id'], current_user()['role_id']]);
    return true;
}

function enterprise_action_notification_read_all(): bool {
    require_perm('page_notifications');
    q("UPDATE notifications SET is_read=1 WHERE user_id=? OR role_id=? OR (user_id IS NULL AND role_id IS NULL)", [current_user()['id'], current_user()['role_id']]);
    flash('success', 'Semua notifikasi ditandai sudah dibaca.');
    return true;
}

function enterprise_action_import_csv(): bool {
    require_perm('import_manage');
    if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) throw new Exception('File CSV belum dipilih.');
    $target = $_POST['target'] ?? '';
    $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$fh) throw new Exception('File CSV tidak bisa dibaca.');
    $headers = fgetcsv($fh);
    if (!$headers) throw new Exception('CSV harus memiliki header.');
    $headers = array_map(fn($h) => strtolower(trim((string)$h)), $headers);
    $count = 0;
    while (($row = fgetcsv($fh)) !== false) {
        $data = [];
        foreach ($headers as $i => $key) $data[$key] = trim((string)($row[$i] ?? ''));
        if (enterprise_import_row($target, $data)) $count++;
    }
    fclose($fh);
    audit_log('Import CSV', $target . ' ' . $count . ' baris');
    flash('success', 'Import selesai: ' . $count . ' baris diproses.');
    return true;
}

function enterprise_import_row(string $target, array $d): bool {
    if ($target === 'items') {
        if (($d['sku'] ?? '') === '' || ($d['name'] ?? '') === '') return false;
        $categoryId = null;
        if (!empty($d['category'])) {
            q("INSERT IGNORE INTO categories(name) VALUES(?)", [$d['category']]);
            $categoryId = (int)scalar("SELECT id FROM categories WHERE name=?", [$d['category']]);
        }
        $supplierId = null;
        if (!empty($d['supplier'])) {
            q("INSERT IGNORE INTO suppliers(name) VALUES(?)", [$d['supplier']]);
            $supplierId = (int)scalar("SELECT id FROM suppliers WHERE name=?", [$d['supplier']]);
        }
        q("INSERT INTO items(sku,name,category_id,supplier_id,unit,min_stock,safety_stock,reorder_point,max_stock,default_lead_time_days,price,barcode,batch_no,expired_date,issue_method,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name), category_id=VALUES(category_id), supplier_id=VALUES(supplier_id), unit=VALUES(unit), min_stock=VALUES(min_stock), safety_stock=VALUES(safety_stock), reorder_point=VALUES(reorder_point), max_stock=VALUES(max_stock), default_lead_time_days=VALUES(default_lead_time_days), price=VALUES(price), barcode=VALUES(barcode), batch_no=VALUES(batch_no), expired_date=VALUES(expired_date), updated_at=NOW()", [
            $d['sku'], $d['name'], $categoryId ?: null, $supplierId ?: null, $d['unit'] ?: 'PCS', (float)($d['min_stock'] ?? 0), (float)($d['safety_stock'] ?? 0), (float)($d['reorder_point'] ?? 0), (float)($d['max_stock'] ?? 0), (int)($d['lead_time_days'] ?? 0), (float)($d['price'] ?? 0), $d['barcode'] ?? '', $d['batch_no'] ?? '', ($d['expired_date'] ?? '') ?: null, ($d['issue_method'] ?? '') ?: 'FIFO'
        ]);
        return true;
    }
    if ($target === 'suppliers') {
        if (($d['name'] ?? '') === '') return false;
        q("INSERT INTO suppliers(name,contact_name,phone,email,address,lead_time_days,last_price,notes) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE contact_name=VALUES(contact_name), phone=VALUES(phone), email=VALUES(email), address=VALUES(address), lead_time_days=VALUES(lead_time_days), last_price=VALUES(last_price), notes=VALUES(notes)", [$d['name'], $d['contact_name'] ?? '', $d['phone'] ?? '', $d['email'] ?? '', $d['address'] ?? '', (int)($d['lead_time_days'] ?? 0), (float)($d['last_price'] ?? 0), $d['notes'] ?? '']);
        return true;
    }
    if ($target === 'stock') {
        $itemId = (int)scalar("SELECT id FROM items WHERE sku=?", [$d['sku'] ?? '']);
        $locationId = (int)scalar("SELECT id FROM locations WHERE code=? LIMIT 1", [$d['location'] ?? '']);
        $qty = (float)($d['qty'] ?? 0);
        if (!$itemId || !$locationId || $qty == 0.0) return false;
        add_stock($itemId, $locationId, $qty, $d['status'] ?: 'available');
        return true;
    }
    if ($target === 'departments') {
        if (($d['name'] ?? '') === '') return false;
        q("INSERT INTO departments(code,name,description,is_active) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE code=VALUES(code), description=VALUES(description)", [$d['code'] ?? '', $d['name'], $d['description'] ?? '']);
        return true;
    }
    if ($target === 'cost_centers') {
        if (($d['code'] ?? '') === '') return false;
        $departmentId = !empty($d['department']) ? (int)scalar("SELECT id FROM departments WHERE name=? OR code=? LIMIT 1", [$d['department'], $d['department']]) : null;
        q("INSERT INTO cost_centers(code,name,department_id,is_active) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name), department_id=VALUES(department_id)", [$d['code'], $d['name'] ?: $d['code'], $departmentId ?: null]);
        return true;
    }
    if ($target === 'customers') {
        if (($d['name'] ?? '') === '') return false;
        q("INSERT INTO customers(name,contact_name,phone,email,address) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE contact_name=VALUES(contact_name), phone=VALUES(phone), email=VALUES(email), address=VALUES(address)", [$d['name'], $d['contact_name'] ?? '', $d['phone'] ?? '', $d['email'] ?? '', $d['address'] ?? '']);
        return true;
    }
    return false;
}

function enterprise_action_backup_create(): bool {
    require_perm('maintenance_manage');
    $dir = __DIR__ . '/../uploads/backups';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    if (!is_file($dir . '/.htaccess')) file_put_contents($dir . '/.htaccess', "Deny from all\n");
    $fileName = 'warehouse_backup_' . date('Ymd_His') . '.sql';
    $path = $dir . '/' . $fileName;
    $sql = "-- Warehouse backup " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n";
    $tables = q("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $t) {
        $table = $t[0];
        if (!enterprise_valid_identifier($table)) continue;
        $sql .= "\n-- Data table `$table`\nTRUNCATE TABLE `$table`;\n";
        $rows = all_rows("SELECT * FROM `$table`");
        foreach ($rows as $row) {
            $cols = array_map(fn($c) => "`" . str_replace("`", "``", $c) . "`", array_keys($row));
            $vals = array_map(fn($v) => $v === null ? 'NULL' : db()->quote((string)$v), array_values($row));
            $sql .= "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES(" . implode(',', $vals) . ");\n";
        }
    }
    $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
    file_put_contents($path, $sql);
    q("INSERT INTO backup_logs(file_name,size_bytes,status,created_by,created_at,note) VALUES(?,?,?,?,NOW(),?)", [$fileName, filesize($path), 'created', current_user()['id'], 'Backup manual dari aplikasi']);
    audit_log('Backup Database', $fileName);
    flash('success', 'Backup dibuat: ' . $fileName);
    return true;
}

function enterprise_split_sql(string $sql): array {
    $statements = [];
    $buf = '';
    $quote = null;
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $buf .= $ch;
        if (($ch === "'" || $ch === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
            $quote = $quote === $ch ? null : ($quote ?: $ch);
        }
        if ($ch === ';' && $quote === null) {
            $stmt = trim($buf);
            if ($stmt !== ';' && $stmt !== '') $statements[] = $stmt;
            $buf = '';
        }
    }
    $tail = trim($buf);
    if ($tail !== '') $statements[] = $tail;
    return $statements;
}

function enterprise_action_backup_restore(): bool {
    require_perm('maintenance_manage');
    if (empty($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) throw new Exception('File SQL belum dipilih.');
    $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
    if ($sql === false || trim($sql) === '') throw new Exception('File SQL kosong.');
    db()->beginTransaction();
    try {
        foreach (enterprise_split_sql($sql) as $stmt) db()->exec($stmt);
        db()->commit();
        audit_log('Restore Database', $_FILES['sql_file']['name']);
        flash('success', 'Restore SQL selesai.');
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return true;
}

function enterprise_action_api_token_create(): bool {
    require_perm('api_manage');
    $name = trim($_POST['name'] ?? '');
    if ($name === '') throw new Exception('Nama token wajib diisi.');
    $token = bin2hex(random_bytes(24));
    q("INSERT INTO api_tokens(token_hash, token_prefix, name, is_active, created_by, created_at) VALUES(?,?,?,?,?,NOW())", [hash('sha256', $token), substr($token, 0, 12), $name, 1, current_user()['id']]);
    $_SESSION['api_plain_token'] = $token;
    flash('success', 'API token dibuat. Salin token yang tampil di halaman tools.');
    return true;
}

function enterprise_action_api_token_toggle(): bool {
    require_perm('api_manage');
    $id = (int)($_POST['id'] ?? 0);
    q("UPDATE api_tokens SET is_active=1-is_active WHERE id=?", [$id]);
    flash('success', 'Status token diperbarui.');
    return true;
}
