# Warehouse Inventory QR System - PHP + MySQL

Aplikasi web inventory gudang berbasis **PHP + MySQL** yang siap di-upload ke shared hosting/cPanel.

## Fitur Utama

- Login user dan role access
- Role Management: Admin/Manager dapat membatasi akses tiap role
- Dashboard inventory
- Master barang CRUD
- Supplier management
- Multi gudang dan lokasi rak/bin
- Data stock per lokasi dan status
- Stock masuk, stock keluar, reservasi, koreksi, transfer lokasi
- Approval system untuk transaksi penting
- Generate dan cetak QR barang
- Scan QR untuk stock opname via kamera HP
- Stock opname otomatis membandingkan stock sistem vs fisik
- Retur, karantina, damaged, disposal
- Purchase request
- Purchase Order dari PR dan goods receipt/penerimaan barang
- Tracking lot/serial number
- Picking slip dan issue barang ke department/customer/project/work order
- Master department, cost center, customer, project, dan work order
- Cycle count terjadwal
- Kartu stock per barang
- Analitik fast moving, slow moving, dead stock, dan rekomendasi reorder
- Import CSV dari Excel, backup/restore SQL, API token, dan endpoint API
- Notifikasi in-app dengan outbox email/WhatsApp
- Laporan dan export CSV
- Audit trail aktivitas user
- Responsive otomatis: desktop di laptop/PC, mobile friendly di HP

## Kebutuhan Hosting

- PHP 7.4+ direkomendasikan PHP 8+
- MySQL/MariaDB
- Ekstensi PHP PDO MySQL aktif
- Browser modern untuk scanner QR

## Cara Install di cPanel / Shared Hosting

### 1. Buat Database

Di cPanel buka **MySQL Database Wizard**:

1. Buat database, contoh: `cpaneluser_warehouse`
2. Buat user database, contoh: `cpaneluser_whuser`
3. Berikan user tersebut privilege **ALL PRIVILEGES** ke database

### 2. Import Database

Buka **phpMyAdmin**:

1. Pilih database yang dibuat
2. Klik tab **Import**
3. Upload file:

```text
/database/schema.sql
```

4. Klik **Go / Import**

### 3. Upload File Web

Upload seluruh isi folder project ke:

```text
public_html/
```

atau ke subfolder, misalnya:

```text
public_html/gudang/
```

### 4. Edit Koneksi Database

Buka file:

```text
config/database.php
```

Ubah sesuai database hosting:

```php
$db_host = 'localhost';
$db_name = 'cpaneluser_warehouse';
$db_user = 'cpaneluser_whuser';
$db_pass = 'PASSWORD_DATABASE_ANDA';
```

### 5. Login Awal

Gunakan akun demo berikut:

```text
Admin   : admin@warehouse.local   / admin123
Manager : manager@warehouse.local / manager123
Staff   : staff@warehouse.local   / staff123
Viewer  : viewer@warehouse.local  / viewer123
```

Setelah login, segera ganti password melalui menu **Setting Data**.

## Catatan Scanner QR

Scanner kamera biasanya hanya aktif jika web dibuka melalui:

```text
https://domain-anda.com
```

atau localhost saat testing. Jika masih HTTP biasa, beberapa browser HP dapat menolak akses kamera.

## Testing Lokal Tanpa Hosting

1. Install XAMPP/Laragon
2. Copy folder project ke `htdocs`
3. Buat database `warehouse_inventory`
4. Import `database/schema.sql`
5. Edit `config/database.php` jika perlu
6. Buka:

```text
http://localhost/warehouse-hosting-app/login.php
```

## Deploy ke Vercel

Project ini sudah memiliki `vercel.json` dan adapter `api/index.php` untuk menjalankan PHP di Vercel memakai community runtime `vercel-php`.

Tambahkan Environment Variables berikut di Vercel sebelum login dipakai:

```text
DB_HOST=host_mysql_anda
DB_NAME=nama_database
DB_USER=user_database
DB_PASS=password_database
DB_CHARSET=utf8mb4
```

Import `database/schema.sql` ke database MySQL eksternal lebih dulu. Vercel tidak menyediakan MySQL lokal di runtime serverless, jadi gunakan database hosting/cPanel, PlanetScale, Aiven, Railway, atau layanan MySQL lain yang menerima koneksi dari Vercel.

## Struktur Folder

```text
warehouse-hosting-app/
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── config/database.php
├── database/schema.sql
├── includes/
│   ├── actions.php
│   ├── bootstrap.php
│   └── layout.php
├── pages/
│   ├── dashboard.php
│   ├── master.php
│   ├── stock.php
│   ├── qr.php
│   ├── movement.php
│   ├── opname.php
│   ├── approval.php
│   ├── supplier.php
│   ├── reports.php
│   ├── roles.php
│   └── lainnya
├── index.php
├── login.php
└── logout.php
```

## Saran Sebelum Dipakai Produksi

- Ganti semua password akun demo
- Gunakan HTTPS agar scanner QR berjalan stabil di HP
- Backup database berkala
- Sesuaikan role permission sesuai SOP perusahaan
- Hapus data demo jika sudah masuk data asli
- Batasi akses phpMyAdmin dan akun cPanel
