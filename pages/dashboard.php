<?php
require_perm('page_dashboard');

if (!function_exists('dashboard_date_value')) {
    function dashboard_date_value($value, string $fallback): string {
        $value = trim((string)$value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
    }

    function dashboard_int_filter(string $key): int {
        if (!isset($_GET[$key]) || $_GET[$key] === '') return 0;
        return max(0, (int)$_GET[$key]);
    }

    function dashboard_status_label(string $status): string {
        return $status === '' ? 'Semua status' : ucwords(str_replace('_', ' ', $status));
    }

    function dashboard_selected($value, $current): string {
        return (string)$value === (string)$current ? 'selected' : '';
    }

    function dashboard_stat_card(string $title, $value, string $icon, string $desc): void {
        echo '<div class="card stat dashboard-stat"><div><h3>' . h($title) . '</h3><strong>' . h($value) . '</strong><p class="small muted">' . h($desc) . '</p></div><div class="icon">' . h($icon) . '</div></div>';
    }

    function dashboard_qty($value): string {
        return number_format((float)$value, 2, ',', '.');
    }
}

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$dateFrom = dashboard_date_value($_GET['date_from'] ?? $monthStart, $monthStart);
$dateTo = dashboard_date_value($_GET['date_to'] ?? $today, $today);
if ($dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$categoryId = dashboard_int_filter('category_id');
$supplierId = dashboard_int_filter('supplier_id');
$warehouseId = dashboard_int_filter('warehouse_id');
$locationId = dashboard_int_filter('location_id');
$stockStatus = array_key_exists('stock_status', $_GET) ? trim((string)$_GET['stock_status']) : 'available';
if ($stockStatus === 'all') $stockStatus = '';

$categories = all_rows("SELECT id, name FROM categories ORDER BY name");
$suppliers = all_rows("SELECT id, name FROM suppliers ORDER BY name");
$warehouses = all_rows("SELECT id, name FROM warehouses ORDER BY name");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name, l.warehouse_id FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$locationById = [];
foreach ($locations as $locationRow) {
    $locationById[(int)$locationRow['id']] = $locationRow;
}
if ($locationId && !isset($locationById[$locationId])) {
    $locationId = 0;
}
if ($locationId && $warehouseId && (int)$locationById[$locationId]['warehouse_id'] !== $warehouseId) {
    $locationId = 0;
}
$statusRows = all_rows("SELECT DISTINCT stock_status FROM stock_balances WHERE stock_status <> '' ORDER BY stock_status");
$statusOptions = array_values(array_unique(array_merge(['available', 'reserved', 'quarantine', 'damaged'], array_column($statusRows, 'stock_status'))));
$categoryNames = array_column($categories, 'name', 'id');
$supplierNames = array_column($suppliers, 'name', 'id');
$warehouseNames = array_column($warehouses, 'name', 'id');

$itemWhere = [];
$itemParams = [];
if ($categoryId) {
    $itemWhere[] = 'i.category_id = ?';
    $itemParams[] = $categoryId;
}
if ($supplierId) {
    $itemWhere[] = 'i.supplier_id = ?';
    $itemParams[] = $supplierId;
}
$itemSql = $itemWhere ? implode(' AND ', $itemWhere) : '1=1';

$stockWhere = $itemWhere;
$stockParams = $itemParams;
if ($warehouseId) {
    $stockWhere[] = 'w.id = ?';
    $stockParams[] = $warehouseId;
}
if ($locationId) {
    $stockWhere[] = 'l.id = ?';
    $stockParams[] = $locationId;
}
if ($stockStatus !== '') {
    $stockWhere[] = 'sb.stock_status = ?';
    $stockParams[] = $stockStatus;
}
$stockSql = $stockWhere ? implode(' AND ', $stockWhere) : '1=1';

$stockScopeSelected = $warehouseId || $locationId || $stockStatus !== '';
if ($stockScopeSelected) {
    $totalSku = (int)scalar(
        "SELECT COUNT(DISTINCT i.id)
         FROM items i
         JOIN stock_balances sb ON sb.item_id=i.id
         JOIN locations l ON l.id=sb.location_id
         JOIN warehouses w ON w.id=l.warehouse_id
         WHERE {$stockSql}",
        $stockParams
    );
} else {
    $totalSku = (int)scalar("SELECT COUNT(*) FROM items i WHERE {$itemSql}", $itemParams);
}

$stockSummary = one(
    "SELECT COALESCE(SUM(sb.qty),0) total_qty,
            COALESCE(SUM(sb.qty*i.price),0) total_value
     FROM stock_balances sb
     JOIN items i ON i.id=sb.item_id
     JOIN locations l ON l.id=sb.location_id
     JOIN warehouses w ON w.id=l.warehouse_id
     WHERE {$stockSql}",
    $stockParams
) ?: ['total_qty' => 0, 'total_value' => 0];

$pending = (int)scalar("SELECT COUNT(*) FROM approvals WHERE status='pending'");

$breakdownWhere = $itemWhere;
$breakdownParams = $itemParams;
if ($warehouseId) {
    $breakdownWhere[] = 'w.id = ?';
    $breakdownParams[] = $warehouseId;
}
if ($locationId) {
    $breakdownWhere[] = 'l.id = ?';
    $breakdownParams[] = $locationId;
}
$breakdownSql = $breakdownWhere ? implode(' AND ', $breakdownWhere) : '1=1';
$statusBreakdown = all_rows(
    "SELECT sb.stock_status,
            COUNT(DISTINCT i.id) sku_count,
            COALESCE(SUM(sb.qty),0) qty,
            COALESCE(SUM(sb.qty*i.price),0) stock_value
     FROM stock_balances sb
     JOIN items i ON i.id=sb.item_id
     JOIN locations l ON l.id=sb.location_id
     JOIN warehouses w ON w.id=l.warehouse_id
     WHERE {$breakdownSql}
     GROUP BY sb.stock_status
     ORDER BY sb.stock_status",
    $breakdownParams
);

$lowWhere = $itemWhere;
$lowParams = $itemParams;
if ($warehouseId) {
    $lowWhere[] = 'w.id = ?';
    $lowParams[] = $warehouseId;
}
if ($locationId) {
    $lowWhere[] = 'l.id = ?';
    $lowParams[] = $locationId;
}
$lowSql = $lowWhere ? implode(' AND ', $lowWhere) : '1=1';
$low = all_rows(
    "SELECT i.id, i.sku, i.name, i.unit, i.min_stock,
            COALESCE(SUM(sb.qty),0) available_qty
     FROM items i
     LEFT JOIN stock_balances sb ON sb.item_id=i.id AND sb.stock_status='available'
     LEFT JOIN locations l ON l.id=sb.location_id
     LEFT JOIN warehouses w ON w.id=l.warehouse_id
     WHERE {$lowSql}
     GROUP BY i.id
     HAVING available_qty <= i.min_stock
     ORDER BY available_qty ASC, i.sku ASC
     LIMIT 8",
    $lowParams
);

$expWhere = $itemWhere;
$expParams = $itemParams;
$expJoin = '';
if ($stockScopeSelected) {
    $expJoin = "JOIN stock_balances sb ON sb.item_id=i.id JOIN locations l ON l.id=sb.location_id JOIN warehouses w ON w.id=l.warehouse_id";
    if ($warehouseId) {
        $expWhere[] = 'w.id = ?';
        $expParams[] = $warehouseId;
    }
    if ($locationId) {
        $expWhere[] = 'l.id = ?';
        $expParams[] = $locationId;
    }
    if ($stockStatus !== '') {
        $expWhere[] = 'sb.stock_status = ?';
        $expParams[] = $stockStatus;
    }
}
$expiryText = 'CAST(i.expired_date AS CHAR)';
$expWhere[] = 'i.expired_date IS NOT NULL';
$expWhere[] = "{$expiryText} REGEXP '^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$'";
$expWhere[] = "{$expiryText} NOT LIKE '%-00-%'";
$expWhere[] = "{$expiryText} <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 45 DAY), '%Y-%m-%d')";
$expSql = implode(' AND ', $expWhere);
$exp = all_rows(
    "SELECT DISTINCT i.id, i.sku, i.name, i.unit, i.batch_no, i.expired_date,
            DATEDIFF(i.expired_date, CURDATE()) days_left
     FROM items i
     {$expJoin}
     WHERE {$expSql}
     ORDER BY i.expired_date ASC
     LIMIT 8",
    $expParams
);

$movementWhere = ['sm.created_at >= ?', 'sm.created_at < DATE_ADD(?, INTERVAL 1 DAY)'];
$movementParams = [$dateFrom, $dateTo];
if ($categoryId) {
    $movementWhere[] = 'i.category_id = ?';
    $movementParams[] = $categoryId;
}
if ($supplierId) {
    $movementWhere[] = 'i.supplier_id = ?';
    $movementParams[] = $supplierId;
}
if ($warehouseId) {
    $movementWhere[] = '(fl.warehouse_id = ? OR tl.warehouse_id = ?)';
    $movementParams[] = $warehouseId;
    $movementParams[] = $warehouseId;
}
if ($locationId) {
    $movementWhere[] = '(sm.from_location_id = ? OR sm.to_location_id = ?)';
    $movementParams[] = $locationId;
    $movementParams[] = $locationId;
}
$movementSql = implode(' AND ', $movementWhere);
$movementStats = one(
    "SELECT COUNT(*) total_tx,
            COALESCE(SUM(CASE WHEN sm.movement_type='in' THEN sm.qty ELSE 0 END),0) qty_in,
            COALESCE(SUM(CASE WHEN sm.movement_type='out' THEN sm.qty ELSE 0 END),0) qty_out,
            COALESCE(SUM(CASE WHEN sm.movement_type='transfer' THEN sm.qty ELSE 0 END),0) qty_transfer,
            COALESCE(SUM(CASE WHEN sm.status='pending' THEN 1 ELSE 0 END),0) pending_tx
     FROM stock_movements sm
     JOIN items i ON i.id=sm.item_id
     LEFT JOIN locations fl ON fl.id=sm.from_location_id
     LEFT JOIN locations tl ON tl.id=sm.to_location_id
     WHERE {$movementSql}",
    $movementParams
) ?: ['total_tx' => 0, 'qty_in' => 0, 'qty_out' => 0, 'qty_transfer' => 0, 'pending_tx' => 0];

$stockRows = all_rows(
    "SELECT i.sku, i.name item_name, i.unit, i.min_stock, i.price, sb.qty, sb.stock_status,
            l.code location_code, w.name warehouse_name
     FROM stock_balances sb
     JOIN items i ON i.id=sb.item_id
     JOIN locations l ON l.id=sb.location_id
     JOIN warehouses w ON w.id=l.warehouse_id
     WHERE {$stockSql}
     ORDER BY sb.qty DESC, i.sku ASC
     LIMIT 8",
    $stockParams
);

$recentMovements = all_rows(
    "SELECT sm.created_at, sm.movement_type, sm.qty, sm.status, i.sku, i.name item_name,
            fl.code from_code, fw.name from_wh, tl.code to_code, tw.name to_wh
     FROM stock_movements sm
     JOIN items i ON i.id=sm.item_id
     LEFT JOIN locations fl ON fl.id=sm.from_location_id
     LEFT JOIN warehouses fw ON fw.id=fl.warehouse_id
     LEFT JOIN locations tl ON tl.id=sm.to_location_id
     LEFT JOIN warehouses tw ON tw.id=tl.warehouse_id
     WHERE {$movementSql}
     ORDER BY sm.id DESC
     LIMIT 6",
    $movementParams
);

$audit = all_rows(
    "SELECT a.*, u.name user_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id=a.user_id
     WHERE a.created_at >= ? AND a.created_at < DATE_ADD(?, INTERVAL 1 DAY)
     ORDER BY a.id DESC
     LIMIT 6",
    [$dateFrom, $dateTo]
);

$statusLabel = dashboard_status_label($stockStatus);
$datePresets = [
    ['Hari ini', $today, $today],
    ['7 hari', date('Y-m-d', strtotime('-6 days')), $today],
    ['Bulan ini', $monthStart, $today],
];
$activeFilters = [];
if ($categoryId) $activeFilters[] = 'Kategori: ' . ($categoryNames[$categoryId] ?? $categoryId);
if ($supplierId) $activeFilters[] = 'Supplier: ' . ($supplierNames[$supplierId] ?? $supplierId);
if ($warehouseId) $activeFilters[] = 'Gudang: ' . ($warehouseNames[$warehouseId] ?? $warehouseId);
if ($locationId) $activeFilters[] = 'Lokasi: ' . ($locationById[$locationId]['warehouse_name'] ?? '') . ' / ' . ($locationById[$locationId]['code'] ?? $locationId);
$activeFilters[] = 'Status: ' . $statusLabel;
$activeFilters[] = 'Periode: ' . $dateFrom . ' s/d ' . $dateTo;
?>
<div class="card dashboard-filter-card">
  <div class="filter-head">
    <div>
      <h3>Filter Dashboard</h3>
      <p class="small muted">Scope data: stok, transaksi, dan audit</p>
    </div>
    <div class="filter-presets">
      <?php foreach ($datePresets as $preset): ?>
        <button class="btn btn-chip" type="button" data-date-from="<?= h($preset[1]) ?>" data-date-to="<?= h($preset[2]) ?>"><?= h($preset[0]) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <form class="dashboard-filters" method="get">
    <input type="hidden" name="p" value="dashboard">
    <div class="field">
      <label>Kategori</label>
      <select class="select" name="category_id">
        <option value="">Semua kategori</option>
        <?php foreach ($categories as $c): ?><option value="<?= h($c['id']) ?>" <?= dashboard_selected($c['id'], $categoryId) ?>><?= h($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Supplier</label>
      <select class="select" name="supplier_id">
        <option value="">Semua supplier</option>
        <?php foreach ($suppliers as $s): ?><option value="<?= h($s['id']) ?>" <?= dashboard_selected($s['id'], $supplierId) ?>><?= h($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Gudang</label>
      <select class="select" name="warehouse_id" id="dashboardWarehouse">
        <option value="">Semua gudang</option>
        <?php foreach ($warehouses as $w): ?><option value="<?= h($w['id']) ?>" <?= dashboard_selected($w['id'], $warehouseId) ?>><?= h($w['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Lokasi/Rak</label>
      <select class="select" name="location_id" id="dashboardLocation">
        <option value="" data-warehouse="">Semua lokasi</option>
        <?php foreach ($locations as $l): ?><option value="<?= h($l['id']) ?>" data-warehouse="<?= h($l['warehouse_id']) ?>" <?= dashboard_selected($l['id'], $locationId) ?>><?= h($l['warehouse_name'] . ' / ' . $l['code']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Status Stok</label>
      <select class="select" name="stock_status">
        <option value="all" <?= dashboard_selected('', $stockStatus) ?>>Semua status</option>
        <?php foreach ($statusOptions as $status): ?><option value="<?= h($status) ?>" <?= dashboard_selected($status, $stockStatus) ?>><?= h(dashboard_status_label($status)) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Dari Tanggal</label>
      <input class="input" type="date" name="date_from" id="dashboardDateFrom" value="<?= h($dateFrom) ?>">
    </div>
    <div class="field">
      <label>Sampai Tanggal</label>
      <input class="input" type="date" name="date_to" id="dashboardDateTo" value="<?= h($dateTo) ?>">
    </div>
    <div class="field filter-actions">
      <button class="btn btn-primary" type="submit">Terapkan</button>
      <a class="btn btn-outline" href="<?= h(url('dashboard')) ?>">Reset</a>
    </div>
  </form>
  <div class="active-filters">
    <?php foreach ($activeFilters as $filter): ?><span><?= h($filter) ?></span><?php endforeach; ?>
  </div>
</div>

<div class="grid grid-4">
  <?php dashboard_stat_card('SKU Terfilter', number_format($totalSku, 0, ',', '.'), 'SKU', 'Item sesuai filter stok'); ?>
  <?php dashboard_stat_card('Qty ' . $statusLabel, dashboard_qty($stockSummary['total_qty']), 'Qty', 'Total qty pada scope aktif'); ?>
  <?php dashboard_stat_card('Nilai Inventory', rupiah($stockSummary['total_value']), 'Rp', 'Valuasi qty x harga'); ?>
  <?php dashboard_stat_card('Approval Pending', number_format($pending, 0, ',', '.'), 'OK', 'Antrian approval aktif'); ?>
</div>

<div class="card">
  <div class="section-title">
    <h3>Pergerakan Periode</h3>
    <span class="small muted"><?= h($dateFrom) ?> s/d <?= h($dateTo) ?></span>
  </div>
  <div class="dashboard-mini-grid">
    <div class="dashboard-mini"><span>Total Transaksi</span><strong><?= h(number_format((float)$movementStats['total_tx'], 0, ',', '.')) ?></strong></div>
    <div class="dashboard-mini"><span>Qty Masuk</span><strong><?= h(dashboard_qty($movementStats['qty_in'])) ?></strong></div>
    <div class="dashboard-mini"><span>Qty Keluar</span><strong><?= h(dashboard_qty($movementStats['qty_out'])) ?></strong></div>
    <div class="dashboard-mini"><span>Qty Transfer</span><strong><?= h(dashboard_qty($movementStats['qty_transfer'])) ?></strong></div>
    <div class="dashboard-mini"><span>Transaksi Pending</span><strong><?= h(number_format((float)$movementStats['pending_tx'], 0, ',', '.')) ?></strong></div>
  </div>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="section-title"><h3>Alert Stock Menipis</h3><a class="btn btn-sm" href="<?= h(url('purchase')) ?>">Buat PR</a></div>
    <div class="timeline">
      <?php if (!$low): ?><p class="muted">Tidak ada stock menipis pada filter ini.</p><?php endif; ?>
      <?php foreach ($low as $i): ?>
        <div class="timeline-item warning">
          <strong><?= h($i['sku']) ?> - <?= h($i['name']) ?></strong><br>
          <span class="small muted">Available <?= h(dashboard_qty($i['available_qty'])) ?> <?= h($i['unit']) ?>, minimum <?= h(dashboard_qty($i['min_stock'])) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="section-title"><h3>Expired / Mendekati Expired</h3><a class="btn btn-sm" href="<?= h(url('quality')) ?>">Review</a></div>
    <div class="timeline">
      <?php if (!$exp): ?><p class="muted">Tidak ada barang expired dekat pada filter ini.</p><?php endif; ?>
      <?php foreach ($exp as $i): ?>
        <div class="timeline-item <?= (int)$i['days_left'] < 0 ? 'danger' : 'warning' ?>">
          <strong><?= h($i['sku']) ?> - <?= h($i['name']) ?></strong><br>
          <span class="small muted">Expired: <?= h($i['expired_date']) ?>, batch: <?= h($i['batch_no'] ?: '-') ?>, sisa <?= h((int)$i['days_left']) ?> hari</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="section-title"><h3>Ringkasan Status Stok</h3><a class="btn btn-sm" href="<?= h(url('stock')) ?>">Detail</a></div>
    <div class="table-wrap dashboard-compact-table">
      <table class="table">
        <thead><tr><th>Status</th><th>SKU</th><th>Qty</th><th>Nilai</th></tr></thead>
        <tbody>
          <?php if (!$statusBreakdown): ?><tr><td colspan="4">Tidak ada data stok.</td></tr><?php endif; ?>
          <?php foreach ($statusBreakdown as $s): ?>
            <tr>
              <td data-label="Status"><?= status_badge($s['stock_status']) ?></td>
              <td data-label="SKU"><?= h($s['sku_count']) ?></td>
              <td data-label="Qty"><?= h(dashboard_qty($s['qty'])) ?></td>
              <td data-label="Nilai"><?= rupiah($s['stock_value']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Stock Teratas</h3><a class="btn btn-sm" href="<?= h(url('stock')) ?>">Data Stock</a></div>
    <div class="table-wrap dashboard-table">
      <table class="table">
        <thead><tr><th>Barang</th><th>Gudang/Rak</th><th>Status</th><th>Qty</th><th>Nilai</th></tr></thead>
        <tbody>
          <?php if (!$stockRows): ?><tr><td colspan="5">Tidak ada stock sesuai filter.</td></tr><?php endif; ?>
          <?php foreach ($stockRows as $r): ?>
            <tr>
              <td data-label="Barang"><strong><?= h($r['sku']) ?></strong><br><span class="small muted"><?= h($r['item_name']) ?></span></td>
              <td data-label="Gudang/Rak"><?= h($r['warehouse_name']) ?><br><span class="small muted"><?= h($r['location_code']) ?></span></td>
              <td data-label="Status"><?= status_badge($r['stock_status']) ?></td>
              <td data-label="Qty"><?= h(dashboard_qty($r['qty'])) ?> <?= h($r['unit']) ?></td>
              <td data-label="Nilai"><?= rupiah((float)$r['qty'] * (float)$r['price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="section-title"><h3>Transaksi Terbaru</h3><a class="btn btn-sm" href="<?= h(url('movement')) ?>">Input Stock</a></div>
    <div class="timeline">
      <?php if (!$recentMovements): ?><p class="muted">Tidak ada transaksi pada periode ini.</p><?php endif; ?>
      <?php foreach ($recentMovements as $m): ?>
        <div class="timeline-item">
          <strong><?= h(strtoupper($m['movement_type'])) ?> - <?= h($m['sku']) ?></strong><br>
          <span class="small muted">
            <?= h($m['created_at']) ?>, qty <?= h(dashboard_qty($m['qty'])) ?>,
            <?= h(($m['from_wh'] ? $m['from_wh'] . '/' . $m['from_code'] : '-') . ' -> ' . ($m['to_wh'] ? $m['to_wh'] . '/' . $m['to_code'] : '-')) ?>,
            <?= h($m['status']) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="section-title"><h3>Aktivitas Terbaru</h3><a class="btn btn-sm" href="<?= h(url('audit')) ?>">Audit</a></div>
  <div class="timeline">
    <?php if (!$audit): ?><p class="muted">Tidak ada aktivitas pada periode ini.</p><?php endif; ?>
    <?php foreach ($audit as $a): ?>
      <div class="timeline-item">
        <strong><?= h($a['action']) ?></strong><br>
        <span class="small muted"><?= h($a['created_at']) ?>, <?= h($a['user_name'] ?? 'System') ?>, <?= h($a['detail']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
