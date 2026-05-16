-- Warehouse Inventory QR System
-- Import file ini ke database MySQL eksternal sebelum deploy ke Vercel.
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS backup_logs;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS notification_outbox;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS cycle_count_plans;
DROP TABLE IF EXISTS picking_slips;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS item_lots;
DROP TABLE IF EXISTS goods_receipts;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS work_orders;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS cost_centers;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS quality_records;
DROP TABLE IF EXISTS purchase_requests;
DROP TABLE IF EXISTS stock_counts;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS stock_balances;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS warehouses;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  perm_key VARCHAR(80) NOT NULL UNIQUE,
  label VARCHAR(120) NOT NULL,
  group_name VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY(role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(50) NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  CONSTRAINT fk_user_role FOREIGN KEY(role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_name VARCHAR(120) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  lead_time_days INT NOT NULL DEFAULT 0,
  last_price DECIMAL(18,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  UNIQUE KEY uq_supplier_name(name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE warehouses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  address TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  warehouse_id INT NOT NULL,
  code VARCHAR(50) NOT NULL,
  description VARCHAR(255) NULL,
  UNIQUE KEY uq_location(warehouse_id, code),
  CONSTRAINT fk_location_warehouse FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NULL,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_department_name(name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cost_centers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  department_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_cost_code(code),
  CONSTRAINT fk_cc_department FOREIGN KEY(department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_name VARCHAR(120) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  UNIQUE KEY uq_customer_name(name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL,
  name VARCHAR(160) NOT NULL,
  customer_id INT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  UNIQUE KEY uq_project_code(code),
  CONSTRAINT fk_project_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE work_orders (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(180) NOT NULL,
  category_id INT NULL,
  supplier_id INT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'PCS',
  min_stock DECIMAL(18,2) NOT NULL DEFAULT 0,
  safety_stock DECIMAL(18,2) NOT NULL DEFAULT 0,
  reorder_point DECIMAL(18,2) NOT NULL DEFAULT 0,
  max_stock DECIMAL(18,2) NOT NULL DEFAULT 0,
  default_lead_time_days INT NOT NULL DEFAULT 0,
  price DECIMAL(18,2) NOT NULL DEFAULT 0,
  barcode VARCHAR(120) NULL,
  batch_no VARCHAR(120) NULL,
  production_date DATE NULL,
  expired_date DATE NULL,
  issue_method VARCHAR(10) NOT NULL DEFAULT 'FIFO',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_item_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_item_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_balances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  location_id INT NOT NULL,
  stock_status VARCHAR(30) NOT NULL DEFAULT 'available',
  qty DECIMAL(18,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_stock(item_id, location_id, stock_status),
  CONSTRAINT fk_sb_item FOREIGN KEY(item_id) REFERENCES items(id) ON DELETE CASCADE,
  CONSTRAINT fk_sb_location FOREIGN KEY(location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  movement_type VARCHAR(30) NOT NULL,
  item_id INT NOT NULL,
  from_location_id INT NULL,
  to_location_id INT NULL,
  qty DECIMAL(18,2) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  department VARCHAR(120) NULL,
  department_id INT NULL,
  cost_center VARCHAR(120) NULL,
  cost_center_id INT NULL,
  customer_id INT NULL,
  project_id INT NULL,
  work_order_id INT NULL,
  reference_no VARCHAR(120) NULL,
  lot_no VARCHAR(120) NULL,
  serial_no VARCHAR(120) NULL,
  note TEXT NULL,
  created_by INT NULL,
  approved_by INT NULL,
  created_at DATETIME NULL,
  approved_at DATETIME NULL,
  KEY idx_sm_item_status(item_id, status),
  KEY idx_sm_org(department_id, cost_center_id, customer_id, project_id, work_order_id),
  CONSTRAINT fk_sm_item FOREIGN KEY(item_id) REFERENCES items(id),
  CONSTRAINT fk_sm_from FOREIGN KEY(from_location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_sm_to FOREIGN KEY(to_location_id) REFERENCES locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_sm_created FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_sm_approved FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_counts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  location_id INT NOT NULL,
  system_qty DECIMAL(18,2) NOT NULL DEFAULT 0,
  physical_qty DECIMAL(18,2) NOT NULL DEFAULT 0,
  variance DECIMAL(18,2) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'selisih',
  counted_by INT NULL,
  note TEXT NULL,
  created_at DATETIME NULL,
  CONSTRAINT fk_sc_item FOREIGN KEY(item_id) REFERENCES items(id),
  CONSTRAINT fk_sc_location FOREIGN KEY(location_id) REFERENCES locations(id),
  CONSTRAINT fk_sc_user FOREIGN KEY(counted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  supplier_id INT NULL,
  qty DECIMAL(18,2) NOT NULL,
  department VARCHAR(120) NULL,
  priority VARCHAR(30) NOT NULL DEFAULT 'Normal',
  expected_date DATE NULL,
  reason TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  requested_by INT NULL,
  created_at DATETIME NULL,
  KEY idx_pr_status(status),
  KEY idx_pr_supplier(supplier_id),
  CONSTRAINT fk_pr_item FOREIGN KEY(item_id) REFERENCES items(id),
  CONSTRAINT fk_pr_user FOREIGN KEY(requested_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(40) NOT NULL,
  item_id INT NOT NULL,
  location_id INT NOT NULL,
  qty DECIMAL(18,2) NOT NULL,
  note TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NULL,
  CONSTRAINT fk_qr_item FOREIGN KEY(item_id) REFERENCES items(id),
  CONSTRAINT fk_qr_location FOREIGN KEY(location_id) REFERENCES locations(id),
  CONSTRAINT fk_qr_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50) NOT NULL,
  ref_table VARCHAR(80) NOT NULL,
  ref_id INT NOT NULL,
  payload TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  requester_id INT NULL,
  approved_by INT NULL,
  notes TEXT NULL,
  created_at DATETIME NULL,
  approved_at DATETIME NULL,
  KEY idx_approval_ref(ref_table, ref_id, status),
  CONSTRAINT fk_ap_requester FOREIGN KEY(requester_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_ap_approver FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(120) NOT NULL,
  detail TEXT NULL,
  ip_address VARCHAR(60) NULL,
  created_at DATETIME NULL,
  KEY idx_audit_created(created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_orders (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE goods_receipts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE item_lots (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attachments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notification_outbox (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE picking_slips (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cycle_count_plans (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE api_tokens (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE backup_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_name VARCHAR(255) NOT NULL,
  size_bytes INT NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'created',
  created_by INT NULL,
  created_at DATETIME NULL,
  note TEXT NULL,
  CONSTRAINT fk_backup_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles(id,name,description) VALUES
(1,'Admin','Full access'),
(2,'Manager','Approval dan laporan'),
(3,'Staff Gudang','Operasional gudang'),
(4,'Viewer','Hanya lihat data');

INSERT INTO permissions(id,perm_key,label,group_name) VALUES
(1,'page_dashboard','Akses Dashboard','Halaman'),
(2,'page_master','Akses Master Barang','Halaman'),
(3,'item_write','Tambah/Edit Barang','Master Barang'),
(4,'item_delete','Hapus Barang','Master Barang'),
(5,'page_stock','Akses Data Stock','Halaman'),
(6,'page_qr','Akses Generate QR','Halaman'),
(7,'qr_generate','Generate/Cetak QR','QR'),
(8,'page_movement','Akses Stock Masuk/Keluar','Halaman'),
(9,'stock_movement','Input Transaksi Stock','Stock'),
(10,'page_opname','Akses Stock Opname QR','Halaman'),
(11,'stock_opname','Input Stock Opname','Stock Opname'),
(12,'page_transfer','Akses Transfer Gudang','Halaman'),
(13,'page_quality','Akses Retur & Karantina','Halaman'),
(14,'quality_write','Input Retur/Karantina','Quality'),
(15,'page_purchase','Akses Purchase Request','Halaman'),
(16,'purchase_write','Input Purchase Request','Purchase'),
(17,'page_approval','Akses Approval','Halaman'),
(18,'approval_manage','Approve/Reject Transaksi','Approval'),
(19,'page_supplier','Akses Supplier','Halaman'),
(20,'supplier_write','Tambah/Edit Supplier','Supplier'),
(21,'page_reports','Akses Laporan','Halaman'),
(22,'report_export','Export Laporan CSV','Laporan'),
(23,'page_audit','Akses Audit Trail','Halaman'),
(24,'page_roles','Akses Role Management','Halaman'),
(25,'user_manage','Tambah/Edit User','User'),
(26,'page_settings','Akses Setting Data','Halaman'),
(27,'settings_write','Tambah/Edit Setting Data','Setting'),
(28,'supplier_delete','Hapus Supplier','Supplier'),
(29,'movement_cancel','Batalkan Transaksi Pending','Stock'),
(30,'movement_delete','Hapus Transaksi Draft/Rejected','Stock'),
(31,'opname_delete','Hapus Opname Belum Approved','Stock Opname'),
(32,'quality_delete','Hapus/Reverse Quality','Quality'),
(33,'purchase_update','Edit/Batalkan Purchase Request','Purchase'),
(34,'purchase_delete','Hapus Purchase Request','Purchase'),
(35,'role_manage','Tambah/Edit/Hapus Role','Role Management'),
(36,'user_delete','Hapus User','User'),
(37,'settings_delete','Hapus Setting Data','Setting'),
(38,'page_procurement','Akses PO & Penerimaan','Halaman'),
(39,'po_manage','Kelola Purchase Order','Procurement'),
(40,'receipt_manage','Input Penerimaan Barang','Procurement'),
(41,'receipt_delete','Hapus/Reverse Penerimaan','Procurement'),
(42,'page_picking','Akses Picking Slip','Halaman'),
(43,'picking_manage','Kelola Picking & Issue Slip','Picking'),
(44,'page_lots','Akses Lot & Serial','Halaman'),
(45,'lot_manage','Kelola Lot & Serial','Lot Serial'),
(46,'page_stock_card','Akses Kartu Stock','Halaman'),
(47,'page_cycle','Akses Cycle Count','Halaman'),
(48,'cycle_manage','Kelola Jadwal Cycle Count','Cycle Count'),
(49,'page_analytics','Akses Analitik Stock','Halaman'),
(50,'page_notifications','Akses Notifikasi','Halaman'),
(51,'notification_manage','Kelola Notifikasi','Notifikasi'),
(52,'page_tools','Akses Tools Import/Backup/API','Halaman'),
(53,'import_manage','Import Data CSV/Excel','Tools'),
(54,'maintenance_manage','Backup Restore Database','Tools'),
(55,'api_manage','Kelola API Token','Tools'),
(56,'master_org_manage','Kelola Master Dept/Project/Customer','Master Organisasi');

INSERT INTO role_permissions(role_id, permission_id) SELECT 1, id FROM permissions;

INSERT INTO role_permissions(role_id, permission_id) VALUES
(2,1),(2,2),(2,5),(2,6),(2,7),(2,10),(2,13),(2,15),(2,17),(2,18),(2,19),(2,20),(2,21),(2,22),(2,23),(2,24),(2,33),(2,38),(2,39),(2,40),(2,42),(2,43),(2,44),(2,46),(2,47),(2,48),(2,49),(2,50),(2,51),(2,52),(2,53),(2,56),
(3,1),(3,2),(3,3),(3,5),(3,6),(3,7),(3,8),(3,9),(3,10),(3,11),(3,12),(3,13),(3,14),(3,15),(3,16),(3,29),(3,38),(3,40),(3,42),(3,43),(3,44),(3,45),(3,46),(3,47),(3,50),
(4,1),(4,5),(4,21),(4,46),(4,49),(4,50);

INSERT INTO users(id,name,email,password,role_id,is_active,created_at) VALUES
(1,'Admin Gudang','admin@warehouse.local','$2y$12$FBCNh2rc..T5OcyUWuVyxeSZ0ecHgNVPu6RI.8ynh1dG/2Ihn/kTa',1,1,NOW()),
(2,'Manager Operasional','manager@warehouse.local','$2y$12$T/KJXAvqpmVhrcLGmxvj3uWo1UW7jRjTTqCFdShuucg8CDpHWv8sC',2,1,NOW()),
(3,'Staff Gudang','staff@warehouse.local','$2y$12$ajx9l5K43OoiebFkHTWYmubfUh3V/hdR5A48fPYJSkJ6s7bT7ykwy',3,1,NOW()),
(4,'Viewer','viewer@warehouse.local','$2y$12$6O7whkEnkZWhtWB7OVLJYeZPiV6P2EaVkYy5IsInF2LVBB/vnt3I2',4,1,NOW());

INSERT INTO categories(id,name) VALUES
(1,'Adhesive'),(2,'Chemical'),(3,'Packaging'),(4,'Sparepart'),(5,'Consumable');

INSERT INTO suppliers(id,name,contact_name,phone,email,address,lead_time_days,last_price,notes) VALUES
(1,'PT Kimia Prima','Budi','0812-1111-2222','sales@kimiaprima.co.id','Jakarta',5,65000,'Supplier adhesive utama'),
(2,'CV Packaging Jaya','Sinta','0812-3333-4444','order@packjaya.co.id','Bandung',3,18000,'Packaging dan label'),
(3,'PT Teknik Mandiri','Agus','0812-5555-6666','sales@teknikmandiri.co.id','Surabaya',7,120000,'Sparepart mesin');

INSERT INTO warehouses(id,name,address) VALUES
(1,'Gudang Utama','Area pusat'),
(2,'Gudang Produksi','Dekat area produksi'),
(3,'Area Karantina','Area barang bermasalah');

INSERT INTO locations(id,warehouse_id,code,description) VALUES
(1,1,'A-01','Adhesive'),
(2,1,'A-02','Chemical'),
(3,1,'B-01','Packaging & sparepart'),
(4,2,'P-01','Kebutuhan produksi'),
(5,3,'Q-01','Karantina');

INSERT INTO departments(id,code,name,description) VALUES
(1,'WH','Warehouse','Operasional gudang'),
(2,'PRD','Produksi','Pemakaian produksi'),
(3,'MTC','Maintenance','Perawatan mesin');

INSERT INTO cost_centers(id,code,name,department_id) VALUES
(1,'WH-001','Warehouse Utama',1),
(2,'PRD-001','Produksi Line 1',2),
(3,'MTC-001','Maintenance Umum',3);

INSERT INTO items(id,sku,name,category_id,supplier_id,unit,min_stock,safety_stock,reorder_point,max_stock,default_lead_time_days,price,barcode,batch_no,production_date,expired_date,issue_method,created_at,updated_at) VALUES
(1,'BRG-0001','Lem Industri A',1,1,'Pail',30,10,50,200,5,65000,'89900010001','BTH-ADH-2401','2026-01-12','2026-09-30','FEFO',NOW(),NOW()),
(2,'BRG-0002','Hardener B',2,1,'Liter',20,5,30,120,5,92000,'89900010002','BTH-HRD-2402','2026-02-01','2026-07-12','FEFO',NOW(),NOW()),
(3,'BRG-0003','Label QR Thermal',3,2,'Roll',10,3,15,80,3,18000,'89900010003','PKG-5521','2026-03-01','2027-03-01','FIFO',NOW(),NOW()),
(4,'BRG-0004','Nozzle Mesin Glue',4,3,'PCS',8,2,10,40,7,120000,'89900010004','SP-771','2025-12-20',NULL,'FIFO',NOW(),NOW()),
(5,'BRG-0005','Cleaner Solvent',2,1,'Liter',25,5,35,150,5,45000,'89900010005','SOL-991','2026-01-15','2026-06-15','FEFO',NOW(),NOW());

INSERT INTO stock_balances(item_id,location_id,stock_status,qty) VALUES
(1,1,'available',120),
(1,4,'reserved',18),
(2,2,'available',16),
(3,3,'available',42),
(4,3,'available',7),
(5,2,'available',26),
(5,5,'quarantine',6);

INSERT INTO stock_movements(movement_type,item_id,from_location_id,to_location_id,qty,status,department,cost_center,reference_no,note,created_by,created_at) VALUES
('in',1,NULL,1,50,'completed','Warehouse','WH-001','PO-2026-001','Stock awal tambahan',1,NOW()),
('out',1,1,NULL,12,'completed','Produksi','PRD-001','WO-1001','Pemakaian produksi',1,NOW());

INSERT INTO stock_counts(item_id,location_id,system_qty,physical_qty,variance,status,counted_by,note,created_at) VALUES
(4,3,7,6,-1,'selisih',3,'Selisih sparepart',NOW());

INSERT INTO approvals(type,ref_table,ref_id,payload,status,requester_id,notes,created_at) VALUES
('stock_adjustment','stock_counts',1,'{"count_id":1,"item_id":4,"location_id":3,"target_qty":6}','pending',3,'Koreksi stock dari hasil opname',NOW());

INSERT INTO audit_logs(user_id,action,detail,ip_address,created_at) VALUES
(1,'Install Demo Data','Database awal berhasil dibuat',NULL,NOW());

SET FOREIGN_KEY_CHECKS=1;
